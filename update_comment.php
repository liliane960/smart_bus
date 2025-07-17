<?php
session_start();
require_once 'database/db.php';

// Check if user is logged in
if (!isset($_SESSION['role'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['event_id'], $_POST['comment'])) {
    $event_id = (int)$_POST['event_id'];
    $comment = trim($_POST['comment']);
    
    if (empty($comment)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Comment cannot be empty']);
        exit();
    }
    
    try {
        // First, get the bus_id from the bus_logs table
        $bus_log_stmt = $conn->prepare("SELECT bus_id FROM bus_logs WHERE id = ?");
        $bus_log_stmt->execute([$event_id]);
        $bus_log = $bus_log_stmt->fetch();
        
        if (!$bus_log) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Event not found']);
            exit();
        }
        
        $bus_id = $bus_log['bus_id'];
        
        // Check if a notification already exists for this bus_id
        $check_stmt = $conn->prepare("SELECT notification_id FROM notifications WHERE bus_id = ? AND message LIKE '%overloading%'");
        $check_stmt->execute([$bus_id]);
        $existing_notification = $check_stmt->fetch();
        
        if ($existing_notification) {
            // Update existing notification with comment
            $stmt = $conn->prepare("UPDATE notifications SET comment = ? WHERE notification_id = ?");
            $result = $stmt->execute([$comment, $existing_notification['notification_id']]);
        } else {
            // Create new notification with comment
            $stmt = $conn->prepare("INSERT INTO notifications (bus_id, message, comment, sent_at) VALUES (?, ?, ?, NOW())");
            $result = $stmt->execute([$bus_id, 'Overloading event detected', $comment]);
        }
        
        if ($result) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Comment saved successfully']);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Failed to save comment']);
        }
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>
