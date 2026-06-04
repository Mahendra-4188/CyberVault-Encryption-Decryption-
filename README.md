# CyberVault-Encryption-Decryption-
CyberVault is a full-stack zero-knowledge file encryption platform built with PHP, MySQL, and Vanilla JS/CSS. It uses client-side AES-256-GCM encryption via the Web Crypto API, securing files before upload. Features include a responsive Light/Dark theme, interactive UI, and smooth cursor-based parallax effects for an enhanced user experience.


🌌 CyberVault - Zero-Knowledge File Encryption & Storage Platform
CyberVault is a professional, full-stack Web Application designed for secure file encryption, decryption, and transit hosting. Developed as a final-year major project for BCA / BBA(CA), it pairs absolute zero-knowledge client-side cryptography with a secure backend storage structure and a premium, high-fidelity user interface inspired by modern AI developer consoles.

🚀 Key Features
🔒 1. Zero-Knowledge Cryptography
On-Device Encryption: All encryption and decryption operations occur locally inside the user's browser using the native Web Crypto API (AES-GCM 256-bit). Plain text, files, and key passphrases never touch the database in an unencrypted state.
PBKDF2 Key Derivation: User passwords are dynamically derived into high-strength 256-bit cryptographic keys using PBKDF2 with 10,000 iterations and a SHA-256 HMAC hash.
Physical Vault Envelopes: Files are packed into custom .vault binary formats containing a file header (CVLT), a unique PBKDF2 salt, an initialization vector (IV), and the encrypted payload before transit.

🎨 2. Google Antigravity-Style UX & Visuals
3D Cursor-Tracking Parallax: Floating stars and grid elements drift and rotate in real-time based on mouse movement, smoothed out using a high-performance Linear Interpolation (LERP) render loop at 60fps.
Dynamic Spotlight Mouse Glow: A cursor-tracking radial lighting spotlight (blending purple, indigo, and emerald gradients) highlights grid points in real-time.
Fluid Dual-Theme Switching: Supports Light Space and Dark Space themes. Everything from gradient text to glass borders automatically adjusts with fluid transitions.
Responsive Layout: Mobile-friendly layouts with a slide-in sidebar dashboard, glassmorphic login modals, and toast notification popups.

⚙️ 3. Robust Backend Architecture
PDO Database Connection: Emulated prepared statements are disabled natively (PDO::ATTR_EMULATE_PREPARES => false) to lock out SQL injection threats.
Storage Collision Prevention: Uploaded vault files are renamed to unique MD5 hashes on the server and physically protected by .htaccess rules to block direct directory snooping.
Dynamic Schema Adaptation: Registration and login endpoints automatically detect database attributes, generating secure API keys dynamically.
Secure Access Channels: Relies on secure session identifiers and HTTPOnly cookies to safeguard users against Cross-Site Scripting (XSS).

🛠️ Technology Stack
Frontend: HTML5, Vanilla CSS3, Modern ES6+ JavaScript, Lucide Icons
Backend: PHP 7.4+ (PDO Engine)
Database: MySQL / MariaDB
Cryptography: Native Browser Web Crypto API (AES-GCM-256)
Local Server: WAMP / XAMPP Server

📁 Project Structure
CyberVault (Encryption & Decryption)/
├── assets/
│   ├── css/
│   │   └── style.css            # Custom CSS vars, responsive layouts, parallax auras
│   └── js/
│       ├── crypto.js            # Client-side AES-GCM & PBKDF2 Web Crypto wrappers
│       └── app.js               # Parallax loop, dynamic glows, AJAX requests controller
│
├── database/
│   └── database.sql             # SQL script creating database tables & demo rows
│
├── includes/
│   ├── config.php               # PDO connection, server-side size configurations
│   ├── session.php              # Route guards and authorization sessions
│   └── auth.php                 # AJAX API handling sign-ins and registration
│
├── api/
│   ├── upload.php               # Handles secure physical uploading & downloads
│   └── history.php              # Logs operations and feeds dashboard logs
│
├── uploads/
│   └── .htaccess                # Blocks direct HTTP access to physical uploads
│
├── index.php                    # Parallax Landing Page with auth modals
├── dashboard.php                # Live statistics and crypt workspace dashboard
└── logout.php                   # Clears session cookies and redirects

⚙️ Installation & Setup
Follow these steps to run this project locally on your system using WAMP or XAMPP:

Prerequisite
Ensure you have WAMP Server or XAMPP Server installed and running on your Windows machine.

Step 1: Clone the Repository
Clone or extract the project directory into your server's root folder:
WAMP: C:\wamp64\www\CyberVault (Encryption & Decryption)
XAMPP: C:\xampp\htdocs\CyberVault (Encryption & Decryption)

Step 2: Database Initialization
i. Open your browser and navigate to http://localhost/phpmyadmin/.
ii. Create a new database named cybervault_db.
iii. Select the database, click the Import tab, choose the script file located at database/database.sql inside the project, and click Go.

Step 3: Configure Environment
i. Open includes/config.php in a text editor.
ii. Confirm your database configurations align with your server:
php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', ''); // Add password if required
define('DB_NAME', 'cybervault_db');

Step 4: Run the Application
i. Open your web browser.
ii. Navigate to: http://localhost/CyberVault (Encryption & Decryption)/
iii. Try registering a new account or sign in using the seeded administrator account:
     Username: specter
     Password: supersecret123

🧪 Verification & Core Workflows
1) Text Operations: Paste plaintext, select a passphrase, and encrypt. Decrypt it in the next tab. Verify that passing an incorrect password fails authentication.

2) File Operations: Drag and drop a file (PDF, TXT, PNG, etc.) into the upload zone, set a password, and encrypt. Your browser automatically triggers the download of the encrypted .vault container.

3) Database Logging: Go to the History tab in the dashboard to view your operations. Verify that physical uploads inside the uploads/ folder match the unique hash logged under phpMyAdmin.
