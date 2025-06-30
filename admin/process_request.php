<?php
global $pdo;
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirectWithMessage('../login.php', 'You must log in as admin to access this page', 'warning');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectWithMessage('all_requests.php', 'Invalid request method', 'danger');
}

$action = $_POST['action'] ?? '';
$requestId = $_POST['request_id'] ?? '';
$adminComment = $_POST['admin_comment'] ?? '';

if (empty($action) || empty($requestId) || !is_numeric($requestId)) {
    redirectWithMessage('all_requests.php', 'Invalid request data', 'danger');
}

try {
    $stmt = $pdo->prepare("SELECT id, user_id, leave_type, start_date, end_date FROM leave_requests WHERE id = ? AND status = 'pending'");
    $stmt->execute([$requestId]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$request) {
        redirectWithMessage('all_requests.php', 'Leave request not found or already processed', 'warning');
    }

    $columnExists = false;
    try {
        $checkStmt = $pdo->query("SHOW COLUMNS FROM leave_requests LIKE 'admin_comment'");
        $columnExists = ($checkStmt && $checkStmt->rowCount() > 0);
    } catch (PDOException $e) {
        $columnExists = false;
    }

    if ($action === 'approve') {
        if ($columnExists) {
            $stmt = $pdo->prepare("UPDATE leave_requests SET status = 'approved', admin_comment = ? WHERE id = ?");
            $stmt->execute([$adminComment, $requestId]);
        } else {
            $stmt = $pdo->prepare("UPDATE leave_requests SET status = 'approved' WHERE id = ?");
            $stmt->execute([$requestId]);
        }

        $days = calculateLeaveDays($request['start_date'], $request['end_date']);
        logActivity(
            'Leave Request Approved',
            "Approved {$request['leave_type']} leave request #{$requestId} for " . $days . " days"
        );

        redirectWithMessage('all_requests.php', 'Leave request approved successfully', 'success');
    }
    else if ($action === 'reject') {
        if (empty($adminComment)) {
            redirectWithMessage('view_request.php?id=' . $requestId, 'Please provide a reason for rejection', 'warning');
        }

        if ($columnExists) {
            $stmt = $pdo->prepare("UPDATE leave_requests SET status = 'rejected', admin_comment = ? WHERE id = ?");
            $stmt->execute([$adminComment, $requestId]);
        } else {
            $stmt = $pdo->prepare("UPDATE leave_requests SET status = 'rejected' WHERE id = ?");
            $stmt->execute([$requestId]);
        }

        logActivity(
            'Leave Request Rejected',
            "Rejected {$request['leave_type']} leave request #{$requestId}"
        );

        redirectWithMessage('all_requests.php', 'Leave request rejected successfully', 'success');
    }
    else {
        redirectWithMessage('all_requests.php', 'Invalid action specified', 'danger');
    }

} catch (PDOException $e) {
    redirectWithMessage('all_requests.php', 'Database error: ' . $e->getMessage(), 'danger');
}
?>