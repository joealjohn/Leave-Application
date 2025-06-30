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
    // Get pending leave requests
    $stmt = $pdo->prepare("
        SELECT * FROM leave_requests 
        WHERE user_id = ? AND status = 'pending'
        ORDER BY applied_at DESC
    ");
    $stmt->execute([$userId]);
    $pendingRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get recent leave requests
    $stmt = $pdo->prepare("
        SELECT * FROM leave_requests 
        WHERE user_id = ?
        ORDER BY applied_at DESC
        LIMIT 5
    ");
    $stmt->execute([$userId]);
    $recentRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get leave statistics
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
        FROM leave_requests 
        WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - Leave Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include '../includes/user-navbar.php'; ?>

<div class="content-wrapper">
    <div class="container-fluid py-4">
        <h2 class="mb-4">User Dashboard</h2>

        <?php displayMessage(); ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Welcome Banner -->
        <div class="welcome-banner">
            <h4>Welcome, <?php echo $_SESSION['user_name']; ?>!</h4>
            <p>From this dashboard, you can apply for leave, view your requests, and manage your profile.</p>
        </div>

        <!-- Dashboard Cards -->
        <div class="row">
            <div class="col-md-3">
                <div class="card bg-primary text-white mb-4 hover-effect">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title">Total Requests</h6>
                                <h2 class="mb-0"><?php echo $stats['total'] ?? 0; ?></h2>
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
                                <h6 class="card-title">Pending</h6>
                                <h2 class="mb-0"><?php echo $stats['pending'] ?? 0; ?></h2>
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
                                <h6 class="card-title">Approved</h6>
                                <h2 class="mb-0"><?php echo $stats['approved'] ?? 0; ?></h2>
                            </div>
                            <i class="fas fa-check-circle fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-danger text-white mb-4 hover-effect">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title">Rejected</h6>
                                <h2 class="mb-0"><?php echo $stats['rejected'] ?? 0; ?></h2>
                            </div>
                            <i class="fas fa-times-circle fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <!-- Recent Leave Requests -->
            <div class="col-md-8">
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
                                        <th>Type</th>
                                        <th>From</th>
                                        <th>To</th>
                                        <th>Days</th>
                                        <th>Status</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($recentRequests as $request): ?>
                                        <?php $days = calculateLeaveDays($request['start_date'], $request['end_date']); ?>
                                        <tr>
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
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="text-center mt-3">
                                <a href="my_requests.php" class="btn btn-success">
                                    <i class="fas fa-list-alt me-1"></i> View All Requests
                                </a>
                            </div>
                        <?php else: ?>
                            <p class="text-center">No leave requests found. <a href="apply_leave.php">Apply for leave</a> now.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Quick Links</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-12 mb-3">
                                <a href="apply_leave.php" class="btn btn-success w-100">
                                    <i class="fas fa-plus-circle me-1"></i> Apply for Leave
                                </a>
                            </div>
                            <div class="col-12 mb-3">
                                <a href="my_requests.php" class="btn btn-info w-100 text-white">
                                    <i class="fas fa-list-alt me-1"></i> View My Requests
                                </a>
                            </div>
                            <div class="col-12 mb-3">
                                <a href="profile.php" class="btn btn-warning w-100">
                                    <i class="fas fa-user-circle me-1"></i> Update Profile
                                </a>
                            </div>
                            <div class="col-12">
                                <a href="../logout.php" class="btn btn-danger w-100">
                                    <i class="fas fa-sign-out-alt me-1"></i> Logout
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div> <!-- End of content-wrapper -->

<?php include '../includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/scripts.js"></script>
</body>
</html>