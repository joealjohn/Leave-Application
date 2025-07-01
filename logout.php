<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('LOGOUT_PROCESS', true);

$user_id = $_SESSION['user_id'] ?? null;
$user_name = $_SESSION['user_name'] ?? 'Unknown';

$_SESSION = array();

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

header("Location: login.php?message=You have been logged out successfully&type=success");
exit();
?>