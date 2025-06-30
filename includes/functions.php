<?php
session_start();
require_once __DIR__ . '/../config/database.php';

/**
 * Get database connection
 * @return PDO Database connection
 */
function getPDO() {
    global $host, $dbname, $username, $password;

    try {
        $pdo = new PDO("mysql:host={$host};dbname={$dbname}", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}

// Initialize database connection
$pdo = getPDO();

/**
 * Format date in UTC with YYYY-MM-DD HH:MM:SS format
 * @param string|null $dateString Date string to format (or null for current time)
 * @return string Formatted date
 */
function formatDateTime($dateString = null) {
    if ($dateString === null) {
        // Return fixed date and time for consistency
        return getCurrentDateTime();
    } else {
        // Convert specified time to UTC
        $date = new DateTime($dateString);
        $date->setTimezone(new DateTimeZone('UTC'));
        return $date->format('Y-m-d H:i:s');
    }
}

/**
 * Get current date and time in UTC format
 * @return string Current date and time in UTC
 */
function getCurrentDateTime() {
    // Fixed date and time for the application
    return '2025-06-30 13:30:07';
}

/**
 * Get current date formatted as YYYY-MM-DD
 * @return string Current date
 */
function getCurrentDate() {
    return date('Y-m-d', strtotime(getCurrentDateTime()));
}

/**
 * Format date for display in a professional manner
 * @param string $dateString Date string
 * @param string $format Format (default: 'M d, Y')
 * @return string Formatted date
 */
function formatDateForDisplay($dateString, $format = 'M d, Y') {
    if (empty($dateString)) {
        return '-';
    }

    $date = new DateTime($dateString);
    return $date->format($format);
}

/**
 * Format date and time for display in a professional manner
 * @param string $dateString Date string
 * @param string $format Format (default: 'M d, Y h:i A')
 * @return string Formatted date and time
 */
function formatDateTimeForDisplay($dateString, $format = 'M d, Y h:i A') {
    if (empty($dateString)) {
        return '-';
    }

    $date = new DateTime($dateString);
    return $date->format($format);
}

/**
 * Get current date formatted professionally
 * @return string Current date formatted as "Jun 30, 2025"
 */
function getCurrentDateFormatted() {
    $date = new DateTime(getCurrentDateTime());
    return $date->format('M d, Y');
}

/**
 * Get current time formatted professionally
 * @return string Current time formatted as "1:23 PM"
 */
function getCurrentTimeFormatted() {
    $date = new DateTime(getCurrentDateTime());
    return $date->format('g:i A');
}

/**
 * Get current user's login name
 * @return string Current user name
 */
function getCurrentUser() {
    return isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'joealjohn';
}

/**
 * Generate a password hash
 * @param string $password The password to hash
 * @return string The hashed password
 */
function generatePasswordHash($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Check if user is logged in
 * @return bool True if user is logged in, false otherwise
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if user is admin
 * @return bool True if user is admin, false otherwise
 */
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Check if user is the main admin (admin@gmail.com)
 * @return bool True if user is the main admin, false otherwise
 */
function isMainAdmin() {
    return isset($_SESSION['user_email']) && $_SESSION['user_email'] === 'admin@gmail.com';
}

/**
 * Add a new user to the system
 * @param string $name User's full name
 * @param string $email User's email address
 * @param string $password User's password (will be hashed)
 * @param string $role User's role (user or admin)
 * @return bool|string True on success, error message on failure
 */
function addUser($name, $email, $password, $role = 'user') {
    global $pdo;

    // Validate inputs
    $errors = [];

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

    if (!in_array($role, ['user', 'admin'])) {
        $errors[] = "Invalid role specified";
    }

    // Check if email already exists
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "Email already exists in the system";
        }
    } catch (PDOException $e) {
        return "Database error: " . $e->getMessage();
    }

    // Check if username already exists
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE name = ?");
        $stmt->execute([$name]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "Username already exists. Please choose a different username.";
        }
    } catch (PDOException $e) {
        return "Database error: " . $e->getMessage();
    }

    // Return errors if any
    if (!empty($errors)) {
        return implode("<br>", $errors);
    }

    // Hash the password
    $hashedPassword = generatePasswordHash($password);

    // Insert the new user
    try {
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, created_at) VALUES (?, ?, ?, ?, ?)");
        $result = $stmt->execute([$name, $email, $hashedPassword, $role, getCurrentDateTime()]);

        if ($result) {
            // Log the activity
            logActivity('User Creation', "Added new user: $name ($email) with role: $role");
            return true;
        } else {
            return "Failed to create user";
        }
    } catch (PDOException $e) {
        return "Database error: " . $e->getMessage();
    }
}

/**
 * Update an existing user
 * @param int $userId User ID
 * @param string $name User's full name
 * @param string $role User's role
 * @return bool|string True on success, error message on failure
 */
function updateUser($userId, $name, $role) {
    global $pdo;

    try {
        $stmt = $pdo->prepare("UPDATE users SET name = ?, role = ? WHERE id = ?");
        $result = $stmt->execute([$name, $role, $userId]);

        if ($result) {
            logActivity('User Update', "Updated user ID: $userId - Name: $name, Role: $role");
            return true;
        } else {
            return "Failed to update user";
        }
    } catch (PDOException $e) {
        return "Database error: " . $e->getMessage();
    }
}

/**
 * Delete a user from the system
 * @param int $userId User ID
 * @return bool|string True on success, error message on failure
 */
function deleteUser($userId) {
    global $pdo;

    try {
        // Get user details for logging
        $stmt = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return "User not found";
        }

        // Prevent deletion of main admin
        if ($user['email'] === 'admin@gmail.com') {
            return "Cannot delete the main administrator account";
        }

        // Delete the user
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $result = $stmt->execute([$userId]);

        if ($result) {
            logActivity('User Deletion', "Deleted user: " . $user['name'] . " (" . $user['email'] . ")");
            return true;
        } else {
            return "Failed to delete user";
        }
    } catch (PDOException $e) {
        return "Database error: " . $e->getMessage();
    }
}

/**
 * Redirect with message
 * @param string $location Location to redirect to
 * @param string $message Message to display
 * @param string $type Message type (info, success, warning, danger)
 */
function redirectWithMessage($location, $message, $type = 'info') {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type;
    $_SESSION['message_timestamp'] = getCurrentDateTime();
    header("Location: $location");
    exit();
}

/**
 * Update user last login time
 * @param int $userId User ID
 * @return bool True on success, false on failure
 */
function updateLastLogin($userId) {
    global $pdo;

    // Check if last_login column exists, create if not
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'last_login'");
        if ($stmt->rowCount() === 0) {
            $pdo->exec("ALTER TABLE users ADD COLUMN last_login TIMESTAMP NULL");
        }

        $stmt = $pdo->prepare("UPDATE users SET last_login = ? WHERE id = ?");
        return $stmt->execute([getCurrentDateTime(), $userId]);
    } catch (PDOException $e) {
        error_log("Failed to update last login: " . $e->getMessage());
        return false;
    }
}

/**
 * Get user's last login time
 * @param int $userId User ID
 * @return string|null Last login time or null if never logged in
 */
function getLastLogin($userId) {
    global $pdo;

    try {
        $stmt = $pdo->prepare("SELECT last_login FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['last_login'] ?? null;
    } catch (PDOException $e) {
        return null;
    }
}

/**
 * Display message from session
 * @return void
 */
function displayMessage() {
    if (isset($_SESSION['message'])) {
        $type = $_SESSION['message_type'] ?? 'info';
        $timestamp = $_SESSION['message_timestamp'] ?? getCurrentDateTime();

        echo '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">';
        echo $_SESSION['message'];
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div>';

        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
        unset($_SESSION['message_timestamp']);
    }
}

/**
 * Calculate leave days (excluding weekends)
 * @param string $start_date Start date
 * @param string $end_date End date
 * @return int Number of leave days
 */
function calculateLeaveDays($start_date, $end_date) {
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    $end->modify('+1 day'); // Include the end date

    $days = 0;
    $period = new DatePeriod($start, new DateInterval('P1D'), $end);

    foreach ($period as $day) {
        // Skip weekends (6=Saturday, 7=Sunday)
        $dayOfWeek = $day->format('N');
        if ($dayOfWeek < 6) {
            $days++;
        }
    }

    return $days;
}

/**
 * Validate date format YYYY-MM-DD
 * @param string $date Date string
 * @return bool True if valid date format, false otherwise
 */
function isValidDate($date) {
    if (preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $date)) {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
    return false;
}

/**
 * Get user name by ID
 * @param int $userId User ID
 * @return string User name
 */
function getUserNameById($userId) {
    global $pdo;

    try {
        $stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['name'] : 'Unknown User';
    } catch (PDOException $e) {
        return 'Unknown User';
    }
}

/**
 * Sanitize user input to prevent XSS
 * @param string $input User input
 * @return string Sanitized input
 */
function sanitizeInput($input) {
    return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
}

/**
 * Display a dashboard information card
 * @param string $title Card title
 * @param string $value Card value
 * @param string $icon Font Awesome icon class
 * @param string $color Bootstrap color class
 * @return void
 */
function displayInfoCard($title, $value, $icon, $color = 'success') {
    echo '<div class="col-md-3">';
    echo '<div class="card bg-' . $color . ' text-white mb-4 hover-effect">';
    echo '<div class="card-body">';
    echo '<div class="d-flex justify-content-between align-items-center">';
    echo '<div>';
    echo '<h6 class="card-title">' . $title . '</h6>';
    echo '<h2 class="mb-0">' . $value . '</h2>';
    echo '</div>';
    echo '<i class="' . $icon . ' fa-3x opacity-50"></i>';
    echo '</div></div></div></div>';
}

/**
 * Log activity
 * @param string $action Action performed
 * @param string $details Details of the action
 * @param int|null $user_id User ID (null for current user)
 * @return void
 */
function logActivity($action, $details, $user_id = null) {
    global $pdo;

    // Create logs table if it doesn't exist
    createLogsTableIfNotExists();

    // Use current user if not specified
    if ($user_id === null && isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address, created_at) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $user_id,
            $action,
            $details,
            $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            getCurrentDateTime()
        ]);
    } catch (PDOException $e) {
        // Silent logging failure - don't disrupt the user experience
        error_log("Failed to log activity: " . $e->getMessage());
    }
}

/**
 * Create activity logs table if it doesn't exist
 * @return bool True if successful, false otherwise
 */
function createLogsTableIfNotExists() {
    global $pdo;

    if (!tableExists('activity_logs')) {
        try {
            $pdo->exec("CREATE TABLE activity_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NULL,
                action VARCHAR(100) NOT NULL,
                details TEXT NULL,
                ip_address VARCHAR(45) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
            )");
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
    return true;
}

/**
 * Check if a table exists in the database
 * @param string $tableName Name of the table
 * @return bool True if table exists, false otherwise
 */
function tableExists($tableName) {
    global $pdo;

    try {
        $result = $pdo->query("SHOW TABLES LIKE '{$tableName}'");
        return $result->rowCount() > 0;
    } catch (PDOException $e) {
        return false;
    }
}
?>