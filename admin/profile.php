<?php
global $pdo;
require_once '../includes/functions.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirectWithMessage('../login.php', 'You must log in as admin to access this page', 'warning');
}

$userId = $_SESSION['user_id'];
$userEmail = $_SESSION['user_email'];

// Get user data
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        redirectWithMessage('dashboard.php', 'User not found', 'danger');
    }
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    $errors = [];

    // Validate name
    if (empty($name)) {
        $errors[] = "Name is required";
    }

    // If current password is provided, validate password change
    if (!empty($currentPassword)) {
        // Verify current password
        if (!password_verify($currentPassword, $user['password'])) {
            $errors[] = "Current password is incorrect";
        }

        // Validate new password
        if (empty($newPassword)) {
            $errors[] = "New password is required";
        } elseif (strlen($newPassword) < 6) {
            $errors[] = "Password must be at least 6 characters";
        }

        // Validate password confirmation
        if ($newPassword !== $confirmPassword) {
            $errors[] = "Passwords do not match";
        }
    }

    // Protect admin@gmail.com from being changed
    if ($userEmail === 'admin@gmail.com' && $name !== 'admin') {
        $errors[] = "Cannot change admin username for the main administrator account";
    }

    // Check if name already exists for another user
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE name = ? AND id != ?");
        $stmt->execute([$name, $userId]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "Username already exists. Please choose a different username.";
        }
    }

    // If no errors, update profile
    if (empty($errors)) {
        try {
            // Update name
            $stmt = $pdo->prepare("UPDATE users SET name = ? WHERE id = ?");
            $stmt->execute([$name, $userId]);

            // Update password if provided
            if (!empty($currentPassword)) {
                $hashedPassword = generatePasswordHash($newPassword);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashedPassword, $userId]);
            }

            // Update session data
            $_SESSION['user_name'] = $name;

            // Log activity
            logActivity('Profile Update', 'User updated their profile information');

            redirectWithMessage('profile.php', 'Profile updated successfully', 'success');
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
    <title>Admin Profile - Leave Management System</title>
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

        .card {
            background-color: rgba(255, 255, 255, 0.95);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
<?php include '../includes/admin-navbar.php'; ?>

<div class="container-fluid py-4">
    <h2 class="mb-4">Admin Profile</h2>

    <?php displayMessage(); ?>

    <?php if (isset($errors) && !empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Profile Information</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="profile.php">
                        <div class="mb-3">
                            <label for="name" class="form-label">Username</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                <input type="text" class="form-control" id="name" name="name" value="<?php echo $user['name']; ?>" required>
                            </div>
                            <?php if ($userEmail === 'admin@gmail.com'): ?>
                                <small class="form-text text-muted">You are the main administrator. This name cannot be changed.</small>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" class="form-control" id="email" value="<?php echo $user['email']; ?>" readonly>
                            </div>
                            <small class="form-text text-muted">Email cannot be changed.</small>
                        </div>

                        <div class="mb-3">
                            <label for="role" class="form-label">Role</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user-shield"></i></span>
                                <input type="text" class="form-control" id="role" value="<?php echo ucfirst($user['role']); ?>" readonly>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="created_at" class="form-label">Account Created</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-calendar-plus"></i></span>
                                <input type="text" class="form-control" id="created_at" value="<?php echo formatDateForDisplay($user['created_at'], 'Y-m-d'); ?>" readonly>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="last_login" class="form-label">Last Login</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-sign-in-alt"></i></span>
                                <input type="text" class="form-control" id="last_login" value="<?php echo formatDateForDisplay($user['last_login'] ?? '', 'Y-m-d'); ?>" readonly>
                            </div>
                        </div>

                        <hr>

                        <h5 class="mb-3">Change Password</h5>

                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="current_password" name="current_password">
                            </div>
                            <small class="form-text text-muted">Leave empty if you don't want to change password.</small>
                        </div>

                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-key"></i></span>
                                <input type="password" class="form-control" id="new_password" name="new_password">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-check"></i></span>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save me-1"></i> Update Profile
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Account Security</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h6><i class="fas fa-shield-alt me-2"></i> Security Recommendations</h6>
                        <ul class="mb-0">
                            <li>Use a strong password with at least 8 characters</li>
                            <li>Include uppercase letters, lowercase letters, numbers, and symbols</li>
                            <li>Do not reuse passwords from other websites</li>
                            <li>Change your password regularly</li>
                        </ul>
                    </div>

                    <hr>

                    <h6><i class="fas fa-history me-2"></i> Recent Activities</h6>
                    <div class="table-responsive">
                        <?php
                        // Get recent activities
                        $stmt = $pdo->prepare("
                                    SELECT * FROM activity_logs 
                                    WHERE user_id = ? 
                                    ORDER BY created_at DESC 
                                    LIMIT 5
                                ");
                        $stmt->execute([$userId]);
                        $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        ?>

                        <?php if (count($activities) > 0): ?>
                            <table class="table table-striped">
                                <thead>
                                <tr>
                                    <th>Activity</th>
                                    <th>Date</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($activities as $activity): ?>
                                    <tr>
                                        <td><?php echo $activity['action']; ?></td>
                                        <td><?php echo formatDateForDisplay($activity['created_at'], 'Y-m-d'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p class="text-center">No recent activities found</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Quick Links</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6 mb-2">
                            <a href="dashboard.php" class="btn btn-outline-success w-100">
                                <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                            </a>
                        </div>
                        <div class="col-6 mb-2">
                            <a href="all_requests.php" class="btn btn-outline-primary w-100">
                                <i class="fas fa-list-alt me-1"></i> All Requests
                            </a>
                        </div>
                        <div class="col-6 mb-2">
                            <a href="manage_users.php" class="btn btn-outline-warning w-100">
                                <i class="fas fa-users me-1"></i> Users
                            </a>
                        </div>
                        <div class="col-6 mb-2">
                            <a href="../logout.php" class="btn btn-outline-danger w-100">
                                <i class="fas fa-sign-out-alt me-1"></i> Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/scripts.js"></script>
</body>
</html>