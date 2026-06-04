<?php
/**
 * CyberVault - Session Management and Route Guard Module
 */

require_once __DIR__ . '/config.php';

/**
 * Checks if the user is currently logged in.
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

/**
 * Route guard that redirects guests back to the index/login screen.
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: index.php?auth_required=1');
        exit;
    }
}

/**
 * Route guard that redirects logged-in users away from auth/index pages to the dashboard.
 */
function requireGuest() {
    if (isLoggedIn()) {
        header('Location: dashboard.php');
        exit;
    }
}

/**
 * Retrieves safe logged-in user context.
 * @return array|null
 */
function getCurrentUser() {
    if (!isLoggedIn()) return null;
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'email' => $_SESSION['email'] ?? ''
    ];
}
?>
