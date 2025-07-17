<?php
session_start();
require_once 'database/db.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo '<div class="alert alert-danger">Access denied. Please log in as an admin.</div>';
    exit();
}

// Test comment saving
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_comment'])) {
    $event_id = $_POST['event_id'];
    $comment = $_POST['test_comment'];
    
    try {
        // First, get the bus_id from the bus_logs table
        $bus_log_stmt = $conn->prepare("SELECT bus_id FROM bus_logs WHERE id = ?");
        $bus_log_stmt->execute([$event_id]);
        $bus_log = $bus_log_stmt->fetch();
        
        if (!$bus_log) {
            $result = "Event not found";
        } else {
            $bus_id = $bus_log['bus_id'];
            
            // Check if a notification already exists for this bus_id
            $check_stmt = $conn->prepare("SELECT notification_id FROM notifications WHERE bus_id = ? AND message LIKE '%overloading%'");
            $check_stmt->execute([$bus_id]);
            $existing_notification = $check_stmt->fetch();
            
            if ($existing_notification) {
                // Update existing notification with comment
                $stmt = $conn->prepare("UPDATE notifications SET comment = ? WHERE notification_id = ?");
                $result = $stmt->execute([$comment, $existing_notification['notification_id']]) ? "Comment updated successfully" : "Failed to update comment";
            } else {
                // Create new notification with comment
                $stmt = $conn->prepare("INSERT INTO notifications (bus_id, message, comment, sent_at) VALUES (?, ?, ?, NOW())");
                $result = $stmt->execute([$bus_id, 'Overloading event detected', $comment]) ? "Comment saved successfully" : "Failed to save comment";
            }
        }
    } catch (Exception $e) {
        $result = "Database error: " . $e->getMessage();
    }
}

// Get overloading events with comments
$sql = "SELECT bl.id, bl.bus_id, bl.event, bl.passenger_count, bl.status, bl.created_at,
               b.plate_number, b.capacity,
               n.notification_id, n.message, n.sent_at, n.comment
        FROM bus_logs bl
        JOIN buses b ON bl.bus_id = b.bus_id
        LEFT JOIN notifications n ON bl.bus_id = n.bus_id AND n.message LIKE '%overloading%'
        WHERE bl.status = 'overloading'
        ORDER BY bl.created_at DESC
        LIMIT 10";

$stmt = $conn->query($sql);
$overloading_events = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Comment System Test</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body>
<div class="container mt-4">
    <h1>Comment System Test</h1>
    
    <?php if (isset($result)): ?>
        <div class="alert alert-info"><?= $result ?></div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-6">
            <h3>Test Comment Saving</h3>
            <form method="POST">
                <div class="mb-3">
                    <label for="event_id" class="form-label">Event ID:</label>
                    <input type="number" class="form-control" id="event_id" name="event_id" required>
                </div>
                <div class="mb-3">
                    <label for="test_comment" class="form-label">Test Comment:</label>
                    <textarea class="form-control" id="test_comment" name="test_comment" rows="3" required></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Save Test Comment</button>
            </form>
        </div>
        
        <div class="col-md-6">
            <h3>Database Structure Check</h3>
            <?php
            // Check notifications table structure
            $check_sql = "DESCRIBE notifications";
            $check_stmt = $conn->query($check_sql);
            $columns = $check_stmt->fetchAll(PDO::FETCH_ASSOC);
            ?>
            <table class="table table-sm">
                <thead>
                    <tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($columns as $column): ?>
                        <tr>
                            <td><?= $column['Field'] ?></td>
                            <td><?= $column['Type'] ?></td>
                            <td><?= $column['Null'] ?></td>
                            <td><?= $column['Key'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <h3>Overloading Events with Comments</h3>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Event ID</th>
                <th>Plate Number</th>
                <th>Passengers</th>
                <th>Time</th>
                <th>Notification ID</th>
                <th>Comment</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($overloading_events as $event): ?>
                <tr>
                    <td><?= $event['id'] ?></td>
                    <td><?= htmlspecialchars($event['plate_number']) ?></td>
                    <td><?= $event['passenger_count'] ?></td>
                    <td><?= $event['created_at'] ?></td>
                    <td><?= $event['notification_id'] ?: 'None' ?></td>
                    <td><?= htmlspecialchars($event['comment'] ?: 'No comment') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <h3>Raw Notifications Data</h3>
    <?php
    $notifications_sql = "SELECT * FROM notifications ORDER BY notification_id DESC LIMIT 10";
    $notifications_stmt = $conn->query($notifications_sql);
    $notifications = $notifications_stmt->fetchAll(PDO::FETCH_ASSOC);
    ?>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Notification ID</th>
                <th>Bus ID</th>
                <th>Message</th>
                <th>Sent At</th>
                <th>Status</th>
                <th>Comment</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($notifications as $notification): ?>
                <tr>
                    <td><?= $notification['notification_id'] ?></td>
                    <td><?= $notification['bus_id'] ?></td>
                    <td><?= htmlspecialchars($notification['message']) ?></td>
                    <td><?= $notification['sent_at'] ?></td>
                    <td><?= $notification['status'] ?></td>
                    <td><?= htmlspecialchars($notification['comment'] ?: 'No comment') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html> 