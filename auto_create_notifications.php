<?php
require_once 'database/db.php';

echo "<h2>Auto Create Notifications from Bus Logs</h2>";

// Function to create notification for overloading event
function createNotification($bus_log_id, $bus_id, $passenger_count, $event) {
    global $conn;
    
    // Check if notification already exists for this bus_log_id
    $check_stmt = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE bus_log_id = ?");
    $check_stmt->execute([$bus_log_id]);
    
    if ($check_stmt->fetchColumn() > 0) {
        return "Notification already exists for bus_log_id: $bus_log_id";
    }
    
    // Create notification message
    $message = "OVERLOADING ALERT: Bus has $passenger_count passengers during $event event";
    
    // Insert notification
    $insert_stmt = $conn->prepare("
        INSERT INTO notifications (bus_id, bus_log_id, message, sent_at, status, comment) 
        VALUES (?, ?, ?, NOW(), 'pending', '')
    ");
    
    if ($insert_stmt->execute([$bus_id, $bus_log_id, $message])) {
        return "Notification created successfully for bus_log_id: $bus_log_id";
    } else {
        return "Error creating notification for bus_log_id: $bus_log_id";
    }
}

// Get all overloading events from bus_logs that don't have notifications yet
$sql = "
    SELECT bl.id, bl.bus_id, bl.event, bl.passenger_count, bl.status, bl.created_at,
           b.plate_number, b.capacity
    FROM bus_logs bl
    JOIN buses b ON bl.bus_id = b.bus_id
    WHERE bl.status = 'overloading'
    AND bl.id NOT IN (SELECT bus_log_id FROM notifications WHERE bus_log_id IS NOT NULL)
    ORDER BY bl.created_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->execute();
$overloading_events = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>Found " . count($overloading_events) . " overloading events without notifications</h3>";

if (count($overloading_events) > 0) {
    echo "<table class='table table-striped'>";
    echo "<thead><tr><th>Bus Log ID</th><th>Plate Number</th><th>Event</th><th>Passengers</th><th>Capacity</th><th>Overload %</th><th>Created At</th><th>Action</th></tr></thead>";
    echo "<tbody>";
    
    foreach ($overloading_events as $event) {
        $overload_percentage = round(($event['passenger_count'] / $event['capacity']) * 100);
        $overload_class = $overload_percentage > 150 ? 'bg-danger' : 'bg-warning';
        
        echo "<tr>";
        echo "<td>{$event['id']}</td>";
        echo "<td><strong>{$event['plate_number']}</strong></td>";
        echo "<td>{$event['event']}</td>";
        echo "<td><span class='badge bg-danger'>{$event['passenger_count']}</span></td>";
        echo "<td>{$event['capacity']}</td>";
        echo "<td><span class='badge {$overload_class}'>{$overload_percentage}%</span></td>";
        echo "<td>{$event['created_at']}</td>";
        echo "<td>";
        
        // Create notification button
        echo "<form method='post' style='display:inline;'>";
        echo "<input type='hidden' name='create_notification' value='{$event['id']}'>";
        echo "<button type='submit' class='btn btn-primary btn-sm'>Create Notification</button>";
        echo "</form>";
        
        echo "</td>";
        echo "</tr>";
    }
    
    echo "</tbody></table>";
    
    // Create all notifications button
    echo "<form method='post' class='mt-3'>";
    echo "<input type='hidden' name='create_all_notifications' value='1'>";
    echo "<button type='submit' class='btn btn-success btn-lg'>Create All Notifications</button>";
    echo "</form>";
    
} else {
    echo "<div class='alert alert-success'>All overloading events already have notifications!</div>";
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_notification'])) {
        $bus_log_id = $_POST['create_notification'];
        
        // Get bus log details
        $stmt = $conn->prepare("
            SELECT bl.bus_id, bl.event, bl.passenger_count 
            FROM bus_logs bl 
            WHERE bl.id = ?
        ");
        $stmt->execute([$bus_log_id]);
        $bus_log = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($bus_log) {
            $result = createNotification($bus_log_id, $bus_log['bus_id'], $bus_log['passenger_count'], $bus_log['event']);
            echo "<div class='alert alert-info'>$result</div>";
        }
        
    } elseif (isset($_POST['create_all_notifications'])) {
        $created_count = 0;
        $errors = [];
        
        foreach ($overloading_events as $event) {
            $result = createNotification($event['id'], $event['bus_id'], $event['passenger_count'], $event['event']);
            if (strpos($result, 'successfully') !== false) {
                $created_count++;
            } else {
                $errors[] = $result;
            }
        }
        
        echo "<div class='alert alert-success'>Created $created_count notifications successfully!</div>";
        if (!empty($errors)) {
            echo "<div class='alert alert-warning'>Some errors occurred: " . implode(', ', $errors) . "</div>";
        }
    }
}

// Show current notifications
echo "<h3>Current Notifications</h3>";
$notifications_sql = "
    SELECT n.notification_id, n.bus_id, n.bus_log_id, n.message, n.sent_at, n.status,
           b.plate_number, bl.passenger_count, bl.event
    FROM notifications n
    JOIN buses b ON n.bus_id = b.bus_id
    LEFT JOIN bus_logs bl ON n.bus_log_id = bl.id
    WHERE n.message LIKE '%OVERLOADING%'
    ORDER BY n.sent_at DESC
    LIMIT 10
";

$notifications_stmt = $conn->prepare($notifications_sql);
$notifications_stmt->execute();
$notifications = $notifications_stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($notifications) > 0) {
    echo "<table class='table table-striped'>";
    echo "<thead><tr><th>ID</th><th>Plate Number</th><th>Message</th><th>Status</th><th>Sent At</th></tr></thead>";
    echo "<tbody>";
    
    foreach ($notifications as $notification) {
        echo "<tr>";
        echo "<td>{$notification['notification_id']}</td>";
        echo "<td><strong>{$notification['plate_number']}</strong></td>";
        echo "<td>{$notification['message']}</td>";
        echo "<td><span class='badge bg-warning'>{$notification['status']}</span></td>";
        echo "<td>{$notification['sent_at']}</td>";
        echo "</tr>";
    }
    
    echo "</tbody></table>";
} else {
    echo "<div class='alert alert-info'>No notifications found.</div>";
}

echo "<div class='mt-4'>";
echo "<a href='dashboard/police_dashboard.php' class='btn btn-secondary'>Back to Police Dashboard</a>";
echo "</div>";
?>

<style>
body { padding: 20px; font-family: Arial, sans-serif; }
.table { margin-top: 20px; }
.alert { margin-top: 20px; }
</style> 