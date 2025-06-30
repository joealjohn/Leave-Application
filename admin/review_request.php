<?php
global $pdo;
require_once '../includes/functions.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirectWithMessage('../login.php', 'You must log in as admin to access this page', 'warning');
}

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirectWithMessage('all_requests.php', 'Invalid request ID', 'warning');
}

$requestId = $_GET['id'];

// Get leave request details
$stmt = $pdo->prepare("SELECT lr.*, u.name as user_name, u.email as user_email 
                       FROM leave_requests lr 
                       JOIN users u ON lr.user_id = u.id 
                       WHERE lr.id = ?");
$stmt->execute([$requestId]);
$request = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if request exists and is pending
if (!$request) {
    redirectWithMessage('all_requests.php', 'Leave request not found', 'warning');
}

if ($request['status'] !== 'pending') {
    redirectWithMessage('view_request.php?id=' . $requestId, 'This request has already been reviewed', 'info');
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['status'] ?? '';
    $comments = $_POST['comments'] ?? '';

    if (!in_array($status, ['approved', 'rejected'])) {
        $error = "Invalid status selected";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE leave_requests SET 
                                  status = ?, 
                                  reviewed_at = ?,
                                  reviewed_by = ? 
                                  WHERE id = ?");
            $stmt->execute([
                $status,
                getCurrentDateTime(),
                getCurrentUser(),
                $requestId
            ]);

            // Log activity
            $action = 'Leave Request ' . ucfirst($status);
            $details = "Request ID: $requestId, User: {$request['user_name']}, Type: {$request['leave_type']}, " .
                "From: {$request['start_date']} To: {$request['end_date']}, Comments: $comments";
            logActivity($action, $details);

            redirectWithMessage('all_requests.php', "Leave request has been $status", 'success');
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Leave Request - Leave Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include '../includes/admin-navbar.php'; ?>

<div class="container-fluid">
    <!-- Date and Time Display -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">
                                <i class="fas fa-calendar-alt me-2"></i> Current Date and Time (UTC - YYYY-MM-DD HH:MM:SS formatted): <?php echo getCurrentDateTime(); ?>
                            </h5>
                        </div>
                        <div>
                                <span class="badge bg-success">
                                    <i class="fas fa-user me-1"></i> Current User's Login: <?php echo getCurrentUser(); ?>
                                </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <h2 class="mb-4">Review Leave Request</h2>

    <?php displayMessage(); ?>
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Request Details</h5>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr>
                            <th>Request ID</th>
                            <td><?php echo $request['id']; ?></td>
                        </tr>
                        <tr>
                            <th>Employee</th>
                            <td><?php echo $request['user_name']; ?> (<?php echo $request['user_email']; ?>)</td>
                        </tr>
                        <tr>
                            <th>Leave Type</th>
                            <td>
                                <?php
                                $leaveTypeClass = '';
                                switch ($request['leave_type']) {
                                    case 'sick': $leaveTypeClass = 'bg-danger'; break;
                                    case 'vacation': $leaveTypeClass = 'bg-primary'; break;
                                    case 'personal': $leaveTypeClass = 'bg-info'; break;
                                    case 'emergency': $leaveTypeClass = 'bg-warning'; break;
                                    default: $leaveTypeClass = 'bg-secondary';
                                }
                                ?>
                                <span class="badge <?php echo $leaveTypeClass; ?>"><?php echo ucfirst($request['leave_type']); ?></span>
                            </td>
                        </tr>
                        <tr>
                            <th>Start Date</th>
                            <td><?php echo formatDateForDisplay($request['start_date'], 'Y-m-d'); ?></td>
                        </tr>
                        <tr>
                            <th>End Date</th>
                            <td><?php echo formatDateForDisplay($request['end_date'], 'Y-m-d'); ?></td>
                        </tr>
                        <tr>
                            <th>Duration</th>
                            <td><?php echo calculateLeaveDays($request['start_date'], $request['end_date']); ?> working days</td>
                        </tr>
                        <tr>
                            <th>Applied On</th>
                            <td><?php echo formatDateForDisplay($request['applied_at'], 'Y-m-d H:i'); ?></td>
                        </tr>
                        <tr>
                            <th>Reason</th>
                            <td><?php echo nl2br(sanitizeInput($request['reason'])); ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Review Decision</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="review_request.php?id=<?php echo $requestId; ?>">
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <div class="d-flex">
                                <div class="form-check me-4">
                                    <input class="form-check-input" type="radio" name="status" id="approve" value="approved" required>
                                    <label class="form-check-label" for="approve">
                                        Approve
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="status" id="reject" value="rejected">
                                    <label class="form-check-label" for="reject">
                                        Reject
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="comments" class="form-label">Comments (Optional)</label>
                            <textarea class="form-control" id="comments" name="comments" rows="5"></textarea>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Submit Decision</button>
                            <a href="all_requests.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/user-navbar.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/scripts.js"></script>
</body>
</html>