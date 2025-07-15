<?php
require_once 'db.php';

echo "<h1>Database Structure Check</h1>";

// Check notifications table structure
echo "<h2>1. Notifications Table Structure</h2>";
try {
    $stmt = $conn->query("DESCRIBE notifications");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "<td>{$column['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}

// Check foreign key constraints
echo "<h2>2. Foreign Key Constraints</h2>";
try {
    $stmt = $conn->query("SELECT 
        CONSTRAINT_NAME,
        COLUMN_NAME,
        REFERENCED_TABLE_NAME,
        REFERENCED_COLUMN_NAME
        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
        WHERE TABLE_SCHEMA = 'smart_bus' 
        AND TABLE_NAME = 'notifications' 
        AND REFERENCED_TABLE_NAME IS NOT NULL");
    $constraints = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($constraints) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Constraint Name</th><th>Column</th><th>Referenced Table</th><th>Referenced Column</th></tr>";
        foreach ($constraints as $constraint) {
            echo "<tr>";
            echo "<td>{$constraint['CONSTRAINT_NAME']}</td>";
            echo "<td>{$constraint['COLUMN_NAME']}</td>";
            echo "<td>{$constraint['REFERENCED_TABLE_NAME']}</td>";
            echo "<td>{$constraint['REFERENCED_COLUMN_NAME']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No foreign key constraints found on notifications table.</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}

// Check if bus_log_id column exists
echo "<h2>3. Check for bus_log_id Column</h2>";
try {
    $stmt = $conn->query("SHOW COLUMNS FROM notifications LIKE 'bus_log_id'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        echo "<p>✅ bus_log_id column exists</p>";
        echo "<p>Type: {$result['Type']}</p>";
        echo "<p>Null: {$result['Null']}</p>";
        echo "<p>Key: {$result['Key']}</p>";
    } else {
        echo "<p>❌ bus_log_id column does NOT exist</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}

// Check bus_logs table structure
echo "<h2>4. Bus Logs Table Structure</h2>";
try {
    $stmt = $conn->query("DESCRIBE bus_logs");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "<td>{$column['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}

// Test inserting a notification without bus_log_id
echo "<h2>5. Test Notification Insert</h2>";
try {
    $insertStmt = $conn->prepare("INSERT INTO notifications (bus_id, message, status, comment) VALUES (?, ?, ?, ?)");
    $result = $insertStmt->execute([1, 'Test message', 'pending', 'Test comment']);
    
    if ($result) {
        echo "<p>✅ Successfully inserted test notification</p>";
        
        // Clean up
        $conn->query("DELETE FROM notifications WHERE message = 'Test message'");
        echo "<p>✅ Cleaned up test data</p>";
    } else {
        echo "<p>❌ Failed to insert test notification</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Error inserting test notification: " . $e->getMessage() . "</p>";
}
?> 