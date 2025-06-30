<?php
global $pdo;
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    redirectWithMessage('../login.php', 'You must log in to access this page', 'warning');
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirectWithMessage('my_requests.php', 'Invalid request ID', 'warning');
}

$requestId = $_GET['id'];
$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM leave_requests WHERE id = ? AND user_id = ?");
$stmt->execute([$requestId, $userId]);
$request = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$request) {
    redirectWithMessage('my_requests.php', 'Leave request not found', 'warning');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Leave Request - Leave Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include '../includes/user-navbar.php'; ?>

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

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Leave Request Details</h2>
        <div>
            <a href="my_requests.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i> Back to My Requests
            </a>
            <?php if ($request['status'] === 'pending'): ?>
                <a href="edit_request.php?id=<?php echo $requestId; ?>" class="btn btn-warning ms-2">
                    <i class="fas fa-edit me-2"></i> Edit Request
                </a>
                <a href="cancel_request.php?id=<?php echo $requestId; ?>" class="btn btn-danger ms-2" onclick="return confirm('Are you sure you want to cancel this request? This action cannot be undone.');">
                    <i class="fas fa-times-circle me-2"></i> Cancel Request
                </a>
            <?php endif; ?>
        </div>
    </div>

    <?php displayMessage(); ?>

    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Request #<?php echo $request['id']; ?> - <?php echo ucfirst($request['leave_type']); ?> Leave</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-bordered">
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
                            <th>Status</th>
                            <td>
                                <?php
                                $statusClass = '';
                                switch ($request['status']) {
                                    case 'approved': $statusClass = 'bg-success'; break;
                                    case 'rejected': $statusClass = 'bg-danger'; break;
                                    default: $statusClass = 'bg-warning';
                                }
                                ?>
                                <span class="badge <?php echo $statusClass; ?>"><?php echo ucfirst($request['status']); ?></span>
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="col-md-6">
                    <table class="table table-bordered">
                        <tr>
                            <th>Applied On</th>
                            <td><?php echo formatDateForDisplay($request['applied_at'], 'Y-m-d H:i:s'); ?></td>
                        </tr>
                        <tr>
                            <th>Reviewed On</th>
                            <td>
                                <?php echo $request['reviewed_at'] ? formatDateForDisplay($request['reviewed_at'], 'Y-m-d H:i:s') : 'Not yet reviewed'; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Reviewed By</th>
                            <td><?php echo $request['reviewed_by'] ?? 'Not yet reviewed'; ?></td>
                        </tr>
                        <tr>
                            <th>Request ID</th>
                            <td>#<?php echo $request['id']; ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="mt-4">
                <h5>Reason for Leave</h5>
                <div class="p-3 border rounded">
                    <?php echo nl2br(sanitizeInput($request['reason'])); ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2">
                        <a href="my_requests.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i> Back to My Requests
                        </a>

                        <?php if ($request['status'] === 'pending'): ?>
                            <a href="edit_request.php?id=<?php echo $requestId; ?>" class="btn btn-warning">
                                <i class="fas fa-edit me-2"></i> Edit Request
                            </a>
                            <a href="cancel_request.php?id=<?php echo $requestId; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to cancel this request? This action cannot be undone.');">
                                <i class="fas fa-times-circle me-2"></i> Cancel Request
                            </a>
                        <?php else: ?>
                            <button type="button" class="btn btn-secondary" disabled>
                                <i class="fas fa-lock me-2"></i> Request Finalized
                            </button>
                        <?php endif; ?>

                        <a href="#" class="btn btn-info" onclick="window.print();">
                            <i class="fas fa-print me-2"></i> Print Details
                        </a>

                        <a href="apply_leave.php" class="btn btn-success">
                            <i class="fas fa-plus-circle me-2"></i> New Leave Request
                        </a>
                    </div>
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