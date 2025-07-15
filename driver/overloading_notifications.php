<?php
// === START DEBUG SETTINGS ===
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// === END DEBUG SETTINGS ===

session_start();

// Adjust path if needed - make sure db.php sets $conn as PDO
require_once '../databasedb.php';

// Check if user is logged in and is driver
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'driver') {
    header('Location: ../login.php');
    exit();
}

$userRole = $_SESSION['role'];
$canComment = in_array($userRole, ['admin', 'driver']);

$message = '';

// Handle POST form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['notification_id'], $_POST['comment'])) {
    $notifId = filter_var(trim($_POST['notification_id']), FILTER_VALIDATE_INT);
    $comment = trim($_POST['comment']);
    if ($notifId && $comment !== '') {
        $stmt = $conn->prepare("INSERT INTO comments (notification_id, comment) VALUES (?, ?)");
        $stmt->execute([$notifId, $comment]);
        $message = 'Comment added successfully.';
    }
}

// Get search term if any
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Fetch overloading notifications and related data
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
<title>Overload Notifications - Driver</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
<style>
    body { padding: 20px; }
    .table-responsive { max-height: 500px; overflow-y: auto; }
</style>
</head>
<body>
<div class="container">
    <h2 class="mb-4">Overload Notifications - Driver</h2>

    <?php if ($message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

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
    <h3>Existing Notifications</h3>
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Plate Number</th>
                    <th>Message</th>
                    <th>Status</th>
                    <th>Comment</th>
                    <th>Time</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($notifications): ?>
                    <?php foreach ($notifications as $note): ?>
                        <tr>
                            <td><?= htmlspecialchars($note['plate_number']) ?></td>
                            <td><?= htmlspecialchars($note['message']) ?></td>
                            <td><?= htmlspecialchars($note['status']) ?></td>
                            <td>
                                <?php 
                                $stmtC = $conn->prepare("SELECT * FROM comments WHERE notification_id = ? ORDER BY created_at ASC");
                                $stmtC->execute([$note['notification_id']]);
                                $comments = $stmtC->fetchAll(PDO::FETCH_ASSOC);
                                if ($comments) {
                                    foreach ($comments as $c) {
                                        echo '<div class="comment-display">'.htmlspecialchars($c['comment']).'<br><small>'.htmlspecialchars($c['created_at']).'</small></div>';
                                    }
                                } else {
                                    echo '<span class="text-muted">No comments</span>';
                                }
                                ?>
                                <form method="post" class="d-flex gap-2 align-items-center" style="margin:0;">
                                    <input type="hidden" name="notification_id" value="<?= htmlspecialchars($note['notification_id']) ?>" />
                                    <input type="text" name="comment" placeholder="Add new comment..." class="form-control form-control-sm" required />
                                    <button type="submit" class="btn btn-sm btn-primary">Add</button>
                                </form>
                            </td>
                            <td><?= htmlspecialchars($note['sent_at']) ?></td>
                            <td>
                                <small class="text-success">Comment exists</small>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="text-center">No notifications found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Buses with Overloading but No Notifications -->
    <?php if ($overloadingBuses): ?>
        <h3 class="mt-4">Buses with Overloading (No Notifications Yet)</h3>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Plate Number</th>
                        <th>Event</th>
                        <th>Passenger Count</th>
                        <th>Time</th>
                        <th>Add Comment</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($overloadingBuses as $bus): ?>
                        <tr>
                            <td><?= htmlspecialchars($bus['plate_number']) ?></td>
                            <td><?= htmlspecialchars($bus['event']) ?></td>
                            <td><?= htmlspecialchars($bus['passenger_count']) ?></td>
                            <td><?= htmlspecialchars($bus['created_at']) ?></td>
                            <td>
                                <form method="post" class="d-flex gap-2 align-items-center" style="margin:0;">
                                    <input type="hidden" name="bus_id" value="<?= htmlspecialchars($bus['bus_id']) ?>" />
                                    <input type="hidden" name="bus_log_id" value="<?= htmlspecialchars($bus['bus_log_id']) ?>" />
                                    <input type="text" name="new_comment" placeholder="Enter comment..." class="form-control form-control-sm" required />
                                    <button type="submit" class="btn btn-sm btn-primary">Add</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <div class="mt-4">
        <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>
</div>
</body>
</html>
