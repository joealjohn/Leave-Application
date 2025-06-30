<?php
global $pdo;
require_once 'includes/functions.php';

echo "<h2>Database Migration: Adding admin_comment column</h2>";
echo "<p>Current time: " . getCurrentDateTime() . "</p>";

try {
    $stmt = $pdo->query("SHOW COLUMNS FROM leave_requests LIKE 'admin_comment'");

    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE leave_requests ADD COLUMN admin_comment TEXT NULL");
        echo "<div style='color: green;'>✓ Successfully added admin_comment column to leave_requests table.</div>";
        echo "<p>The column allows administrators to add optional comments when approving or rejecting leave requests.</p>";
    } else {
        echo "<div style='color: blue;'>✓ admin_comment column already exists in the database.</div>";
    }

    $stmt = $pdo->query("DESCRIBE leave_requests");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<h3>Current leave_requests table structure:</h3>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";

    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . $column['Field'] . "</td>";
        echo "<td>" . $column['Type'] . "</td>";
        echo "<td>" . $column['Null'] . "</td>";
        echo "<td>" . $column['Key'] . "</td>";
        echo "<td>" . $column['Default'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";

    $pdo = null;
    $pdo = getPDO();

    echo "<br><div style='color: green; font-weight: bold;'>✅ Database schema is now up to date!</div>";
    echo "<p>You can now return to the admin panel and try approving/rejecting leave requests.</p>";
    echo "<p><a href='admin/all_requests.php' class='btn btn-primary'>Go to All Requests</a></p>";

} catch (PDOException $e) {
    echo "<div style='color: red;'>❌ Error: " . $e->getMessage() . "</div>";
}
?>

<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    table { margin: 10px 0; }
    th, td { padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
    .btn {
        display: inline-block;
        padding: 6px 12px;
        background-color: #0d6efd;
        color: white;
        text-decoration: none;
        border-radius: 4px;
    }
    .btn:hover {
        background-color: #0b5ed7;
    }
</style>