<?php
require_once '../db.php';

header('Content-Type: application/json');

$event = $_GET['event'] ?? null;
$count = $_GET['count'] ?? null;
$status = $_GET['status'] ?? null;
$plate_number = $_GET['plate_number'] ?? null;

if (!$event || !$count || !$status || !$plate_number) {
    echo json_encode(['success' => false, 'message' => 'Missing data']);
    exit;
}

try {
    // Check if bus exists
    $stmt = $conn->prepare("SELECT bus_id FROM buses WHERE plate_number = ?");
    $stmt->execute([$plate_number]);
    $bus = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$bus) {
        echo json_encode(['success' => false, 'message' => 'Bus not found']);
        exit;
    }

    $bus_id = $bus['bus_id'];

    // Insert log
    $stmt = $conn->prepare("INSERT INTO bus_logs (bus_id, event, passenger_count, status, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->execute([$bus_id, $event, $count, $status]);

    echo json_encode(['success' => true, 'message' => 'Data saved']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
