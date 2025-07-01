<?php
global $pdo;
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirectWithMessage('../login.php', 'You must log in as admin to access this page', 'warning');
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$reportType = $_GET['type'] ?? 'all';
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-t');
$userId = $_GET['user_id'] ?? null;
$leaveType = $_GET['leave_type'] ?? null;
$department = $_GET['department'] ?? null;
$status = $_GET['status'] ?? null;

function isValidDate($date, $format = 'Y-m-d') {
    if (empty($date)) {
        return false;
    }

    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

if (!isValidDate($startDate) || !isValidDate($endDate)) {
    $startDate = date('Y-m-01');
    $endDate = date('Y-m-t');
}

try {
    $sql = "SELECT lr.*, u.name as user_name, u.email, u.department 
            FROM leave_requests lr 
            JOIN users u ON lr.user_id = u.id 
            WHERE lr.start_date >= ? AND lr.end_date <= ?";

    $params = [$startDate, $endDate];

    if ($userId) {
        $sql .= " AND lr.user_id = ?";
        $params[] = $userId;
    }

    if ($leaveType) {
        $sql .= " AND lr.leave_type = ?";
        $params[] = $leaveType;
    }

    if ($department) {
        $sql .= " AND u.department = ?";
        $params[] = $department;
    }

    if ($status) {
        $sql .= " AND lr.status = ?";
        $params[] = $status;
    }

    $sql .= " ORDER BY lr.start_date DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $reportData = $stmt->fetchAll();

    $stmt = $pdo->query("SELECT DISTINCT department FROM users WHERE department IS NOT NULL ORDER BY department");
    $departments = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $stmt = $pdo->query("SELECT DISTINCT leave_type FROM leave_requests ORDER BY leave_type");
    $leaveTypes = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $stmt = $pdo->query("SELECT id, name FROM users ORDER BY name");
    $users = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Database error in reports: " . $e->getMessage());
    $reportData = [];
    $departments = [];
    $leaveTypes = ['sick', 'vacation', 'personal', 'emergency'];
    $users = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Reports - Leave Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include '../includes/admin-navbar.php'; ?>

<div class="container-fluid py-4">
    <h2 class="mb-4">Leave Reports</h2>

    <?php displayMessage(); ?>

    <div class="card mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0">Filter Reports</h5>
        </div>
        <div class="card-body">
            <form method="GET" class="row">
                <div class="col-md-3 mb-3">
                    <label for="startDate" class="form-label">Start Date</label>
                    <input type="date" class="form-control" id="startDate" name="start_date" value="<?php echo $startDate; ?>">
                </div>

                <div class="col-md-3 mb-3">
                    <label for="endDate" class="form-label">End Date</label>
                    <input type="date" class="form-control" id="endDate" name="end_date" value="<?php echo $endDate; ?>">
                </div>

                <div class="col-md-3 mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">All Status</option>
                        <option value="pending" <?php echo $status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="approved" <?php echo $status == 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="rejected" <?php echo $status == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>

                <div class="col-md-3 mb-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter me-1"></i> Apply Filters
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-light">
            <h5 class="mb-0">Leave Request Report</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Employee</th>
                        <th>Leave Type</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (!empty($reportData)): ?>
                        <?php foreach ($reportData as $request): ?>
                            <tr>
                                <td>#<?php echo $request['id']; ?></td>
                                <td><?php echo htmlspecialchars($request['user_name']); ?></td>
                                <td>
                                            <span class="badge bg-info">
                                                <?php echo ucfirst($request['leave_type']); ?>
                                            </span>
                                </td>
                                <td><?php echo formatDateForDisplay($request['start_date']); ?></td>
                                <td><?php echo formatDateForDisplay($request['end_date']); ?></td>
                                <td>
                                    <?php
                                    $statusClass = '';
                                    switch ($request['status']) {
                                        case 'approved': $statusClass = 'bg-success'; break;
                                        case 'rejected': $statusClass = 'bg-danger'; break;
                                        default: $statusClass = 'bg-warning text-dark';
                                    }
                                    ?>
                                    <span class="badge <?php echo $statusClass; ?>">
                                                <?php echo ucfirst($request['status']); ?>
                                            </span>
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
                            <td colspan="7" class="text-center">No leave requests found matching your criteria.</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php include '../includes/footer-scripts.php'; ?>
</body>
</html>