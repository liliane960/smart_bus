<?php
require_once 'db.php';

echo "<h1>Fix Database Structure</h1>";

// Step 1: Check current structure
echo "<h2>1. Current Notifications Table Structure</h2>";
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

// Step 2: Drop the foreign key constraint
echo "<h2>2. Dropping Foreign Key Constraint</h2>";
try {
    $conn->query("ALTER TABLE notifications DROP FOREIGN KEY notifications_ibfk_2");
    echo "<p>✅ Successfully dropped foreign key constraint notifications_ibfk_2</p>";
} catch (Exception $e) {
    echo "<p>⚠️ Warning: " . $e->getMessage() . "</p>";
}

// Step 3: Make bus_log_id nullable
echo "<h2>3. Making bus_log_id Nullable</h2>";
try {
    $conn->query("ALTER TABLE notifications MODIFY COLUMN bus_log_id int(11) NULL");
    echo "<p>✅ Successfully made bus_log_id nullable</p>";
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}

// Step 4: Re-add the foreign key constraint (now nullable)
echo "<h2>4. Re-adding Foreign Key Constraint</h2>";
try {
    $conn->query("ALTER TABLE notifications ADD CONSTRAINT notifications_ibfk_2 FOREIGN KEY (bus_log_id) REFERENCES bus_logs(id) ON DELETE SET NULL");
    echo "<p>✅ Successfully re-added foreign key constraint with ON DELETE SET NULL</p>";
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}

// Step 5: Check final structure
echo "<h2>5. Final Notifications Table Structure</h2>";
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

// Step 6: Test inserting a notification without bus_log_id
echo "<h2>6. Test Insert Without bus_log_id</h2>";
try {
    $insertStmt = $conn->prepare("INSERT INTO notifications (bus_id, message, status, comment) VALUES (?, ?, ?, ?)");
    $result = $insertStmt->execute([1, 'Test message without bus_log_id', 'pending', 'Test comment']);
    
    if ($result) {
        echo "<p>✅ Successfully inserted test notification without bus_log_id</p>";
        
        // Clean up
        $conn->query("DELETE FROM notifications WHERE message = 'Test message without bus_log_id'");
        echo "<p>✅ Cleaned up test data</p>";
    } else {
        echo "<p>❌ Failed to insert test notification</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Error inserting test notification: " . $e->getMessage() . "</p>";
}

// Step 7: Test inserting a notification with bus_log_id
echo "<h2>7. Test Insert With bus_log_id</h2>";
try {
    // Get a valid bus_log_id
    $stmt = $conn->query("SELECT id FROM bus_logs WHERE status = 'overloading' LIMIT 1");
    $busLog = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($busLog) {
        $insertStmt = $conn->prepare("INSERT INTO notifications (bus_id, bus_log_id, message, status, comment) VALUES (?, ?, ?, ?, ?)");
        $result = $insertStmt->execute([1, $busLog['id'], 'Test message with bus_log_id', 'pending', 'Test comment with bus_log_id']);
        
        if ($result) {
            echo "<p>✅ Successfully inserted test notification with bus_log_id</p>";
            
            // Clean up
            $conn->query("DELETE FROM notifications WHERE message = 'Test message with bus_log_id'");
            echo "<p>✅ Cleaned up test data</p>";
        } else {
            echo "<p>❌ Failed to insert test notification with bus_log_id</p>";
        }
    } else {
        echo "<p>⚠️ No overloading bus logs found to test with</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Error inserting test notification with bus_log_id: " . $e->getMessage() . "</p>";
}

echo "<h2>8. Summary</h2>";
echo "<p>The database structure has been fixed to allow:</p>";
echo "<ul>";
echo "<li>Notifications without bus_log_id (nullable field)</li>";
echo "<li>Notifications with bus_log_id (foreign key to bus_logs)</li>";
echo "<li>Proper foreign key constraint with ON DELETE SET NULL</li>";
echo "</ul>";

echo "<p><strong>Next Steps:</strong></p>";
echo "<ol>";
echo "<li>Run the auto_create_notifications.php script to create notifications for existing overloading events</li>";
echo "<li>Test the notification views for admin, driver, and police</li>";
echo "<li>The comment system should now work properly</li>";
echo "</ol>";
?> 