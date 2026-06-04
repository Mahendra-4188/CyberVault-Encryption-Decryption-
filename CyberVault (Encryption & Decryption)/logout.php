<?php
/**
 * CyberVault - Logout Session Termination Module
 */

require_once __DIR__ . '/includes/config.php';

// Empty session array variables
$_SESSION = [];

// Destroy session cookies if set
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Terminate PHP Session
session_destroy();

// Redirect back to Landing Home page
header('Location: index.php?logout=1');
exit;
?>
