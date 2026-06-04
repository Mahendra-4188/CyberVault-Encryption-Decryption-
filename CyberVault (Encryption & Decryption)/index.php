<?php
/**
 * CyberVault - Modern SaaS Landing Page (with Parallax & Scroll Reveals)
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/session.php';

// Route Guard: Logged-in users are redirected to the Dashboard instantly
requireGuest();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CyberVault - Client-Side Encryption & Secure Cloud Storage</title>
    <!-- Google Fonts: Inter & Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="light-mode">

    <!-- Responsive SaaS Navbar -->
    <header class="home-navbar">
        <div class="logo-container">
            <div class="logo-icon">
                <i data-lucide="shield-check"></i>
            </div>
            <span class="logo-text">Cyber<span>Vault</span></span>
        </div>
        
        <nav>
            <ul class="home-nav-links">
                <li><a href="#features">Features</a></li>
                <li><a href="#security">Security Standard</a></li>
                <li><a href="#how-it-works">How It Works</a></li>
            </ul>
        </nav>

        <div class="home-nav-actions">
            <button class="theme-toggle" id="theme-toggle" aria-label="Toggle Theme">
                <i data-lucide="moon" class="moon-icon"></i>
                <i data-lucide="sun" class="sun-icon"></i>
            </button>
            <button class="btn btn-secondary btn-open-login">Sign In</button>
            <button class="btn btn-blue btn-open-register">Get Started</button>
        </div>
    </header>

    <!-- Parallax Scrolling Hero Section -->
    <section class="hero-parallax-container" id="hero-interactive-zone">
        <!-- Interactive Mouse Glow spotlight -->
        <div class="layer-mouse-glow" id="mouse-glow-overlay"></div>

        <!-- Parallax Background Layers -->
        <div class="parallax-layer layer-stars"></div>
        <div class="parallax-layer layer-bg-grid"></div>
        
        <!-- Floating Visual Layers (moving independently) -->
        <div class="parallax-layer layer-floating-shield">
            <i data-lucide="shield-alert"></i>
        </div>

        <!-- Foreground Hero Content -->
        <div class="parallax-foreground">
            <div class="hero-badge">
                <i data-lucide="sparkles" style="width:12px; height:12px;"></i>
                <span>Zero-Knowledge Secure Encryption</span>
            </div>
            <h1 class="hero-title">Protect Sensitive Files With <span>Military-Grade</span> AES-256</h1>
            <p class="hero-subtitle">
                CyberVault secures your private documents and messages right inside your browser before they even hit the server. Absolute privacy, absolute integrity.
            </p>
            <div class="hero-actions">
                <button class="btn btn-blue btn-large btn-open-register">Secure Your Files Now</button>
                <a href="#features" class="btn btn-secondary btn-large">Discover Features <i data-lucide="chevron-down" style="width:14px; height:14px;"></i></a>
            </div>
        </div>

        <!-- Bouncing scroll indicator -->
        <a href="#features" class="scroll-indicator-btn">
            <span>SCROLL TO DISCOVER</span>
            <i data-lucide="arrow-down"></i>
        </a>
    </section>

    <!-- Product Features Section (Scroll Reveal) -->
    <section class="info-section reveal-hidden" id="features">
        <div class="section-container">
            <div class="section-header">
                <h2>Platform Features</h2>
                <p>Engineered for high performance, ease of use, and state-of-the-art privacy.</p>
            </div>

            <div class="features-grid">
                <div class="feature-item-card">
                    <div class="feature-icon-circle">
                        <i data-lucide="lock"></i>
                    </div>
                    <h3>AES-256-GCM</h3>
                    <p>Industry-standard Galois/Counter Mode symmetric encryption. Provides both extreme security and authentic data integrity checks.</p>
                </div>
                <div class="feature-item-card">
                    <div class="feature-icon-circle">
                        <i data-lucide="cpu"></i>
                    </div>
                    <h3>On-Device Crypto</h3>
                    <p>Web Crypto API executes on your hardware. Keys and plaintexts never get uploaded to the WAMP server in an raw, exposed state.</p>
                </div>
                <div class="feature-item-card">
                    <div class="feature-icon-circle">
                        <i data-lucide="cloud-lightning"></i>
                    </div>
                    <h3>WAMP Cloud Storage</h3>
                    <p>Stores physical encrypted vault envelopes in WAMP uploads. Database-backed transaction logging organizes your assets.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Security Standards Section (Scroll Reveal) -->
    <section class="info-section reveal-hidden" id="security" style="background-color: var(--bg-app);">
        <div class="section-container">
            <div class="section-header">
                <h2>Cryptographic Standards</h2>
                <p>An academic-grade college project implementing real industry security guidelines.</p>
            </div>

            <div class="features-grid" style="grid-template-columns: repeat(2, 1fr);">
                <div class="feature-item-card" style="background-color: var(--bg-card);">
                    <div class="feature-icon-circle" style="background-color: var(--emerald-50); color: var(--emerald-500);">
                        <i data-lucide="key-round"></i>
                    </div>
                    <h3>Key Derivation via PBKDF2</h3>
                    <p>Derives strong 256-bit keys from plain user password strings using PBKDF2 with 10,000 iterations and a SHA-256 HMAC hash. Effectively blocks brute-force decryption attacks.</p>
                </div>
                <div class="feature-item-card" style="background-color: var(--bg-card);">
                    <div class="feature-icon-circle" style="background-color: var(--emerald-50); color: var(--emerald-500);">
                        <i data-lucide="shield-check"></i>
                    </div>
                    <h3>Zero-Knowledge Architecture</h3>
                    <p>Unlike standard systems, we upload only `.vault` binary envelopes. Even if WAMP databases are compromised, no attacker can reconstruct files without your original password passphrase.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works Section (Scroll Reveal) -->
    <section class="info-section reveal-hidden" id="how-it-works">
        <div class="section-container" style="text-align: center;">
            <div class="section-header">
                <h2>Simplified Security Workflow</h2>
                <p>Practical zero-configuration secure cryptography in 3 simple phases.</p>
            </div>

            <div style="display:flex; justify-content:center; align-items:center; gap:40px; flex-wrap:wrap; margin-top:40px;">
                <div style="max-width:240px; text-align:center;">
                    <div class="feature-icon-circle" style="margin: 0 auto 16px auto;"><span style="font-weight:700;">1</span></div>
                    <h4>Upload File</h4>
                    <p style="font-size:12px; color:var(--text-secondary); margin-top:8px;">Drag and drop your file into the secure workspace dropzone panel.</p>
                </div>
                <i data-lucide="arrow-right" style="color:var(--text-muted); width:20px; height:20px;"></i>
                <div style="max-width:240px; text-align:center;">
                    <div class="feature-icon-circle" style="margin: 0 auto 16px auto;"><span style="font-weight:700;">2</span></div>
                    <h4>Enter Key Passphrase</h4>
                    <p style="font-size:12px; color:var(--text-secondary); margin-top:8px;">Choose a strong password key. Browser derives your security key locally.</p>
                </div>
                <i data-lucide="arrow-right" style="color:var(--text-muted); width:20px; height:20px;"></i>
                <div style="max-width:240px; text-align:center;">
                    <div class="feature-icon-circle" style="margin: 0 auto 16px auto;"><span style="font-weight:700;">3</span></div>
                    <h4>Download Result</h4>
                    <p style="font-size:12px; color:var(--text-secondary); margin-top:8px;">Download secured `.vault` container, automatically saving to WAMP uploads!</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Professional Footer -->
    <footer class="home-footer">
        <div class="logo-container" style="justify-content: center; margin-bottom: 16px;">
            <div class="logo-icon">
                <i data-lucide="shield-check"></i>
            </div>
            <span class="logo-text">Cyber<span>Vault</span></span>
        </div>
        <p class="footer-text">© 2026 CyberVault Security Platform. Designed for Final Year BBA(CA)/BCA Major Project Demonstration.</p>
    </footer>

    <!-- ==========================================================================
       GLASSMORPHISM MODALS (AUTH)
       ========================================================================== */ -->

    <!-- A. LOGIN MODAL -->
    <div class="modal-overlay" id="login-modal">
        <div class="modal-card">
            <div class="modal-header">
                <h2>Account Login</h2>
                <button class="modal-close-btn">&times;</button>
            </div>
            <form class="modal-form" id="login-form">
                <div class="form-group">
                    <label for="login-ident">Username or Email</label>
                    <input type="text" id="login-ident" name="username" placeholder="Type username or email..." required>
                </div>
                <div class="form-group">
                    <label for="login-pass">Password</label>
                    <input type="password" id="login-pass" name="password" placeholder="Type account password..." required>
                </div>
                <button type="submit" class="btn btn-blue btn-full" style="margin-top:10px; height:42px;">Authenticate & Login</button>
            </form>
            <div class="form-toggle-footer">
                Don't have a secure account? <a href="#" class="btn-open-register">Sign Up Free</a>
            </div>
        </div>
    </div>

    <!-- B. REGISTRATION MODAL -->
    <div class="modal-overlay" id="register-modal">
        <div class="modal-card">
            <div class="modal-header">
                <h2>Create Secure Account</h2>
                <button class="modal-close-btn">&times;</button>
            </div>
            <form class="modal-form" id="register-form">
                <div class="form-group">
                    <label for="reg-user">Username</label>
                    <input type="text" id="reg-user" name="username" placeholder="Choose username..." required>
                </div>
                <div class="form-group">
                    <label for="reg-email">Email Address</label>
                    <input type="email" id="reg-email" name="email" placeholder="Type email address..." required>
                </div>
                <div class="form-group">
                    <label for="reg-pass">Security Password</label>
                    <input type="password" id="reg-pass" name="password" placeholder="Must be at least 6 characters..." required minlength="6">
                </div>
                <button type="submit" class="btn btn-emerald btn-full" style="margin-top:10px; height:42px;">Register & Create Profile</button>
            </form>
            <div class="form-toggle-footer">
                Already registered? <a href="#" class="btn-open-login">Sign In</a>
            </div>
        </div>
    </div>

    <!-- Floating Toast Alerts System -->
    <div class="toast-container" id="toast-container"></div>

    <!-- Global JS Scripts -->
    <script src="assets/js/app.js"></script>
</body>
</html>
