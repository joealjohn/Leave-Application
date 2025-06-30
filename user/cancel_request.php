<?php
global $pdo;
require_once '../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirectWithMessage('../login.php', 'You must log in to access this page', 'warning');
}

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirectWithMessage('my_requests.php', 'Invalid request ID', 'warning');
}

$requestId = $_GET['id'];
$userId = $_SESSION['user_id'];

// Get leave request details
$stmt = $pdo->prepare("SELECT * FROM leave_requests WHERE id = ? AND user_id = ?");
$stmt->execute([$requestId, $userId]);
$request = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if request exists and is pending
if (!$request) {
    redirectWithMessage('my_requests.php', 'Leave request not found', 'warning');
}

if ($request['status'] !== 'pending') {
    redirectWithMessage('view_request.php?id=' . $requestId, 'You can only cancel pending requests', 'warning');
}

// Process cancellation
try {
    // Delete the request
    $stmt = $pdo->prepare("DELETE FROM leave_requests WHERE id = ? AND user_id = ? AND status = 'pending'");
    $stmt->execute([$requestId, $userId]);

    // Log activity
    logActivity(
        'Leave Request Cancelled',
        "Cancelled {$request['leave_type']} leave request #$requestId from {$request['start_date']} to {$request['end_date']}"
    );

    redirectWithMessage('my_requests.php', "Leave request cancelled successfully", 'success');
} catch (PDOException $e) {
    redirectWithMessage('view_request.php?id=' . $requestId, "Error cancelling request: " . $e->getMessage(), 'danger');
}
?>