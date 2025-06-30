<?php
global $pdo;
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    redirectWithMessage('../login.php', 'You must log in to access this page', 'warning');
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirectWithMessage('my_requests.php', 'Invalid request ID', 'warning');
}

$requestId = $_GET['id'];
$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM leave_requests WHERE id = ? AND user_id = ?");
$stmt->execute([$requestId, $userId]);
$request = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$request) {
    redirectWithMessage('my_requests.php', 'Leave request not found', 'warning');
}

if ($request['status'] !== 'pending') {
    redirectWithMessage('view_request.php?id=' . $requestId, 'You can only edit pending requests', 'warning');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $leaveType = $_POST['leave_type'] ?? '';
    $startDate = $_POST['start_date'] ?? '';
    $endDate = $_POST['end_date'] ?? '';
    $reason = $_POST['reason'] ?? '';

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

    if (!empty($startDate) && !empty($endDate) && strtotime($endDate) < strtotime($startDate)) {
        $errors[] = "End date cannot be before start date";
    }

    if (empty($reason)) {
        $errors[] = "Reason for leave is required";
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("UPDATE leave_requests 
                                  SET leave_type = ?, start_date = ?, end_date = ?, reason = ? 
                                  WHERE id = ? AND user_id = ? AND status = 'pending'");
            $stmt->execute([
                $leaveType,
                $startDate,
                $endDate,
                $reason,
                $requestId,
                $userId
            ]);

            $days = calculateLeaveDays($startDate, $endDate);
            logActivity(
                'Leave Request Updated',
                "Updated $leaveType leave request #$requestId from $startDate to $endDate ($days days)"
            );

            redirectWithMessage('my_requests.php', "Leave request updated successfully", 'success');
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Leave Request - Leave Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include '../includes/user-navbar.php'; ?>

<div class="container-fluid">
    <!-- Date and Time Display -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">
                                <i class="fas fa-calendar-alt me-2"></i> Current Date and Time (UTC - YYYY-MM-DD HH:MM:SS formatted): <?php echo getCurrentDateTime(); ?>
                            </h5>
                        </div>
                        <div>
                                <span class="badge bg-success">
                                    <i class="fas fa-user me-1"></i> Current User's Login: <?php echo getCurrentUser(); ?>
                                </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <h2 class="mb-4">Edit Leave Request</h2>

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

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Edit Leave Request #<?php echo $requestId; ?></h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="edit_request.php?id=<?php echo $requestId; ?>">
                        <div class="mb-3">
                            <label for="leave_type" class="form-label">Leave Type</label>
                            <select class="form-select" id="leave_type" name="leave_type" required>
                                <option value="" disabled>Select Leave Type</option>
                                <option value="sick" <?php echo $request['leave_type'] === 'sick' ? 'selected' : ''; ?>>Sick Leave</option>
                                <option value="vacation" <?php echo $request['leave_type'] === 'vacation' ? 'selected' : ''; ?>>Vacation Leave</option>
                                <option value="personal" <?php echo $request['leave_type'] === 'personal' ? 'selected' : ''; ?>>Personal Leave</option>
                                <option value="emergency" <?php echo $request['leave_type'] === 'emergency' ? 'selected' : ''; ?>>Emergency Leave</option>
                                <option value="other" <?php echo $request['leave_type'] === 'other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="start_date" class="form-label">Start Date</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $request['start_date']; ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="end_date" class="form-label">End Date</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $request['end_date']; ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="reason" class="form-label">Reason for Leave</label>
                            <textarea class="form-control" id="reason" name="reason" rows="5" required><?php echo htmlspecialchars($request['reason']); ?></textarea>
                            <div class="form-text">Please provide a detailed reason for your leave request.</div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i> Update Leave Request
                            </button>
                            <a href="my_requests.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Request Information</h5>
                </div>
                <div class="card-body">
                    <p><strong>Request ID:</strong> #<?php echo $requestId; ?></p>
                    <p><strong>Status:</strong> <span class="badge bg-warning">Pending</span></p>
                    <p><strong>Applied On:</strong> <?php echo formatDateForDisplay($request['applied_at'], 'Y-m-d H:i'); ?></p>
                    <p class="mb-0"><strong>Current Duration:</strong> <?php echo calculateLeaveDays($request['start_date'], $request['end_date']); ?> working days</p>
                </div>
            </div>

            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">Important Note</h5>
                </div>
                <div class="card-body">
                    <p>You are editing a pending leave request. Once you submit the changes, the request will remain in pending status and will need to be reviewed by an administrator.</p>
                    <p class="mb-0">If you wish to cancel this request completely, please go back to your requests and use the cancel option.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/user-navbar.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/scripts.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const startDateInput = document.getElementById('start_date');
        const endDateInput = document.getElementById('end_date');

        startDateInput.addEventListener('change', validateDates);
        endDateInput.addEventListener('change', validateDates);

        function validateDates() {
            const startDate = new Date(startDateInput.value);
            const endDate = new Date(endDateInput.value);

            if (startDate > endDate) {
                endDateInput.setCustomValidity('End date cannot be before start date');
            } else {
                endDateInput.setCustomValidity('');
            }
        }
    });
</script>
</body>
</html>