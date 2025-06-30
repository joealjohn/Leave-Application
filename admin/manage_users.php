<?php
global $pdo;
require_once '../includes/functions.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirectWithMessage('../login.php', 'You must log in as admin to access this page', 'warning');
}

// Get current user data
$currentUserId = $_SESSION['user_id'];
$currentUserEmail = $_SESSION['user_email'];

// Process user actions (add, edit, delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add new user
    if (isset($_POST['action']) && $_POST['action'] === 'add_user') {
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'user';

        $result = addUser($name, $email, $password, $role);

        if ($result === true) {
            redirectWithMessage('manage_users.php', 'User added successfully', 'success');
        } else {
            $error = $result; // Error message from addUser function
        }
    }

    // Edit user
    else if (isset($_POST['action']) && $_POST['action'] === 'edit_user') {
        $userId = $_POST['user_id'] ?? 0;
        $name = $_POST['name'] ?? '';
        $role = $_POST['role'] ?? 'user';

        $errors = [];

        // Check if name already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE name = ? AND id != ?");
        $stmt->execute([$name, $userId]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "Username already exists. Please choose a different username.";
        }

        // Check if user is changing admin@gmail.com username
        $stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $userEmail = $stmt->fetchColumn();

        if ($userEmail === 'admin@gmail.com' && $name !== 'admin') {
            $errors[] = "Cannot change admin username for the main administrator account";
        }

        // If no errors, update user
        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("UPDATE users SET name = ?, role = ? WHERE id = ?");
                $stmt->execute([$name, $role, $userId]);

                // Log activity
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

<div class="container-fluid py-4">
    <h2 class="mb-4">Manage Users</h2>

    <?php displayMessage(); ?>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="row mb-4">
        <!-- Add User Card -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Add New User</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="manage_users.php">
                        <div class="mb-3">
                            <label for="name" class="form-label">Username</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                            <small class="form-text text-muted">Password must be at least 6 characters.</small>
                        </div>
                        <div class="mb-3">
                            <label for="role" class="form-label">Role</label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="user">User</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <input type="hidden" name="action" value="add_user">
                        <div class="d-grid">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-user-plus me-1"></i> Add User
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- User Stats Card -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">User Statistics</h5>
                </div>
                <div class="card-body">
                    <?php
                    // Calculate user statistics
                    $totalUsers = count($users);
                    $adminUsers = 0;
                    $regularUsers = 0;
                    $activeUsers = 0;

                    foreach ($users as $user) {
                        if ($user['role'] === 'admin') {
                            $adminUsers++;
                        } else {
                            $regularUsers++;
                        }

                        if ($user['last_login']) {
                            $lastLogin = new DateTime($user['last_login']);
                            $now = new DateTime();
                            $diff = $now->diff($lastLogin);

                            if ($diff->days < 30) {
                                $activeUsers++;
                            }
                        }
                    }
                    ?>

                    <div class="row">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white mb-3">
                                <div class="card-body text-center">
                                    <h2><?php echo $totalUsers; ?></h2>
                                    <p class="mb-0">Total Users</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-danger text-white mb-3">
                                <div class="card-body text-center">
                                    <h2><?php echo $adminUsers; ?></h2>
                                    <p class="mb-0">Admin Users</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white mb-3">
                                <div class="card-body text-center">
                                    <h2><?php echo $regularUsers; ?></h2>
                                    <p class="mb-0">Regular Users</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white mb-3">
                                <div class="card-body text-center">
                                    <h2><?php echo $activeUsers; ?></h2>
                                    <p class="mb-0">Active Users</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- User List Card -->
    <div class="card">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0">User List</h5>
        </div>
        <div class="card-body">
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
                            <td><?php echo $user['name']; ?></td>
                            <td><?php echo $user['email']; ?></td>
                            <td>
                                        <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'danger' : 'info'; ?>">
                                            <?php echo ucfirst($user['role']); ?>
                                        </span>
                            </td>
                            <td><?php echo formatDateForDisplay($user['created_at']); ?></td>
                            <td>
                                <?php echo $user['last_login'] ? formatDateForDisplay($user['last_login']) : 'Never'; ?>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $user['id']; ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php if ($user['id'] != $currentUserId && $user['email'] !== 'admin@gmail.com'): ?>
                                        <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $user['id']; ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>

                        <!-- Edit User Modal -->
                        <div class="modal fade" id="editModal<?php echo $user['id']; ?>" tabindex="-1" aria-labelledby="editModalLabel<?php echo $user['id']; ?>" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header bg-primary text-white">
                                        <h5 class="modal-title" id="editModalLabel<?php echo $user['id']; ?>">Edit User</h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <form method="POST" action="manage_users.php">
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label for="edit_name<?php echo $user['id']; ?>" class="form-label">Username</label>
                                                <input type="text" class="form-control" id="edit_name<?php echo $user['id']; ?>" name="name" value="<?php echo $user['name']; ?>" required <?php echo $user['email'] === 'admin@gmail.com' ? 'readonly' : ''; ?>>
                                                <?php if ($user['email'] === 'admin@gmail.com'): ?>
                                                    <small class="form-text text-danger">Cannot change admin username for the main administrator account.</small>
                                                <?php endif; ?>
                                            </div>
                                            <div class="mb-3">
                                                <label for="edit_email<?php echo $user['id']; ?>" class="form-label">Email</label>
                                                <input type="email" class="form-control" id="edit_email<?php echo $user['id']; ?>" value="<?php echo $user['email']; ?>" readonly>
                                                <small class="form-text text-muted">Email cannot be changed.</small>
                                            </div>
                                            <div class="mb-3">
                                                <label for="edit_role<?php echo $user['id']; ?>" class="form-label">Role</label>
                                                <select class="form-select" id="edit_role<?php echo $user['id']; ?>" name="role" <?php echo $user['email'] === 'admin@gmail.com' ? 'disabled' : ''; ?>>
                                                    <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>User</option>
                                                    <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                                </select>
                                                <?php if ($user['email'] === 'admin@gmail.com'): ?>
                                                    <small class="form-text text-danger">Main administrator role cannot be changed.</small>
                                                <?php endif; ?>
                                            </div>
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <input type="hidden" name="action" value="edit_user">
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
                        <?php if ($user['id'] != $currentUserId && $user['email'] !== 'admin@gmail.com'): ?>
                            <div class="modal fade" id="deleteModal<?php echo $user['id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?php echo $user['id']; ?>" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header bg-danger text-white">
                                            <h5 class="modal-title" id="deleteModalLabel<?php echo $user['id']; ?>">Delete User</h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p>Are you sure you want to delete user <strong><?php echo $user['name']; ?></strong> (<?php echo $user['email']; ?>)?</p>
                                            <p class="text-danger">This action cannot be undone!</p>
                                        </div>
                                        <div class="modal-footer">
                                            <form method="POST" action="manage_users.php">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <input type="hidden" name="action" value="delete_user">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-danger">Delete User</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/scripts.js"></script>
</body>
</html>