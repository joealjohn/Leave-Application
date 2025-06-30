<?php
global $pdo;
require_once '../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirectWithMessage('../login.php', 'You must log in to access this page', 'warning');
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $leaveType = $_POST['leave_type'] ?? '';
    $startDate = $_POST['start_date'] ?? '';
    $endDate = $_POST['end_date'] ?? '';
    $reason = $_POST['reason'] ?? '';

    // Validate inputs
    $errors = [];

    if (empty($leaveType)) {
        $errors[] = "Please select a leave type";
    }

    if (empty($startDate) || !isValidDate($startDate)) {
        $errors[] = "Please enter a valid start date";
    }

    if (empty($endDate) || !isValidDate($endDate)) {
        $errors[] = "Please enter a valid end date";
    }

    if (!empty($startDate) && !empty($endDate) && strtotime($startDate) > strtotime($endDate)) {
        $errors[] = "End date must be after start date";
    }

    if (empty($reason)) {
        $errors[] = "Please provide a reason for leave";
    }

    // If no errors, save leave request
    if (empty($errors)) {
        $userId = $_SESSION['user_id'];

        try {
            $stmt = $pdo->prepare("INSERT INTO leave_requests (user_id, leave_type, start_date, end_date, reason, status, applied_at) VALUES (?, ?, ?, ?, ?, 'pending', ?)");
            $stmt->execute([$userId, $leaveType, $startDate, $endDate, $reason, getCurrentDateTime()]);

            // Log activity
            logActivity('Leave Application', "Applied for {$leaveType} leave from {$startDate} to {$endDate}");

            redirectWithMessage('dashboard.php', 'Leave request submitted successfully!', 'success');
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

// Get user's recent leave requests
$userId = $_SESSION['user_id'];
try {
    $stmt = $pdo->prepare("
        SELECT * FROM leave_requests 
        WHERE user_id = ? 
        ORDER BY applied_at DESC 
        LIMIT 3
    ");
    $stmt->execute([$userId]);
    $recentLeaves = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
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
    <!-- Include Flatpickr for better date picker -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include '../includes/user-navbar.php'; ?>

<div class="content-wrapper">
    <div class="container-fluid py-4">
        <h2 class="mb-4">Apply for Leave</h2>

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
            <!-- Leave Request Form -->
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Leave Request Form</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="apply_leave.php">
                            <div class="mb-3">
                                <label for="leave_type" class="form-label">Leave Type <span class="text-danger">*</span></label>
                                <select class="form-select" id="leave_type" name="leave_type" required>
                                    <option value="" selected disabled>Select Leave Type</option>
                                    <option value="sick" <?php echo isset($_POST['leave_type']) && $_POST['leave_type'] === 'sick' ? 'selected' : ''; ?>>Sick Leave</option>
                                    <option value="vacation" <?php echo isset($_POST['leave_type']) && $_POST['leave_type'] === 'vacation' ? 'selected' : ''; ?>>Vacation Leave</option>
                                    <option value="personal" <?php echo isset($_POST['leave_type']) && $_POST['leave_type'] === 'personal' ? 'selected' : ''; ?>>Personal Leave</option>
                                    <option value="emergency" <?php echo isset($_POST['leave_type']) && $_POST['leave_type'] === 'emergency' ? 'selected' : ''; ?>>Emergency Leave</option>
                                </select>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="start_date" class="form-label">Start Date <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                                        <input type="text" class="form-control datepicker" id="start_date" name="start_date" placeholder="yyyy-mm-dd" value="<?php echo $_POST['start_date'] ?? ''; ?>" required>
                                    </div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="end_date" class="form-label">End Date <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                                        <input type="text" class="form-control datepicker" id="end_date" name="end_date" placeholder="yyyy-mm-dd" value="<?php echo $_POST['end_date'] ?? ''; ?>" required>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="reason" class="form-label">Reason for Leave <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="reason" name="reason" rows="4" required><?php echo $_POST['reason'] ?? ''; ?></textarea>
                                <div class="form-text">Please provide a detailed reason for your leave request.</div>
                            </div>

                            <div class="mb-3 pt-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-paper-plane me-2"></i> Submit Leave Request
                                </button>
                                <a href="dashboard.php" class="btn btn-secondary btn-lg ms-2">
                                    <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Leave Guidelines -->
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">Leave Guidelines</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex">
                                    <span class="badge rounded-pill bg-info text-white me-2">
                                        <i class="fas fa-info"></i>
                                    </span>
                                Leave requests must be submitted at least 3 working days in advance for planned leaves.
                            </li>
                            <li class="list-group-item d-flex">
                                    <span class="badge rounded-pill bg-info text-white me-2">
                                        <i class="fas fa-info"></i>
                                    </span>
                                For sick leaves, medical certificate may be required for absences longer than 2 days.
                            </li>
                            <li class="list-group-item d-flex">
                                    <span class="badge rounded-pill bg-info text-white me-2">
                                        <i class="fas fa-info"></i>
                                    </span>
                                Weekend days are automatically excluded from leave calculation.
                            </li>
                            <li class="list-group-item d-flex">
                                    <span class="badge rounded-pill bg-info text-white me-2">
                                        <i class="fas fa-info"></i>
                                    </span>
                                Emergency leaves can be applied on the same day but require approval.
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Recent Leaves -->
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Your Recent Leaves</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($recentLeaves)): ?>
                            <div class="list-group">
                                <?php foreach ($recentLeaves as $leave): ?>
                                    <div class="list-group-item list-group-item-action">
                                        <div class="d-flex justify-content-between">
                                            <h6 class="mb-1"><?php echo ucfirst($leave['leave_type']); ?> Leave</h6>
                                            <span class="badge bg-<?php
                                            echo $leave['status'] === 'approved' ? 'success' :
                                                ($leave['status'] === 'rejected' ? 'danger' : 'warning');
                                            ?>">
                                                    <?php echo ucfirst($leave['status']); ?>
                                                </span>
                                        </div>
                                        <p class="mb-1">
                                            <i class="fas fa-calendar-alt me-1"></i>
                                            <?php echo formatDateForDisplay($leave['start_date']); ?> to <?php echo formatDateForDisplay($leave['end_date']); ?>
                                        </p>
                                        <small class="text-muted">
                                            Applied on: <?php echo formatDateForDisplay($leave['applied_at'], 'M d, Y'); ?>
                                        </small>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="mt-3">
                                <a href="my_requests.php" class="btn btn-outline-success btn-sm">
                                    <i class="fas fa-list me-1"></i> View All Requests
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info mb-0">
                                <i class="fas fa-info-circle me-2"></i> No recent leave requests found.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add spacer at bottom to prevent footer overlap -->
        <div class="back-btn-container"></div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Include Flatpickr for better date picker -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize date pickers with better formatting
        flatpickr(".datepicker", {
            dateFormat: "Y-m-d",
            minDate: "today",
            disableMobile: true,
            allowInput: true
        });

        // Calculate leave days when dates change
        const startDateInput = document.getElementById('start_date');
        const endDateInput = document.getElementById('end_date');

        function updateLeaveDays() {
            const startDate = startDateInput.value;
            const endDate = endDateInput.value;

            if (startDate && endDate) {
                // You could add AJAX call here to calculate days excluding weekends
                // For now we'll just show a simple message
                console.log("Dates selected:", startDate, "to", endDate);
            }
        }

        startDateInput.addEventListener('change', updateLeaveDays);
        endDateInput.addEventListener('change', updateLeaveDays);
    });
</script>
</body>
</html>