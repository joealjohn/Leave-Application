<?php
global $pdo;
require_once 'includes/config.php';

echo "<h2>Database Migration: Adding admin_comment column</h2>";
echo "<p>Current time: " . date('Y-m-d H:i:s') . "</p>";

try {
    // Check if admin_comment column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM leave_requests LIKE 'admin_comment'");

    if ($stmt->rowCount() == 0) {
        // Column doesn't exist, add it
        $pdo->exec("ALTER TABLE leave_requests ADD COLUMN admin_comment TEXT NULL");
        echo "<div style='color: green;'>✓ Successfully added admin_comment column to leave_requests table.</div>";
        echo "<p>The column allows administrators to add optional comments when approving or rejecting leave requests.</p>";
    } else {
        echo "<div style='color: blue;'>✓ admin_comment column already exists in the database.</div>";
    }

    // Verify the column was added
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

    echo "<br><div style='color: green; font-weight: bold;'>✅ Database schema is now up to date!</div>";
    echo "<p>You can now return to the admin panel and try approving/rejecting leave requests.</p>";

} catch (PDOException $e) {
    echo "<div style='color: red;'>❌ Error: " . $e->getMessage() . "</div>";
}
?>

<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    table { margin: 10px 0; }
    th, td { padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
</style>