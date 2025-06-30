<?php
global $pdo;
require_once '../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirectWithMessage('../login.php', 'You must log in to access this page', 'warning');
}

// Get user's leave requests
$userId = $_SESSION['user_id'];

try {
    // Get leave requests for the user
    $stmt = $pdo->prepare("
        SELECT * FROM leave_requests 
        WHERE user_id = ?
        ORDER BY applied_at DESC
    ");
    $stmt->execute([$userId]);
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
    <title>My Leave Requests - Leave Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php
// IMPORTANT: Include navbar ONLY ONCE
include '../includes/user-navbar.php';
?>

<div class="content-wrapper">
    <div class="container-fluid py-4">
        <h2 class="mb-4">My Leave Requests</h2>

        <?php displayMessage(); ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Filter Options -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Filter Requests</h5>
            </div>
            <div class="card-body">
                <form method="GET" action="my_requests.php" class="row g-3">
                    <div class="col-md-4">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="all" <?php echo isset($_GET['status']) && $_GET['status'] === 'all' ? 'selected' : ''; ?>>All</option>
                            <option value="pending" <?php echo isset($_GET['status']) && $_GET['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="approved" <?php echo isset($_GET['status']) && $_GET['status'] === 'approved' ? 'selected' : ''; ?>>Approved</option>
                            <option value="rejected" <?php echo isset($_GET['status']) && $_GET['status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="type" class="form-label">Leave Type</label>
                        <select class="form-select" id="type" name="type">
                            <option value="all" <?php echo isset($_GET['type']) && $_GET['type'] === 'all' ? 'selected' : ''; ?>>All Types</option>
                            <option value="sick" <?php echo isset($_GET['type']) && $_GET['type'] === 'sick' ? 'selected' : ''; ?>>Sick</option>
                            <option value="vacation" <?php echo isset($_GET['type']) && $_GET['type'] === 'vacation' ? 'selected' : ''; ?>>Vacation</option>
                            <option value="personal" <?php echo isset($_GET['type']) && $_GET['type'] === 'personal' ? 'selected' : ''; ?>>Personal</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">&nbsp;</label>
                        <div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter me-1"></i> Apply Filters
                            </button>
                            <a href="my_requests.php" class="btn btn-secondary ms-2">
                                <i class="fas fa-undo me-1"></i> Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Leave Requests Table -->
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">Leave Requests</h5>
            </div>
            <div class="card-body">
                <?php if (count($requests) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                            <tr>
                                <th>ID</th>
                                <th>Leave Type</th>
                                <th>From</th>
                                <th>To</th>
                                <th>Days</th>
                                <th>Status</th>
                                <th>Applied On</th>
                                <th>Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($requests as $request): ?>
                                <?php
                                $days = calculateLeaveDays($request['start_date'], $request['end_date']);
                                // Apply filters if set
                                if (isset($_GET['status']) && $_GET['status'] !== 'all' && $_GET['status'] !== $request['status']) {
                                    continue;
                                }
                                if (isset($_GET['type']) && $_GET['type'] !== 'all' && $_GET['type'] !== $request['leave_type']) {
                                    continue;
                                }
                                ?>
                                <tr>
                                    <td><?php echo $request['id']; ?></td>
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
                                    <td><?php echo formatDateForDisplay($request['applied_at'], 'M d, Y'); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-info view-btn" data-bs-toggle="modal" data-bs-target="#viewModal" data-id="<?php echo $request['id']; ?>" data-reason="<?php echo htmlspecialchars($request['reason']); ?>" data-comment="<?php echo htmlspecialchars($request['admin_comment'] ?? ''); ?>">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <?php if ($request['status'] === 'pending'): ?>
                                            <a href="cancel_request.php?id=<?php echo $request['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to cancel this request?')">
                                                <i class="fas fa-times"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i> No leave requests found.
                        <a href="apply_leave.php" class="alert-link">Apply for leave</a> to get started.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="d-flex justify-content-between mt-4">
            <a href="apply_leave.php" class="btn btn-success">
                <i class="fas fa-plus-circle me-1"></i> Apply for New Leave
            </a>
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
            </a>
        </div>

        <!-- Add spacer at bottom to prevent footer overlap -->
        <div class="back-btn-container"></div>
    </div>
</div>

<!-- View Leave Request Modal -->
<div class="modal fade" id="viewModal" tabindex="-1" aria-labelledby="viewModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="viewModalLabel">Leave Request Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6>Reason for Leave:</h6>
                <p id="leaveReason" class="p-3 bg-light rounded"></p>

                <div id="adminCommentSection">
                    <h6>Admin Comment:</h6>
                    <p id="adminComment" class="p-3 bg-light rounded"></p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php
// IMPORTANT: Include footer ONLY ONCE and after content-wrapper
include '../includes/footer.php';
?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Setup view modal
        var viewBtns = document.querySelectorAll('.view-btn');
        viewBtns.forEach(function(btn) {
            btn.addEventListener('click', function() {
                var reason = this.getAttribute('data-reason');
                var comment = this.getAttribute('data-comment');

                document.getElementById('leaveReason').textContent = reason;

                var commentSection = document.getElementById('adminCommentSection');
                var commentText = document.getElementById('adminComment');

                if (comment && comment.trim() !== '') {
                    commentText.textContent = comment;
                    commentSection.style.display = 'block';
                } else {
                    commentSection.style.display = 'none';
                }
            });
        });
    });
</script>
</body>
</html>