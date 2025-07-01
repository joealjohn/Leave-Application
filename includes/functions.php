<?php
// functions.php - Start of file
// Check if session is already started before starting a new one
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define database connection parameters
$host = 'localhost';
$dbname = 'leave_management';  // Confirmed database name
$username = 'root';            // Default XAMPP username
$password = '';                // Default XAMPP password (empty)

// Only attempt database connection if not in logout process
if (!defined('LOGOUT_PROCESS')) {
    try {
        // Database connection
        $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        $pdo = new PDO($dsn, $username, $password, $options);
    } catch (PDOException $e) {
        // Handle connection error but don't display details to users in production
        error_log("Database Error: " . $e->getMessage());

        // Only show error if not in logout process
        if (!isset($_SERVER['HTTP_REFERER']) || strpos($_SERVER['HTTP_REFERER'], 'logout.php') === false) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
}

/**
 * Check if user is logged in
 * @return bool True if user is logged in, false otherwise
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if logged in user is admin
 * @return bool True if user is admin, false otherwise
 */
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Redirect with a message that will be displayed on the target page
 * @param string $location The URL to redirect to
 * @param string $message The message to display
 * @param string $type The message type (success, warning, danger, info)
 */
function redirectWithMessage($location, $message, $type = 'info') {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type;
    header("Location: $location");
    exit();
}

/**
 * Display message from session if available
 */
function displayMessage() {
    if (isset($_SESSION['message']) && isset($_SESSION['message_type'])) {
        $message = $_SESSION['message'];
        $type = $_SESSION['message_type'];

        // Map message type to Bootstrap alert class
        $alertClass = 'alert-info';
        switch ($type) {
            case 'success':
                $alertClass = 'alert-success';
                $icon = 'check-circle';
                break;
            case 'warning':
                $alertClass = 'alert-warning';
                $icon = 'exclamation-triangle';
                break;
            case 'danger':
            case 'error':
                $alertClass = 'alert-danger';
                $icon = 'times-circle';
                break;
            default:
                $alertClass = 'alert-info';
                $icon = 'info-circle';
                break;
        }

        echo '<div class="alert ' . $alertClass . ' alert-dismissible fade show" role="alert">';
        echo '<i class="fas fa-' . $icon . ' me-2"></i>' . $message;
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div>';

        // Clear the message from session
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
    }
}

/**
 * Format date for display
 * @param string $date The date string to format
 * @param string $format The format to use (default: Y-m-d)
 * @return string Formatted date
 */
function formatDateForDisplay($date, $format = 'Y-m-d') {
    if (empty($date)) return 'N/A';

    $dateObj = new DateTime($date);
    return $dateObj->format($format);
}

/**
 * Check if a date string is valid
 * @param string $date Date string to check
 * @param string $format Expected format (default: Y-m-d)
 * @return bool True if valid date, false otherwise
 */
function isValidDate($date, $format = 'Y-m-d') {
    if (empty($date)) {
        return false;
    }

    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

/**
 * Get current date and time in MySQL format (Y-m-d H:i:s)
 * @return string Current date and time
 */
function getCurrentDateTime() {
    return date('Y-m-d H:i:s');
}

/**
 * Calculate working days between two dates (excluding weekends)
 * @param string $startDate Start date
 * @param string $endDate End date
 * @return int Number of working days
 */
function calculateLeaveDays($startDate, $endDate) {
    $start = new DateTime($startDate);
    $end = new DateTime($endDate);
    $end->modify('+1 day'); // Include end date

    $interval = new DateInterval('P1D');
    $dateRange = new DatePeriod($start, $interval, $end);

    $workingDays = 0;

    foreach ($dateRange as $date) {
        $dayOfWeek = $date->format('N');
        // Skip weekends (6=Saturday, 7=Sunday)
        if ($dayOfWeek < 6) {
            $workingDays++;
        }
    }

    return $workingDays;
}

/**
 * Get user name by ID
 * @param int $userId User ID
 * @return string User's name or 'Unknown User' if not found
 */
function getUserName($userId) {
    global $pdo;

    if (!isset($pdo) || defined('LOGOUT_PROCESS')) {
        return "Unknown User";
    }

    try {
        $stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();

        return $result ? $result['name'] : 'Unknown User';
    } catch (PDOException $e) {
        error_log("Error getting user name: " . $e->getMessage());
        return 'Unknown User';
    }
}

/**
 * Get user details by ID
 * @param int $userId User ID
 * @return array|null User details or null if not found
 */
function getUserDetails($userId) {
    global $pdo;

    if (!isset($pdo) || defined('LOGOUT_PROCESS')) {
        return null;
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error getting user details: " . $e->getMessage());
        return null;
    }
}

/**
 * Log activity for auditing purposes
 * @param string $action The action performed
 * @param string $details Additional details about the action
 * @param int $userId User ID who performed the action (defaults to current user)
 */
function logActivity($action, $details, $userId = null) {
    global $pdo;

    if (!isset($pdo) || defined('LOGOUT_PROCESS')) {
        return;
    }

    // Use current user ID if not specified
    if ($userId === null && isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$userId, $action, $details]);
    } catch (PDOException $e) {
        // Just log the error but don't stop the script
        error_log("Error logging activity: " . $e->getMessage());
    }
}

/**
 * Clean and sanitize input data
 * @param mixed $data Data to sanitize
 * @return mixed Sanitized data
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = sanitizeInput($value);
        }
    } else {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
    }
    return $data;
}

/**
 * Get leave type name by ID
 * @param int $leaveTypeId Leave type ID
 * @return string Leave type name or 'Unknown' if not found
 */
function getLeaveTypeName($leaveTypeId) {
    global $pdo;

    if (!isset($pdo) || defined('LOGOUT_PROCESS')) {
        return "Unknown";
    }

    try {
        $stmt = $pdo->prepare("SELECT name FROM leave_types WHERE id = ?");
        $stmt->execute([$leaveTypeId]);
        $result = $stmt->fetch();

        return $result ? $result['name'] : 'Unknown';
    } catch (PDOException $e) {
        error_log("Error getting leave type: " . $e->getMessage());
        return 'Unknown';
    }
}

/**
 * Get all available leave types
 * @return array Array of leave types
 */
function getAllLeaveTypes() {
    global $pdo;

    if (!isset($pdo) || defined('LOGOUT_PROCESS')) {
        return [];
    }

    try {
        $stmt = $pdo->query("SELECT * FROM leave_types ORDER BY name");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error getting leave types: " . $e->getMessage());
        return [];
    }
}

/**
 * Check if user has enough leave balance for requested leave
 * @param int $userId User ID
 * @param string $leaveType Type of leave
 * @param int $days Number of days requested
 * @return bool True if user has enough balance, false otherwise
 */
function hasLeaveBalance($userId, $leaveType, $days) {
    global $pdo;

    if (!isset($pdo) || defined('LOGOUT_PROCESS')) {
        return false;
    }

    try {
        // Get user's leave balance for specified leave type
        $stmt = $pdo->prepare("SELECT balance FROM leave_balances WHERE user_id = ? AND leave_type = ?");
        $stmt->execute([$userId, $leaveType]);
        $balance = $stmt->fetchColumn();

        // If no balance record found, assume 0 balance
        if ($balance === false) {
            $balance = 0;
        }

        // Check if user has enough balance
        return $balance >= $days;
    } catch (PDOException $e) {
        error_log("Error checking leave balance: " . $e->getMessage());
        return false;
    }
}

/**
 * Update user's leave balance after approval/rejection
 * @param int $userId User ID
 * @param string $leaveType Type of leave
 * @param int $days Number of days to deduct (negative to add back)
 * @return bool True if update successful, false otherwise
 */
function updateLeaveBalance($userId, $leaveType, $days) {
    global $pdo;

    if (!isset($pdo) || defined('LOGOUT_PROCESS')) {
        return false;
    }

    try {
        // Check if user already has a balance record for this leave type
        $stmt = $pdo->prepare("SELECT id, balance FROM leave_balances WHERE user_id = ? AND leave_type = ?");
        $stmt->execute([$userId, $leaveType]);
        $balanceRecord = $stmt->fetch();

        if ($balanceRecord) {
            // Update existing balance
            $newBalance = $balanceRecord['balance'] - $days;
            $stmt = $pdo->prepare("UPDATE leave_balances SET balance = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$newBalance, $balanceRecord['id']]);
        } else {
            // Create new balance record with default allocation minus requested days
            $defaultAllocation = getDefaultLeaveAllocation($leaveType);
            $newBalance = $defaultAllocation - $days;
            $stmt = $pdo->prepare("INSERT INTO leave_balances (user_id, leave_type, balance, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
            $stmt->execute([$userId, $leaveType, $newBalance]);
        }

        return true;
    } catch (PDOException $e) {
        error_log("Error updating leave balance: " . $e->getMessage());
        return false;
    }
}

/**
 * Get default leave allocation for a leave type
 * @param string $leaveType Type of leave
 * @return int Default allocation
 */
function getDefaultLeaveAllocation($leaveType) {
    // Default allocations by leave type
    $allocations = [
        'annual' => 20,
        'sick' => 10,
        'personal' => 5,
        'vacation' => 15,
        'maternity' => 90,
        'paternity' => 14,
        'unpaid' => 0,
        'emergency' => 3,
        'bereavement' => 5
    ];

    return $allocations[$leaveType] ?? 0;
}

/**
 * Generate a random password
 * @param int $length Password length
 * @return string Random password
 */
function generateRandomPassword($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*()';
    $password = '';
    $max = strlen($characters) - 1;

    for ($i = 0; $i < $length; $i++) {
        $password .= $characters[mt_rand(0, $max)];
    }

    return $password;
}

/**
 * Send email notification
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $message Email message
 * @return bool True if email sent, false otherwise
 */
function sendEmail($to, $subject, $message) {
    // Simple mail function - in production, consider using a proper email library like PHPMailer
    $headers = 'From: no-reply@leavemanagement.com' . "\r\n" .
        'Reply-To: no-reply@leavemanagement.com' . "\r\n" .
        'Content-type: text/html; charset=UTF-8' . "\r\n" .
        'X-Mailer: PHP/' . phpversion();

    return mail($to, $subject, $message, $headers);
}

/**
 * Check if a date range overlaps with existing leave requests for a user
 * @param int $userId User ID
 * @param string $startDate Start date
 * @param string $endDate End date
 * @param int $excludeRequestId Request ID to exclude (for updates)
 * @return bool True if overlap exists, false otherwise
 */
function hasLeaveOverlap($userId, $startDate, $endDate, $excludeRequestId = null) {
    global $pdo;

    if (!isset($pdo) || defined('LOGOUT_PROCESS')) {
        return false;
    }

    try {
        // Check if the leave_requests table exists before running the query
        $checkTable = $pdo->query("SHOW TABLES LIKE 'leave_requests'");
        if ($checkTable->rowCount() === 0) {
            // Table doesn't exist yet
            return false;
        }

        // Check if the user has any leave requests
        $checkRequests = $pdo->prepare("SELECT COUNT(*) FROM leave_requests WHERE user_id = ?");
        $checkRequests->execute([$userId]);
        if ($checkRequests->fetchColumn() == 0) {
            // No leave requests for this user
            return false;
        }

        $sql = "SELECT COUNT(*) FROM leave_requests 
                WHERE user_id = ? 
                AND status != 'rejected'
                AND ((start_date BETWEEN ? AND ?) OR (end_date BETWEEN ? AND ?) OR (start_date <= ? AND end_date >= ?))";

        $params = [$userId, $startDate, $endDate, $startDate, $endDate, $startDate, $endDate];

        if ($excludeRequestId) {
            $sql .= " AND id != ?";
            $params[] = $excludeRequestId;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        error_log("Error checking leave overlap: " . $e->getMessage());
        return false;
    }
}

/**
 * Get public holidays within a date range
 * @param string $startDate Start date
 * @param string $endDate End date
 * @return array Array of holiday dates
 */
function getHolidaysInRange($startDate, $endDate) {
    global $pdo;

    if (!isset($pdo) || defined('LOGOUT_PROCESS')) {
        return [];
    }

    try {
        $stmt = $pdo->prepare("SELECT date FROM holidays WHERE date BETWEEN ? AND ?");
        $stmt->execute([$startDate, $endDate]);

        $holidays = [];
        while ($row = $stmt->fetch()) {
            $holidays[] = $row['date'];
        }

        return $holidays;
    } catch (PDOException $e) {
        error_log("Error getting holidays: " . $e->getMessage());
        return [];
    }
}

/**
 * Check if a given date is a holiday
 * @param string $date Date to check
 * @return bool True if holiday, false otherwise
 */
function isHoliday($date) {
    global $pdo;

    if (!isset($pdo) || defined('LOGOUT_PROCESS')) {
        return false;
    }

    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM holidays WHERE date = ?");
        $stmt->execute([$date]);

        return $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        error_log("Error checking holiday: " . $e->getMessage());
        return false;
    }
}

// End of file
?>