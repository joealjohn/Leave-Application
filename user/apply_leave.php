<?php
global $pdo;
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    redirectWithMessage('../login.php', 'You must log in to access this page', 'warning');
}

$userId = $_SESSION['user_id'];
$userDetails = getUserDetails($userId);

$leaveTypes = [
    'sick' => 'Sick Leave',
    'vacation' => 'Vacation Leave',
    'emergency' => 'Emergency Leave',
    'personal' => 'Personal Leave'
];

try {
    $checkTable = $pdo->query("SHOW TABLES LIKE 'leave_requests'");
    if ($checkTable->rowCount() == 0) {
        $createTableSQL = "
            CREATE TABLE `leave_requests` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `user_id` int(11) NOT NULL,
              `leave_type` varchar(50) NOT NULL,
              `start_date` date NOT NULL,
              `end_date` date NOT NULL,
              `days` int(11) DEFAULT NULL,
              `reason` text NOT NULL,
              `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
              `approved_by` int(11) DEFAULT NULL,
              `approved_at` datetime DEFAULT NULL,
              `applied_at` datetime DEFAULT NULL,
              PRIMARY KEY (`id`),
              KEY `user_id` (`user_id`),
              KEY `approved_by` (`approved_by`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";
        $pdo->exec($createTableSQL);
    }
} catch (PDOException $e) {
    error_log("Error checking/creating table: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $leaveType = sanitizeInput($_POST['leave_type'] ?? '');
    $startDate = sanitizeInput($_POST['start_date'] ?? '');
    $endDate = sanitizeInput($_POST['end_date'] ?? '');
    $reason = sanitizeInput($_POST['reason'] ?? '');

    $errors = [];

    if (empty($leaveType)) {
        $errors[] = "Leave type is required";
    }

    if (empty($startDate) || !isValidDate($startDate)) {
        $errors[] = "Valid start date is required";
    }

    if (empty($endDate) || !isValidDate($endDate)) {
        $errors[] = "Valid end date is required";
    }

    if (isValidDate($startDate) && isValidDate($endDate)) {
        $start = new DateTime($startDate);
        $end = new DateTime($endDate);

        if ($start > $end) {
            $errors[] = "Start date cannot be after end date";
        }
    }

    if (empty($reason)) {
        $errors[] = "Reason is required";
    }

    $leaveDays = calculateLeaveDays($startDate, $endDate);

    if (empty($errors)) {
        try {
            global $pdo;

            $tableCheck = $pdo->query("DESCRIBE leave_requests");
            $columns = [];
            while($row = $tableCheck->fetch(PDO::FETCH_ASSOC)) {
                $columns[] = $row['Field'];
            }

            $sql = "INSERT INTO leave_requests (user_id, leave_type, start_date, end_date, reason, status";
            $values = "(?, ?, ?, ?, ?, 'pending'";
            $params = [$userId, $leaveType, $startDate, $endDate, $reason];

            if (in_array('days', $columns)) {
                $sql .= ", days";
                $values .= ", ?";
                $params[] = $leaveDays;
            }

            if (in_array('applied_at', $columns)) {
                $sql .= ", applied_at";
                $values .= ", ?";
                $params[] = getCurrentDateTime();
            }

            $sql .= ") VALUES " . $values . ")";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            logActivity('leave_request', "Applied for {$leaveType} leave from {$startDate} to {$endDate}");

            redirectWithMessage('my_requests.php', 'Leave request submitted successfully. It is pending approval.', 'success');
        } catch (PDOException $e) {
            error_log("Error submitting leave request: " . $e->getMessage());
            $errors[] = "An error occurred while submitting your request. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply for Leave - Leave Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include '../includes/user-navbar.php'; ?>

<div class="container py-4">
    <h2 class="mb-4"><i class="fas fa-calendar-plus me-2"></i>Apply for Leave</h2>

    <?php displayMessage(); ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="leaveType" class="form-label">Leave Type</label>
                    <select class="form-select" id="leaveType" name="leave_type" required>
                        <option value="">Select Leave Type</option>
                        <option value="sick">Sick Leave</option>
                        <option value="vacation">Vacation Leave</option>
                        <option value="emergency">Emergency Leave</option>
                        <option value="personal">Personal Leave</option>
                    </select>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="startDate" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="startDate" name="start_date"
                               value="<?php echo isset($_POST['start_date']) ? $_POST['start_date'] : ''; ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="endDate" class="form-label">End Date</label>
                        <input type="date" class="form-control" id="endDate" name="end_date"
                               value="<?php echo isset($_POST['end_date']) ? $_POST['end_date'] : ''; ?>" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="days" class="form-label">Number of Working Days</label>
                    <input type="text" class="form-control" id="days" readonly>
                    <small class="text-muted">This is automatically calculated excluding weekends.</small>
                </div>

                <div class="mb-3">
                    <label for="reason" class="form-label">Reason for Leave</label>
                    <textarea class="form-control" id="reason" name="reason" rows="4" required><?php echo isset($_POST['reason']) ? $_POST['reason'] : ''; ?></textarea>
                </div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <button type="submit" class="btn btn-primary">Submit Application</button>
                    <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
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
    document.addEventListener('DOMContentLoaded', function() {
        const startDateInput = document.getElementById('startDate');
        const endDateInput = document.getElementById('endDate');
        const daysInput = document.getElementById('days');

        function calculateWorkingDays() {
            const startDate = new Date(startDateInput.value);
            const endDate = new Date(endDateInput.value);

            if (isNaN(startDate.getTime()) || isNaN(endDate.getTime())) {
                daysInput.value = '';
                return;
            }

            endDate.setDate(endDate.getDate() + 1);

            let workingDays = 0;
            const currentDate = new Date(startDate);

            while (currentDate < endDate) {
                const dayOfWeek = currentDate.getDay();
                if (dayOfWeek !== 0 && dayOfWeek !== 6) {
                    workingDays++;
                }

                currentDate.setDate(currentDate.getDate() + 1);
            }

            daysInput.value = workingDays;
        }

        startDateInput.addEventListener('change', calculateWorkingDays);
        endDateInput.addEventListener('change', calculateWorkingDays);

        const today = new Date();
        const yyyy = today.getFullYear();
        const mm = String(today.getMonth() + 1).padStart(2, '0');
        const dd = String(today.getDate()).padStart(2, '0');
        const todayFormatted = `${yyyy}-${mm}-${dd}`;

        startDateInput.setAttribute('min', todayFormatted);
        endDateInput.setAttribute('min', todayFormatted);
    });
</script>
</body>
</html>