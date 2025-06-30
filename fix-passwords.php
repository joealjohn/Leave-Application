<?php
global $pdo;
require_once 'includes/functions.php';

// Password check utility
echo "<h2>Password Hash Verification</h2>";

// Define test accounts
$accounts = [
    ["admin@gmail.com", "admin@12345", '$2y$10$qFnmvhYoM4ywLCyuY.dwK.ZLquBkbqIzQIZhA383k4Zt2NAByR.wK'],
    ["test@gmail.com", "test@1234", '$2y$10$3fW.Y8AFl0gUpHl1o1.fiuGQgNJ5E5kz9abLF483DTwH53XMcNnvO']
];

// Check if passwords match hashes
foreach ($accounts as $account) {
    list($email, $password, $storedHash) = $account;

    // Generate a new hash
    $newHash = password_hash($password, PASSWORD_DEFAULT);

    // Verify if the stored hash matches the password
    $verify = password_verify($password, $storedHash);

    echo "<hr>";
    echo "<p><strong>Account:</strong> $email / $password</p>";
    echo "<p><strong>Stored Hash:</strong> $storedHash</p>";
    echo "<p><strong>Verification Result:</strong> " . ($verify ? "PASS ✅" : "FAIL ❌") . "</p>";

    if (!$verify) {
        echo "<p><strong>New Hash:</strong> $newHash</p>";

        // Update the password in database
        try {
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
            $result = $stmt->execute([$newHash, $email]);
            echo "<p><strong>Database Update:</strong> " . ($result ? "SUCCESS ✅" : "FAILED ❌") . "</p>";
        } catch (Exception $e) {
            echo "<p><strong>Database Error:</strong> " . $e->getMessage() . "</p>";
        }
    }
}

// Report on users in the database
echo "<hr><h3>Users in Database</h3>";
try {
    $stmt = $pdo->query("SELECT id, name, email, role FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th></tr>";
    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>{$user['id']}</td>";
        echo "<td>{$user['name']}</td>";
        echo "<td>{$user['email']}</td>";
        echo "<td>{$user['role']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "<p>Error retrieving users: " . $e->getMessage() . "</p>";
}

echo "<p><a href='login.php'>Back to Login</a></p>";
?>