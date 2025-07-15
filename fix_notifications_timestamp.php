<?php
require_once 'database/db.php';

echo "<h2>Fixing Notifications Timestamps</h2>";

try {
    // First, let's see how many notifications have NULL sent_at
    $stmt = $conn->query("SELECT COUNT(*) as null_count FROM notifications WHERE sent_at IS NULL");
    $nullCount = $stmt->fetch()['null_count'];
    
    echo "<p>Found {$nullCount} notifications with NULL sent_at values.</p>";
    
    if ($nullCount > 0) {
        // Update notifications with NULL sent_at to use current timestamp
        $updateStmt = $conn->prepare("UPDATE notifications SET sent_at = CURRENT_TIMESTAMP WHERE sent_at IS NULL");
        $updateStmt->execute();
        
        $affectedRows = $updateStmt->rowCount();
        echo "<p>Updated {$affectedRows} notifications with current timestamp.</p>";
        
        // Show the updated records
        echo "<h3>Updated Notifications:</h3>";
        $stmt = $conn->query("SELECT notification_id, bus_id, message, sent_at, comment FROM notifications ORDER BY notification_id DESC LIMIT 10");
        $notifications = $stmt->fetchAll();
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Bus ID</th><th>Message</th><th>Sent At</th><th>Comment</th></tr>";
        
        foreach ($notifications as $row) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['notification_id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['bus_id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['message']) . "</td>";
            echo "<td>" . htmlspecialchars($row['sent_at']) . "</td>";
            echo "<td>" . htmlspecialchars($row['comment']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } else {
        echo "<p>All notifications already have proper timestamps.</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<p><a href='admin/view_notifications.php'>Back to Notifications</a></p>";
?> 