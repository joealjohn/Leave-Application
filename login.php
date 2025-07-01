<?php
global $pdo;
require_once 'includes/functions.php';

if (isLoggedIn()) {
    if (isAdmin()) {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: user/dashboard.php");
    }
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, name, email, password, role FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                if ($user['email'] === 'admin@gmail.com' && $user['role'] !== 'admin') {
                    $updateStmt = $pdo->prepare("UPDATE users SET role = 'admin' WHERE id = ?");
                    $updateStmt->execute([$user['id']]);
                    $user['role'] = 'admin';
                    logActivity('System', 'Default admin privileges restored for user ID: ' . $user['id']);
                }

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];

                logActivity('User Login', 'User logged in successfully');

                if ($user['role'] === 'admin') {
                    redirectWithMessage('admin/dashboard.php', 'Welcome back, ' . $user['name'] . '!', 'success');
                } else {
                    redirectWithMessage('user/dashboard.php', 'Welcome back, ' . $user['name'] . '!', 'success');
                }
            } else {
                $error = "Invalid email or password.";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
            error_log("Login error: " . $e->getMessage());
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
            background: url('assets/img/bg.jpg') no-repeat center center fixed;
            background-size: cover;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
            padding: 20px;
        }
        .login-container {
            max-width: 400px;
            width: 100%;
            padding: 30px;
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.18);
        }
        .system-logo {
            text-align: center;
            margin-bottom: 25px;
        }
        .system-logo i {
            font-size: 3rem;
            color: #28a745;
        }
        .login-title {
            text-align: center;
            margin-bottom: 25px;
            color: #343a40;
            font-weight: 500;
        }
        .form-control {
            background-color: rgba(255, 255, 255, 0.8);
            border: 1px solid rgba(0, 0, 0, 0.1);
            padding: 12px;
        }
        .form-control:focus {
            box-shadow: none;
            border-color: #28a745;
            background-color: rgba(255, 255, 255, 0.95);
        }
        .input-group-text {
            background-color: rgba(255, 255, 255, 0.5);
            border: 1px solid rgba(0, 0, 0, 0.1);
        }
        .login-btn {
            background-color: #28a745;
            border-color: #28a745;
            padding: 10px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .login-btn:hover {
            background-color: #218838;
            border-color: #1e7e34;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .alert {
            background-color: rgba(255, 255, 255, 0.9);
            border-left: 4px solid #dc3545;
        }
        a {
            color: #28a745;
            text-decoration: none;
            transition: all 0.2s;
        }
        a:hover {
            color: #218838;
            text-decoration: underline;
        }
        @media (max-width: 576px) {
            .login-container {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
<div class="login-container">
    <div class="system-logo">
        <i class="fas fa-calendar-check"></i>
    </div>
    <h2 class="login-title">Leave Management System</h2>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
        </div>
    <?php endif; ?>

    <?php displayMessage(); ?>

    <form action="login.php" method="POST">
        <div class="mb-3">
            <label for="email" class="form-label">Email Address</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" required>
            </div>
        </div>
        <div class="mb-4">
            <label for="password" class="form-label">Password</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
        </div>
        <button type="submit" class="btn login-btn text-white w-100 mb-3">
            <i class="fas fa-sign-in-alt me-2"></i>Login
        </button>
        <div class="text-center">
            <p class="mb-2">Don't have an account? <a href="register.php">Register</a></p>
            <p class="mb-0 small"><a href="#" id="forgotPasswordLink">Forgot Password?</a></p>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const togglePassword = document.getElementById('togglePassword');
        const password = document.getElementById('password');

        togglePassword.addEventListener('click', function() {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.querySelector('i').classList.toggle('fa-eye');
            this.querySelector('i').classList.toggle('fa-eye-slash');
        });

        document.getElementById('forgotPasswordLink').addEventListener('click', function(e) {
            e.preventDefault();
            alert('Please contact your system administrator to reset your password.');
        });
    });
</script>
</body>
</html>