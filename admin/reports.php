<?php
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirectWithMessage('../login.php', 'You must be an admin to access this page', 'warning');
}

$fromDate = '';
$toDate = '';
$leaveType = '';
$status = '';
$reports = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fromDate = sanitizeInput($_POST['from_date'] ?? '');
    $toDate = sanitizeInput($_POST['to_date'] ?? '');
    $leaveType = sanitizeInput($_POST['leave_type'] ?? '');
    $status = sanitizeInput($_POST['status'] ?? '');

    $sql = "SELECT lr.*, u.name as user_name, u.email as user_email 
            FROM leave_requests lr
            JOIN users u ON lr.user_id = u.id
            WHERE 1=1";
    $params = [];

    if (!empty($fromDate) && isValidDate($fromDate)) {
        $sql .= " AND lr.start_date >= ?";
        $params[] = $fromDate;
    }

    if (!empty($toDate) && isValidDate($toDate)) {
        $sql .= " AND lr.end_date <= ?";
        $params[] = $toDate;
    }

    if (!empty($leaveType)) {
        $sql .= " AND lr.leave_type = ?";
        $params[] = $leaveType;
    }

    if (!empty($status)) {
        $sql .= " AND lr.status = ?";
        $params[] = $status;
    }

    $sql .= " ORDER BY lr.applied_at DESC";

    try {
        global $pdo;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $reports = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error fetching reports: " . $e->getMessage());
    }
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
    <h2 class="mb-4"><i class="fas fa-chart-bar me-2"></i>Leave Reports</h2>

    <?php displayMessage(); ?>

    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Filter Reports</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="" class="row g-3">
                <div class="col-md-3">
                    <label for="fromDate" class="form-label">From Date</label>
                    <input type="date" class="form-control" id="fromDate" name="from_date" value="<?php echo $fromDate; ?>">
                </div>

                <div class="col-md-3">
                    <label for="toDate" class="form-label">To Date</label>
                    <input type="date" class="form-control" id="toDate" name="to_date" value="<?php echo $toDate; ?>">
                </div>

                <div class="col-md-2">
                    <label for="leaveType" class="form-label">Leave Type</label>
                    <select class="form-select" id="leaveType" name="leave_type">
                        <option value="">All Types</option>
                        <option value="sick" <?php echo $leaveType === 'sick' ? 'selected' : ''; ?>>Sick Leave</option>
                        <option value="vacation" <?php echo $leaveType === 'vacation' ? 'selected' : ''; ?>>Vacation Leave</option>
                        <option value="emergency" <?php echo $leaveType === 'emergency' ? 'selected' : ''; ?>>Emergency Leave</option>
                        <option value="personal" <?php echo $leaveType === 'personal' ? 'selected' : ''; ?>>Personal Leave</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">All Status</option>
                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="approved" <?php echo $status === 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="rejected" <?php echo $status === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>

                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter me-1"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Leave Request Reports</h5>

            <?php if (!empty($reports)): ?>
                <div>
                    <button type="button" class="btn btn-light btn-sm" onclick="exportToPDF()">
                        <i class="fas fa-file-pdf me-1"></i> Export to PDF
                    </button>
                    <button type="button" class="btn btn-light btn-sm" onclick="exportToExcel()">
                        <i class="fas fa-file-excel me-1"></i> Export to Excel
                    </button>
                </div>
            <?php endif; ?>
        </div>

        <div class="card-body">
            <?php if (empty($reports)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i> No leave requests found matching your criteria. Please adjust your filters.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="reportsTable">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Employee</th>
                            <th>Leave Type</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Days</th>
                            <th>Status</th>
                            <th>Applied On</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($reports as $report): ?>
                            <tr>
                                <td><?php echo $report['id']; ?></td>
                                <td>
                                    <?php echo htmlspecialchars($report['user_name']); ?><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($report['user_email']); ?></small>
                                </td>
                                <td><?php echo ucfirst($report['leave_type']); ?></td>
                                <td><?php echo formatDateForDisplay($report['start_date']); ?></td>
                                <td><?php echo formatDateForDisplay($report['end_date']); ?></td>
                                <td><?php echo $report['days'] ?? calculateLeaveDays($report['start_date'], $report['end_date']); ?></td>
                                <td>
                                    <?php if ($report['status'] === 'pending'): ?>
                                        <span class="badge bg-warning">Pending</span>
                                    <?php elseif ($report['status'] === 'approved'): ?>
                                        <span class="badge bg-success">Approved</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Rejected</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo formatDateForDisplay($report['applied_at'], 'Y-m-d H:i'); ?></td>
                                <td>
                                    <a href="view_request.php?id=<?php echo $report['id']; ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<footer class="bg-success text-white text-center py-3 mt-4">
    <div class="container">
        <p class="mb-0">&copy; <?php echo date('Y'); ?> Leave Management System. All rights reserved.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php include '../includes/footer-scripts.php'; ?>

<script>
    function exportToPDF() {
        window.print();
    }

    function exportToExcel() {
        let table = document.getElementById("reportsTable");
        let html = table.outerHTML.replace(/ /g, '%20');

        let downloadLink = document.createElement("a");
        document.body.appendChild(downloadLink);

        downloadLink.href = 'data:application/vnd.ms-excel,' + html;
        downloadLink.download = 'leave_report_<?php echo date('Y-m-d'); ?>.xls';
        downloadLink.click();
        document.body.removeChild(downloadLink);
    }
</script>
</body>
</html>