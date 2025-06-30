<?php
global $pdo;
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirectWithMessage('../login.php', 'You must log in as admin to access this page', 'warning');
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirectWithMessage('all_requests.php', 'Invalid request ID', 'warning');
}

$requestId = $_GET['id'];

$stmt = $pdo->prepare("SELECT lr.*, u.name as user_name, u.email as user_email 
                       FROM leave_requests lr 
                       JOIN users u ON lr.user_id = u.id 
                       WHERE lr.id = ?");
$stmt->execute([$requestId]);
$request = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$request) {
    redirectWithMessage('all_requests.php', 'Leave request not found', 'warning');
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
<?php include '../includes/admin-navbar.php'; ?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <h2>Leave Request Details</h2>
            <div class="d-flex justify-content-end">
                <a href="all_requests.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i> Back to All Requests
                </a>
            </div>
        </div>
    </div>

    <?php displayMessage(); ?>

    <!-- Request Details Card -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Request #<?php echo $request['id']; ?> - <?php echo ucfirst($request['leave_type']); ?> Leave</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-bordered">
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
                            <td><?php echo formatDateForDisplay($request['start_date']); ?></td>
                        </tr>
                        <tr>
                            <th>End Date</th>
                            <td><?php echo formatDateForDisplay($request['end_date']); ?></td>
                        </tr>
                        <tr>
                            <th>Duration</th>
                            <td><?php echo calculateLeaveDays($request['start_date'], $request['end_date']); ?> working days</td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-bordered">
                        <tr>
                            <th>Applied On</th>
                            <td><?php echo formatDateForDisplay($request['applied_at'], 'Y-m-d H:i'); ?></td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td>
                                <?php
                                $statusClass = '';
                                switch ($request['status']) {
                                    case 'approved': $statusClass = 'bg-success'; break;
                                    case 'rejected': $statusClass = 'bg-danger'; break;
                                    default: $statusClass = 'bg-warning text-dark';
                                }
                                ?>
                                <span class="badge <?php echo $statusClass; ?>"><?php echo ucfirst($request['status']); ?></span>
                            </td>
                        </tr>
                        <?php if (isset($request['admin_comment']) && !empty($request['admin_comment'])): ?>
                            <tr>
                                <th>Admin Comment</th>
                                <td><?php echo htmlspecialchars($request['admin_comment']); ?></td>
                            </tr>
                        <?php endif; ?>
                        <tr>
                            <th>Request ID</th>
                            <td>#<?php echo $request['id']; ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Reason Section -->
            <div class="mt-4">
                <h5>Reason for Leave</h5>
                <div class="border p-3 bg-light">
                    <?php echo nl2br(htmlspecialchars($request['reason'])); ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0">Actions</h5>
        </div>
        <div class="card-body">
            <div class="d-flex gap-2">
                <a href="all_requests.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Back to All Requests
                </a>

                <?php if ($request['status'] === 'pending'): ?>
                    <button class="btn btn-success approve-btn" data-id="<?php echo $request['id']; ?>" data-bs-toggle="modal" data-bs-target="#approveModal">
                        <i class="fas fa-check-circle me-1"></i> Approve Request
                    </button>
                    <button class="btn btn-danger reject-btn" data-id="<?php echo $request['id']; ?>" data-bs-toggle="modal" data-bs-target="#rejectModal">
                        <i class="fas fa-times-circle me-1"></i> Reject Request
                    </button>
                <?php else: ?>
                    <button class="btn btn-outline-secondary" disabled>
                        <i class="fas fa-info-circle me-1"></i> Already <?php echo ucfirst($request['status']); ?>
                    </button>
                <?php endif; ?>

                <button class="btn btn-outline-primary" onclick="window.print()">
                    <i class="fas fa-print me-1"></i> Print Details
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Approve Modal -->
<div class="modal fade" id="approveModal" tabindex="-1" aria-labelledby="approveModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="process_request.php" method="POST">
                <input type="hidden" name="action" value="approve">
                <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">

                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="approveModalLabel">Approve Leave Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to approve this leave request?</p>

                    <div class="mb-3">
                        <label for="admin_comment" class="form-label">Comment (Optional)</label>
                        <textarea class="form-control" id="admin_comment" name="admin_comment" rows="3" placeholder="Add an optional comment..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Approve Leave</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="process_request.php" method="POST">
                <input type="hidden" name="action" value="reject">
                <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">

                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="rejectModalLabel">Reject Leave Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to reject this leave request?</p>

                    <div class="mb-3">
                        <label for="reject_admin_comment" class="form-label">Reason for Rejection</label>
                        <textarea class="form-control" id="reject_admin_comment" name="admin_comment" rows="3" placeholder="Provide a reason for rejection..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject Leave</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
    });
</script>

<!-- Only include the footer - no duplication -->
<?php include '../includes/footer.php'; ?>
</body>
</html>