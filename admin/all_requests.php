<?php
global $pdo;
require_once '../includes/functions.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirectWithMessage('../login.php', 'You must log in as admin to access this page', 'warning');
}

// Get leave requests status filter
$status = isset($_GET['status']) ? $_GET['status'] : 'all';

// Process leave request actions (approve/reject)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['request_id'])) {
    $action = $_POST['action'];
    $requestId = $_POST['request_id'];
    $comment = $_POST['comment'] ?? '';

    if ($action === 'approve' || $action === 'reject') {
        try {
            // Update leave request status
            $newStatus = ($action === 'approve') ? 'approved' : 'rejected';
            $stmt = $pdo->prepare("UPDATE leave_requests SET status = ?, reviewed_at = ?, reviewed_by = ? WHERE id = ?");
            $stmt->execute([$newStatus, getCurrentDateTime(), $_SESSION['user_name'], $requestId]);

            // Get leave request details for logging
            $stmt = $pdo->prepare("
                SELECT lr.*, u.name as user_name, u.email as user_email 
                FROM leave_requests lr 
                JOIN users u ON lr.user_id = u.id 
                WHERE lr.id = ?
            ");
            $stmt->execute([$requestId]);
            $request = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($request) {
                // Log the action
                $actionDetail = $action === 'approve' ? 'Approved' : 'Rejected';
                logActivity(
                    'Leave Request ' . $actionDetail,
                    "Request from {$request['user_name']} ({$request['start_date']} to {$request['end_date']}) has been {$newStatus}"
                );

                // Show success message
                redirectWithMessage('all_requests.php', "Leave request {$newStatus} successfully", 'success');
            } else {
                redirectWithMessage('all_requests.php', "Error: Request not found", 'danger');
            }
        } catch (PDOException $e) {
            redirectWithMessage('all_requests.php', "Database error: " . $e->getMessage(), 'danger');
        }
    }
}

// Get leave requests
try {
    // Build query based on status filter
    $query = "
        SELECT lr.*, u.name as user_name, u.email as user_email
        FROM leave_requests lr
        JOIN users u ON lr.user_id = u.id
    ";

    $params = [];

    if ($status !== 'all') {
        $query .= " WHERE lr.status = ?";
        $params[] = $status;
    }

    $query .= " ORDER BY lr.applied_at DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Leave Requests - Leave Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include '../includes/admin-navbar.php'; ?>

<div class="container-fluid py-4">
    <h2 class="mb-4">All Leave Requests</h2>

    <?php displayMessage(); ?>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <!-- Status Filter Buttons -->
    <div class="mb-4">
        <div class="btn-group">
            <a href="all_requests.php?status=all" class="btn <?php echo $status === 'all' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                All Requests
            </a>
            <a href="all_requests.php?status=pending" class="btn <?php echo $status === 'pending' ? 'btn-warning' : 'btn-outline-warning'; ?>">
                Pending
            </a>
            <a href="all_requests.php?status=approved" class="btn <?php echo $status === 'approved' ? 'btn-success' : 'btn-outline-success'; ?>">
                Approved
            </a>
            <a href="all_requests.php?status=rejected" class="btn <?php echo $status === 'rejected' ? 'btn-danger' : 'btn-outline-danger'; ?>">
                Rejected
            </a>
        </div>
    </div>

    <!-- Leave Requests Table -->
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Leave Requests</h5>
        </div>
        <div class="card-body">
            <?php if (count($requests) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Email</th>
                            <th>Leave Type</th>
                            <th>From</th>
                            <th>To</th>
                            <th>Days</th>
                            <th>Applied On</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($requests as $request): ?>
                            <?php $days = calculateLeaveDays($request['start_date'], $request['end_date']); ?>
                            <tr>
                                <td><?php echo $request['user_name']; ?></td>
                                <td><?php echo $request['user_email']; ?></td>
                                <td><?php echo ucfirst($request['leave_type']); ?></td>
                                <td><?php echo formatDateForDisplay($request['start_date']); ?></td>
                                <td><?php echo formatDateForDisplay($request['end_date']); ?></td>
                                <td><?php echo $days; ?></td>
                                <td><?php echo formatDateForDisplay($request['applied_at']); ?></td>
                                <td>
                                            <span class="badge bg-<?php
                                            echo $request['status'] === 'approved' ? 'success' :
                                                ($request['status'] === 'rejected' ? 'danger' : 'warning');
                                            ?>">
                                                <?php echo ucfirst($request['status']); ?>
                                            </span>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#viewModal<?php echo $request['id']; ?>">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <?php if ($request['status'] === 'pending'): ?>
                                            <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#approveModal<?php echo $request['id']; ?>">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal<?php echo $request['id']; ?>">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>

                            <!-- View Modal -->
                            <div class="modal fade" id="viewModal<?php echo $request['id']; ?>" tabindex="-1" aria-labelledby="viewModalLabel<?php echo $request['id']; ?>" aria-hidden="true">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="viewModalLabel<?php echo $request['id']; ?>">Leave Request Details</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <p><strong>Employee:</strong> <?php echo $request['user_name']; ?></p>
                                                    <p><strong>Email:</strong> <?php echo $request['user_email']; ?></p>
                                                    <p><strong>Leave Type:</strong> <?php echo ucfirst($request['leave_type']); ?></p>
                                                    <p><strong>From:</strong> <?php echo formatDateForDisplay($request['start_date']); ?></p>
                                                    <p><strong>To:</strong> <?php echo formatDateForDisplay($request['end_date']); ?></p>
                                                </div>
                                                <div class="col-md-6">
                                                    <p><strong>Total Days:</strong> <?php echo $days; ?></p>
                                                    <p><strong>Applied On:</strong> <?php echo formatDateForDisplay($request['applied_at']); ?></p>
                                                    <p>
                                                        <strong>Status:</strong>
                                                        <span class="badge bg-<?php
                                                        echo $request['status'] === 'approved' ? 'success' :
                                                            ($request['status'] === 'rejected' ? 'danger' : 'warning');
                                                        ?>">
                                                                    <?php echo ucfirst($request['status']); ?>
                                                                </span>
                                                    </p>
                                                    <?php if ($request['reviewed_at']): ?>
                                                        <p><strong>Reviewed On:</strong> <?php echo formatDateForDisplay($request['reviewed_at']); ?></p>
                                                        <p><strong>Reviewed By:</strong> <?php echo $request['reviewed_by']; ?></p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <hr>
                                            <h6>Reason for Leave:</h6>
                                            <div class="p-3 bg-light rounded">
                                                <?php echo nl2br($request['reason']); ?>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Approve Modal -->
                            <?php if ($request['status'] === 'pending'): ?>
                                <div class="modal fade" id="approveModal<?php echo $request['id']; ?>" tabindex="-1" aria-labelledby="approveModalLabel<?php echo $request['id']; ?>" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header bg-success text-white">
                                                <h5 class="modal-title" id="approveModalLabel<?php echo $request['id']; ?>">Approve Leave Request</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <form method="POST" action="all_requests.php">
                                                <div class="modal-body">
                                                    <p>Are you sure you want to approve the leave request from <strong><?php echo $request['user_name']; ?></strong>?</p>
                                                    <div class="mb-3">
                                                        <label for="comment<?php echo $request['id']; ?>" class="form-label">Comment (Optional)</label>
                                                        <textarea class="form-control" id="comment<?php echo $request['id']; ?>" name="comment" rows="3" placeholder="Add a comment for this approval"></textarea>
                                                    </div>
                                                    <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                                    <input type="hidden" name="action" value="approve">
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-success">Approve</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Reject Modal -->
                            <?php if ($request['status'] === 'pending'): ?>
                                <div class="modal fade" id="rejectModal<?php echo $request['id']; ?>" tabindex="-1" aria-labelledby="rejectModalLabel<?php echo $request['id']; ?>" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header bg-danger text-white">
                                                <h5 class="modal-title" id="rejectModalLabel<?php echo $request['id']; ?>">Reject Leave Request</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <form method="POST" action="all_requests.php">
                                                <div class="modal-body">
                                                    <p>Are you sure you want to reject the leave request from <strong><?php echo $request['user_name']; ?></strong>?</p>
                                                    <div class="mb-3">
                                                        <label for="comment<?php echo $request['id']; ?>" class="form-label">Reason for Rejection</label>
                                                        <textarea class="form-control" id="comment<?php echo $request['id']; ?>" name="comment" rows="3" placeholder="Provide a reason for rejection"></textarea>
                                                    </div>
                                                    <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                                    <input type="hidden" name="action" value="reject">
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-danger">Reject</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i> No leave requests found.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/scripts.js"></script>
</body>
</html>