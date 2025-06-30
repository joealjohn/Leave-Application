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
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($name) || empty($email) || empty($password) || empty($confirmPassword)) {
        $error = "All fields are required.";
    } else if ($password !== $confirmPassword) {
        $error = "Passwords do not match.";
    } else if (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);

            if ($stmt->rowCount() > 0) {
                $error = "Email already registered. Please use a different email or login.";
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                if ($email === 'admin@gmail.com') {
                    $role = 'admin';
                    logActivity('System', 'Default admin account registered');
                } else {
                    $role = 'user';
                }

                $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, created_at) VALUES (?, ?, ?, ?, NOW())");
                $stmt->execute([$name, $email, $hashedPassword, $role]);

                $success = "Registration successful! You can now login.";
            }
        } catch (PDOException $e) {
            $error = "Registration failed. Please try again later.";
            error_log("Registration error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Leave Management System</title>
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
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }
        .register-container {
            max-width: 500px;
            width: 100%;
            padding: 30px;
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.18);
            margin: 20px 0;
        }
        .system-logo {
            text-align: center;
            margin-bottom: 25px;
        }
        .system-logo i {
            font-size: 3rem;
            color: #28a745; /* Changed to green to match login page */
        }
        .register-title {
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
            border-color: #28a745; /* Changed to green to match login page */
            background-color: rgba(255, 255, 255, 0.95);
        }
        .input-group-text {
            background-color: rgba(255, 255, 255, 0.5);
            border: 1px solid rgba(0, 0, 0, 0.1);
        }
        .register-btn {
            background-color: #28a745; /* Changed to green to match login page */
            border-color: #28a745; /* Changed to green to match login page */
            padding: 10px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .register-btn:hover {
            background-color: #218838; /* Changed to green to match login page */
            border-color: #1e7e34; /* Changed to green to match login page */
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .password-strength {
            height: 5px;
            margin-top: 5px;
            border-radius: 2px;
        }
        .alert {
            background-color: rgba(255, 255, 255, 0.9);
        }
        a {
            color: #28a745; /* Changed to green to match login page */
            text-decoration: none;
            transition: all 0.2s;
        }
        a:hover {
            color: #218838; /* Changed to green to match login page */
            text-decoration: underline;
        }
        @media (max-width: 576px) {
            .register-container {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
<div class="register-container">
    <div class="system-logo">
        <i class="fas fa-user-plus"></i>
    </div>
    <h2 class="register-title">Create Account</h2>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
            <div class="mt-2">
                <a href="login.php" class="btn btn-sm btn-outline-success">Login Now</a>
            </div>
        </div>
    <?php else: ?>
        <form action="register.php" method="POST" id="registerForm">
            <div class="mb-3">
                <label for="name" class="form-label">Full Name</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                    <input type="text" class="form-control" id="name" name="name" placeholder="Enter your full name" required>
                </div>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                    <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" required>
                </div>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <div class="password-strength bg-secondary" id="passwordStrength"></div>
                <small class="text-muted">Password must be at least 6 characters long</small>
            </div>
            <div class="mb-4">
                <label for="confirm_password" class="form-label">Confirm Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required>
                </div>
            </div>
            <button type="submit" class="btn register-btn text-white w-100 mb-3">
                <i class="fas fa-user-plus me-2"></i>Register
            </button>
            <div class="text-center">
                <p class="mb-0">Already have an account? <a href="login.php">Login</a></p>
            </div>
        </form>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const togglePassword = document.getElementById('togglePassword');
        const password = document.getElementById('password');

        togglePassword?.addEventListener('click', function() {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.querySelector('i').classList.toggle('fa-eye');
            this.querySelector('i').classList.toggle('fa-eye-slash');
        });

        const passwordInput = document.getElementById('password');
        const passwordStrength = document.getElementById('passwordStrength');

        passwordInput?.addEventListener('input', function() {
            const value = this.value;
            let strength = 0;

            if (value.length >= 6) strength += 25;
            if (value.match(/[a-z]+/)) strength += 25;
            if (value.match(/[A-Z]+/)) strength += 25;
            if (value.match(/[0-9]+/) || value.match(/[!@#$%^&*()]+/)) strength += 25;

            passwordStrength.style.width = strength + '%';

            if (strength < 50) {
                passwordStrength.classList.remove('bg-warning', 'bg-info', 'bg-success');
                passwordStrength.classList.add('bg-danger');
            } else if (strength < 75) {
                passwordStrength.classList.remove('bg-danger', 'bg-info', 'bg-success');
                passwordStrength.classList.add('bg-warning');
            } else if (strength < 100) {
                passwordStrength.classList.remove('bg-danger', 'bg-warning', 'bg-success');
                passwordStrength.classList.add('bg-info');
            } else {
                passwordStrength.classList.remove('bg-danger', 'bg-warning', 'bg-info');
                passwordStrength.classList.add('bg-success');
            }
        });

        const form = document.getElementById('registerForm');
        const confirmPassword = document.getElementById('confirm_password');

        form?.addEventListener('submit', function(event) {
            if (password.value !== confirmPassword.value) {
                event.preventDefault();
                alert('Passwords do not match!');
            }

            if (password.value.length < 6) {
                event.preventDefault();
                alert('Password must be at least 6 characters long!');
            }
        });
    });
</script>
</body>
</html>