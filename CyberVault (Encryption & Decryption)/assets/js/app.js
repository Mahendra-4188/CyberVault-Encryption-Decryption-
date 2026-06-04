/**
 * CyberVault - Master Frontend Controller (AJAX Core, Parallax & Scroll Reveal)
 */

const app = {
    // Current Frontend UI States
    state: {
        theme: 'light',
        activeView: 'dashboard',
        files: [],
        history: [],
        notifications: []
    },

    // Temp operational file references in memory
    activeEncryptFile: null,
    activeDecryptFile: null,
    lastEncryptedBlob: null,
    lastEncryptedFilename: null,
    lastDecryptedBlob: null,
    lastDecryptedFilename: null,

    // Initialize application script
    init() {
        this.detectPageMode();
        this.initTheme();
        this.bindGlobalThemeToggle();
        
        // Re-render vector icons loaded dynamically
        if (window.lucide) {
            window.lucide.createIcons();
        }
    },

    // Detect if browser is on Landing index.php or internal dashboard.php
    detectPageMode() {
        const isDashboard = document.getElementById('dashboard-view-container') !== null;
        
        if (isDashboard) {
            this.initDashboardMode();
        } else {
            this.initLandingMode();
        }
    },

    /* ==========================================================================
       1. LANDING PAGE INITS (PARALLAX & SCROLL REVEALS)
       ========================================================================== */
    initLandingMode() {
        const self = this;
        console.log('CyberVault - Landing Page mode active.');

        // Bind Landing Page Navigation Scroll changes
        const header = document.querySelector('.home-navbar');
        window.addEventListener('scroll', () => {
            if (window.scrollY > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });

        // A. BUTTERY-SMOOTH 3D PARALLAX & DYNAMIC GLOW ENGINE
        const starsLayer = document.querySelector('.layer-stars');
        const shieldLayer = document.querySelector('.layer-floating-shield');
        const bgGridLayer = document.querySelector('.layer-bg-grid');
        const heroContainer = document.getElementById('hero-interactive-zone');
        const mouseGlow = document.getElementById('mouse-glow-overlay');
        
        if (heroContainer) {
            let targetMouseX = 0;
            let targetMouseY = 0;
            let currentMouseX = 0;
            let currentMouseY = 0;
            const easeFactor = 0.08; // Smooth interpolation speed (lerp)

            // Listen for mouse movement to track coordinates relative to center
            heroContainer.addEventListener('mousemove', (e) => {
                const rect = heroContainer.getBoundingClientRect();
                
                // 1. Calculate values for 3D parallax shifts (normalized from -1 to 1)
                const x = e.clientX - rect.left - rect.width / 2;
                const y = e.clientY - rect.top - rect.height / 2;
                targetMouseX = x / (rect.width / 2);
                targetMouseY = y / (rect.height / 2);

                // 2. Calculate values for CSS mouse spotlight glow position in percentages
                const glowX = ((e.clientX - rect.left) / rect.width) * 100;
                const glowY = ((e.clientY - rect.top) / rect.height) * 100;
                heroContainer.style.setProperty('--mouse-x', `${glowX}%`);
                heroContainer.style.setProperty('--mouse-y', `${glowY}%`);
            });

            // Fade in mouse glow on enter
            heroContainer.addEventListener('mouseenter', () => {
                if (mouseGlow) mouseGlow.classList.add('active');
            });

            // Fade out mouse glow on leave & reset target parallax positions smoothly
            heroContainer.addEventListener('mouseleave', () => {
                if (mouseGlow) mouseGlow.classList.remove('active');
                targetMouseX = 0;
                targetMouseY = 0;
            });

            // 60FPS Butter-smooth rendering render loop integrating scroll & mouse offsets
            function renderParallaxLoop() {
                // Lerp mouse positions for absolute organic smoothness
                currentMouseX += (targetMouseX - currentMouseX) * easeFactor;
                currentMouseY += (targetMouseY - currentMouseY) * easeFactor;

                const scrollY = window.scrollY;

                // Only render if within or near hero viewport for performance optimization
                if (scrollY < window.innerHeight) {
                    // Shift layers on mouse moves & page scrolling
                    if (starsLayer) {
                        starsLayer.style.transform = `translate(${currentMouseX * -20}px, ${scrollY * 0.4 + currentMouseY * -20}px)`;
                    }
                    if (bgGridLayer) {
                        bgGridLayer.style.transform = `translate(${currentMouseX * -10}px, ${currentMouseY * -10}px)`;
                    }
                    if (shieldLayer) {
                        shieldLayer.style.transform = `translate(${currentMouseX * -30}px, ${scrollY * -0.1 + currentMouseY * -30}px) rotate(${scrollY * -0.03 + currentMouseX * -8}deg)`;
                    }
                }

                requestAnimationFrame(renderParallaxLoop);
            }

            // Fire rendering cycle
            requestAnimationFrame(renderParallaxLoop);
        }

        // B. SCROLL REVEAL TRIGGERS (Intersection Observer)
        const revealElements = document.querySelectorAll('.reveal-hidden');
        if (revealElements.length > 0) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('reveal-visible');
                        // Unobserve once animation triggers
                        observer.unobserve(entry.target);
                    }
                });
            }, {
                root: null, // viewport
                threshold: 0.1, // trigger when 10% visible
                rootMargin: '0px 0px -50px 0px' // adjust timing offset
            });

            revealElements.forEach(el => observer.observe(el));
        }

        // C. HOME VIEW INTERACTIVE MODALS (Login/Register Forms)
        const loginModal = document.getElementById('login-modal');
        const registerModal = document.getElementById('register-modal');

        const openLogin = () => {
            registerModal.classList.remove('show');
            loginModal.classList.add('show');
        };
        const openRegister = () => {
            loginModal.classList.remove('show');
            registerModal.classList.add('show');
        };
        const closeAllModals = () => {
            loginModal.classList.remove('show');
            registerModal.classList.add('show'); // clear states
            document.querySelectorAll('.modal-overlay').forEach(m => m.classList.remove('show'));
        };

        // Click handlers to open
        document.querySelectorAll('.btn-open-login').forEach(btn => btn.addEventListener('click', openLogin));
        document.querySelectorAll('.btn-open-register').forEach(btn => btn.addEventListener('click', openRegister));
        
        // Click handlers to close
        document.querySelectorAll('.modal-close-btn').forEach(btn => btn.addEventListener('click', closeAllModals));
        document.querySelectorAll('.modal-overlay').forEach(overlay => {
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) closeAllModals();
            });
        });

        // D. AJAX USER AUTHENTICATION REQUEST SUBMISSIONS
        const loginForm = document.getElementById('login-form');
        const registerForm = document.getElementById('register-form');

        const handleAuthSubmit = async (form, action) => {
            const formData = new FormData(form);
            formData.append('action', action);

            try {
                const response = await fetch('includes/auth.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    self.showToast(data.message, 'success');
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 1200);
                } else {
                    self.showToast(data.message, 'error');
                }
            } catch (err) {
                console.error(err);
                self.showToast('Auth network error. Please ensure WAMP connection is active.', 'error');
            }
        };

        if (loginForm) {
            loginForm.addEventListener('submit', (e) => {
                e.preventDefault();
                handleAuthSubmit(loginForm, 'login');
            });
        }

        if (registerForm) {
            registerForm.addEventListener('submit', (e) => {
                e.preventDefault();
                handleAuthSubmit(registerForm, 'register');
            });
        }
    },

    /* ==========================================================================
       2. DASHBOARD VIEW INITS (SPA TABS & AJAX DATABASE SYNC)
       ========================================================================== */
    initDashboardMode() {
        console.log('CyberVault - Dashboard Page mode active.');
        this.bindDashboardEvents();
        this.loadDashboardData();
    },

    // Bind event controllers inside dashboard panel
    bindDashboardEvents() {
        const self = this;

        // B. SPA View Tab Swaps
        document.querySelectorAll('.nav-link[data-view]').forEach(btn => {
            btn.addEventListener('click', () => {
                const targetView = btn.getAttribute('data-view');
                self.switchDashboardView(targetView);
            });
        });

        // C. Password input visibility toggles
        document.querySelectorAll('.toggle-password-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const input = btn.previousElementSibling;
                const icon = btn.querySelector('i, svg');
                if (input.type === 'password') {
                    input.type = 'text';
                    if (icon) icon.setAttribute('data-lucide', 'eye-off');
                } else {
                    input.type = 'password';
                    if (icon) icon.setAttribute('data-lucide', 'eye');
                }
                if (window.lucide) window.lucide.createIcons();
            });
        });

        // D. Segmented forms controller (Text vs File cards)
        document.querySelectorAll('.segmented-control button').forEach(btn => {
            btn.addEventListener('click', () => {
                const target = btn.getAttribute('data-target');
                const pane = btn.closest('.form-pane');
                
                pane.querySelectorAll('.segmented-control button').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');

                pane.querySelectorAll('.form-section').forEach(sec => sec.classList.remove('active'));
                document.getElementById(`${target}-section`).classList.add('active');
            });
        });

        // E. Drag & Drop upload bindings
        const encDropzone = document.getElementById('enc-file-dropzone');
        const encFileInput = document.getElementById('enc-file-input');

        if (encDropzone && encFileInput) {
            encDropzone.addEventListener('click', () => encFileInput.click());
            encFileInput.addEventListener('change', (e) => {
                if (e.target.files.length > 0) self.setEncryptFile(e.target.files[0]);
            });
            this.configureDragZone(encDropzone, (files) => {
                if (files.length > 0) self.setEncryptFile(files[0]);
            });
        }

        const decDropzone = document.getElementById('dec-file-dropzone');
        const decFileInput = document.getElementById('dec-file-input');

        if (decDropzone && decFileInput) {
            decDropzone.addEventListener('click', () => decFileInput.click());
            decFileInput.addEventListener('change', (e) => {
                if (e.target.files.length > 0) self.setDecryptFile(e.target.files[0]);
            });
            this.configureDragZone(decDropzone, (files) => {
                if (files.length > 0) self.setDecryptFile(files[0]);
            });
        }

        // File previews clear buttons
        document.getElementById('enc-remove-file').addEventListener('click', () => {
            self.activeEncryptFile = null;
            document.getElementById('enc-file-preview').style.display = 'none';
            encDropzone.style.display = 'flex';
        });

        document.getElementById('dec-remove-file').addEventListener('click', () => {
            self.activeDecryptFile = null;
            document.getElementById('dec-file-preview').style.display = 'none';
            decDropzone.style.display = 'flex';
        });

        // F. Run crypt operations event binds
        document.getElementById('btn-run-encrypt').addEventListener('click', () => self.executeClientSideEncryption());
        document.getElementById('btn-run-decrypt').addEventListener('click', () => self.executeClientSideDecryption());

        // Result displays actions buttons (Copy & downloads)
        document.getElementById('btn-copy-ciphertext').addEventListener('click', () => {
            const text = document.getElementById('enc-ciphertext-output').innerText;
            navigator.clipboard.writeText(text);
            self.showToast('Encrypted text block copied to clipboard!', 'success');
        });

        document.getElementById('btn-download-vault').addEventListener('click', () => {
            if (self.lastEncryptedBlob && self.lastEncryptedFilename) {
                self.triggerBlobDownload(self.lastEncryptedBlob, self.lastEncryptedFilename);
            }
        });

        document.getElementById('btn-copy-plaintext').addEventListener('click', () => {
            const text = document.getElementById('dec-plaintext-output').innerText;
            navigator.clipboard.writeText(text);
            self.showToast('Decrypted output copied to clipboard!', 'success');
        });

        document.getElementById('btn-download-restored').addEventListener('click', () => {
            if (self.lastDecryptedBlob && self.lastDecryptedFilename) {
                self.triggerBlobDownload(self.lastDecryptedBlob, self.lastDecryptedFilename);
            }
        });

        // G. Profile database save forms binds
        const profileForm = document.getElementById('profile-form');
        if (profileForm) {
            profileForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(profileForm);
                formData.append('action', 'update_profile');

                try {
                    const response = await fetch('dashboard.php', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await response.json();
                    if (data.success) {
                        self.showToast(data.message, 'success');
                        document.querySelectorAll('.profile-name').forEach(el => el.innerText = formData.get('username'));
                    } else {
                        self.showToast(data.message, 'error');
                    }
                } catch (err) {
                    self.showToast('Profile save network failure.', 'error');
                }
            });
        }

        // Clears SQL History transaction logs
        const clearHistBtn = document.getElementById('btn-clear-history');
        if (clearHistBtn) {
            clearHistBtn.addEventListener('click', async () => {
                if (confirm('Are you sure you want to permanently clear all cryptographic activity logs in the database?')) {
                    const formData = new FormData();
                    formData.append('action', 'clear');

                    try {
                        const response = await fetch('api/history.php', {
                            method: 'POST',
                            body: formData
                        });
                        const data = await response.json();
                        if (data.success) {
                            self.showToast(data.message, 'success');
                            self.loadDashboardData();
                        } else {
                            self.showToast(data.message, 'error');
                        }
                    } catch (e) {
                        self.showToast('Clear history failure.', 'error');
                    }
                }
            });
        }

        // Global key prefilling helper
        const defaultKeyField = document.getElementById('default-crypt-key-value');
        if (defaultKeyField) {
            const keyVal = defaultKeyField.value;
            document.getElementById('enc-password').value = keyVal;
            document.getElementById('dec-password').value = keyVal;
        }

        // Sync searches
        document.getElementById('files-search').addEventListener('input', (e) => {
            const val = e.target.value.toLowerCase().trim();
            self.filterSecuredFilesTable(val);
        });
    },

    // Drag-over styling configurations
    configureDragZone(el, callback) {
        ['dragenter', 'dragover'].forEach(name => {
            el.addEventListener(name, (e) => {
                e.preventDefault();
                e.stopPropagation();
                el.classList.add('dragover');
            });
        });
        ['dragleave', 'drop'].forEach(name => {
            el.addEventListener(name, (e) => {
                e.preventDefault();
                e.stopPropagation();
                el.classList.remove('dragover');
            });
        });
        el.addEventListener('drop', (e) => {
            const files = e.dataTransfer.files;
            callback(files);
        });
    },

    // Set encrypt file previews
    setEncryptFile(file) {
        this.activeEncryptFile = file;
        const drop = document.getElementById('enc-file-dropzone');
        const preview = document.getElementById('enc-file-preview');

        document.getElementById('enc-preview-name').innerText = file.name;
        document.getElementById('enc-preview-size').innerText = this.formatFileSize(file.size);
        
        const icon = document.getElementById('enc-preview-icon');
        icon.setAttribute('data-lucide', this.getFileIconName(file.name));
        if (window.lucide) window.lucide.createIcons();

        drop.style.display = 'none';
        preview.style.display = 'flex';
    },

    // Set decrypt file previews
    setDecryptFile(file) {
        if (!file.name.endsWith('.vault')) {
            this.showToast('Please upload a valid secure .vault container envelope', 'error');
            return;
        }
        this.activeDecryptFile = file;
        const drop = document.getElementById('dec-file-dropzone');
        const preview = document.getElementById('dec-file-preview');

        document.getElementById('dec-preview-name').innerText = file.name;
        document.getElementById('dec-preview-size').innerText = this.formatFileSize(file.size);

        drop.style.display = 'none';
        preview.style.display = 'flex';
    },

    // Dashboard panel swaps
    switchDashboardView(viewId) {
        const activeView = document.querySelector('.content-view.active');
        if (activeView) activeView.classList.remove('active');

        const target = document.getElementById(`view-${viewId}`);
        if (target) {
            target.classList.add('active');
            this.state.activeView = viewId;
        }

        // Links
        document.querySelectorAll('.sidebar-nav .nav-link').forEach(link => {
            if (link.getAttribute('data-view') === viewId) {
                link.classList.add('active');
            } else {
                link.classList.remove('active');
            }
        });

        this.loadDashboardData();
        if (window.lucide) window.lucide.createIcons();
    },

    /* ==========================================================================
       3. AJAX CORE: SYNC DATA TO WAMP BACKEND (GET STATS & LOGS)
       ========================================================================== */
    async loadDashboardData() {
        const self = this;
        try {
            // A. FETCH HISTORY LOGS
            const histRes = await fetch('api/history.php');
            const histData = await histRes.json();
            
            if (histData.success) {
                self.state.history = histData.history;
                self.renderHistoryLogs();
            }

            // B. FETCH SECURED FILES CATALOGUE
            const filesRes = await fetch('api/upload.php'); // default GET fetch
            const filesData = await filesRes.json();

            // Handle server PHP arrays fallback
            const finalFiles = filesData.files || [];
            self.state.files = finalFiles;
            self.renderMyFilesTable();

            // C. FETCH & RECALCULATE STATISTICS COUNTERS
            const statsRes = await fetch('dashboard.php?action=get_stats');
            const stats = await statsRes.json();

            if (stats.success) {
                document.getElementById('stat-total-files').innerText = stats.total_files;
                document.getElementById('stat-encrypted-files').innerText = stats.encrypted_count;
                document.getElementById('stat-decrypted-files').innerText = stats.decrypted_count;
                document.getElementById('stat-total-ops').innerText = stats.total_ops;
            }

        } catch (err) {
            console.error('Data sync failed:', err);
        }
    },

    // Render History logs dynamic rows
    renderHistoryLogs() {
        const tbody = document.getElementById('history-tbody');
        const activityFeed = document.getElementById('dashboard-activity-list');
        if (!tbody) return;

        // Render Recent Activity on widget (Dashboard)
        if (activityFeed) {
            const topLogs = this.state.history.slice(0, 5);
            if (topLogs.length === 0) {
                activityFeed.innerHTML = `<div class="empty-state"><i data-lucide="clock"></i><p>No activity logged.</p></div>`;
            } else {
                activityFeed.innerHTML = topLogs.map(item => {
                    const isEnc = item.operation_type === 'encrypt';
                    const icon = isEnc ? 'lock' : 'unlock';
                    const colorClass = isEnc ? 'icon-blue' : 'icon-emerald';
                    const dateStr = this.formatTimeAgo(new Date(item.created_at));
                    
                    return `
                        <div class="activity-item" style="display:flex; align-items:center; gap:12px; padding:12px 24px; border-bottom:1px solid var(--border-color);">
                            <div class="activity-item-icon ${colorClass}" style="width:32px; height:32px; border-radius:50%; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                                <i data-lucide="${icon}" style="width:14px; height:14px;"></i>
                            </div>
                            <div style="flex-grow:1; display:flex; flex-direction:column; gap:2px; overflow:hidden;">
                                <span style="font-size:12px; font-weight:500;" class="activity-title">${item.target_name} <span style="font-weight:600;">${item.operation_type}ed</span></span>
                                <span style="font-size:10px; color:${item.status === 'success' ? 'var(--emerald-500)' : 'var(--danger-500)'}">${item.status.toUpperCase()}</span>
                            </div>
                            <span style="font-size:10px; color:var(--text-muted); flex-shrink:0;">${dateStr}</span>
                        </div>
                    `;
                }).join('');
            }
        }

        // Render table
        if (this.state.history.length === 0) {
            tbody.innerHTML = `
                <tr class="table-empty">
                    <td colspan="5">
                        <div class="empty-state">
                            <i data-lucide="clock"></i>
                            <p>No cryptographic transaction activity logged.</p>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = this.state.history.map(item => {
            const isEnc = item.operation_type === 'encrypt';
            const icon = isEnc ? 'lock' : 'unlock';
            const colorClass = isEnc ? 'badge-blue' : 'badge-emerald';
            const dateStr = new Date(item.created_at).toLocaleString();

            return `
                <tr>
                    <td>
                        <span class="badge ${colorClass}">
                            <i data-lucide="${icon}" style="width:10px; height:10px;"></i>
                            ${item.operation_type.toUpperCase()}
                        </span>
                    </td>
                    <td><strong>${item.target_name}</strong></td>
                    <td>${dateStr}</td>
                    <td><span class="badge ${item.status === 'success' ? 'badge-emerald' : 'badge-danger'}">${item.status.toUpperCase()}</span></td>
                </tr>
            `;
        }).join('');
    },

    // Render My Files dynamic database catalog
    renderMyFilesTable() {
        const tbody = document.getElementById('my-files-tbody');
        if (!tbody) return;

        if (this.state.files.length === 0) {
            tbody.innerHTML = `
                <tr class="table-empty">
                    <td colspan="6">
                        <div class="empty-state">
                            <i data-lucide="folder-open"></i>
                            <p>No secured file envelopes logged. Start by encrypting a file!</p>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = this.state.files.map(file => {
            const dateStr = new Date(file.created_at).toLocaleDateString();
            
            return `
                <tr>
                    <td>
                        <div style="display:flex; align-items:center; gap:10px;">
                            <i data-lucide="${this.getFileIconName(file.original_name)}" style="width:16px; height:16px; color:var(--blue-500);"></i>
                            <strong>${file.original_name}</strong>
                        </div>
                    </td>
                    <td><code style="font-size:11px;">${file.original_name}.vault</code></td>
                    <td>${this.formatFileSize(file.file_size)}</td>
                    <td>${dateStr}</td>
                    <td><span class="badge badge-blue">${file.algorithm}</span></td>
                    <td class="actions-col">
                        <a class="btn btn-secondary btn-icon" href="api/upload.php?action=download&id=${file.id}" style="padding:6px; margin-right:4px;" title="Securely download file">
                            <i data-lucide="download" style="width:14px; height:14px;"></i>
                        </a>
                        <button class="btn btn-danger-outline btn-icon" onclick="app.deleteServerFileRecord('${file.id}')" style="padding:6px; border-color:transparent;" title="Delete permanently">
                            <i data-lucide="trash-2" style="width:14px; height:14px;"></i>
                        </button>
                    </td>
                </tr>
            `;
        }).join('');
    },

    // Permanent file deletion from Server
    async deleteServerFileRecord(fileId) {
        if (!confirm('Are you absolutely sure you want to permanently delete this encrypted file off the WAMP server?')) return;
        
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', fileId);

        try {
            const response = await fetch('api/upload.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            if (data.success) {
                this.showToast(data.message, 'success');
                this.loadDashboardData();
            } else {
                this.showToast(data.message, 'error');
            }
        } catch (e) {
            this.showToast('Delete request network failure.', 'error');
        }
    },

    /* ==========================================================================
       4. CLIENT-SIDE WEB CRYPTO ENGINE WRAPPERS (AES-GCM-256)
       ========================================================================== */

    // Core encryption orchestrator
    async executeClientSideEncryption() {
        const isFileMode = document.querySelector('.segmented-control button[data-target="encrypt-file"]').classList.contains('active');
        const password = document.getElementById('enc-password').value;

        if (!password) {
            this.showToast('Please enter a password key passphrase.', 'error');
            return;
        }

        const btn = document.getElementById('btn-run-encrypt');
        const placeholder = document.getElementById('enc-placeholder');
        const display = document.getElementById('enc-result-display');
        const textResult = document.getElementById('enc-text-result-body');
        const fileResult = document.getElementById('enc-file-result-body');

        try {
            btn.disabled = true;
            btn.innerHTML = `<i data-lucide="loader" class="pulse-icon"></i> Local Crypto Encrypting...`;
            if (window.lucide) window.lucide.createIcons();

            const startTime = performance.now();

            if (isFileMode) {
                // A. FILE ENCRYPTION
                if (!this.activeEncryptFile) {
                    this.showToast('Please choose a file to encrypt first.', 'error');
                    this.resetEncryptBtn(btn);
                    return;
                }

                const file = this.activeEncryptFile;
                // Encrypt locally using browser Web Crypto API (zero-knowledge)
                const encryptedBlob = await CyberVaultCrypto.encryptFile(file, password);
                
                // POST payload structure to PHP backend using XMLHttpRequest to track dynamic transfer rates
                await this.uploadEncryptedFileToWamp(encryptedBlob, file.name, 'AES-GCM-256');

                const timeTaken = ((performance.now() - startTime) / 1000).toFixed(3);

                // Set download properties
                this.lastEncryptedBlob = encryptedBlob;
                this.lastEncryptedFilename = `${file.name}.vault`;

                document.getElementById('enc-result-metadata').innerText = `AES-GCM-256 | Size: ${this.formatFileSize(file.size)} | Time: ${timeTaken}s`;
                document.getElementById('enc-vault-filename').innerText = this.lastEncryptedFilename;

                textResult.style.display = 'none';
                fileResult.style.display = 'block';

            } else {
                // B. TEXT ENCRYPTION
                const text = document.getElementById('enc-text-input').value.trim();
                if (!text) {
                    this.showToast('Please enter plain text data blocks to encrypt.', 'error');
                    this.resetEncryptBtn(btn);
                    return;
                }

                const ciphertext = await CyberVaultCrypto.encryptText(text, password);
                const timeTaken = ((performance.now() - startTime) / 1000).toFixed(3);

                document.getElementById('enc-result-metadata').innerText = `AES-GCM-256 | Time: ${timeTaken}s`;
                document.getElementById('enc-ciphertext-output').innerText = ciphertext;

                fileResult.style.display = 'none';
                textResult.style.display = 'block';

                // Log successfully to MySQL
                await this.logTextActionToMySQL('encrypt', text.substring(0, 30) + '...', 'success');
                this.showToast('Text encrypted locally!', 'success');
            }

            placeholder.style.display = 'none';
            display.style.display = 'flex';
            this.loadDashboardData(); // sync stats widgets

        } catch (err) {
            console.error(err);
            this.showToast(err.message || 'Encryption processing failed.', 'error');
        } finally {
            this.resetEncryptBtn(btn);
        }
    },

    resetEncryptBtn(btn) {
        btn.disabled = false;
        btn.innerHTML = `<i data-lucide="lock"></i> Securely Encrypt Now`;
        if (window.lucide) window.lucide.createIcons();
    },

    // Secure XMLHttpRequest uploader showing authenticating live transfer fills
    uploadEncryptedFileToWamp(blob, originalName, algo) {
        const self = this;
        return new Promise((resolve, reject) => {
            const formData = new FormData();
            formData.append('file', blob, `${originalName}.vault`);
            formData.append('original_name', originalName);
            formData.append('algorithm', algo);
            formData.append('file_size', blob.size);

            const progressContainer = document.getElementById('enc-progress-container');
            const barFill = document.getElementById('enc-progress-fill');
            const labelText = document.getElementById('enc-progress-text');

            progressContainer.style.display = 'block';
            barFill.style.width = '0%';
            labelText.innerText = 'Encrypting & preparing envelope payload...';

            const xhr = new XMLHttpRequest();
            
            // Listen to real-time byte transfers
            xhr.upload.addEventListener('progress', (e) => {
                if (e.lengthComputable) {
                    const percent = Math.round((e.loaded / e.total) * 100);
                    barFill.style.width = `${percent}%`;
                    labelText.innerText = `Uploading envelope container: ${percent}% completed...`;
                }
            });

            xhr.addEventListener('load', () => {
                if (xhr.status === 200) {
                    const data = JSON.parse(xhr.responseText);
                    if (data.success) {
                        labelText.innerText = 'Upload successful! File secured on WAMP Server.';
                        self.showToast('Secured file envelope uploaded to server!', 'success');
                        setTimeout(() => {
                            progressContainer.style.display = 'none';
                        }, 2000);
                        resolve(data);
                    } else {
                        labelText.innerText = 'Upload rejected by server policies.';
                        reject(new Error(data.message || 'Server upload failed.'));
                    }
                } else {
                    reject(new Error('Network server failure.'));
                }
            });

            xhr.addEventListener('error', () => reject(new Error('WAMP connection terminated.')));
            
            xhr.open('POST', 'api/upload.php');
            xhr.send(formData);
        });
    },

    // Core decryption orchestrator
    async executeClientSideDecryption() {
        const isFileMode = document.querySelector('.segmented-control button[data-target="decrypt-file"]').classList.contains('active');
        const password = document.getElementById('dec-password').value;

        if (!password) {
            this.showToast('Please enter the decryption password key.', 'error');
            return;
        }

        const btn = document.getElementById('btn-run-decrypt');
        const placeholder = document.getElementById('dec-placeholder');
        const display = document.getElementById('dec-result-display');
        const textResult = document.getElementById('dec-text-result-body');
        const fileResult = document.getElementById('dec-file-result-body');
        const header = document.getElementById('dec-result-header');
        const titleText = document.getElementById('dec-result-title');

        try {
            btn.disabled = true;
            btn.innerHTML = `<i data-lucide="loader" class="pulse-icon"></i> Authenticating Envelope...`;
            if (window.lucide) window.lucide.createIcons();

            const startTime = performance.now();

            if (isFileMode) {
                // A. FILE DECRYPTION
                if (!this.activeDecryptFile) {
                    this.showToast('Please upload a secure .vault envelope to decrypt.', 'error');
                    this.resetDecryptBtn(btn);
                    return;
                }

                const file = this.activeDecryptFile;
                // Read local .vault file locally and decrypt (client-side)
                const result = await CyberVaultCrypto.decryptFile(file, password);
                const timeTaken = ((performance.now() - startTime) / 1000).toFixed(3);

                // Set download values
                this.lastDecryptedBlob = result.blob;
                this.lastDecryptedFilename = result.filename;

                document.getElementById('dec-result-metadata').innerText = `AES-GCM-256 | Time: ${timeTaken}s`;
                document.getElementById('dec-restored-filename').innerText = result.filename;

                textResult.style.display = 'none';
                fileResult.style.display = 'block';

                // Log decryption success directly into MySQL
                await this.logTextActionToMySQL('decrypt', result.filename, 'success');
                this.showToast('Verification passed! File unlocked.', 'success');

            } else {
                // B. TEXT DECRYPTION
                const ciphertext = document.getElementById('dec-text-input').value.trim();
                if (!ciphertext) {
                    this.showToast('Please paste Base64 cipher text to decrypt.', 'error');
                    this.resetDecryptBtn(btn);
                    return;
                }

                const decryptedText = await CyberVaultCrypto.decryptText(ciphertext, password);
                const timeTaken = ((performance.now() - startTime) / 1000).toFixed(3);

                document.getElementById('dec-result-metadata').innerText = `AES-GCM-256 | Time: ${timeTaken}s`;
                document.getElementById('dec-plaintext-output').innerText = decryptedText;

                fileResult.style.display = 'none';
                textResult.style.display = 'block';

                // Log decryption success to MySQL
                await this.logTextActionToMySQL('decrypt', decryptedText.substring(0, 30) + '...', 'success');
                this.showToast('Verification passed! Ciphertext decrypted.', 'success');
            }

            // Restore success visual states
            header.className = 'result-header success';
            const successIcon = header.querySelector('i, svg');
            if (successIcon) successIcon.setAttribute('data-lucide', 'shield-check');
            titleText.innerText = 'Decryption Successful';

            placeholder.style.display = 'none';
            display.style.display = 'flex';
            this.loadDashboardData();

        } catch (err) {
            console.error(err);
            this.showToast(err.message || 'Decryption blocked. Authentication check failed.', 'error');

            const targetName = isFileMode ? (this.activeDecryptFile ? this.activeDecryptFile.name : 'Vault Envelope') : 'Cipher Text Block';
            await this.logTextActionToMySQL('decrypt', targetName, 'failed');

            // Render error banner
            header.className = 'result-header error';
            const errorIcon = header.querySelector('i, svg');
            if (errorIcon) errorIcon.setAttribute('data-lucide', 'shield-alert');
            titleText.innerText = 'Decryption Blocked';
            document.getElementById('dec-result-metadata').innerText = 'Verification Tag Mismatch';

            textResult.style.display = 'none';
            fileResult.style.display = 'none';

            placeholder.style.display = 'none';
            display.style.display = 'flex';
            this.loadDashboardData();
        } finally {
            this.resetDecryptBtn(btn);
        }
    },

    resetDecryptBtn(btn) {
        btn.disabled = false;
        btn.innerHTML = `<i data-lucide="key-round"></i> Authenticate & Decrypt`;
        if (window.lucide) window.lucide.createIcons();
    },

    // Sync AJAX logs to MySQL via PHP API
    async logTextActionToMySQL(opType, targetName, status) {
        const formData = new FormData();
        formData.append('action', 'log');
        formData.append('operation_type', opType);
        formData.append('target_name', targetName);
        formData.append('status', status);

        try {
            await fetch('api/history.php', {
                method: 'POST',
                body: formData
            });
        } catch (e) {
            console.error('AJAX logging error:', e);
        }
    },

    // Trigger local direct file downloads in browser
    triggerBlobDownload(blob, filename) {
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        setTimeout(() => {
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }, 100);
    },

    /* ==========================================================================
       5. AUXILIARY UTILITIES (FILE EXTENSIONS MAPPERS, SIZES, TIME AGO)
       ========================================================================== */
    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
    },

    formatTimeAgo(date) {
        const seconds = Math.floor((new Date() - date) / 1000);
        let interval = Math.floor(seconds / 31536000);
        if (interval >= 1) return interval + " years ago";
        interval = Math.floor(seconds / 2592000);
        if (interval >= 1) return interval + " months ago";
        interval = Math.floor(seconds / 86400);
        if (interval >= 1) return interval + " days ago";
        interval = Math.floor(seconds / 3600);
        if (interval >= 1) return interval + " hours ago";
        interval = Math.floor(seconds / 60);
        if (interval >= 1) return interval + " minutes ago";
        if (seconds < 10) return "just now";
        return Math.floor(seconds) + " seconds ago";
    },

    getFileIconName(filename) {
        const ext = filename.split('.').pop().toLowerCase();
        switch (ext) {
            case 'pdf': return 'file-text';
            case 'jpg':
            case 'jpeg':
            case 'png':
            case 'gif':
            case 'svg':
                return 'image';
            case 'mp3':
            case 'wav':
                return 'music';
            case 'mp4':
            case 'avi':
            case 'mov':
            case 'mkv':
                return 'video';
            case 'zip':
            case 'rar':
            case 'tar':
            case 'gz':
                return 'archive';
            case 'xml':
            case 'html':
            case 'json':
            case 'css':
            case 'js':
                return 'code';
            default:
                return 'file';
        }
    },

    // Apply Active CSS Theme Mode
    initTheme() {
        const localTheme = localStorage.getItem('cybervault_theme') || 'light';
        this.state.theme = localTheme;
        const body = document.body;
        if (localTheme === 'dark') {
            body.classList.remove('light-mode');
            body.classList.add('dark-mode');
        } else {
            body.classList.remove('dark-mode');
            body.classList.add('light-mode');
        }
    },

    // Bind Global Interactive Theme Toggler (Landing & Dashboard)
    bindGlobalThemeToggle() {
        const self = this;
        const toggleBtn = document.getElementById('theme-toggle');
        if (toggleBtn) {
            toggleBtn.addEventListener('click', () => {
                self.state.theme = self.state.theme === 'light' ? 'dark' : 'light';
                localStorage.setItem('cybervault_theme', self.state.theme);
                self.initTheme();
                self.showToast(`Theme switched to ${self.state.theme.toUpperCase()}`, 'info');
            });
        }
    },

    // Spawns premium toast notifications on screen bottom right
    showToast(message, type = 'success') {
        const container = document.getElementById('toast-container');
        if (!container) return;

        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        
        let iconName = 'check-circle-2';
        if (type === 'error') iconName = 'alert-triangle';
        if (type === 'info') iconName = 'info';

        toast.innerHTML = `
            <i data-lucide="${iconName}"></i>
            <div class="toast-message">${message}</div>
            <button class="toast-close" style="font-size: 16px; color: var(--text-muted);">&times;</button>
        `;

        container.appendChild(toast);
        if (window.lucide) window.lucide.createIcons();

        // Click to dismiss
        const closeBtn = toast.querySelector('.toast-close');
        closeBtn.addEventListener('click', () => toast.remove());

        // Auto remove
        setTimeout(() => {
            if (toast.parentNode) toast.remove();
        }, 4000);
    },

    // Live search secured files table catalog
    filterSecuredFilesTable(keyword) {
        const rows = document.querySelectorAll('#my-files-tbody tr');
        let visibleCount = 0;

        rows.forEach(row => {
            if (row.classList.contains('table-empty')) return;
            const originalName = row.querySelector('td strong').innerText.toLowerCase();
            const envelopeName = row.querySelector('td code').innerText.toLowerCase();

            if (originalName.includes(keyword) || envelopeName.includes(keyword)) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        const emptyRow = document.querySelector('#my-files-tbody .table-empty');
        if (emptyRow) {
            emptyRow.style.display = (visibleCount === 0 && rows.length > 1) ? '' : 'none';
        }
    }
};

// Start app on DOM Content Loaded
document.addEventListener('DOMContentLoaded', () => {
    app.init();
});
