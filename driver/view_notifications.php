<?php 
require_once '../db.php';

session_start();

// Check if user is logged in and is driver
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'driver') {
    header('Location: ../login.php');
    exit();
}

$message = '';

// Handle update existing comment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['notification_id'], $_POST['comment'])) {
        $notifId = trim($_POST['notification_id']);
        $comment = trim($_POST['comment']);

        if ($comment === '') {
            $message = 'Comment cannot be empty.';
        } else {
            $update = $conn->prepare("UPDATE notifications SET comment = :comment WHERE notification_id = :id");
            $update->execute([':comment' => $comment, ':id' => $notifId]);
            $message = 'Comment updated successfully.';
        }
    }
}

// Initialize search variable
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build base SQL to get overloading notifications with comments
$sql = "SELECT n.notification_id, n.bus_id, n.bus_log_id, b.plate_number, n.message, n.sent_at, n.status, n.comment,
               bl.passenger_count, bl.event, bl.created_at as log_time
        FROM notifications n
        JOIN buses b ON n.bus_id = b.bus_id
        LEFT JOIN bus_logs bl ON n.bus_log_id = bl.id
        WHERE n.message LIKE '%overloading%'";

// If search entered, add condition
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
    }
    .comment-edit-form {
        display: none;
        margin-top: 10px;
    }
    .comment-edit-form.show {
        display: block;
    }
    .btn-edit-comment {
        font-size: 0.8em;
        padding: 2px 8px;
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
                    <th>Comment</th>
                    <th>Passengers</th>
                    <th>Event</th>
                    <th>Time</th>
                    <th>Actions</th>
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
                                    <button type="button" class="btn btn-sm btn-outline-primary btn-edit-comment mt-1" 
                                            onclick="toggleEditForm(<?= $note['notification_id'] ?>)">
                                        Edit Comment
                                    </button>
                                    <form method="post" class="comment-edit-form" id="edit-form-<?= $note['notification_id'] ?>">
                                        <input type="hidden" name="notification_id" value="<?= htmlspecialchars($note['notification_id']) ?>" />
                                        <div class="input-group input-group-sm">
                                            <textarea name="comment" class="form-control" rows="3" required><?= htmlspecialchars($note['comment']) ?></textarea>
                                            <button type="submit" class="btn btn-success">Save</button>
                                            <button type="button" class="btn btn-secondary" onclick="toggleEditForm(<?= $note['notification_id'] ?>)">Cancel</button>
                                        </div>
                                    </form>
                                <?php else: ?>
                                    <em class="text-muted">No comment</em>
                                    <button type="button" class="btn btn-sm btn-outline-primary btn-edit-comment mt-1" 
                                            onclick="toggleEditForm(<?= $note['notification_id'] ?>)">
                                        Add Comment
                                    </button>
                                    <form method="post" class="comment-edit-form" id="edit-form-<?= $note['notification_id'] ?>">
                                        <input type="hidden" name="notification_id" value="<?= htmlspecialchars($note['notification_id']) ?>" />
                                        <div class="input-group input-group-sm">
                                            <textarea name="comment" class="form-control" rows="3" placeholder="Enter comment..." required></textarea>
                                            <button type="submit" class="btn btn-success">Save</button>
                                            <button type="button" class="btn btn-secondary" onclick="toggleEditForm(<?= $note['notification_id'] ?>)">Cancel</button>
                                        </div>
                                    </form>
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

    <div class="mt-4">
        <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>
</div>

<script>
function toggleEditForm(notificationId) {
    const form = document.getElementById('edit-form-' + notificationId);
    form.classList.toggle('show');
}
</script>
</body>
</html>
