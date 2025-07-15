<?php
require_once '../database/db.php';

// Same filters as in the main page
$search = $_GET['search'] ?? '';
$filter = $_GET['filter'] ?? '';

$where = "WHERE 1";
$params = [];

if ($search) {
    $where .= " AND b.plate_number LIKE ?";
    $params[] = '%' . $search . '%';
}
if (in_array($filter, ['normal', 'full', 'overloading'])) {
    $where .= " AND bl.status = ?";
    $params[] = $filter;
}

// Fetch all logs matching filters
$sql = "
    SELECT bl.id, b.plate_number, bl.event, bl.passenger_count, bl.status, bl.created_at
    FROM bus_logs bl
    JOIN buses b ON bl.bus_id = b.bus_id
    $where
    ORDER BY bl.created_at DESC
";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Export to Excel (CSV for simplicity)
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=bus_logs_export.csv');
$output = fopen('php://output', 'w');

// Column headers
fputcsv($output, ['ID', 'Plate Number', 'Event', 'Passenger Count', 'Status', 'Created At']);

// Data rows
foreach ($logs as $log) {
    fputcsv($output, [
        $log['id'],
        $log['plate_number'],
        $log['event'],
        $log['passenger_count'],
        $log['status'],
        $log['created_at']
    ]);
}
fclose($output);
exit;
?>
