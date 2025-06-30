<?php
global $pdo;
require_once '../includes/functions.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirectWithMessage('../login.php', 'Access denied. Admin privileges required.', 'danger');
}

$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        $requestId = $_POST['request_id'] ?? null;
        $comment = $_POST['comment'] ?? '';

        if ($action === 'approve' && $requestId) {
            try {
                $stmt = $pdo->prepare("UPDATE leave_requests SET status = 'approved', admin_comment = ?, reviewed_by = ?, reviewed_at = ? WHERE id = ?");
                $result = $stmt->execute([$comment, $_SESSION['user_id'], getCurrentDateTime(), $requestId]);

                if ($result) {
                    logActivity('Leave Request Approval', "Approved leave request ID: $requestId with comment: $comment");
                    redirectWithMessage('all_requests.php', 'Leave request approved successfully!', 'success');
                } else {
                    $error = 'Failed to approve leave request.';
                }
            } catch (PDOException $e) {
                $error = 'Database error: ' . $e->getMessage();
            }
        } elseif ($action === 'reject' && $requestId) {
            try {
                $stmt = $pdo->prepare("UPDATE leave_requests SET status = 'rejected', admin_comment = ?, reviewed_by = ?, reviewed_at = ? WHERE id = ?");
                $result = $stmt->execute([$comment, $_SESSION['user_id'], getCurrentDateTime(), $requestId]);

                if ($result) {
                    logActivity('Leave Request Rejection', "Rejected leave request ID: $requestId with comment: $comment");
                    redirectWithMessage('all_requests.php', 'Leave request rejected successfully!', 'success');
                } else {
                    $error = 'Failed to reject leave request.';
                }
            } catch (PDOException $e) {
                $error = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

// Get filter parameters
$status = $_GET['status'] ?? 'all';
$type = $_GET['type'] ?? 'all';

// Build WHERE clause for filtering
$whereClause = "WHERE 1=1";
$params = [];

if ($status !== 'all') {
    $whereClause .= " AND lr.status = ?";
    $params[] = $status;
}

if ($type !== 'all') {
    $whereClause .= " AND lr.leave_type = ?";
    $params[] = $type;
}

// Fetch leave requests with user information
try {
    $stmt = $pdo->prepare("
        SELECT lr.*, u.name as user_name, u.email as user_email 
        FROM leave_requests lr 
        JOIN users u ON lr.user_id = u.id 
        $whereClause 
        ORDER BY lr.applied_at DESC
    ");
    $stmt->execute($params);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Failed to fetch leave requests: ' . $e->getMessage();
    $requests = [];
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
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Days</th>
                            <th>Status</th>
                            <th>Applied</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($requests as $request): ?>
                            <?php
                            // Calculate leave days
                            $days = calculateLeaveDays($request['start_date'], $request['end_date']);
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($request['user_name']); ?></td>
                                <td><?php echo htmlspecialchars($request['user_email']); ?></td>
                                <td><?php echo ucfirst($request['leave_type']); ?></td>
                                <td><?php echo formatDateForDisplay($request['start_date']); ?></td>
                                <td><?php echo formatDateForDisplay($request['end_date']); ?></td>
                                <td><?php echo $days; ?></td>
                                <td>
                                            <span class="badge bg-<?php
                                            echo $request['status'] === 'approved' ? 'success' :
                                                ($request['status'] === 'rejected' ? 'danger' : 'warning');
                                            ?>">
                                                <?php echo ucfirst($request['status']); ?>
                                            </span>
                                </td>
                                <td><?php echo formatDateForDisplay($request['applied_at']); ?></td>
                                <td>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-info view-request-btn"
                                                data-bs-toggle="modal"
                                                data-bs-target="#viewModal"
                                                data-request-id="<?php echo $request['id']; ?>"
                                                data-employee="<?php echo htmlspecialchars($request['user_name']); ?>"
                                                data-email="<?php echo htmlspecialchars($request['user_email']); ?>"
                                                data-leave-type="<?php echo ucfirst($request['leave_type']); ?>"
                                                data-start-date="<?php echo formatDateForDisplay($request['start_date']); ?>"
                                                data-end-date="<?php echo formatDateForDisplay($request['end_date']); ?>"
                                                data-days="<?php echo $days; ?>"
                                                data-status="<?php echo ucfirst($request['status']); ?>"
                                                data-applied-on="<?php echo formatDateForDisplay($request['applied_at']); ?>"
                                                data-reviewed-on="<?php echo $request['reviewed_at'] ? formatDateForDisplay($request['reviewed_at']) : 'Not reviewed'; ?>"
                                                data-reviewed-by="<?php echo $request['reviewed_by'] ? getUserNameById($request['reviewed_by']) : 'Not reviewed'; ?>"
                                                data-reason="<?php echo htmlspecialchars($request['reason']); ?>"
                                                data-admin-comment="<?php echo htmlspecialchars($request['admin_comment'] ?? ''); ?>">
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
                                                    <p>Are you sure you want to approve the leave request from <strong><?php echo htmlspecialchars($request['user_name']); ?></strong>?</p>
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
                                                    <p>Are you sure you want to reject the leave request from <strong><?php echo htmlspecialchars($request['user_name']); ?></strong>?</p>
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

<!-- Single View Modal (Reusable) -->
<div class="modal fade" id="viewModal" tabindex="-1" aria-labelledby="viewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="viewModalLabel">Leave Request Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Employee:</strong> <span id="modal-employee"></span></p>
                        <p><strong>Email:</strong> <span id="modal-email"></span></p>
                        <p><strong>Leave Type:</strong> <span id="modal-leave-type"></span></p>
                        <p><strong>From:</strong> <span id="modal-start-date"></span></p>
                        <p><strong>To:</strong> <span id="modal-end-date"></span></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Total Days:</strong> <span id="modal-days"></span></p>
                        <p><strong>Applied On:</strong> <span id="modal-applied-on"></span></p>
                        <p><strong>Status:</strong> <span id="modal-status-badge"></span></p>
                        <p><strong>Reviewed On:</strong> <span id="modal-reviewed-on"></span></p>
                        <p><strong>Reviewed By:</strong> <span id="modal-reviewed-by"></span></p>
                    </div>
                </div>

                <hr>

                <div class="mb-3">
                    <h6>Reason for Leave:</h6>
                    <div id="modal-reason" class="p-3 bg-light rounded"></div>
                </div>

                <div id="modal-admin-comment-section" style="display: none;">
                    <h6>Admin Comment:</h6>
                    <div id="modal-admin-comment" class="p-3 bg-light rounded"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/scripts.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle view modal
        const viewButtons = document.querySelectorAll('.view-request-btn');
        const viewModal = document.getElementById('viewModal');

        viewButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Get data from button attributes
                const employee = this.getAttribute('data-employee');
                const email = this.getAttribute('data-email');
                const leaveType = this.getAttribute('data-leave-type');
                const startDate = this.getAttribute('data-start-date');
                const endDate = this.getAttribute('data-end-date');
                const days = this.getAttribute('data-days');
                const status = this.getAttribute('data-status');
                const appliedOn = this.getAttribute('data-applied-on');
                const reviewedOn = this.getAttribute('data-reviewed-on');
                const reviewedBy = this.getAttribute('data-reviewed-by');
                const reason = this.getAttribute('data-reason');
                const adminComment = this.getAttribute('data-admin-comment');

                // Populate modal fields
                document.getElementById('modal-employee').textContent = employee;
                document.getElementById('modal-email').textContent = email;
                document.getElementById('modal-leave-type').textContent = leaveType;
                document.getElementById('modal-start-date').textContent = startDate;
                document.getElementById('modal-end-date').textContent = endDate;
                document.getElementById('modal-days').textContent = days;
                document.getElementById('modal-applied-on').textContent = appliedOn;
                document.getElementById('modal-reviewed-on').textContent = reviewedOn;
                document.getElementById('modal-reviewed-by').textContent = reviewedBy;
                document.getElementById('modal-reason').textContent = reason;

                // Set status badge
                const statusBadge = document.getElementById('modal-status-badge');
                let badgeClass = 'badge ';
                if (status.toLowerCase() === 'approved') {
                    badgeClass += 'bg-success';
                } else if (status.toLowerCase() === 'rejected') {
                    badgeClass += 'bg-danger';
                } else {
                    badgeClass += 'bg-warning';
                }
                statusBadge.innerHTML = `<span class="${badgeClass}">${status}</span>`;

                // Show admin comment if exists
                const adminCommentSection = document.getElementById('modal-admin-comment-section');
                const adminCommentDiv = document.getElementById('modal-admin-comment');

                if (adminComment && adminComment.trim() !== '') {
                    adminCommentDiv.textContent = adminComment;
                    adminCommentSection.style.display = 'block';
                } else {
                    adminCommentSection.style.display = 'none';
                }
            });
        });

        // Enhanced modal cleanup
        const allModals = document.querySelectorAll('.modal');
        allModals.forEach(modal => {
            modal.addEventListener('hidden.bs.modal', function() {
                // Force cleanup of backdrop
                setTimeout(() => {
                    const backdrops = document.querySelectorAll('.modal-backdrop');
                    backdrops.forEach(backdrop => {
                        backdrop.remove();
                    });

                    document.body.classList.remove('modal-open');
                    document.body.style.overflow = '';
                    document.body.style.paddingRight = '';
                }, 100);
            });
        });
    });
</script>

</body>
</html>