<?php
// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';

try {
    // Connect to MySQL server
    $pdo = new PDO("mysql:host={$host}", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create database if it doesn't exist
    $dbname = 'mmnss_leave_management';
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}`");

    // Switch to the database
    $pdo->exec("USE `{$dbname}`");

    // Current timestamp as specified
    $timestamp = '2025-06-29 18:18:34';

    // Create users table
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin', 'user') DEFAULT 'user',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_login TIMESTAMP NULL
    )");

    // Create leave_requests table
    $pdo->exec("CREATE TABLE IF NOT EXISTS leave_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        leave_type ENUM('sick', 'vacation', 'personal', 'emergency', 'other') NOT NULL,
        reason TEXT NOT NULL,
        start_date DATE NOT NULL,
        end_date DATE NOT NULL,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        reviewed_at TIMESTAMP NULL,
        reviewed_by VARCHAR(100) NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    // Create activity_logs table
    $pdo->exec("CREATE TABLE IF NOT EXISTS activity_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NULL,
        action VARCHAR(100) NOT NULL,
        details TEXT NULL,
        ip_address VARCHAR(45) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    )");

    // Check if users already exist
    $stmt = $pdo->prepare("SELECT email FROM users WHERE email IN ('test@gmail.com', 'admin@gmail.com')");
    $stmt->execute();
    $existingUsers = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $results = [];

    // Add test user if doesn't exist
    if (!in_array('test@gmail.com', $existingUsers)) {
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, created_at) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            'Test',
            'test@gmail.com',
            '$2y$10$3fW.Y8AFl0gUpHl1o1.fiuGQgNJ5E5kz9abLF483DTwH53XMcNnvO', // test@1234
            'user',
            $timestamp
        ]);
        $results[] = "✓ Added test user (test@gmail.com)";
    } else {
        $results[] = "✓ Test user already exists";
    }

    // Add admin user if doesn't exist
    if (!in_array('admin@gmail.com', $existingUsers)) {
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, created_at) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            'admin',
            'admin@gmail.com',
            '$2y$10$qFnmvhYoM4ywLCyuY.dwK.ZLquBkbqIzQIZhA383k4Zt2NAByR.wK', // admin@12345
            'admin',
            $timestamp
        ]);
        $results[] = "✓ Added admin user (admin@gmail.com)";
    } else {
        $results[] = "✓ Admin user already exists";
    }

    // Add activity log
    $stmt = $pdo->prepare("INSERT INTO activity_logs (action, details, ip_address, created_at) VALUES (?, ?, ?, ?)");
    $stmt->execute([
        'System Initialized',
        'Database setup completed',
        $_SERVER['REMOTE_ADDR'],
        $timestamp
    ]);
    $results[] = "✓ Added activity log entry";

    // Output results with a design similar to what's in your image
    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Leave Application Setup</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <style>
            body {
                background-image: url("assets/img/bg.jpg");
                background-size: cover;
                background-position: center;
                background-repeat: no-repeat;
                min-height: 100vh;
                padding: 20px;
                position: relative;
            }
            .setup-container {
                background-color: rgba(255, 255, 255, 0.9);
                max-width: 800px;
                margin: 40px auto;
                padding: 30px;
                border-radius: 10px;
                box-shadow: 0 5px 15px rgba(0,0,0,0.2);
                position: relative;
                z-index: 1;
            }
            .success-text {
                color: #2e8b57;
            }
            .warning-text {
                color: #dc3545;
            }
            .footer {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                background-color: rgba(0,0,0,0.7);
                color: white;
                padding: 10px;
                display: flex;
                justify-content: space-between;
            }
            .heading {
                color: #2e8b57;
                border-bottom: 2px solid #2e8b57;
                padding-bottom: 10px;
                margin-bottom: 20px;
            }
            .btn-login {
                background-color: #2e8b57;
                border-color: #2e8b57;
                color: white;
                padding: 10px 20px;
                border-radius: 5px;
                text-decoration: none;
                display: inline-block;
                margin-top: 20px;
                transition: all 0.3s;
            }
            .btn-login:hover {
                background-color: #3CB371;
                border-color: #3CB371;
                color: white;
            }
            .date-display {
                text-align: right;
                margin-bottom: 20px;
            }
            .date-badge {
                background-color: #2e8b57;
                color: white;
                padding: 5px 15px;
                border-radius: 20px;
                display: inline-block;
                font-size: 0.9rem;
            }
        </style>
    </head>
    <body>
        <div class="setup-container">
            <h1 class="heading">Leave Application Setup</h1>
            <div class="date-display">
                <span class="date-badge">
                    <i class="fas fa-calendar-alt me-1"></i> 
                    ' . date('Y-m-d', strtotime($timestamp)) . '
                </span>
            </div>
            
            <h3 class="mt-4">Database Setup</h3>
            <p class="success-text"><i class="fas fa-check-circle me-2"></i> Database \'' . $dbname . '\' created or connected successfully</p>
            <p class="success-text"><i class="fas fa-check-circle me-2"></i> Required tables created</p>
            
            <h3 class="mt-4">Setup Results</h3>
            <ul>';

    foreach ($results as $result) {
        $class = strpos($result, "✓") !== false ? "success-text" : "warning-text";
        $icon = strpos($result, "✓") !== false ? "check-circle" : "exclamation-circle";
        echo '<li class="' . $class . '"><i class="fas fa-' . $icon . ' me-2"></i> ' . $result . '</li>';
    }

    echo '</ul>
            
            <p class="mt-4">See README.md file for test account credentials.</p>
            
            <a href="login.php" class="btn-login">
                <i class="fas fa-sign-in-alt me-2"></i> Go to Login Page
            </a>
        </div>
        
        <div class="footer">
            <div><i class="fas fa-home me-2"></i> Leave Management System</div>
        </div>
        
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>';

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>