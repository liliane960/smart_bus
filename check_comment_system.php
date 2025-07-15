<?php
require_once 'db.php';

echo "<h1>Comment System Check</h1>";

// Check 1: Count of overloading events
echo "<h2>1. Overloading Events Check</h2>";
$stmt = $conn->query("SELECT COUNT(*) as count FROM bus_logs WHERE status = 'overloading'");
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo "<p>Total overloading events: <strong>{$result['count']}</strong></p>";

if ($result['count'] > 0) {
    $stmt = $conn->query("SELECT bl.*, b.plate_number FROM bus_logs bl 
                         JOIN buses b ON bl.bus_id = b.bus_id 
                         WHERE bl.status = 'overloading' 
                         ORDER BY bl.created_at DESC LIMIT 5");
    $overloadingEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Recent overloading events:</p>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Bus ID</th><th>Plate Number</th><th>Event</th><th>Passengers</th><th>Status</th><th>Time</th></tr>";
    foreach ($overloadingEvents as $event) {
        echo "<tr>";
        echo "<td>{$event['bus_id']}</td>";
        echo "<td>{$event['plate_number']}</td>";
        echo "<td>{$event['event']}</td>";
        echo "<td>{$event['passenger_count']}</td>";
        echo "<td>{$event['status']}</td>";
        echo "<td>{$event['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>❌ No overloading events found in bus_logs table.</p>";
}

// Check 2: Count of notifications
echo "<h2>2. Notifications Check</h2>";
$stmt = $conn->query("SELECT COUNT(*) as count FROM notifications");
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo "<p>Total notifications: <strong>{$result['count']}</strong></p>";

if ($result['count'] > 0) {
    $stmt = $conn->query("SELECT n.*, b.plate_number FROM notifications n 
                         JOIN buses b ON n.bus_id = b.bus_id 
                         ORDER BY n.sent_at DESC LIMIT 5");
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Recent notifications:</p>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Bus ID</th><th>Plate Number</th><th>Message</th><th>Status</th><th>Comment</th><th>Time</th></tr>";
    foreach ($notifications as $note) {
        echo "<tr>";
        echo "<td>{$note['notification_id']}</td>";
        echo "<td>{$note['bus_id']}</td>";
        echo "<td>{$note['plate_number']}</td>";
        echo "<td>{$note['message']}</td>";
        echo "<td>{$note['status']}</td>";
        echo "<td>" . ($note['comment'] ?? 'No comment') . "</td>";
        echo "<td>{$note['sent_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>❌ No notifications found in notifications table.</p>";
}

// Check 3: Buses with overloading but no notifications
echo "<h2>3. Buses with Overloading but No Notifications</h2>";
$sql = "SELECT DISTINCT bl.bus_id, b.plate_number, COUNT(bl.id) as overloading_count
        FROM bus_logs bl
        JOIN buses b ON bl.bus_id = b.bus_id
        WHERE bl.status = 'overloading'
        AND bl.bus_id NOT IN (SELECT DISTINCT bus_id FROM notifications WHERE message LIKE '%overloading%')
        GROUP BY bl.bus_id, b.plate_number";
$stmt = $conn->query($sql);
$busesWithoutNotifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($busesWithoutNotifications) {
    echo "<p>Buses with overloading events but no notifications:</p>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Bus ID</th><th>Plate Number</th><th>Overloading Events</th></tr>";
    foreach ($busesWithoutNotifications as $bus) {
        echo "<tr>";
        echo "<td>{$bus['bus_id']}</td>";
        echo "<td>{$bus['plate_number']}</td>";
        echo "<td>{$bus['overloading_count']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>✅ All buses with overloading events have notifications.</p>";
}

// Create test data if needed
echo "<h2>4. Create Test Data</h2>";

if ($result['count'] == 0) {
    echo "<p>No notifications found. Creating test notifications...</p>";
    
    // Get buses that have overloading events
    $stmt = $conn->query("SELECT DISTINCT bl.bus_id, b.plate_number 
                         FROM bus_logs bl 
                         JOIN buses b ON bl.bus_id = b.bus_id 
                         WHERE bl.status = 'overloading' 
                         LIMIT 3");
    $busesWithOverloading = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($busesWithOverloading) {
        foreach ($busesWithOverloading as $bus) {
            $messageText = "Overloading detected";
            $status = 'pending';
            $comment = "Test comment for " . $bus['plate_number'];
            
            try {
                $insert = $conn->prepare("INSERT INTO notifications 
                    (bus_id, message, status, comment) 
                    VALUES (?, ?, ?, ?)");
                $result = $insert->execute([$bus['bus_id'], $messageText, $status, $comment]);
                
                if ($result) {
                    echo "<p>✅ Created test notification for bus {$bus['plate_number']}</p>";
                } else {
                    echo "<p>❌ Failed to create notification for bus {$bus['plate_number']}</p>";
                }
            } catch (Exception $e) {
                echo "<p>❌ Error creating notification: " . $e->getMessage() . "</p>";
            }
        }
    } else {
        echo "<p>No buses with overloading events found. Creating test overloading event...</p>";
        
        // Create a test overloading event
        try {
            $insert = $conn->prepare("INSERT INTO bus_logs (bus_id, event, passenger_count, status) VALUES (?, ?, ?, ?)");
            $result = $insert->execute([1, 'entry', 25, 'overloading']);
            
            if ($result) {
                echo "<p>✅ Created test overloading event for bus ID 1</p>";
                
                // Create notification for this event
                $insert = $conn->prepare("INSERT INTO notifications (bus_id, message, status, comment) VALUES (?, ?, ?, ?)");
                $result = $insert->execute([1, 'Overloading detected', 'pending', 'Test comment for overloading event']);
                
                if ($result) {
                    echo "<p>✅ Created test notification for the overloading event</p>";
                }
            } else {
                echo "<p>❌ Failed to create test overloading event</p>";
            }
        } catch (Exception $e) {
            echo "<p>❌ Error creating test data: " . $e->getMessage() . "</p>";
        }
    }
} else {
    echo "<p>✅ Test data already exists.</p>";
}

echo "<h2>5. Test the Comment System</h2>";
echo "<p>Now you can test the comment system:</p>";
echo "<ul>";
echo "<li><a href='admin/view_notifications.php' target='_blank'>Admin View Notifications</a></li>";
echo "<li><a href='driver/view_notifications.php' target='_blank'>Driver View Notifications</a></li>";
echo "<li><a href='police/view_notifications.php' target='_blank'>Police View Notifications</a></li>";
echo "</ul>";

echo "<h2>6. How to Add Comments</h2>";
echo "<ol>";
echo "<li>Login as admin or driver</li>";
echo "<li>Go to View Notifications</li>";
echo "<li>You'll see buses with overloading status</li>";
echo "<li>Click 'Add' button to create a notification with comment</li>";
echo "<li>Or click 'Save' to update existing comments</li>";
echo "<li>Police can only view comments, not edit them</li>";
echo "</ol>";

echo "<p><a href='login.php' class='btn btn-primary'>Go to Login</a></p>";
?> 