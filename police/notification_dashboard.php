<?php
require_once '../database/db.php';

session_start();

// Check if user is logged in and is police
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'police') {
    header('Location: ../login.php');
    exit();
}

// Get filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';
$date_filter = isset($_GET['date']) ? trim($_GET['date']) : '';

// Build base SQL to get notifications with comments
$sql = "SELECT n.notification_id, n.bus_id, n.bus_log_id, b.plate_number, n.message, n.sent_at, n.status, n.comment,
               bl.passenger_count, bl.event, bl.created_at as log_time
        FROM notifications n
        JOIN buses b ON n.bus_id = b.bus_id
        LEFT JOIN bus_logs bl ON n.bus_log_id = bl.id
        WHERE n.message LIKE '%overloading%'";

$params = [];

// Add search filter
if ($search !== '') {
    $sql .= " AND b.plate_number LIKE :search";
    $params[':search'] = "%$search%";
}

// Add status filter
if ($status_filter !== '') {
    $sql .= " AND n.status = :status";
    $params[':status'] = $status_filter;
}

// Add date filter
if ($date_filter !== '') {
    $sql .= " AND DATE(n.sent_at) = :date";
    $params[':date'] = $date_filter;
}

$sql .= " ORDER BY n.sent_at DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stats_sql = "SELECT 
    COUNT(*) as total_notifications,
    COUNT(CASE WHEN comment IS NOT NULL AND comment != '' THEN 1 END) as with_comments,
    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
    COUNT(CASE WHEN status = 'resolved' THEN 1 END) as resolved
FROM notifications 
WHERE message LIKE '%overloading%'";

$stats_stmt = $conn->query($stats_sql);
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

// Get recent overloading events
$recent_sql = "SELECT bl.id, b.plate_number, bl.passenger_count, bl.event, bl.created_at, bl.status
               FROM bus_logs bl
               JOIN buses b ON bl.bus_id = b.bus_id
               WHERE bl.status = 'overloading'
               ORDER BY bl.created_at DESC
               LIMIT 10";

$recent_stmt = $conn->query($recent_sql);
$recent_events = $recent_stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_notification_id'], $_POST['edit_comment'])) {
    $notifId = filter_var(trim($_POST['edit_notification_id']), FILTER_VALIDATE_INT);
    $comment = trim($_POST['edit_comment']);
    if ($notifId !== false) {
        $stmt = $conn->prepare("UPDATE notifications SET comment = ? WHERE notification_id = ?");
        $stmt->execute([$comment, $notifId]);
        $message = 'Main comment updated successfully.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment_notification_id'], $_POST['add_comment_text'])) {
    $notifId = filter_var(trim($_POST['add_comment_notification_id']), FILTER_VALIDATE_INT);
    $comment = trim($_POST['add_comment_text']);
    $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    if ($notifId !== false && $comment !== '') {
        $stmt = $conn->prepare("INSERT INTO comments (notification_id, user_id, comment) VALUES (?, ?, ?)");
        $stmt->execute([$notifId, $userId, $comment]);
        $message = 'Comment added successfully.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Police Notification Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
<style>
    body { padding: 20px; background-color: #f8f9fa; }
    .table-responsive { max-height: 500px; overflow-y: auto; }
    .comment-display { 
        max-width: 250px; 
        word-wrap: break-word; 
        background-color: #f8f9fa; 
        padding: 6px; 
        border-radius: 4px; 
        border: 1px solid #dee2e6;
        font-size: 0.85em;
    }
    .no-comment {
        color: #6c757d;
        font-style: italic;
    }
    .stat-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
    }
    .stat-card h3 {
        margin: 0;
        font-size: 2.5rem;
        font-weight: bold;
    }
    .stat-card p {
        margin: 5px 0 0 0;
        opacity: 0.9;
    }
    .filter-section {
        background: white;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .notification-table {
        background: white;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .recent-events {
        background: white;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
</style>
</head>
<body>
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="mb-3"><i class="fas fa-shield-alt"></i> Police Notification Dashboard</h1>
            <div class="alert alert-info" role="alert">
                <i class="fas fa-info-circle"></i> <strong>Police View:</strong> Monitor overloading notifications and comments from admin and driver users.
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <h3><?= $stats['total_notifications'] ?></h3>
                <p><i class="fas fa-bell"></i> Total Notifications</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                <h3><?= $stats['with_comments'] ?></h3>
                <p><i class="fas fa-comments"></i> With Comments</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                <h3><?= $stats['pending'] ?></h3>
                <p><i class="fas fa-clock"></i> Pending</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                <h3><?= $stats['resolved'] ?></h3>
                <p><i class="fas fa-check-circle"></i> Resolved</p>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="filter-section">
        <h4><i class="fas fa-filter"></i> Filters</h4>
        <form method="get" class="row g-3">
            <div class="col-md-3">
                <label for="search" class="form-label">Plate Number</label>
                <input type="text" class="form-control" id="search" name="search" 
                       value="<?= htmlspecialchars($search) ?>" placeholder="Search plate number...">
            </div>
            <div class="col-md-3">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All Status</option>
                    <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="resolved" <?= $status_filter === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="date" class="form-label">Date</label>
                <input type="date" class="form-control" id="date" name="date" 
                       value="<?= htmlspecialchars($date_filter) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">&nbsp;</label>
                <div>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
                    <a href="notification_dashboard.php" class="btn btn-secondary"><i class="fas fa-times"></i> Clear</a>
                </div>
            </div>
        </form>
    </div>

    <div class="row">
        <!-- Notifications Table -->
        <div class="col-md-8">
            <div class="notification-table">
                <div class="p-3 border-bottom">
                    <h4><i class="fas fa-list"></i> Notifications (<?= count($notifications) ?> found)</h4>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Plate</th>
                                <th>Message</th>
                                <th>Status</th>
                                <th>Comment</th>
                                <th>Passengers</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($notifications): ?>
                                <?php foreach ($notifications as $note): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($note['plate_number']) ?></strong></td>
                                        <td><?= htmlspecialchars($note['message']) ?></td>
                                        <td>
                                            <?php if ($note['status'] === 'pending'): ?>
                                                <span class="badge bg-warning">Pending</span>
                                            <?php else: ?>
                                                <span class="badge bg-success">Resolved</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <form method="post" style="display:flex;gap:5px;align-items:center;">
                                                <input type="hidden" name="edit_notification_id" value="<?= htmlspecialchars($note['notification_id']) ?>">
                                                <input type="text" name="edit_comment" value="<?= htmlspecialchars($note['comment'] ?? '') ?>" placeholder="Write main comment..." required style="flex:1;padding:4px 8px;">
                                                <button type="submit" class="btn btn-link" style="color:#007bff;text-decoration:underline;padding:4px 10px;background:none;border:none;cursor:pointer;">Save</button>
                                            </form>
                                            <?php 
                                            // Show all additional comments from the comments table
                                            $stmtC = $conn->prepare("SELECT * FROM comments WHERE notification_id = ? ORDER BY created_at ASC");
                                            $stmtC->execute([$note['notification_id']]);
                                            $comments = $stmtC->fetchAll(PDO::FETCH_ASSOC);
                                            foreach ($comments as $c) {
                                                echo '<div class="comment-display">'.htmlspecialchars($c['comment']).'<br><small>'.htmlspecialchars($c['created_at']).'</small></div>';
                                            }
                                            ?>
                                            <form method="post" style="display:flex;gap:5px;align-items:center;margin-top:4px;">
                                                <input type="hidden" name="add_comment_notification_id" value="<?= htmlspecialchars($note['notification_id']) ?>">
                                                <input type="text" name="add_comment_text" placeholder="Add new comment..." required style="flex:1;padding:4px 8px;">
                                                <button type="submit" class="btn btn-link" style="color:#007bff;text-decoration:underline;padding:4px 10px;background:none;border:none;cursor:pointer;">Add</button>
                                            </form>
                                        </td>
                                        <td>
                                            <span class="badge bg-danger"><?= htmlspecialchars($note['passenger_count'] ?? 'N/A') ?></span>
                                        </td>
                                        <td>
                                            <small><?= htmlspecialchars($note['log_time'] ?? $note['sent_at']) ?></small>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        <i class="fas fa-inbox fa-2x mb-2"></i><br>
                                        No notifications found
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Recent Events Sidebar -->
        <div class="col-md-4">
            <div class="recent-events">
                <h4><i class="fas fa-history"></i> Recent Overloading Events</h4>
                <?php if ($recent_events): ?>
                    <?php foreach ($recent_events as $event): ?>
                        <div class="border-bottom pb-2 mb-2">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <strong><?= htmlspecialchars($event['plate_number']) ?></strong><br>
                                    <small class="text-muted">
                                        <?= htmlspecialchars($event['event']) ?> - 
                                        <?= htmlspecialchars($event['passenger_count']) ?> passengers
                                    </small>
                                </div>
                                <span class="badge bg-danger"><?= htmlspecialchars($event['passenger_count']) ?></span>
                            </div>
                            <small class="text-muted"><?= htmlspecialchars($event['created_at']) ?></small>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted">No recent overloading events</p>
                <?php endif; ?>
            </div>

            <!-- Quick Actions -->
            <div class="recent-events mt-3">
                <h4><i class="fas fa-bolt"></i> Quick Actions</h4>
                <div class="d-grid gap-2">
                    <a href="view_notifications.php" class="btn btn-outline-primary">
                        <i class="fas fa-list"></i> View All Notifications
                    </a>
                    <a href="overloading_notifications.php" class="btn btn-outline-warning">
                        <i class="fas fa-exclamation-triangle"></i> Overloading Only
                    </a>
                    <a href="view_logs.php" class="btn btn-outline-info">
                        <i class="fas fa-chart-line"></i> View Bus Logs
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-4">
        <a href="dashboard.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>
</div>
</body>
</html> 