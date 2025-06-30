<?php
global $pdo;
require_once 'includes/functions.php';

// This script ensures the admin@gmail.com account exists and has admin role
// Run it if you're experiencing issues with the admin account

// Check if admin exists
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute(['admin@gmail.com']);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

$adminCreated = false;
$adminFixed = false;

if (!$admin) {
    // Create admin user
    $hashedPassword = password_hash('admin@12345', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, created_at) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute(['admin', 'admin@gmail.com', $hashedPassword, 'admin', getCurrentDateTime()]);
    $adminCreated = true;
} else {
    // Check if admin has admin role
    if ($admin['role'] !== 'admin') {
        $stmt = $pdo->prepare("UPDATE users SET role = 'admin' WHERE email = ?");
        $stmt->execute(['admin@gmail.com']);
        $adminFixed = true;
    }

    // Re-hash password if needed
    if (!password_verify('admin@12345', $admin['password'])) {
        $hashedPassword = password_hash('admin@12345', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->execute([$hashedPassword, 'admin@gmail.com']);
        $adminFixed = true;
    }
}

// HTML output
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Account Check - Leave Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            padding: 20px;
        }
        .container {
            background-color: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-top: 50px;
        }
        .success-icon {
            font-size: 48px;
            color: #2e8b57;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="text-center mb-4">
        <i class="fas fa-user-shield success-icon"></i>
        <h2 class="mt-3">Admin Account Check</h2>
    </div>

    <div class="alert alert-info">
        <p><strong>Current Date and Time:</strong> <?php echo getCurrentDateTime(); ?></p>
        <p><strong>Current User:</strong> <?php echo getCurrentUser(); ?></p>
    </div>

    <?php if ($adminCreated): ?>
        <div class="alert alert-success">
            <h4><i class="fas fa-check-circle me-2"></i> Admin Account Created</h4>
            <p>The admin account has been created successfully with the following credentials:</p>
            <ul>
                <li><strong>Email:</strong> admin@gmail.com</li>
                <li><strong>Password:</strong> admin@12345</li>
            </ul>
        </div>
    <?php elseif ($adminFixed): ?>
        <div class="alert alert-success">
            <h4><i class="fas fa-check-circle me-2"></i> Admin Account Fixed</h4>
            <p>The admin account has been updated with the following credentials:</p>
            <ul>
                <li><strong>Email:</strong> admin@gmail.com</li>
                <li><strong>Password:</strong> admin@12345</li>
                <li><strong>Role:</strong> admin</li>
            </ul>
        </div>
    <?php else: ?>
        <div class="alert alert-success">
            <h4><i class="fas fa-check-circle me-2"></i> Admin Account Verified</h4>
            <p>The admin account is already set up correctly with admin privileges.</p>
            <ul>
                <li><strong>Email:</strong> admin@gmail.com</li>
                <li><strong>Password:</strong> admin@12345</li>
            </ul>
        </div>
    <?php endif; ?>

    <div class="mt-4 text-center">
        <a href="login.php" class="btn btn-success">
            <i class="fas fa-sign-in-alt me-2"></i> Go to Login Page
        </a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>