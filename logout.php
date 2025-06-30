<?php
// Start the session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include functions for logging if needed
if (file_exists('includes/functions.php')) {
    require_once 'includes/functions.php';
} else if (file_exists('../includes/functions.php')) {
    require_once '../includes/functions.php';
}

// Log the logout activity if the user is logged in and functions are available
if (isset($_SESSION['user_id']) && function_exists('logActivity')) {
    logActivity('Logout', 'User logged out successfully', $_SESSION['user_id']);
}

// Clear all session variables
$_SESSION = array();

// If it's desired to kill the session, also delete the session cookie.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Finally, destroy the session
session_destroy();

// Determine the correct path to redirect
$base_path = '';

// If we're in a subdirectory, adjust the path
if (strpos($_SERVER['PHP_SELF'], '/admin/') !== false ||
    strpos($_SERVER['PHP_SELF'], '/user/') !== false) {
    $base_path = '../';
}

// Redirect to login page with a message
header("Location: {$base_path}login.php?message=You have been logged out successfully&type=success");
exit;
?>