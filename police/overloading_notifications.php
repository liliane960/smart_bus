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
$sql = "SELECT n.notification_id, n.bus_id, n.bus_log_id, b.plate_number, n.message, n.sent_at, n.status, n.comment,
               bl.passenger_count, bl.event, bl.created_at as log_time
        FROM notifications n
        JOIN buses b ON n.bus_id = b.bus_id
        LEFT JOIN bus_logs bl ON n.bus_log_id = bl.id
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
$sql2 = "SELECT bl.id as bus_log_id, bl.bus_id, b.plate_number, bl.passenger_count, bl.event, bl.created_at, bl.status
         FROM bus_logs bl
         JOIN buses b ON bl.bus_id = b.bus_id
         WHERE bl.status = 'overloading'
         AND bl.id NOT IN (SELECT bus_log_id FROM notifications WHERE bus_log_id IS NOT NULL)";

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
    .table-responsive { max-height: 600px; overflow-y: auto; }
    .comment-display { 
        max-width: 300px; 
        word-wrap: break-word; 
        background-color: #f8f9fa; 
        padding: 8px; 
        border-radius: 4px; 
        border: 1px solid #dee2e6;
        font-size: 0.9em;
    }
    .no-comment {
        color: #6c757d;
        font-style: italic;
    }
    .alert-info {
        background-color: #d1ecf1;
        border-color: #bee5eb;
        color: #0c5460;
    }
</style>
</head>
<body>
<div class="container-fluid">
    <h2 class="mb-4">Overloading Notifications - Police (Read Only)</h2>

    <!-- Info Alert -->
    <div class="alert alert-info" role="alert">
        <strong>Police View:</strong> This is a read-only view of overloading notifications. You can view comments added by admin and driver users, but cannot edit them.
    </div>

    <!-- Search Form -->
    <form method="get" class="mb-4">
        <div class="row">
            <div class="col-md-6">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                       placeholder="Search by plate number..." class="form-control" />
            </div>
            <div class="col-md-6">
                <button type="submit" class="btn btn-primary">Search</button>
                <a href="overloading_notifications.php" class="btn btn-secondary">Clear</a>
            </div>
        </div>
    </form>

    <!-- Existing Notifications -->
    <h3>Overloading Notifications with Comments</h3>
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Plate Number</th>
                    <th>Message</th>
                    <th>Status</th>
                    <th>Comment</th>
                    <th>Passengers</th>
                    <th>Event</th>
                    <th>Time</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($notifications): ?>
                    <?php foreach ($notifications as $note): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($note['plate_number']) ?></strong></td>
                            <td><?= htmlspecialchars($note['message']) ?></td>
                            <td><span class="badge bg-warning"><?= htmlspecialchars($note['status']) ?></span></td>
                            <td>
                                <?php if (!empty($note['comment'])): ?>
                                    <div class="comment-display">
                                        <?= htmlspecialchars($note['comment']) ?>
                                    </div>
                                <?php else: ?>
                                    <span class="no-comment">No comment available</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($note['passenger_count'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($note['event'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($note['log_time'] ?? $note['sent_at']) ?></td>
                            <td>
                                <?php if (!empty($note['comment'])): ?>
                                    <span class="badge bg-success">Comment exists</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">No comment</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="8" class="text-center">No notifications found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Buses with Overloading but No Notifications -->
    <?php if ($overloadingBuses): ?>
        <h3 class="mt-4">Buses with Overloading (No Notifications Yet)</h3>
        <div class="alert alert-warning" role="alert">
            <strong>Note:</strong> The following buses have overloading events but no notifications have been created yet. These will be automatically processed by the system.
        </div>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Plate Number</th>
                        <th>Event</th>
                        <th>Passenger Count</th>
                        <th>Time</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($overloadingBuses as $bus): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($bus['plate_number']) ?></strong></td>
                            <td><?= htmlspecialchars($bus['event']) ?></td>
                            <td><span class="badge bg-danger"><?= htmlspecialchars($bus['passenger_count']) ?></span></td>
                            <td><?= htmlspecialchars($bus['created_at']) ?></td>
                            <td><span class="badge bg-warning">No notification</span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <!-- Summary Statistics -->
    <div class="row mt-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Total Notifications</h5>
                    <p class="card-text display-6"><?= count($notifications) ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">With Comments</h5>
                    <p class="card-text display-6"><?= count(array_filter($notifications, function($n) { return !empty($n['comment']); })) ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Pending Processing</h5>
                    <p class="card-text display-6"><?= count($overloadingBuses) ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-4">
        <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        <a href="view_notifications.php" class="btn btn-primary">View All Notifications</a>
    </div>
</div>
</body>
</html>
