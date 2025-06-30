<?php
global $pdo;
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirectWithMessage('../login.php', 'You must log in as admin to access this page', 'warning');
}

try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $totalUsers = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM leave_requests");
    $totalRequests = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM leave_requests WHERE status = 'pending'");
    $pendingRequests = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM leave_requests WHERE status = 'approved'");
    $approvedRequests = $stmt->fetchColumn();

    $stmt = $pdo->query("
        SELECT lr.*, u.name as user_name
        FROM leave_requests lr
        JOIN users u ON lr.user_id = u.id
        ORDER BY lr.applied_at DESC
        LIMIT 5
    ");
    $recentRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->query("
        SELECT *
        FROM users
        ORDER BY created_at DESC
        LIMIT 5
    ");
    $recentUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Leave Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include '../includes/admin-navbar.php'; ?>

<div class="content-wrapper">
    <div class="container-fluid py-4">
        <h2 class="mb-4">Admin Dashboard</h2>

        <?php displayMessage(); ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Dashboard Cards -->
        <div class="row">
            <div class="col-md-3">
                <div class="card bg-primary text-white mb-4 hover-effect">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title">Total Users</h6>
                                <h2 class="mb-0"><?php echo $totalUsers; ?></h2>
                            </div>
                            <i class="fas fa-users fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white mb-4 hover-effect">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title">Total Requests</h6>
                                <h2 class="mb-0"><?php echo $totalRequests; ?></h2>
                            </div>
                            <i class="fas fa-list-alt fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white mb-4 hover-effect">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title">Pending Requests</h6>
                                <h2 class="mb-0"><?php echo $pendingRequests; ?></h2>
                            </div>
                            <i class="fas fa-clock fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white mb-4 hover-effect">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title">Approved Requests</h6>
                                <h2 class="mb-0"><?php echo $approvedRequests; ?></h2>
                            </div>
                            <i class="fas fa-check-circle fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <!-- Recent Leave Requests -->
            <div class="col-md-7">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Recent Leave Requests</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($recentRequests) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                    <tr>
                                        <th>Employee</th>
                                        <th>Type</th>
                                        <th>From</th>
                                        <th>To</th>
                                        <th>Status</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($recentRequests as $request): ?>
                                        <tr>
                                            <td><?php echo $request['user_name']; ?></td>
                                            <td><?php echo ucfirst($request['leave_type']); ?></td>
                                            <td><?php echo formatDateForDisplay($request['start_date'], 'Y-m-d'); ?></td>
                                            <td><?php echo formatDateForDisplay($request['end_date'], 'Y-m-d'); ?></td>
                                            <td>
                                                        <span class="badge bg-<?php
                                                        echo $request['status'] === 'approved' ? 'success' :
                                                            ($request['status'] === 'rejected' ? 'danger' : 'warning');
                                                        ?>">
                                                            <?php echo ucfirst($request['status']); ?>
                                                        </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="text-center mt-3">
                                <a href="all_requests.php" class="btn btn-success">
                                    <i class="fas fa-list-alt me-1"></i> View All Requests
                                </a>
                            </div>
                        <?php else: ?>
                            <p class="text-center">No leave requests found</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Recent Users -->
            <div class="col-md-5">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Recent Users</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($recentUsers) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Date</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($recentUsers as $user): ?>
                                        <tr>
                                            <td><?php echo $user['name']; ?></td>
                                            <td><?php echo $user['email']; ?></td>
                                            <td>
                                                        <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'danger' : 'info'; ?>">
                                                            <?php echo ucfirst($user['role']); ?>
                                                        </span>
                                            </td>
                                            <td><?php echo formatDateForDisplay($user['created_at'], 'Y-m-d'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="text-center mt-3">
                                <a href="manage_users.php" class="btn btn-primary">
                                    <i class="fas fa-users me-1"></i> Manage Users
                                </a>
                            </div>
                        <?php else: ?>
                            <p class="text-center">No users found</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Links Card -->
                <div class="card mt-3">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">Quick Links</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-6 mb-2">
                                <a href="all_requests.php" class="btn btn-outline-success w-100">
                                    <i class="fas fa-list-alt me-1"></i> All Requests
                                </a>
                            </div>
                            <div class="col-6 mb-2">
                                <a href="manage_users.php" class="btn btn-outline-primary w-100">
                                    <i class="fas fa-users me-1"></i> Manage Users
                                </a>
                            </div>
                            <div class="col-6 mb-2">
                                <a href="reports.php" class="btn btn-outline-warning w-100">
                                    <i class="fas fa-chart-bar me-1"></i> Reports
                                </a>
                            </div>
                            <div class="col-6 mb-2">
                                <a href="profile.php" class="btn btn-outline-info w-100">
                                    <i class="fas fa-user-circle me-1"></i> Profile
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add a spacer at the bottom to ensure content doesn't get hidden by footer -->
        <div class="back-btn-container"></div>
    </div>
</div> <!-- End of content-wrapper -->

<?php include '../includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/scripts.js"></script>
</body>
</html>