<?php
/**
 * CyberVault - Configuration & Database Connection Module
 * Optimized for WAMP/XAMPP environments.
 */

// Error Reporting (Turn off in production, keep active for local testing)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database configuration settings
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', ''); // Default blank password for WAMP MySQL root
define('DB_NAME', 'cybervault_db');

try {
    // 1. Establish secure PDO connection with error modes
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false, // Prevents SQL injections natively
        ]
    );
} catch (PDOException $e) {
    // Graceful error termination for academic demonstration
    die("Database Connection Failed: " . $e->getMessage() . "<br>Please ensure WAMP Server MySQL service is running and database 'cybervault_db' is imported.");
}

// System configuration parameters
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 50 * 1024 * 1024); // 50MB maximum upload limit for local safety
define('ALLOWED_EXTENSIONS', ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'mp3', 'wav', 'mp4', 'avi', 'mov', 'mkv', 'zip', 'rar', 'tar', 'gz', 'txt', 'xml', 'json', 'html', 'css', 'js', 'vault']);

// Safe Session Start Trigger
if (session_status() === PHP_SESSION_NONE) {
    // Set secure cookie settings (HTTPOnly prevents XSS theft of session cookies)
    session_start([
        'cookie_httponly' => true,
    ]);
}
?>
