<?php
require_once '../db.php';

session_start();

// Check if user is logged in and is police
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'police') {
    header('Location: ../login.php');
    exit();
}

// get search input
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// build SQL to get notifications with comments
$sql = "SELECT n.notification_id, n.bus_id, b.plate_number, n.message, n.sent_at, n.status, n.comment,
               bl.passenger_count, bl.event, bl.created_at as log_time
        FROM notifications n
        JOIN buses b ON n.bus_id = b.bus_id
        LEFT JOIN bus_logs bl ON bl.bus_id = n.bus_id AND bl.status = 'overloading'
        WHERE n.message LIKE '%overloading%'";

$params = [];
if ($search !== '') {
    $sql .= " AND b.plate_number LIKE :search";
    $params[':search'] = "%$search%";
}

$sql .= " ORDER BY n.sent_at DESC";

// run query
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Also get buses with overloading status that don't have notifications yet
$sql2 = "SELECT DISTINCT bl.bus_id, b.plate_number, bl.passenger_count, bl.event, bl.created_at, bl.status
         FROM bus_logs bl
         JOIN buses b ON bl.bus_id = b.bus_id
         WHERE bl.status = 'overloading'
         AND bl.bus_id NOT IN (SELECT DISTINCT bus_id FROM notifications WHERE message LIKE '%overloading%')";

$params2 = [];
if ($search !== '') {
    $sql2 .= " AND b.plate_number LIKE :search";
    $params2[':search'] = "%$search%";
}

$sql2 .= " ORDER BY bl.created_at DESC";

$stmt2 = $conn->prepare($sql2);
$stmt2->execute($params2);
$overloadingBuses = $stmt2->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Overloading Notifications - Police</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
<style>
    body { padding: 20px; }
    .table-responsive { max-height: 500px; overflow-y: auto; }
</style>
</head>
<body>
<div class="container">
    <h2 class="mb-4">Overloading Notifications - Police</h2>

    <form method="get" class="row g-3 mb-3">
        <div class="col-auto">
            <input type="text" name="search" class="form-control" placeholder="Search by Plate Number" value="<?= htmlspecialchars($search) ?>" />
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-primary mb-3">Search</button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-striped table-bordered align-middle">
            <thead class="table-light">
                <tr>
                    <th>Plate Number</th>
                    <th>Message</th>
                    <th>Status</th>
                    <th>Comment</th>
                    <th>Time</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($notifications): ?>
                    <?php foreach ($notifications as $note): ?>
                        <tr>
                            <td><?= htmlspecialchars($note['plate_number']) ?></td>
                            <td><?= htmlspecialchars($note['message']) ?></td>
                            <td><?= htmlspecialchars($note['status']) ?></td>
                            <td><?= htmlspecialchars($note['comment'] ?? 'No comment') ?></td>
                            <td><?= htmlspecialchars($note['sent_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <?php if ($overloadingBuses): ?>
                    <?php foreach ($overloadingBuses as $bus): ?>
                        <tr>
                            <td><?= htmlspecialchars($bus['plate_number']) ?></td>
                            <td><?= htmlspecialchars("Event: {$bus['event']}, Passengers: {$bus['passenger_count']}") ?></td>
                            <td><?= htmlspecialchars($bus['status']) ?></td>
                            <td><em>No comment added yet</em></td>
                            <td><?= htmlspecialchars($bus['created_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <?php if (!$notifications && !$overloadingBuses): ?>
                    <tr><td colspan="5">No overloading notifications found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
