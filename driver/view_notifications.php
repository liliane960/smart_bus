<?php 
require_once 'db.php';

session_start();

// Check if user is logged in and is driver
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'driver') {
    header('Location: ../login.php');
    exit();
}

$message = '';

// Handle add comment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['notification_id'], $_POST['comment'])) {
    $notifId = filter_var(trim($_POST['notification_id']), FILTER_VALIDATE_INT);
    $comment = trim($_POST['comment']);
    if ($notifId && $comment !== '') {
        $stmt = $conn->prepare("INSERT INTO comments (notification_id, comment) VALUES (?, ?)");
        $stmt->execute([$notifId, $comment]);
        $message = 'Comment added successfully.';
    }
}

// Initialize search variable
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build dynamic WHERE clause
$sql = "SELECT n.notification_id, n.bus_id, n.bus_log_id, b.plate_number, n.message, n.sent_at, n.status, bl.passenger_count, bl.event, bl.created_at as log_time
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Overloading Notifications - Driver</title>
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
        margin-bottom: 4px;
    }
</style>
</head>
<body>
<div class="container-fluid">
    <h2 class="mb-4">Overloading Notifications - Driver</h2>

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
                <a href="view_notifications.php" class="btn btn-secondary">Clear</a>
            </div>
        </div>
    </form>

    <!-- Existing Notifications -->
    <h3>Overloading Notifications</h3>
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Plate Number</th>
                    <th>Message</th>
                    <th>Status</th>
                    <th>Comments</th>
                    <th>Passengers</th>
                    <th>Event</th>
                    <th>Time</th>
                    <th>Add Comment</th>
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
                            </td>
                            <td><?= htmlspecialchars($note['passenger_count'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($note['event'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($note['log_time'] ?? $note['sent_at']) ?></td>
                            <td>
                                <form method="post" style="display:flex;gap:5px;align-items:center;">
                                    <input type="hidden" name="notification_id" value="<?= htmlspecialchars($note['notification_id']) ?>">
                                    <input type="text" name="comment" placeholder="Add comment..." required style="flex:1;padding:4px 8px;">
                                    <button type="submit" class="btn btn-link" style="color:#007bff;text-decoration:underline;padding:4px 10px;background:none;border:none;cursor:pointer;">Add</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="8" class="text-center">No notifications found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>
</div>
</body>
</html>
