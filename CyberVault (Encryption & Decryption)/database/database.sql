-- CyberVault Database Initialization Script
-- Optimized for WAMP/XAMPP (MySQL/MariaDB)

CREATE DATABASE IF NOT EXISTS cybervault_db;
USE cybervault_db;

-- 1. Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Files Table
CREATE TABLE IF NOT EXISTS files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    encrypted_name VARCHAR(255) NOT NULL,
    file_size BIGINT NOT NULL,
    algorithm VARCHAR(50) DEFAULT 'AES-GCM-256',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. History Logs Table
CREATE TABLE IF NOT EXISTS history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    operation_type VARCHAR(20) NOT NULL,
    target_name VARCHAR(255) NOT NULL,
    status VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default demo system administrator user
-- Username: specter
-- Password: supersecret123
-- Bcrypt Hash: $2y$10$p3gp5NFPTHOwZkY2bgKxo..2Ze3OMrtTkgHZ/2osslhjq8y6Oi8m. (standard PASSWORD_DEFAULT)
INSERT INTO users (id, username, email, password) 
VALUES (1, 'specter', 'admin@cybervault.local', '$2y$10$p3gp5NFPTHOwZkY2bgKxo..2Ze3OMrtTkgHZ/2osslhjq8y6Oi8m.')
ON DUPLICATE KEY UPDATE username='specter';

-- Insert matching demo histories for specter to populate dashboard visuals initially
INSERT INTO history (user_id, operation_type, target_name, status, created_at) VALUES 
(1, 'encrypt', 'document.pdf', 'success', DATE_SUB(NOW(), INTERVAL 2 MINUTE)),
(1, 'decrypt', 'photo.jpg', 'success', DATE_SUB(NOW(), INTERVAL 15 MINUTE)),
(1, 'encrypt', 'presentation.mp4', 'success', DATE_SUB(NOW(), INTERVAL 1 HOUR)),
(1, 'decrypt', 'song.mp3', 'success', DATE_SUB(NOW(), INTERVAL 2 HOUR)),
(1, 'encrypt', 'notes.txt', 'success', DATE_SUB(NOW(), INTERVAL 3 HOUR)),
(1, 'decrypt', 'malicious_payload.vault', 'failed', DATE_SUB(NOW(), INTERVAL 4 HOUR));

-- Insert matching demo files catalog records
INSERT INTO files (user_id, original_name, encrypted_name, file_size, algorithm, created_at) VALUES
(1, 'document.pdf', 'document.pdf.vault', 1468006, 'AES-GCM-256', DATE_SUB(NOW(), INTERVAL 2 MINUTE)),
(1, 'presentation.mp4', 'presentation.mp4.vault', 15728640, 'AES-GCM-256', DATE_SUB(NOW(), INTERVAL 1 HOUR)),
(1, 'notes.txt', 'notes.txt.vault', 24576, 'AES-GCM-256', DATE_SUB(NOW(), INTERVAL 3 HOUR));
