/**
 * CyberVault - Client-Side Cryptographic Engine
 * Uses the Web Crypto API for secure, local AES-GCM-256 encryption.
 */

const CyberVaultCrypto = {
    // Unique signature header for CyberVault files: "CVLT" in bytes
    SIGNATURE: new Uint8Array([67, 86, 76, 84]),
    
    // Default PBKDF2 parameters
    PBKDF2_ITERATIONS: 10000,
    
    /**
     * Derives a cryptographic key from a text password and a salt.
     */
    async deriveKey(password, salt) {
        const encoder = new TextEncoder();
        const passwordBytes = encoder.encode(password);
        
        // Import password as PBKDF2 base key
        const baseKey = await window.crypto.subtle.importKey(
            'raw',
            passwordBytes,
            { name: 'PBKDF2' },
            false,
            ['deriveKey']
        );
        
        // Derive AES key
        return await window.crypto.subtle.deriveKey(
            {
                name: 'PBKDF2',
                salt: salt,
                iterations: this.PBKDF2_ITERATIONS,
                hash: 'SHA-256'
            },
            baseKey,
            { name: 'AES-GCM', length: 256 },
            false,
            ['encrypt', 'decrypt']
        );
    },

    /**
     * Encrypts a text string using AES-GCM-256.
     * Combined Format: Salt (16B) + IV (12B) + Ciphertext
     */
    async encryptText(plainText, password) {
        try {
            const encoder = new TextEncoder();
            const textBytes = encoder.encode(plainText);
            
            const salt = window.crypto.getRandomValues(new Uint8Array(16));
            const iv = window.crypto.getRandomValues(new Uint8Array(12));
            
            const key = await this.deriveKey(password, salt);
            
            const ciphertextBuffer = await window.crypto.subtle.encrypt(
                {
                    name: 'AES-GCM',
                    iv: iv,
                    tagLength: 128
                },
                key,
                textBytes
            );
            
            const ciphertextBytes = new Uint8Array(ciphertextBuffer);
            
            // Combine: salt (16) + iv (12) + ciphertext
            const combined = new Uint8Array(salt.length + iv.length + ciphertextBytes.length);
            combined.set(salt, 0);
            combined.set(iv, salt.length);
            combined.set(ciphertextBytes, salt.length + iv.length);
            
            return this.bytesToBase64(combined);
        } catch (e) {
            console.error('Text encryption error:', e);
            throw new Error('Encryption failed: ' + e.message);
        }
    },

    /**
     * Decrypts text ciphertext block using AES-GCM-256.
     */
    async decryptText(cipherTextBase64, password) {
        try {
            const combinedBytes = this.base64ToBytes(cipherTextBase64);
            
            const saltSize = 16;
            const ivSize = 12;
            
            if (combinedBytes.length < saltSize + ivSize) {
                throw new Error('Ciphertext is too short or corrupted.');
            }
            
            const salt = combinedBytes.slice(0, saltSize);
            const iv = combinedBytes.slice(saltSize, saltSize + ivSize);
            const ciphertext = combinedBytes.slice(saltSize + ivSize);
            
            const key = await this.deriveKey(password, salt);
            
            const decryptedBuffer = await window.crypto.subtle.decrypt(
                {
                    name: 'AES-GCM',
                    iv: iv,
                    tagLength: 128
                },
                key,
                ciphertext
            );
            
            return new TextDecoder().decode(decryptedBuffer);
        } catch (e) {
            console.error('Text decryption error:', e);
            throw new Error('Decryption failed: Verification tag mismatch. Invalid password or corrupted payload.');
        }
    },

    /**
     * Encrypts a local physical file.
     * Combined Format: [SIGNATURE (4B)] + [SALT (16B)] + [IV (12B)] + [META_LEN (2B)] + [META_JSON (NB)] + [CIPHERTEXT (MB)]
     */
    async encryptFile(file, password) {
        try {
            const fileDataBuffer = await this.readFileAsArrayBuffer(file);
            
            const salt = window.crypto.getRandomValues(new Uint8Array(16));
            const iv = window.crypto.getRandomValues(new Uint8Array(12));
            
            const key = await this.deriveKey(password, salt);
            
            const ciphertextBuffer = await window.crypto.subtle.encrypt(
                {
                    name: 'AES-GCM',
                    iv: iv,
                    tagLength: 128
                },
                key,
                fileDataBuffer
            );
            
            const ciphertextBytes = new Uint8Array(ciphertextBuffer);
            
            // Create metadata JSON details
            const metadata = {
                name: file.name,
                type: file.type || 'application/octet-stream',
                size: file.size
            };
            const metadataBytes = new TextEncoder().encode(JSON.stringify(metadata));
            const metadataLength = metadataBytes.length;
            
            // Allocate envelope
            const headerSize = this.SIGNATURE.length + salt.length + iv.length + 2 + metadataLength;
            const combined = new Uint8Array(headerSize + ciphertextBytes.length);
            
            let offset = 0;
            combined.set(this.SIGNATURE, offset);
            offset += this.SIGNATURE.length;
            
            combined.set(salt, offset);
            offset += salt.length;
            
            combined.set(iv, offset);
            offset += iv.length;
            
            combined[offset] = (metadataLength >> 8) & 0xff;
            combined[offset + 1] = metadataLength & 0xff;
            offset += 2;
            
            combined.set(metadataBytes, offset);
            offset += metadataLength;
            
            combined.set(ciphertextBytes, offset);
            
            return new Blob([combined], { type: 'application/octet-stream' });
        } catch (e) {
            console.error('File encryption error:', e);
            throw new Error('Encryption failed: ' + e.message);
        }
    },

    /**
     * Decrypts a physical .vault envelope file.
     */
    async decryptFile(fileBlob, password) {
        try {
            const envelopeBuffer = await this.readFileAsArrayBuffer(fileBlob);
            const combinedBytes = new Uint8Array(envelopeBuffer);
            
            const sigSize = this.SIGNATURE.length;
            const saltSize = 16;
            const ivSize = 12;
            
            if (combinedBytes.length < sigSize + saltSize + ivSize + 2) {
                throw new Error('Envelope is too short or corrupt.');
            }
            
            let offset = 0;
            for (let i = 0; i < sigSize; i++) {
                if (combinedBytes[i] !== this.SIGNATURE[i]) {
                    throw new Error('Decryption Failed: This is not a valid CyberVault (.vault) file.');
                }
            }
            offset += sigSize;
            
            const salt = combinedBytes.slice(offset, offset + saltSize);
            offset += saltSize;
            
            const iv = combinedBytes.slice(offset, offset + ivSize);
            offset += ivSize;
            
            const metadataLength = (combinedBytes[offset] << 8) | combinedBytes[offset + 1];
            offset += 2;
            
            if (combinedBytes.length < offset + metadataLength) {
                throw new Error('Corrupted header: Metadata block missing.');
            }
            
            const metadataBytes = combinedBytes.slice(offset, offset + metadataLength);
            offset += metadataLength;
            
            const metadataString = new TextDecoder().decode(metadataBytes);
            const metadata = JSON.parse(metadataString);
            
            const ciphertext = combinedBytes.slice(offset);
            
            const key = await this.deriveKey(password, salt);
            
            const decryptedBuffer = await window.crypto.subtle.decrypt(
                {
                    name: 'AES-GCM',
                    iv: iv,
                    tagLength: 128
                },
                key,
                ciphertext
            );
            
            const decryptedBlob = new Blob([decryptedBuffer], { type: metadata.type });
            
            return {
                blob: decryptedBlob,
                filename: metadata.name
            };
        } catch (e) {
            console.error('File decryption error:', e);
            throw new Error('Decryption failed: Verification tag mismatch. Invalid password or corrupted file.');
        }
    },

    readFileAsArrayBuffer(fileBlob) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = () => resolve(reader.result);
            reader.onerror = () => reject(new Error('Failed to read file buffer.'));
            reader.readAsArrayBuffer(fileBlob);
        });
    },

    bytesToBase64(bytes) {
        let binary = '';
        const len = bytes.byteLength;
        for (let i = 0; i < len; i++) {
            binary += String.fromCharCode(bytes[i]);
        }
        return window.btoa(binary);
    },

    base64ToBytes(base64Str) {
        const binary = window.atob(base64Str);
        const len = binary.length;
        const bytes = new Uint8Array(len);
        for (let i = 0; i < len; i++) {
            bytes[i] = binary.charCodeAt(i);
        }
        return bytes;
    }
};
