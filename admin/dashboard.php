<?php
global $pdo;
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirectWithMessage('../login.php', 'You must log in as admin to access this page', 'warning');
}

try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM leave_requests");
    $totalRequests = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM leave_requests WHERE status = ?");
    $stmt->execute(['pending']);
    $pendingRequests = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM leave_requests WHERE status = ?");
    $stmt->execute(['approved']);
    $approvedRequests = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM leave_requests WHERE status = ?");
    $stmt->execute(['rejected']);
    $rejectedRequests = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT lr.*, u.name as user_name 
                        FROM leave_requests lr 
                        JOIN users u ON lr.user_id = u.id 
                        ORDER BY lr.applied_at DESC LIMIT 5");
    $recentRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Dashboard stats error: " . $e->getMessage());
    $totalRequests = 0;
    $pendingRequests = 0;
    $approvedRequests = 0;
    $rejectedRequests = 0;
    $recentRequests = [];
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
    <style>
        .stat-card {
            border-radius: 10px;
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }

        .display-4 {
            font-size: 2.5rem;
        }

        .recent-requests-table th {
            background-color: #f5f5f5;
        }

        @media (max-width: 768px) {
            .display-4 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
<?php include '../includes/admin-navbar.php'; ?>

<div class="container-fluid py-4">
    <?php displayMessage(); ?>

    <h2 class="mb-4">Admin Dashboard</h2>

    <div class="row">
        <div class="col-md-3 mb-4">
            <div class="card bg-primary text-white stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h5 class="card-title">Total Requests</h5>
                            <p class="card-text display-4"><?php echo $totalRequests; ?></p>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-file-alt fa-3x opacity-25"></i>
                        </div>
                    </div>
                    <a href="all_requests.php" class="btn btn-outline-light btn-sm mt-3">
                        <i class="fas fa-list me-1"></i> View All
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-4">
            <div class="card bg-warning text-dark stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h5 class="card-title">Pending Requests</h5>
                            <p class="card-text display-4"><?php echo $pendingRequests; ?></p>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-hourglass-half fa-3x opacity-25"></i>
                        </div>
                    </div>
                    <a href="all_requests.php?status=pending" class="btn btn-outline-dark btn-sm mt-3">
                        <i class="fas fa-clock me-1"></i> View Pending
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-4">
            <div class="card bg-success text-white stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h5 class="card-title">Approved Requests</h5>
                            <p class="card-text display-4"><?php echo $approvedRequests; ?></p>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-check-circle fa-3x opacity-25"></i>
                        </div>
                    </div>
                    <a href="all_requests.php?status=approved" class="btn btn-outline-light btn-sm mt-3">
                        <i class="fas fa-thumbs-up me-1"></i> View Approved
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-4">
            <div class="card bg-danger text-white stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h5 class="card-title">Rejected Requests</h5>
                            <p class="card-text display-4"><?php echo $rejectedRequests; ?></p>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-times-circle fa-3x opacity-25"></i>
                        </div>
                    </div>
                    <a href="all_requests.php?status=rejected" class="btn btn-outline-light btn-sm mt-3">
                        <i class="fas fa-thumbs-down me-1"></i> View Rejected
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity Section -->
    <div class="row mt-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Leave Requests</h5>
                    <a href="all_requests.php" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover recent-requests-table">
                            <thead>
                            <tr>
                                <th>ID</th>
                                <th>Employee</th>
                                <th>Type</th>
                                <th>Duration</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if (count($recentRequests) > 0): ?>
                                <?php foreach ($recentRequests as $request): ?>
                                    <tr>
                                        <td>#<?php echo $request['id']; ?></td>
                                        <td><?php echo htmlspecialchars($request['user_name']); ?></td>
                                        <td>
                                            <?php
                                            $typeClass = '';
                                            switch ($request['leave_type']) {
                                                case 'sick': $typeClass = 'bg-danger'; break;
                                                case 'vacation': $typeClass = 'bg-primary'; break;
                                                case 'personal': $typeClass = 'bg-info'; break;
                                                case 'emergency': $typeClass = 'bg-warning text-dark'; break;
                                                default: $typeClass = 'bg-secondary';
                                            }
                                            ?>
                                            <span class="badge <?php echo $typeClass; ?>"><?php echo ucfirst($request['leave_type']); ?></span>
                                        </td>
                                        <td>
                                            <?php
                                            $start = date("M d", strtotime($request['start_date']));
                                            $end = date("M d", strtotime($request['end_date']));
                                            echo $start . ' - ' . $end;
                                            ?>
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
                                        </td>
                                        <td>
                                            <a href="view_request.php?id=<?php echo $request['id']; ?>" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center">No recent leave requests found</td>
                                </tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <a href="all_requests.php?status=pending" class="list-group-item list-group-item-action">
                            <i class="fas fa-hourglass-half me-2 text-warning"></i> Review Pending Requests
                        </a>
                        <a href="manage_users.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-user-plus me-2 text-primary"></i> Manage Users
                        </a>
                        <a href="reports.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-chart-bar me-2 text-success"></i> Generate Reports
                        </a>
                        <a href="profile.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-user-cog me-2 text-info"></i> Update Profile
                        </a>
                    </div>
                </div>
            </div>

            <!-- System Info Card -->
            <div class="card mt-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">System Information</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Total Users
                            <span class="badge bg-primary rounded-pill">
                                    <?php
                                    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
                                    echo $stmt->fetchColumn();
                                    ?>
                                </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Admins
                            <span class="badge bg-info rounded-pill">
                                    <?php
                                    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
                                    echo $stmt->fetchColumn();
                                    ?>
                                </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Regular Users
                            <span class="badge bg-secondary rounded-pill">
                                    <?php
                                    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'");
                                    echo $stmt->fetchColumn();
                                    ?>
                                </span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php include '../includes/footer-scripts.php'; ?>
</body>
</html>