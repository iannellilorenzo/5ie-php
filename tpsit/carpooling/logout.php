<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set root path for redirect
$rootPath = "";

// Clear all session variables
$_SESSION = array();

// If a session cookie is used, destroy it
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// If "remember me" functionality is implemented, clear that cookie too
if (isset($_COOKIE['remember_carpooling'])) {
    setcookie('remember_carpooling', '', time() - 3600, '/');
}

// Destroy the session
session_destroy();

// Redirect to login page with a logout message
header("Location: {$rootPath}login.php?logout=success");
exit();