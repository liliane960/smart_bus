<?php
// admin/view_logs.php
require_once '../db.php';

try {
    $stmt = $conn->query("SELECT id, bus_id, event, passenger_count, status, created_at FROM bus_logs ORDER BY created_at DESC");
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Bus Logs</title>
    <link rel="stylesheet" href="assets/style.css" />
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
        }
        th, td {
            padding: 8px 12px;
            border: 1px solid #ddd;
            text-align: center;
        }
        .status-normal {
            color: green;
            font-weight: bold;
        }
        .status-full {
            color: orange;
            font-weight: bold;
        }
        .status-overloading {
            color: red;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <h1>Bus Logs</h1>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Bus ID</th>
                <th>Event</th>
                <th>Passenger Count</th>
                <th>Status</th>
                <th>Created At</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($logs): ?>
                <?php foreach ($logs as $log): ?>
                    <?php
                        // Decide CSS class based on status
                        $statusClass = '';
                        if ($log['status'] == 'normal') $statusClass = 'status-normal';
                        elseif ($log['status'] == 'full') $statusClass = 'status-full';
                        elseif ($log['status'] == 'overloading') $statusClass = 'status-overloading';
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($log['id']) ?></td>
                        <td><?= htmlspecialchars($log['bus_id']) ?></td>
                        <td><?= htmlspecialchars($log['event']) ?></td>
                        <td><?= htmlspecialchars($log['passenger_count']) ?></td>
                        <td class="<?= $statusClass ?>"><?= htmlspecialchars($log['status']) ?></td>
                        <td><?= htmlspecialchars($log['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="6">No logs found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
