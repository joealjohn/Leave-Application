<?php
global $pdo;
require_once '../includes/functions.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirectWithMessage('../login.php', 'Access denied. Admin privileges required.', 'danger');
}

$error = '';
$success = '';
$currentUserId = $_SESSION['user_id'];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add new user
    if (isset($_POST['action']) && $_POST['action'] === 'add_user') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'user';

        $errors = [];

        // Validate inputs
        if (empty($name)) {
            $errors[] = "Name is required";
        }

        if (empty($email)) {
            $errors[] = "Email is required";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format";
        }

        if (empty($password)) {
            $errors[] = "Password is required";
        } elseif (strlen($password) < 6) {
            $errors[] = "Password must be at least 6 characters long";
        }

        // Check if email already exists
        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetchColumn() > 0) {
                    $errors[] = "Email already exists";
                }
            } catch (PDOException $e) {
                $errors[] = "Database error: " . $e->getMessage();
            }
        }

        // If no errors, add user
        if (empty($errors)) {
            try {
                $hashedPassword = generatePasswordHash($password);
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, created_at) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$name, $email, $hashedPassword, $role, getCurrentDateTime()]);

                logActivity('User Creation', "Created new user: $name with role: $role");
                redirectWithMessage('manage_users.php', 'User added successfully', 'success');
            } catch (PDOException $e) {
                $errors[] = "Database error: " . $e->getMessage();
            }
        } else {
            $error = implode("<br>", $errors);
        }
    }

    // Update user
    else if (isset($_POST['action']) && $_POST['action'] === 'update_user') {
        $userId = $_POST['user_id'] ?? 0;
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? 'user';

        $errors = [];

        // Validate inputs
        if (empty($name)) {
            $errors[] = "Name is required";
        }

        if (empty($email)) {
            $errors[] = "Email is required";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format";
        }

        // Check if trying to change admin@gmail.com
        if ($email === 'admin@gmail.com' && $userId != $currentUserId) {
            // Get current email to check if it's being changed
            $stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $currentEmail = $stmt->fetchColumn();

            if ($currentEmail === 'admin@gmail.com') {
                $errors[] = "Cannot change admin username for the main administrator account";
            }
        }

        // Check email uniqueness (excluding current user)
        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND id != ?");
                $stmt->execute([$email, $userId]);
                if ($stmt->fetchColumn() > 0) {
                    $errors[] = "Email already exists";
                }
            } catch (PDOException $e) {
                $errors[] = "Database error: " . $e->getMessage();
            }
        }

        // Don't allow role changes for admin@gmail.com
        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $userEmail = $stmt->fetchColumn();

                if ($userEmail === 'admin@gmail.com' && $role !== 'admin') {
                    $errors[] = "Main administrator role cannot be changed";
                }
            } catch (PDOException $e) {
                $errors[] = "Database error: " . $e->getMessage();
            }
        }

        // If no errors, update user
        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, role = ? WHERE id = ?");
                $stmt->execute([$name, $email, $role, $userId]);

                logActivity('User Update', "Updated user: $name (ID: $userId)");
                redirectWithMessage('manage_users.php', 'User updated successfully', 'success');
            } catch (PDOException $e) {
                $errors[] = "Database error: " . $e->getMessage();
            }
        } else {
            $error = implode("<br>", $errors);
        }
    }

    // Delete user
    else if (isset($_POST['action']) && $_POST['action'] === 'delete_user') {
        $userId = $_POST['user_id'] ?? 0;

        // Don't allow self-deletion
        if ($userId == $currentUserId) {
            redirectWithMessage('manage_users.php', 'You cannot delete your own account', 'danger');
            exit;
        }

        // Check if user is trying to delete admin@gmail.com
        $stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $userEmail = $stmt->fetchColumn();

        if ($userEmail === 'admin@gmail.com') {
            redirectWithMessage('manage_users.php', 'Cannot delete the main administrator account', 'danger');
            exit;
        }

        try {
            // Get user name for logging
            $stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $userName = $stmt->fetchColumn();

            // Delete user
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$userId]);

            // Log activity
            logActivity('User Deletion', "Deleted user: $userName (ID: $userId)");

            redirectWithMessage('manage_users.php', 'User deleted successfully', 'success');
        } catch (PDOException $e) {
            redirectWithMessage('manage_users.php', 'Error deleting user: ' . $e->getMessage(), 'danger');
        }
    }
}

// Get all users
try {
    $stmt = $pdo->query("SELECT * FROM users ORDER BY role = 'admin' DESC, created_at DESC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Leave Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include '../includes/admin-navbar.php'; ?>

<div class="content-wrapper">
    <div class="container-fluid py-4">
        <h2 class="mb-4">Manage Users</h2>

        <?php displayMessage(); ?>

        <?php if (isset($error) && !empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="row mb-4">
            <!-- Add User Card -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-user-plus me-2"></i>Add New User</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="manage_users.php">
                            <div class="mb-3">
                                <label for="name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <div class="form-text">Password must be at least 6 characters.</div>
                            </div>

                            <div class="mb-3">
                                <label for="role" class="form-label">Role</label>
                                <select class="form-select" id="role" name="role" required>
                                    <option value="user">User</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>

                            <input type="hidden" name="action" value="add_user">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-user-plus me-2"></i> Add User
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- User List -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-users me-2"></i>User List</h5>
                    </div>
                    <div class="card-body">
                        <?php if (isset($users) && count($users) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Created</th>
                                        <th>Last Login</th>
                                        <th>Actions</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td><?php echo $user['id']; ?></td>
                                            <td><?php echo htmlspecialchars($user['name']); ?></td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'danger' : 'info'; ?>">
                                                    <?php echo ucfirst($user['role']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo formatDateForDisplay($user['created_at'], 'M d, Y'); ?></td>
                                            <td><?php echo $user['last_login'] ? formatDateForDisplay($user['last_login'], 'M d, Y') : 'Never'; ?></td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button type="button" class="btn btn-sm btn-outline-primary edit-user-btn"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#editUserModal"
                                                            data-user-id="<?php echo $user['id']; ?>"
                                                            data-user-name="<?php echo htmlspecialchars($user['name']); ?>"
                                                            data-user-email="<?php echo htmlspecialchars($user['email']); ?>"
                                                            data-user-role="<?php echo $user['role']; ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <?php if ($user['id'] != $currentUserId && $user['email'] !== 'admin@gmail.com'): ?>
                                                        <button type="button" class="btn btn-sm btn-outline-danger delete-user-btn"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#deleteUserModal"
                                                                data-user-id="<?php echo $user['id']; ?>"
                                                                data-user-name="<?php echo htmlspecialchars($user['name']); ?>">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i> No users found.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="manage_users.php" id="editUserForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Username</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                        <div id="admin-name-warning" class="form-text text-danger" style="display: none;">
                            Cannot change admin username for the main administrator account.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="edit_email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="edit_email" name="email" required>
                        <div id="admin-email-warning" class="form-text text-muted" style="display: none;">
                            Email cannot be changed.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="edit_role" class="form-label">Role</label>
                        <select class="form-select" id="edit_role" name="role" required>
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                        <div id="admin-role-warning" class="form-text text-danger" style="display: none;">
                            Main administrator role cannot be changed.
                        </div>
                    </div>

                    <input type="hidden" name="user_id" id="edit_user_id">
                    <input type="hidden" name="action" value="update_user">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete User Modal -->
<div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteUserModalLabel">Delete User</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="manage_users.php">
                <div class="modal-body">
                    <p>Are you sure you want to delete the user <strong id="delete_user_name"></strong>?</p>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        This action cannot be undone!
                    </div>
                    <input type="hidden" name="user_id" id="delete_user_id">
                    <input type="hidden" name="action" value="delete_user">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/scripts.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Edit User Modal Handler
        const editButtons = document.querySelectorAll('.edit-user-btn');
        const editModal = document.getElementById('editUserModal');

        editButtons.forEach(button => {
            button.addEventListener('click', function() {
                const userId = this.getAttribute('data-user-id');
                const userName = this.getAttribute('data-user-name');
                const userEmail = this.getAttribute('data-user-email');
                const userRole = this.getAttribute('data-user-role');

                // Populate form fields
                document.getElementById('edit_user_id').value = userId;
                document.getElementById('edit_name').value = userName;
                document.getElementById('edit_email').value = userEmail;
                document.getElementById('edit_role').value = userRole;

                // Handle admin@gmail.com restrictions
                const isMainAdmin = userEmail === 'admin@gmail.com';
                const nameField = document.getElementById('edit_name');
                const emailField = document.getElementById('edit_email');
                const roleField = document.getElementById('edit_role');
                const nameWarning = document.getElementById('admin-name-warning');
                const emailWarning = document.getElementById('admin-email-warning');
                const roleWarning = document.getElementById('admin-role-warning');

                if (isMainAdmin) {
                    nameField.readOnly = true;
                    emailField.readOnly = true;
                    roleField.disabled = true;
                    nameWarning.style.display = 'block';
                    emailWarning.style.display = 'block';
                    roleWarning.style.display = 'block';
                } else {
                    nameField.readOnly = false;
                    emailField.readOnly = false;
                    roleField.disabled = false;
                    nameWarning.style.display = 'none';
                    emailWarning.style.display = 'none';
                    roleWarning.style.display = 'none';
                }
            });
        });

        // Delete User Modal Handler
        const deleteButtons = document.querySelectorAll('.delete-user-btn');

        deleteButtons.forEach(button => {
            button.addEventListener('click', function() {
                const userId = this.getAttribute('data-user-id');
                const userName = this.getAttribute('data-user-name');

                document.getElementById('delete_user_id').value = userId;
                document.getElementById('delete_user_name').textContent = userName;
            });
        });

        // Enhanced modal cleanup to prevent black overlay
        const allModals = document.querySelectorAll('.modal');
        allModals.forEach(modal => {
            // When modal is fully hidden
            modal.addEventListener('hidden.bs.modal', function() {
                // Force cleanup of backdrop with a slight delay
                setTimeout(() => {
                    // Remove any orphaned modal backdrops
                    const backdrops = document.querySelectorAll('.modal-backdrop');
                    backdrops.forEach(backdrop => {
                        backdrop.remove();
                    });

                    // Reset body styles
                    document.body.classList.remove('modal-open');
                    document.body.style.overflow = '';
                    document.body.style.paddingRight = '';
                }, 100);
            });

            // When modal is being hidden
            modal.addEventListener('hide.bs.modal', function() {
                // Ensure proper cleanup
                setTimeout(() => {
                    if (document.querySelectorAll('.modal.show').length === 0) {
                        document.body.classList.remove('modal-open');
                        document.body.style.overflow = '';
                        document.body.style.paddingRight = '';
                    }
                }, 150);
            });
        });

        // Emergency cleanup on page click
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal-backdrop')) {
                // Force close all modals and clean up
                const openModals = document.querySelectorAll('.modal.show');
                openModals.forEach(modal => {
                    const bsModal = bootstrap.Modal.getInstance(modal);
                    if (bsModal) {
                        bsModal.hide();
                    }
                });

                // Force cleanup
                setTimeout(() => {
                    const backdrops = document.querySelectorAll('.modal-backdrop');
                    backdrops.forEach(backdrop => backdrop.remove());
                    document.body.classList.remove('modal-open');
                    document.body.style.overflow = '';
                    document.body.style.paddingRight = '';
                }, 50);
            }
        });
    });
</script>

</body>
</html>