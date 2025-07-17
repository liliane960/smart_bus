<?php
session_start();
require_once 'database/db.php';

// Test the comment system with debugging
echo "<h2>Comment System Debug Test</h2>";

// Check if we have overloading events
$sql = "SELECT bl.id, bl.bus_id, bl.event, bl.passenger_count, bl.status, bl.created_at,
               b.plate_number
        FROM bus_logs bl
        JOIN buses b ON bl.bus_id = b.bus_id
        WHERE bl.status = 'overloading'
        ORDER BY bl.created_at DESC
        LIMIT 3";

$stmt = $conn->query($sql);
$overloading_events = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>Overloading Events Found:</h3>";
if ($overloading_events) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Bus ID</th><th>Plate Number</th><th>Passengers</th><th>Status</th></tr>";
    foreach ($overloading_events as $event) {
        echo "<tr>";
        echo "<td>" . $event['id'] . "</td>";
        echo "<td>" . $event['bus_id'] . "</td>";
        echo "<td>" . $event['plate_number'] . "</td>";
        echo "<td>" . $event['passenger_count'] . "</td>";
        echo "<td>" . $event['status'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No overloading events found.</p>";
}

// Check notifications
echo "<h3>Current Notifications:</h3>";
$notif_sql = "SELECT * FROM notifications ORDER BY sent_at DESC LIMIT 5";
$notif_stmt = $conn->query($notif_sql);
$notifications = $notif_stmt->fetchAll(PDO::FETCH_ASSOC);

if ($notifications) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Bus ID</th><th>Message</th><th>Comment</th><th>Sent At</th></tr>";
    foreach ($notifications as $notif) {
        echo "<tr>";
        echo "<td>" . $notif['notification_id'] . "</td>";
        echo "<td>" . $notif['bus_id'] . "</td>";
        echo "<td>" . $notif['message'] . "</td>";
        echo "<td>" . ($notif['comment'] ?? 'No comment') . "</td>";
        echo "<td>" . $notif['sent_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No notifications found.</p>";
}

// Test comment functionality
if ($overloading_events) {
    $test_event = $overloading_events[0];
    echo "<h3>Test Comment Functionality:</h3>";
    echo "<p>Testing with event ID: " . $test_event['id'] . " (Bus: " . $test_event['plate_number'] . ")</p>";
    
    // Test the update_comment.php directly
    $test_comment = "Test comment from debug " . date('Y-m-d H:i:s');
    $bus_id = $test_event['bus_id'];
    
    echo "<p>Testing comment: " . $test_comment . "</p>";
    
    // Check if notification exists
    $check_stmt = $conn->prepare("SELECT notification_id FROM notifications WHERE bus_id = ? AND message LIKE '%overloading%'");
    $check_stmt->execute([$bus_id]);
    $existing_notification = $check_stmt->fetch();
    
    if ($existing_notification) {
        // Update existing notification
        $stmt = $conn->prepare("UPDATE notifications SET comment = ? WHERE notification_id = ?");
        $result = $stmt->execute([$test_comment, $existing_notification['notification_id']]);
        echo "<p>Updated existing notification: " . ($result ? "SUCCESS" : "FAILED") . "</p>";
    } else {
        // Create new notification
        $stmt = $conn->prepare("INSERT INTO notifications (bus_id, message, comment, sent_at) VALUES (?, ?, ?, NOW())");
        $result = $stmt->execute([$bus_id, 'Overloading event detected', $test_comment]);
        echo "<p>Created new notification: " . ($result ? "SUCCESS" : "FAILED") . "</p>";
    }
    
    // Show updated notifications
    echo "<h3>Updated Notifications:</h3>";
    $updated_notif_stmt = $conn->query($notif_sql);
    $updated_notifications = $updated_notif_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($updated_notifications) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Bus ID</th><th>Message</th><th>Comment</th><th>Sent At</th></tr>";
        foreach ($updated_notifications as $notif) {
            echo "<tr>";
            echo "<td>" . $notif['notification_id'] . "</td>";
            echo "<td>" . $notif['bus_id'] . "</td>";
            echo "<td>" . $notif['message'] . "</td>";
            echo "<td>" . ($notif['comment'] ?? 'No comment') . "</td>";
            echo "<td>" . $notif['sent_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
}

echo "<h3>JavaScript Test:</h3>";
echo "<p>Click the button below to test JavaScript functionality:</p>";
echo "<button onclick='testJavaScript()'>Test JavaScript</button>";
echo "<div id='js-result'></div>";

echo "<script>
function testJavaScript() {
    document.getElementById('js-result').innerHTML = 'JavaScript is working!';
    console.log('JavaScript test function called');
}

// Test if the comment functions exist
console.log('Testing comment functions...');
if (typeof showCommentForm === 'function') {
    console.log('showCommentForm function exists');
} else {
    console.log('showCommentForm function NOT found');
}

if (typeof saveComment === 'function') {
    console.log('saveComment function exists');
} else {
    console.log('saveComment function NOT found');
}
</script>";

echo "<p><strong>Debug test completed!</strong></p>";
?> 