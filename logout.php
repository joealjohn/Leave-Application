<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (file_exists('includes/functions.php')) {
    require_once 'includes/functions.php';
} else if (file_exists('../includes/functions.php')) {
    require_once '../includes/functions.php';
}

if (isset($_SESSION['user_id']) && function_exists('logActivity')) {
    logActivity('Logout', 'User logged out successfully', $_SESSION['user_id']);
}

$_SESSION = array();

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

session_destroy();

$base_path = '';

if (strpos($_SERVER['PHP_SELF'], '/admin/') !== false ||
    strpos($_SERVER['PHP_SELF'], '/user/') !== false) {
    $base_path = '../';
}

header("Location: {$base_path}login.php?message=You have been logged out successfully&type=success");
exit;
?>