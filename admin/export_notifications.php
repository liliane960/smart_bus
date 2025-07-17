<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die('Access denied. Please log in as an admin.');
}

require_once '../database/db.php';

// Filter settings
$plate_filter = isset($_GET['plate']) ? $_GET['plate'] : '';
$comment_filter = isset($_GET['comment']) ? $_GET['comment'] : '';

// Build WHERE clause for filters
$where_conditions = ["bl.status = 'overloading'"];
$params = [];

if (!empty($plate_filter)) {
    $where_conditions[] = "b.plate_number LIKE ?";
    $params[] = "%$plate_filter%";
}

if (!empty($comment_filter)) {
    if ($comment_filter == 'with_comment') {
        $where_conditions[] = "n.comment IS NOT NULL AND n.comment != ''";
    } elseif ($comment_filter == 'without_comment') {
        $where_conditions[] = "(n.comment IS NULL OR n.comment = '')";
    }
}

$where_clause = "WHERE " . implode(" AND ", $where_conditions);

// Get all data for export (no pagination)
$sql = "SELECT bl.id, bl.bus_id, bl.event, bl.passenger_count, bl.status, bl.created_at,
               b.plate_number, b.capacity,
               n.notification_id, n.message, n.sent_at, n.comment
        FROM bus_logs bl
        JOIN buses b ON bl.bus_id = b.bus_id
        LEFT JOIN notifications n ON bl.bus_id = n.bus_id AND n.message LIKE '%overloading%'
        $where_clause
        ORDER BY bl.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$overloading_events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Set headers for Excel download
$filename = 'overloading_notifications_' . date('Y-m-d_H-i-s') . '.csv';
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// Create output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for proper Excel encoding
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Write headers
fputcsv($output, [
    'ID',
    'Plate Number',
    'Event',
    'Passenger Count',
    'Status',
    'Capacity',
    'Timestamp',
    'Notification ID',
    'Notification Message',
    'Notification Sent At',
    'Comment'
]);

// Write data
foreach ($overloading_events as $event) {
    fputcsv($output, [
        $event['id'],
        $event['plate_number'],
        ucfirst($event['event']),
        $event['passenger_count'],
        ucfirst($event['status']),
        $event['capacity'],
        $event['created_at'],
        $event['notification_id'] ?: 'N/A',
        $event['message'] ?: 'N/A',
        $event['sent_at'] ?: 'N/A',
        $event['comment'] ?: 'No comment'
    ]);
}

fclose($output);
exit;
?> 