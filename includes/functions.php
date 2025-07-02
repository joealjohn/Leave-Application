<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = 'localhost';
$dbname = 'leave_management';
$username = 'root';
$password = '';

if (!defined('LOGOUT_PROCESS')) {
    try {
        $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        $pdo = new PDO($dsn, $username, $password, $options);
    } catch (PDOException $e) {
        error_log("Database Error: " . $e->getMessage());

        if (!isset($_SERVER['HTTP_REFERER']) || strpos($_SERVER['HTTP_REFERER'], 'logout.php') === false) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function redirectWithMessage($location, $message, $type = 'info') {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type;
    header("Location: $location");
    exit();
}

function displayMessage() {
    if (isset($_SESSION['message']) && isset($_SESSION['message_type'])) {
        $message = $_SESSION['message'];
        $type = $_SESSION['message_type'];

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

        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
    }
}

function formatDateForDisplay($date, $format = 'Y-m-d') {
    if (empty($date)) return 'N/A';

    $dateObj = new DateTime($date);
    return $dateObj->format($format);
}

if (!function_exists('isValidDate')) {
    function isValidDate($date, $format = 'Y-m-d') {
        if (empty($date)) {
            return false;
        }

        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }
}

function getCurrentDateTime() {
    return date('Y-m-d H:i:s');
}

function calculateLeaveDays($startDate, $endDate) {
    $start = new DateTime($startDate);
    $end = new DateTime($endDate);
    $end->modify('+1 day');

    $interval = new DateInterval('P1D');
    $dateRange = new DatePeriod($start, $interval, $end);

    $workingDays = 0;

    foreach ($dateRange as $date) {
        $dayOfWeek = $date->format('N');
        if ($dayOfWeek < 6) {
            $workingDays++;
        }
    }

    return $workingDays;
}

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

function logActivity($action, $details, $userId = null) {
    global $pdo;

    if (!isset($pdo) || defined('LOGOUT_PROCESS')) {
        return;
    }

    if ($userId === null && isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$userId, $action, $details]);
    } catch (PDOException $e) {
        error_log("Error logging activity: " . $e->getMessage());
    }
}

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

function hasLeaveBalance($userId, $leaveType, $days) {
    global $pdo;

    if (!isset($pdo) || defined('LOGOUT_PROCESS')) {
        return false;
    }

    try {
        $stmt = $pdo->prepare("SELECT balance FROM leave_balances WHERE user_id = ? AND leave_type = ?");
        $stmt->execute([$userId, $leaveType]);
        $balance = $stmt->fetchColumn();

        if ($balance === false) {
            $balance = 0;
        }

        return $balance >= $days;
    } catch (PDOException $e) {
        error_log("Error checking leave balance: " . $e->getMessage());
        return false;
    }
}

function updateLeaveBalance($userId, $leaveType, $days) {
    global $pdo;

    if (!isset($pdo) || defined('LOGOUT_PROCESS')) {
        return false;
    }

    try {
        $stmt = $pdo->prepare("SELECT id, balance FROM leave_balances WHERE user_id = ? AND leave_type = ?");
        $stmt->execute([$userId, $leaveType]);
        $balanceRecord = $stmt->fetch();

        if ($balanceRecord) {
            $newBalance = $balanceRecord['balance'] - $days;
            $stmt = $pdo->prepare("UPDATE leave_balances SET balance = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$newBalance, $balanceRecord['id']]);
        } else {
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

function getDefaultLeaveAllocation($leaveType) {
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

function generateRandomPassword($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*()';
    $password = '';
    $max = strlen($characters) - 1;

    for ($i = 0; $i < $length; $i++) {
        $password .= $characters[mt_rand(0, $max)];
    }

    return $password;
}

function generatePasswordHash($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function sendEmail($to, $subject, $message) {
    $headers = 'From: no-reply@leavemanagement.com' . "\r\n" .
        'Reply-To: no-reply@leavemanagement.com' . "\r\n" .
        'Content-type: text/html; charset=UTF-8' . "\r\n" .
        'X-Mailer: PHP/' . phpversion();

    return mail($to, $subject, $message, $headers);
}

function hasLeaveOverlap($userId, $startDate, $endDate, $excludeRequestId = null) {
    global $pdo;

    if (!isset($pdo) || defined('LOGOUT_PROCESS')) {
        return false;
    }

    try {
        $checkTable = $pdo->query("SHOW TABLES LIKE 'leave_requests'");
        if ($checkTable->rowCount() === 0) {
            return false;
        }

        $checkRequests = $pdo->prepare("SELECT COUNT(*) FROM leave_requests WHERE user_id = ?");
        $checkRequests->execute([$userId]);
        if ($checkRequests->fetchColumn() == 0) {
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
?>