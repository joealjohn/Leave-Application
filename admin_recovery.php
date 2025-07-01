<?php
global $pdo;
require_once 'includes/functions.php';
require_once 'config/database.php';

$message = '';
$messageType = '';
$adminExists = false;
$action = isset($_POST['action']) ? $_POST['action'] : '';

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin'");
    $stmt->execute();
    $adminCount = $stmt->fetchColumn();

    if ($adminCount > 0) {
        $adminExists = true;

        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = 'admin@gmail.com' AND role = 'admin'");
        $stmt->execute();
        $defaultAdminExists = $stmt->rowCount() > 0;
    }

    if ($action === 'create_admin' && !$defaultAdminExists) {
        $password = isset($_POST['password']) && !empty($_POST['password']) ?
            $_POST['password'] : generateRandomPassword(12);

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, created_at) 
                              VALUES ('admin', 'admin@gmail.com', ?, 'admin', NOW())");
        $stmt->execute([$hashedPassword]);

        logActivity('System', 'Created recovery admin account');

        $message = "Admin account successfully created! Email: admin@gmail.com, Password: " . ($password);
        $messageType = 'success';

        $adminExists = true;
        $defaultAdminExists = true;
    } else if ($action === 'reset_admin' && $defaultAdminExists) {
        $password = isset($_POST['password']) && !empty($_POST['password']) ?
            $_POST['password'] : generateRandomPassword(12);

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = 'admin@gmail.com' AND role = 'admin'");
        $stmt->execute([$hashedPassword]);

        logActivity('System', 'Reset admin password through recovery tool');

        $message = "Admin password successfully reset! Email: admin@gmail.com, New Password: " . ($password);
        $messageType = 'success';
    }
} catch (PDOException $e) {
    $message = "Database Error: " . $e->getMessage();
    $messageType = 'danger';
}

function generateRandomPassword($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $password;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Recovery - Leave Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 40px;
        }
        .recovery-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 30px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .system-status {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="recovery-container">
        <div class="d-flex justify-content-center mb-4">
            <i class="fas fa-user-shield text-primary" style="font-size: 4rem;"></i>
        </div>
        <h2 class="text-center mb-4">Admin Account Recovery</h2>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="system-status bg-light">
            <h5><i class="fas fa-info-circle me-2"></i>System Status</h5>
            <p><strong>Current Date/Time:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
            <p><strong>Admin Accounts:</strong> <?php echo $adminCount ?? '0'; ?></p>
            <p><strong>Default Admin:</strong> <?php echo $defaultAdminExists ? 'Exists' : 'Not Found'; ?></p>
        </div>

        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Admin Recovery Options</h5>
            </div>
            <div class="card-body">
                <?php if (!$defaultAdminExists): ?>
                    <form method="POST" action="admin_recovery.php" class="mb-4">
                        <input type="hidden" name="action" value="create_admin">
                        <div class="mb-3">
                            <label for="password" class="form-label">Set Admin Password (Optional)</label>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Leave blank for auto-generated password">
                            <small class="text-muted">If left blank, a secure password will be generated for you.</small>
                        </div>
                        <button type="submit" class="btn btn-success w-100">Create Default Admin Account</button>
                    </form>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Warning:</strong> No default admin account exists. Create one for system management.
                    </div>
                <?php else: ?>
                    <form method="POST" action="admin_recovery.php" class="mb-4">
                        <input type="hidden" name="action" value="reset_admin">
                        <div class="mb-3">
                            <label for="password" class="form-label">New Admin Password (Optional)</label>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Leave blank for auto-generated password">
                            <small class="text-muted">If left blank, a secure password will be generated for you.</small>
                        </div>
                        <button type="submit" class="btn btn-warning w-100">Reset Default Admin Password</button>
                    </form>
                    <div class="alert alert-info">
                        <i class="fas fa-check-circle me-2"></i>
                        Default admin account exists. You can reset the password if needed.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="mt-4 text-center">
            <a href="index.php" class="btn btn-outline-primary"><i class="fas fa-home me-1"></i> Return to Home</a>
            <a href="login.php" class="btn btn-outline-secondary"><i class="fas fa-sign-in-alt me-1"></i> Go to Login</a>
        </div>

        <div class="mt-4 pt-3 border-top">
            <small class="text-muted">
                <i class="fas fa-shield-alt me-1"></i> This recovery tool should be accessed only by system administrators.
                Consider protecting this file with additional security measures in production.
            </small>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Highlight the password text when generated
    document.addEventListener('DOMContentLoaded', function() {
        const alert = document.querySelector('.alert-success');
        if (alert && alert.textContent.includes('Password:')) {
            const passwordText = alert.textContent.split('Password:')[1].trim();
            alert.innerHTML = alert.innerHTML.replace(
                passwordText,
                '<span class="bg-warning p-1">' + passwordText + '</span>'
            );
        }
    });
</script>
</body>
</html>