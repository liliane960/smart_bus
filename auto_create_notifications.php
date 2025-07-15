<?php
require_once 'db.php';

echo "<h1>Auto Create Notifications from Bus Logs</h1>";

// Step 1: Check current overloading events
echo "<h2>1. Current Overloading Events</h2>";
$stmt = $conn->query("SELECT COUNT(*) as count FROM bus_logs WHERE status = 'overloading'");
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo "<p>Total overloading events in bus_logs: <strong>{$result['count']}</strong></p>";

if ($result['count'] > 0) {
    $stmt = $conn->query("SELECT bl.*, b.plate_number FROM bus_logs bl 
                         JOIN buses b ON bl.bus_id = b.bus_id 
                         WHERE bl.status = 'overloading' 
                         ORDER BY bl.created_at DESC");
    $overloadingEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>All overloading events:</p>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Bus ID</th><th>Plate Number</th><th>Event</th><th>Passengers</th><th>Status</th><th>Time</th></tr>";
    foreach ($overloadingEvents as $event) {
        echo "<tr>";
        echo "<td>{$event['id']}</td>";
        echo "<td>{$event['bus_id']}</td>";
        echo "<td>{$event['plate_number']}</td>";
        echo "<td>{$event['event']}</td>";
        echo "<td>{$event['passenger_count']}</td>";
        echo "<td>{$event['status']}</td>";
        echo "<td>{$event['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Step 2: Check which overloading events don't have notifications yet
echo "<h2>2. Overloading Events Without Notifications</h2>";
$sql = "SELECT bl.*, b.plate_number 
        FROM bus_logs bl 
        JOIN buses b ON bl.bus_id = b.bus_id 
        WHERE bl.status = 'overloading' 
        AND bl.id NOT IN (
            SELECT bus_log_id 
            FROM notifications 
            WHERE bus_log_id IS NOT NULL
        )
        ORDER BY bl.created_at DESC";
$stmt = $conn->query($sql);
$eventsWithoutNotifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<p>Overloading events without notifications: <strong>" . count($eventsWithoutNotifications) . "</strong></p>";

if ($eventsWithoutNotifications) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Log ID</th><th>Bus ID</th><th>Plate Number</th><th>Event</th><th>Passengers</th><th>Time</th></tr>";
    foreach ($eventsWithoutNotifications as $event) {
        echo "<tr>";
        echo "<td>{$event['id']}</td>";
        echo "<td>{$event['bus_id']}</td>";
        echo "<td>{$event['plate_number']}</td>";
        echo "<td>{$event['event']}</td>";
        echo "<td>{$event['passenger_count']}</td>";
        echo "<td>{$event['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Step 3: Create notifications for events without notifications
echo "<h2>3. Creating Notifications</h2>";
$createdCount = 0;
$errorCount = 0;

foreach ($eventsWithoutNotifications as $event) {
    $busId = $event['bus_id'];
    $busLogId = $event['id']; // This is the key fix - we need the bus_log_id
    $plateNumber = $event['plate_number'];
    $passengerCount = $event['passenger_count'];
    $eventType = $event['event'];
    $createdAt = $event['created_at'];
    
    // Create a meaningful comment based on the event data
    $comment = "Auto-generated: Bus {$plateNumber} had {$passengerCount} passengers during {$eventType} event at {$createdAt}";
    $message = "Overloading detected - {$passengerCount} passengers";
    $status = 'pending';
    
    try {
        // Check if notification already exists for this bus_log_id
        $checkStmt = $conn->prepare("SELECT notification_id FROM notifications WHERE bus_log_id = ?");
        $checkStmt->execute([$busLogId]);
        $existing = $checkStmt->fetch();
        
        if (!$existing) {
            // Create new notification with bus_log_id
            $insertStmt = $conn->prepare("INSERT INTO notifications (bus_id, bus_log_id, message, status, comment) VALUES (?, ?, ?, ?, ?)");
            $result = $insertStmt->execute([$busId, $busLogId, $message, $status, $comment]);
            
            if ($result) {
                echo "<p>✅ Created notification for bus {$plateNumber} (Bus ID: {$busId}, Log ID: {$busLogId})</p>";
                echo "<p style='margin-left: 20px; font-size: 12px; color: #666;'>Comment: {$comment}</p>";
                $createdCount++;
            } else {
                echo "<p>❌ Failed to create notification for bus {$plateNumber}</p>";
                $errorCount++;
            }
        } else {
            echo "<p>⚠️ Notification already exists for bus log ID {$busLogId} (bus {$plateNumber})</p>";
        }
    } catch (Exception $e) {
        echo "<p>❌ Error creating notification for bus {$plateNumber}: " . $e->getMessage() . "</p>";
        $errorCount++;
    }
}

if ($createdCount == 0 && count($eventsWithoutNotifications) == 0) {
    echo "<p>✅ All overloading events already have notifications.</p>";
} else {
    echo "<p><strong>Summary:</strong> Created {$createdCount} notifications, {$errorCount} errors</p>";
}

// Step 4: Show final result
echo "<h2>4. Final Notifications Status</h2>";
$stmt = $conn->query("SELECT COUNT(*) as count FROM notifications WHERE message LIKE '%overloading%'");
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo "<p>Total overloading notifications: <strong>{$result['count']}</strong></p>";

if ($result['count'] > 0) {
    $stmt = $conn->query("SELECT n.*, b.plate_number, bl.passenger_count, bl.event 
                         FROM notifications n 
                         JOIN buses b ON n.bus_id = b.bus_id 
                         LEFT JOIN bus_logs bl ON n.bus_log_id = bl.id
                         WHERE n.message LIKE '%overloading%'
                         ORDER BY n.sent_at DESC");
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>All overloading notifications:</p>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Bus ID</th><th>Bus Log ID</th><th>Plate Number</th><th>Message</th><th>Status</th><th>Comment</th><th>Time</th></tr>";
    foreach ($notifications as $note) {
        echo "<tr>";
        echo "<td>{$note['notification_id']}</td>";
        echo "<td>{$note['bus_id']}</td>";
        echo "<td>{$note['bus_log_id']}</td>";
        echo "<td>{$note['plate_number']}</td>";
        echo "<td>{$note['message']}</td>";
        echo "<td>{$note['status']}</td>";
        echo "<td style='max-width: 300px; word-wrap: break-word;'>" . htmlspecialchars($note['comment'] ?? 'No comment') . "</td>";
        echo "<td>{$note['sent_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Step 5: Create a trigger function for future automation
echo "<h2>5. Future Automation Setup</h2>";
echo "<p>To automatically create notifications when new overloading events occur, you can:</p>";
echo "<ol>";
echo "<li>Create a database trigger (if using MySQL 5.7+)</li>";
echo "<li>Run this script periodically</li>";
echo "<li>Integrate the logic into your bus monitoring system</li>";
echo "</ol>";

echo "<h2>6. Database Structure Note</h2>";
echo "<p><strong>Important:</strong> The notifications table now requires a bus_log_id field due to foreign key constraints.</p>";
echo "<p>When creating notifications manually, make sure to include the bus_log_id from the corresponding bus_logs record.</p>";
?> 