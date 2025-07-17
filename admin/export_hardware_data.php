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
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Build WHERE clause for filters
$where_conditions = [];
$params = [];

if (!empty($status_filter)) {
    $where_conditions[] = "bl.status = ?";
    $params[] = $status_filter;
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = "WHERE " . implode(" AND ", $where_conditions);
}

// Get all data for export (no pagination)
$sql = "SELECT bl.id, bl.bus_id, bl.event, bl.passenger_count, bl.status, bl.created_at,
               b.plate_number, b.capacity
        FROM bus_logs bl
        JOIN buses b ON bl.bus_id = b.bus_id
        $where_clause
        ORDER BY bl.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$hardware_events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Set headers for Excel download
$filename = 'hardware_data_' . date('Y-m-d_H-i-s') . '.csv';
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
    'Timestamp'
]);

// Write data
foreach ($hardware_events as $event) {
    fputcsv($output, [
        $event['id'],
        $event['plate_number'],
        ucfirst($event['event']),
        $event['passenger_count'],
        ucfirst($event['status']),
        $event['capacity'],
        $event['created_at']
    ]);
}

fclose($output);
exit;
?> 