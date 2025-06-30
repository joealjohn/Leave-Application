<?php
global $pdo;
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirectWithMessage('../login.php', 'You must log in as admin to access this page', 'warning');
}

$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-t');
$filterType = $_GE['type'] ?? 'all';
$filterStatus = $_GET['status'] ?? 'all';


if (!isValidDate($startDate)) {
    $startDate = date('Y-m-01');
}
if (!isValidDate($endDate)) {
    $endDate = date('Y-m-t');
}


$conditions = ['1=1'];
$params = [];

$conditions[] = "lr.start_date >= ? AND lr.end_date <= ?";
$params[] = $startDate;
$params[] = $endDate;

if ($filterType !== 'all') {
    $conditions[] = "lr.leave_type = ?";
    $params[] = $filterType;
}

if ($filterStatus !== 'all') {
    $conditions[] = "lr.status = ?";
    $params[] = $filterStatus;
}

$whereClause = implode(' AND ', $conditions);

try {
    $stmt = $pdo->prepare("
        SELECT lr.*, u.name as user_name, u.email as user_email
        FROM leave_requests lr
        JOIN users u ON lr.user_id = u.id
        WHERE {$whereClause}
        ORDER BY lr.applied_at DESC
    ");
    $stmt->execute($params);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $totalRequests = count($requests);
    $approvedRequests = 0;
    $pendingRequests = 0;
    $rejectedRequests = 0;
    $totalDays = 0;

    foreach ($requests as $request) {
        $days = calculateLeaveDays($request['start_date'], $request['end_date']);
        $totalDays += $days;

        switch ($request['status']) {
            case 'approved':
                $approvedRequests++;
                break;
            case 'pending':
                $pendingRequests++;
                break;
            case 'rejected':
                $rejectedRequests++;
                break;
        }
    }

} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
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
    <style>
        body {
            position: relative;
        }

        body::before {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('../assets/img/bg.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            filter: blur(8px);
            opacity: 0.2;
            z-index: -1;
        }

        .container-fluid {
            position: relative;
            z-index: 1;
        }

        .hover-effect:hover {
            transform: translateY(-5px);
            transition: transform 0.3s ease;
            box-shadow: 0 10px 15px rgba(0,0,0,0.1);
        }

        .filters {
            background-color: rgba(248, 249, 250, 0.9);
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #dee2e6;
        }

        .card {
            background-color: rgba(255, 255, 255, 0.95);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
<?php include '../includes/admin-navbar.php'; ?>

<div class="container-fluid py-4">
    <h2 class="mb-4">Leave Reports</h2>

    <?php displayMessage(); ?>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <!-- Report Filters -->
    <div class="filters">
        <form method="GET" action="reports.php" class="row g-3">
            <div class="col-md-3">
                <label for="start_date" class="form-label">Start Date</label>
                <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $startDate; ?>">
            </div>
            <div class="col-md-3">
                <label for="end_date" class="form-label">End Date</label>
                <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $endDate; ?>">
            </div>
            <div class="col-md-2">
                <label for="type" class="form-label">Leave Type</label>
                <select class="form-select" id="type" name="type">
                    <option value="all" <?php echo $filterType === 'all' ? 'selected' : ''; ?>>All Types</option>
                    <option value="sick" <?php echo $filterType === 'sick' ? 'selected' : ''; ?>>Sick</option>
                    <option value="vacation" <?php echo $filterType === 'vacation' ? 'selected' : ''; ?>>Vacation</option>
                    <option value="personal" <?php echo $filterType === 'personal' ? 'selected' : ''; ?>>Personal</option>
                    <option value="emergency" <?php echo $filterType === 'emergency' ? 'selected' : ''; ?>>Emergency</option>
                    <option value="other" <?php echo $filterType === 'other' ? 'selected' : ''; ?>>Other</option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="all" <?php echo $filterStatus === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                    <option value="pending" <?php echo $filterStatus === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="approved" <?php echo $filterStatus === 'approved' ? 'selected' : ''; ?>>Approved</option>
                    <option value="rejected" <?php echo $filterStatus === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-success w-100">
                    <i class="fas fa-filter me-1"></i> Apply Filters
                </button>
            </div>
        </form>
    </div>

    <!-- Report Statistics -->
    <div class="row">
        <div class="col-md-3">
            <div class="card bg-primary text-white mb-4 hover-effect">
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
                            <h6 class="card-title">Pending</h6>
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
                            <h6 class="card-title">Approved</h6>
                            <h2 class="mb-0"><?php echo $approvedRequests; ?></h2>
                        </div>
                        <i class="fas fa-check-circle fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white mb-4 hover-effect">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">Total Days</h6>
                            <h2 class="mb-0"><?php echo $totalDays; ?></h2>
                        </div>
                        <i class="fas fa-calendar-day fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Report Results -->
    <div class="card mt-4">
        <div class="card-header bg-success text-white">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Leave Report: <?php echo formatDateForDisplay($startDate); ?> to <?php echo formatDateForDisplay($endDate); ?></h5>
                <button class="btn btn-outline-light btn-sm" onclick="exportReport()">
                    <i class="fas fa-download me-1"></i> Export Report
                </button>
            </div>
        </div>
        <div class="card-body">
            <?php if ($totalRequests > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover" id="report-table">
                        <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Email</th>
                            <th>Type</th>
                            <th>From</th>
                            <th>To</th>
                            <th>Days</th>
                            <th>Status</th>
                            <th>Applied On</th>
                            <th>Reason</th>
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
                                    <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="tooltip" title="<?php echo htmlspecialchars($request['reason']); ?>">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i> No leave requests found for the selected criteria.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
    });

    function exportReport() {
        const table = document.getElementById("report-table");
        if (!table) return;

        let csvContent = "data:text/csv;charset=utf-8,";

        const headers = [];
        const headerCells = table.querySelectorAll("thead th");
        headerCells.forEach(cell => {
            headers.push(cell.textContent.trim());
        });
        csvContent += headers.join(",") + "\r\n";

        const rows = table.querySelectorAll("tbody tr");
        rows.forEach(row => {
            const rowData = [];
            const cells = row.querySelectorAll("td");
            cells.forEach((cell, index) => {
                if (index === 6) {
                    const badge = cell.querySelector(".badge");
                    rowData.push(badge ? badge.textContent.trim() : "");
                }
                else if (index === 8) {
                    const button = cell.querySelector("button");
                    rowData.push(button ? button.getAttribute("title") : "");
                } else {
                    rowData.push('"' + cell.textContent.trim().replace(/"/g, '""') + '"');
                }
            });
            csvContent += rowData.join(",") + "\r\n";
        });

        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", "leave_report.csv");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
</script>
</body>
</html>