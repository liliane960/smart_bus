<?php
require_once '../db.php';
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$message = '';

// Handle POST form submissions for comments
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
    } elseif (isset($_POST['bus_id'], $_POST['bus_log_id'], $_POST['new_comment'])) {
        $busId = trim($_POST['bus_id']);
        $busLogId = trim($_POST['bus_log_id']);
        $comment = trim($_POST['new_comment']);

        if ($comment === '') {
            $message = 'Comment cannot be empty.';
        } else {
            $messageText = "Overloading detected";
            $status = 'pending';

            $insert = $conn->prepare("INSERT INTO notifications 
                (bus_id, bus_log_id, message, status, comment) 
                VALUES (:bus_id, :bus_log_id, :message, :status, :comment)");
            $insert->execute([
                ':bus_id' => $busId,
                ':bus_log_id' => $busLogId,
                ':message' => $messageText,
                ':status' => $status,
                ':comment' => $comment
            ]);
            $message = 'Notification added successfully.';
        }
    }
}

// Search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Get notifications
$sql = "SELECT n.notification_id, b.plate_number, n.message, n.comment, bl.event, bl.created_at as log_time, n.sent_at
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

// Get buses with overloading but no notifications
$sql2 = "SELECT bl.id as bus_log_id, bl.bus_id, b.plate_number, bl.passenger_count, bl.event, bl.created_at
         FROM bus_logs bl
         JOIN buses b ON bl.bus_id = b.bus_id
         WHERE bl.status = 'overloading' AND bl.id NOT IN (SELECT bus_log_id FROM notifications WHERE bus_log_id IS NOT NULL)";

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
<title>Overloading Notifications</title>
<style>
    body {
        font-family: Arial, sans-serif;
        margin: 20px;
        background: #f8f9fa;
        color: #212529;
    }
    h2, h3 {
        margin-bottom: 15px;
        color: #343a40;
    }
    .container {
        max-width: 1100px;
        margin: 0 auto;
    }
    .alert {
        padding: 10px 15px;
        background-color: #d4edda;
        color: #155724;
        border-radius: 5px;
        margin-bottom: 20px;
        border: 1px solid #c3e6cb;
    }
    form {
        margin-bottom: 20px;
    }
    input[type="text"], textarea {
        padding: 8px 12px;
        border: 1px solid #ced4da;
        border-radius: 4px;
        width: 100%;
        font-size: 14px;
        box-sizing: border-box;
    }
    button {
        padding: 6px 12px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 14px;
    }
    button.btn-primary {
        background-color: #007bff;
        color: white;
    }
    button.btn-secondary {
        background-color: #6c757d;
        color: white;
    }
    button.btn-success {
        background-color: #28a745;
        color: white;
    }
    button.btn-outline-primary {
        background: none;
        border: 1px solid #007bff;
        color: #007bff;
    }
    button:hover {
        opacity: 0.9;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        background: white;
        box-shadow: 0 0 5px rgba(0,0,0,0.1);
        margin-bottom: 30px;
    }
    th, td {
        border: 1px solid #dee2e6;
        padding: 10px 12px;
        text-align: center;
        font-size: 14px;
    }
    th {
        background-color: #e9ecef;
        color: #495057;
    }
    .comment-display {
        white-space: pre-wrap;
        text-align: left;
        background: #f1f3f5;
        border: 1px solid #ced4da;
        border-radius: 4px;
        padding: 6px;
        max-height: 80px;
        overflow-y: auto;
    }
    .comment-edit-form {
        display: none;
        margin-top: 5px;
        text-align: left;
    }
    .comment-edit-form.show {
        display: block;
    }
    .input-group {
        display: flex;
        gap: 5px;
    }
    .input-group textarea {
        flex: 1;
    }
</style>
</head>
<body>
<div class="container">
    <h2>Overloading Notifications</h2>
    <?php if ($message): ?>
        <div class="alert"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="get" style="display:flex; gap:10px; max-width:400px;">
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search by plate number...">
        <button type="submit" class="btn-primary">Search</button>
        <a href="view_notifications.php" class="btn-secondary" style="text-decoration:none;">Clear</a>
    </form>

    <table>
        <thead>
            <tr>
                <th>Plate Number</th>
                <th>Message</th>
                <th>Comment</th>
                <th>Event</th>
                <th>Time</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($notifications): ?>
                <?php foreach ($notifications as $note): ?>
                    <tr>
                        <td><?= htmlspecialchars($note['plate_number']) ?></td>
                        <td><?= htmlspecialchars($note['message']) ?></td>
                        <td>
                            <?php if (!empty($note['comment'])): ?>
                                <div class="comment-display"><?= htmlspecialchars($note['comment']) ?></div>
                                <button type="button" class="btn-outline-primary btn-sm" onclick="toggleEditForm(<?= $note['notification_id'] ?>)">Edit</button>
                                <form method="post" class="comment-edit-form" id="edit-form-<?= $note['notification_id'] ?>">
                                    <input type="hidden" name="notification_id" value="<?= htmlspecialchars($note['notification_id']) ?>">
                                    <div class="input-group">
                                        <textarea name="comment" rows="2" required><?= htmlspecialchars($note['comment']) ?></textarea>
                                        <button type="submit" class="btn-success">Save</button>
                                        <button type="button" class="btn-secondary" onclick="toggleEditForm(<?= $note['notification_id'] ?>)">Cancel</button>
                                    </div>
                                </form>
                            <?php else: ?>
                                <em style="color:#6c757d;">No comment</em>
                                <button type="button" class="btn-outline-primary btn-sm" onclick="toggleEditForm(<?= $note['notification_id'] ?>)">Add</button>
                                <form method="post" class="comment-edit-form" id="edit-form-<?= $note['notification_id'] ?>">
                                    <input type="hidden" name="notification_id" value="<?= htmlspecialchars($note['notification_id']) ?>">
                                    <div class="input-group">
                                        <textarea name="comment" rows="2" required></textarea>
                                        <button type="submit" class="btn-success">Save</button>
                                        <button type="button" class="btn-secondary" onclick="toggleEditForm(<?= $note['notification_id'] ?>)">Cancel</button>
                                    </div>
                                </form>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($note['event'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($note['log_time'] ?? $note['sent_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="5">No notifications found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if ($overloadingBuses): ?>
        <h3>Buses with Overloading (No Notifications Yet)</h3>
        <table>
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
                            <form method="post" style="display:flex; gap:5px;">
                                <input type="hidden" name="bus_id" value="<?= htmlspecialchars($bus['bus_id']) ?>">
                                <input type="hidden" name="bus_log_id" value="<?= htmlspecialchars($bus['bus_log_id']) ?>">
                                <input type="text" name="new_comment" placeholder="Enter comment..." required>
                                <button type="submit" class="btn-primary btn-sm">Add</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <a href="dashboard.php" class="btn-secondary" style="text-decoration:none; padding:6px 12px;">Back to Dashboard</a>
</div>

<script>
function toggleEditForm(id) {
    document.getElementById('edit-form-' + id).classList.toggle('show');
}
</script>
</body>
</html>
