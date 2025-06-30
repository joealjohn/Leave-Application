<?php
global $pdo;
require_once '../includes/functions.php';

// Check if user is logged in (FIXED - removed admin check)
if (!isLoggedIn()) {
    redirectWithMessage('../login.php', 'You must log in to access this page', 'warning');
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

    // Protect admin@gmail.com from being changed (only if current user IS admin@gmail.com)
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
    <title>Profile - Leave Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include '../includes/user-navbar.php'; ?>

<div class="content-wrapper">
    <div class="container-fluid py-4">
        <h2 class="mb-4">My Profile</h2>

        <?php displayMessage(); ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

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
            <!-- Profile Information -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-user-edit me-2"></i>Profile Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="profile.php">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="name" class="form-label">Full Name</label>
                                        <input type="text" class="form-control" id="name" name="name"
                                               value="<?php echo htmlspecialchars($user['name']); ?>" required
                                            <?php echo ($userEmail === 'admin@gmail.com') ? 'readonly' : ''; ?>>
                                        <?php if ($userEmail === 'admin@gmail.com'): ?>
                                            <div class="form-text text-danger">
                                                Cannot change admin username for the main administrator account.
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email Address</label>
                                        <input type="email" class="form-control" id="email"
                                               value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                                        <div class="form-text">Email cannot be changed.</div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="role" class="form-label">Role</label>
                                        <input type="text" class="form-control"
                                               value="<?php echo ucfirst($user['role']); ?>" readonly>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="created_at" class="form-label">Member Since</label>
                                        <input type="text" class="form-control"
                                               value="<?php echo formatDateForDisplay($user['created_at']); ?>" readonly>
                                    </div>
                                </div>
                            </div>

                            <hr>

                            <h6 class="mb-3">Change Password</h6>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="current_password" class="form-label">Current Password</label>
                                        <input type="password" class="form-control" id="current_password"
                                               name="current_password" autocomplete="current-password">
                                        <div class="form-text">Leave blank to keep current password.</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="new_password" class="form-label">New Password</label>
                                        <input type="password" class="form-control" id="new_password"
                                               name="new_password" autocomplete="new-password">
                                        <div class="form-text">Must be at least 6 characters.</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                                        <input type="password" class="form-control" id="confirm_password"
                                               name="confirm_password" autocomplete="new-password">
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3 pt-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-save me-2"></i> Update Profile
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
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">
                            <i class="fas fa-shield-alt me-2"></i>Security Tips
                        </h5>
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
                                            <td><?php echo htmlspecialchars($activity['action']); ?></td>
                                            <td><?php echo formatDateForDisplay($activity['created_at'], 'M d'); ?></td>
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

                <div class="card mt-3">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-link me-2"></i>Quick Links
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-6 mb-2">
                                <a href="dashboard.php" class="btn btn-outline-success w-100">
                                    <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                                </a>
                            </div>
                            <div class="col-6 mb-2">
                                <a href="apply_leave.php" class="btn btn-outline-primary w-100">
                                    <i class="fas fa-plus-circle me-1"></i> Apply Leave
                                </a>
                            </div>
                            <div class="col-6 mb-2">
                                <a href="my_requests.php" class="btn btn-outline-warning w-100">
                                    <i class="fas fa-list-alt me-1"></i> My Requests
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
</div>

<?php include '../includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/scripts.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Password strength indicator
        const passwordInput = document.getElementById('new_password');
        const confirmPasswordInput = document.getElementById('confirm_password');

        if (passwordInput && confirmPasswordInput) {
            // Add password matching validation
            confirmPasswordInput.addEventListener('input', function() {
                const password = passwordInput.value;
                const confirmPassword = this.value;

                if (password && confirmPassword) {
                    if (password === confirmPassword) {
                        this.setCustomValidity('');
                        this.classList.remove('is-invalid');
                        this.classList.add('is-valid');
                    } else {
                        this.setCustomValidity('Passwords do not match');
                        this.classList.remove('is-valid');
                        this.classList.add('is-invalid');
                    }
                } else {
                    this.setCustomValidity('');
                    this.classList.remove('is-invalid', 'is-valid');
                }
            });

            passwordInput.addEventListener('input', function() {
                const confirmPassword = confirmPasswordInput.value;
                if (confirmPassword) {
                    confirmPasswordInput.dispatchEvent(new Event('input'));
                }
            });
        }
    });
</script>

</body>
</html>