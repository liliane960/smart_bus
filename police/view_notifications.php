<?php
require_once '../db.php';

// Fetch only overloading notifications
$sql = "SELECT id, bus_id, event, passenger_count, status, created_at 
        FROM bus_logs 
        WHERE status = 'overloading' 
        ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->execute();
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Overloading Notifications</title>
<style>
    table { border-collapse: collapse; width: 100%; margin-top: 10px; }
    th, td { padding: 8px 12px; border: 1px solid #ccc; text-align: center; }
    th { background: #f0f0f0; }
</style>
</head>
<body>
<h2>Overloading Notifications</h2>
<table>
<thead>
    <tr>
        <th>Plate</th>
        <th>Message</th>
        <th>Status</th>
        <th>Time</th>
    </tr>
</thead>
<tbody>
<?php if ($notifications): ?>
    <?php foreach ($notifications as $note): ?>
        <tr>
            <td><?= htmlspecialchars($note['bus_id']) // replace with plate_number if joined ?></td>
            <td>
                <?= htmlspecialchars("Event: {$note['event']}, Passengers: {$note['passenger_count']}") ?>
            </td>
            <td><?= htmlspecialchars($note['status']) ?></td>
            <td><?= htmlspecialchars($note['created_at']) ?></td>
        </tr>
    <?php endforeach; ?>
<?php else: ?>
    <tr><td colspan="4">No overloading notifications found.</td></tr>
<?php endif; ?>
</tbody>
</table>
</body>
</html>
