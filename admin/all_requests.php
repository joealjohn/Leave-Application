<?php
global $pdo;
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirectWithMessage('../login.php', 'You must log in as admin to access this page', 'warning');
}

try {
    $checkStmt = $pdo->query("SHOW COLUMNS FROM leave_requests LIKE 'admin_comment'");
    if ($checkStmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE leave_requests ADD COLUMN admin_comment TEXT NULL");
        $pdo = null;
        $pdo = getPDO();
        logActivity('System', 'Added missing admin_comment column to leave_requests table');
    }
} catch (PDOException $e) {
    error_log("Failed to check or add admin_comment column: " . $e->getMessage());
}

$filterStatus = $_GET['status'] ?? 'all';
$filterType = $_GET['type'] ?? 'all';

try {
    $query = "SELECT lr.id, lr.user_id, lr.leave_type, lr.start_date, lr.end_date, 
              lr.reason, lr.status, lr.applied_at";

    try {
        $checkStmt = $pdo->query("SHOW COLUMNS FROM leave_requests LIKE 'admin_comment'");
        if ($checkStmt->rowCount() > 0) {
            $query .= ", lr.admin_comment";
        }
    } catch (PDOException $e) {
    }

    $query .= ", u.name as user_name, u.email as user_email
              FROM leave_requests lr
              JOIN users u ON lr.user_id = u.id";

    $whereConditions = [];
    $params = [];

    if ($filterStatus !== 'all') {
        $whereConditions[] = "lr.status = ?";
        $params[] = $filterStatus;
    }

    if ($filterType !== 'all') {
        $whereConditions[] = "lr.leave_type = ?";
        $params[] = $filterType;
    }

    if (!empty($whereConditions)) {
        $query .= " WHERE " . implode(' AND ', $whereConditions);
    }

    $query .= " ORDER BY lr.applied_at DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $allRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM leave_requests");
    $totalRequests = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM leave_requests WHERE status = 'pending'");
    $pendingRequests = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM leave_requests WHERE status = 'approved'");
    $approvedRequests = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM leave_requests WHERE status = 'rejected'");
    $rejectedRequests = $stmt->fetchColumn();
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

<div class="content-wrapper">
    <div class="container-fluid py-4">
        <h2 class="mb-4">All Leave Requests</h2>

        <?php displayMessage(); ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white mb-3 hover-effect">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title">Total</h6>
                                <h2 class="mb-0"><?php echo $totalRequests ?? 0; ?></h2>
                            </div>
                            <i class="fas fa-list fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-dark mb-3 hover-effect">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title">Pending</h6>
                                <h2 class="mb-0"><?php echo $pendingRequests ?? 0; ?></h2>
                            </div>
                            <i class="fas fa-clock fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white mb-3 hover-effect">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title">Approved</h6>
                                <h2 class="mb-0"><?php echo $approvedRequests ?? 0; ?></h2>
                            </div>
                            <i class="fas fa-check-circle fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-danger text-white mb-3 hover-effect">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title">Rejected</h6>
                                <h2 class="mb-0"><?php echo $rejectedRequests ?? 0; ?></h2>
                            </div>
                            <i class="fas fa-times-circle fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="all_requests.php" class="row g-3">
                    <div class="col-md-4">
                        <label for="status" class="form-label">Filter by Status</label>
                        <select name="status" id="status" class="form-select">
                            <option value="all" <?php echo $filterStatus === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                            <option value="pending" <?php echo $filterStatus === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="approved" <?php echo $filterStatus === 'approved' ? 'selected' : ''; ?>>Approved</option>
                            <option value="rejected" <?php echo $filterStatus === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="type" class="form-label">Filter by Leave Type</label>
                        <select name="type" id="type" class="form-select">
                            <option value="all" <?php echo $filterType === 'all' ? 'selected' : ''; ?>>All Types</option>
                            <option value="sick" <?php echo $filterType === 'sick' ? 'selected' : ''; ?>>Sick Leave</option>
                            <option value="vacation" <?php echo $filterType === 'vacation' ? 'selected' : ''; ?>>Vacation Leave</option>
                            <option value="personal" <?php echo $filterType === 'personal' ? 'selected' : ''; ?>>Personal Leave</option>
                            <option value="emergency" <?php echo $filterType === 'emergency' ? 'selected' : ''; ?>>Emergency Leave</option>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fas fa-filter me-2"></i> Apply Filters
                        </button>
                        <a href="all_requests.php" class="btn btn-secondary">
                            <i class="fas fa-redo me-2"></i> Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Leave Requests Table -->
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Leave Requests</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($allRequests)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                            <tr>
                                <th>ID</th>
                                <th>Employee</th>
                                <th>Leave Type</th>
                                <th>Dates</th>
                                <th>Duration</th>
                                <th>Applied On</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($allRequests as $request): ?>
                                <tr>
                                    <td><?php echo $request['id']; ?></td>
                                    <td>
                                        <?php echo $request['user_name']; ?><br>
                                        <small class="text-muted"><?php echo $request['user_email']; ?></small>
                                    </td>
                                    <td>
                                        <?php
                                        $leaveTypeClass = '';
                                        switch ($request['leave_type']) {
                                            case 'sick': $leaveTypeClass = 'bg-danger'; break;
                                            case 'vacation': $leaveTypeClass = 'bg-primary'; break;
                                            case 'personal': $leaveTypeClass = 'bg-info'; break;
                                            case 'emergency': $leaveTypeClass = 'bg-warning text-dark'; break;
                                            default: $leaveTypeClass = 'bg-secondary';
                                        }
                                        ?>
                                        <span class="badge <?php echo $leaveTypeClass; ?>"><?php echo ucfirst($request['leave_type']); ?></span>
                                    </td>
                                    <td>
                                        <?php echo formatDateForDisplay($request['start_date'], 'Y-m-d'); ?> to<br>
                                        <?php echo formatDateForDisplay($request['end_date'], 'Y-m-d'); ?>
                                    </td>
                                    <td>
                                        <?php echo calculateLeaveDays($request['start_date'], $request['end_date']); ?> days
                                    </td>
                                    <td>
                                        <?php echo formatDateForDisplay($request['applied_at'], 'Y-m-d H:i'); ?>
                                    </td>
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
                                        <?php if (isset($request['admin_comment']) && !empty($request['admin_comment'])): ?>
                                            <i class="fas fa-comment-dots ms-1" data-bs-toggle="tooltip" title="<?php echo htmlspecialchars($request['admin_comment']); ?>"></i>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="view_request.php?id=<?php echo $request['id']; ?>" class="btn btn-primary btn-sm">
                                            <i class="fas fa-eye"></i>
                                        </a>

                                        <?php if ($request['status'] === 'pending'): ?>
                                            <div class="dropdown d-inline">
                                                <button class="btn btn-secondary btn-sm dropdown-toggle" type="button" id="dropdownMenuButton<?php echo $request['id']; ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-cog"></i>
                                                </button>
                                                <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton<?php echo $request['id']; ?>">
                                                    <li>
                                                        <a class="dropdown-item approve-btn" href="#" data-id="<?php echo $request['id']; ?>" data-bs-toggle="modal" data-bs-target="#approveModal">
                                                            <i class="fas fa-check-circle text-success me-2"></i> Approve
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item reject-btn" href="#" data-id="<?php echo $request['id']; ?>" data-bs-toggle="modal" data-bs-target="#rejectModal">
                                                            <i class="fas fa-times-circle text-danger me-2"></i> Reject
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-info-circle me-2"></i> No leave requests found with the selected filters.
                    </div>
                <?php endif; ?>
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
                <input type="hidden" name="request_id" id="approve_request_id">

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
                <input type="hidden" name="request_id" id="reject_request_id">

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

<?php include '../includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        })

        document.querySelectorAll('.approve-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                document.getElementById('approve_request_id').value = this.getAttribute('data-id');
            });
        });

        document.querySelectorAll('.reject-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                document.getElementById('reject_request_id').value = this.getAttribute('data-id');
            });
        });
    });
</script>
</body>
</html>