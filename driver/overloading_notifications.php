<?php
// === START DEBUG SETTINGS ===
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// === END DEBUG SETTINGS ===

session_start();

// Adjust path if needed - make sure db.php sets $conn as PDO
require_once '../db.php';

// Check if user is logged in and is driver
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'driver') {
    header('Location: ../login.php');
    exit();
}

$userRole = $_SESSION['role'];
$canComment = in_array($userRole, ['admin', 'driver']);

$message = '';

// Handle POST form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canComment) {
    if (isset($_POST['notification_id'], $_POST['comment'])) {
        // Update existing comment
        $notifId = trim($_POST['notification_id']);
        $comment = trim($_POST['comment']);

        if ($comment === '') {
            $message = 'Comment cannot be empty.';
        } else {
            $update = $conn->prepare("UPDATE notifications SET comment = :comment WHERE notification_id = :id");
            $update->execute([':comment' => $comment, ':id' => $notifId]);
            $message = 'Comment updated successfully.';
        }
    } elseif (isset($_POST['bus_id'], $_POST['new_comment'])) {
        // Insert new notification
        $busId = trim($_POST['bus_id']);
        $comment = trim($_POST['new_comment']);

        if ($comment === '') {
            $message = 'Comment cannot be empty.';
        } else {
            $messageText = "Overloading detected";
            $status = 'pending';

            $insert = $conn->prepare("INSERT INTO notifications 
                (bus_id, message, status, comment) 
                VALUES (:bus_id, :message, :status, :comment)");
            $insert->execute([
                ':bus_id' => $busId,
                ':message' => $messageText,
                ':status' => $status,
                ':comment' => $comment
            ]);
            $message = 'Notification added successfully.';
        }
    }
}

// Get search term if any
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Fetch overloading notifications and related data
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
<meta charset="UTF-8" />
<title>Overloading Notifications - Driver</title>
<!-- Bootstrap 5 CDN -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
<style>
    body { padding: 20px; }
    .table-responsive { max-height: 500px; overflow-y: auto; }
</style>
</head>
<body>
<div class="container">
    <h2 class="mb-4">Overloading Notifications - Driver</h2>

    <?php if ($message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

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
                    <?php if ($canComment): ?>
                        <th>Action</th>
                    <?php endif; ?>
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
                                <?php if ($canComment): ?>
                                    <form method="post" class="d-flex gap-2 align-items-center" style="margin:0;">
                                        <input type="hidden" name="notification_id" value="<?= htmlspecialchars($note['notification_id']) ?>" />
                                        <input type="text" name="comment" value="<?= htmlspecialchars($note['comment'] ?? '') ?>" class="form-control form-control-sm" required />
                                        <button type="submit" class="btn btn-sm btn-success">Save</button>
                                    </form>
                                <?php else: ?>
                                    <?= htmlspecialchars($note['comment'] ?? '') ?>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($note['sent_at']) ?></td>
                            <?php if ($canComment): ?>
                                <td>
                                    <small class="text-success">Comment exists</small>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <?php if ($overloadingBuses): ?>
                    <?php foreach ($overloadingBuses as $bus): ?>
                        <tr>
                            <td><?= htmlspecialchars($bus['plate_number']) ?></td>
                            <td><?= htmlspecialchars("Event: {$bus['event']}, Passengers: {$bus['passenger_count']}") ?></td>
                            <td><?= htmlspecialchars($bus['status']) ?></td>
                            <td>
                                <?php if ($canComment): ?>
                                    <form method="post" class="d-flex gap-2 align-items-center" style="margin:0;">
                                        <input type="hidden" name="bus_id" value="<?= htmlspecialchars($bus['bus_id']) ?>" />
                                        <input type="text" name="new_comment" placeholder="Add comment" class="form-control form-control-sm" required />
                                        <button type="submit" class="btn btn-sm btn-primary">Add</button>
                                    </form>
                                <?php else: ?>
                                    <em>No comment</em>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($bus['created_at']) ?></td>
                            <?php if ($canComment): ?>
                                <td>
                                    <small class="text-muted">Add comment</small>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <?php if (!$notifications && !$overloadingBuses): ?>
                    <tr><td colspan="<?= $canComment ? 6 : 5 ?>">No overloading notifications found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
