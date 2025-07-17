<?php
header('Content-Type: application/json');
require_once '../database/db.php';

// Function to create notification for overloading event
function createNotification($bus_log_id, $bus_id, $passenger_count, $event) {
    global $conn;
    
    // Check if notification already exists for this bus_log_id
    $check_stmt = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE bus_log_id = ?");
    $check_stmt->execute([$bus_log_id]);
    
    if ($check_stmt->fetchColumn() > 0) {
        return false; // Notification already exists
    }
    
    // Create notification message
    $message = "OVERLOADING ALERT: Bus has $passenger_count passengers during $event event";
    
    // Insert notification
    $insert_stmt = $conn->prepare("
        INSERT INTO notifications (bus_id, bus_log_id, message, sent_at, status, comment) 
        VALUES (?, ?, ?, NOW(), 'pending', '')
    ");
    
    return $insert_stmt->execute([$bus_id, $bus_log_id, $message]);
}

try {
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
    
    $created_notifications = [];
    $errors = [];
    
    // Create notifications for each overloading event
    foreach ($overloading_events as $event) {
        if (createNotification($event['id'], $event['bus_id'], $event['passenger_count'], $event['event'])) {
            $created_notifications[] = [
                'bus_log_id' => $event['id'],
                'plate_number' => $event['plate_number'],
                'passenger_count' => $event['passenger_count'],
                'capacity' => $event['capacity'],
                'event' => $event['event'],
                'created_at' => $event['created_at']
            ];
        } else {
            $errors[] = "Failed to create notification for bus_log_id: " . $event['id'];
        }
    }
    
    // Get current notification count
    $count_stmt = $conn->query("SELECT COUNT(*) FROM notifications WHERE message LIKE '%OVERLOADING%'");
    $total_notifications = $count_stmt->fetchColumn();
    
    // Return JSON response
    echo json_encode([
        'success' => true,
        'message' => 'Checked for overloading events',
        'data' => [
            'found_events' => count($overloading_events),
            'created_notifications' => count($created_notifications),
            'total_notifications' => $total_notifications,
            'new_notifications' => $created_notifications,
            'errors' => $errors
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error checking overloading events: ' . $e->getMessage(),
        'data' => null
    ]);
}
?> 