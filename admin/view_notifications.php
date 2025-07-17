<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once '../database/db.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo '<div class="alert alert-danger">Access denied. Please log in as an admin.</div>';
    exit();
}

// Filter settings
$plate_filter = isset($_GET['plate']) ? $_GET['plate'] : '';
$comment_filter = isset($_GET['comment']) ? $_GET['comment'] : '';

// Pagination settings
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 50; // Limit to 50 records per page
$offset = ($page - 1) * $per_page;

// Build WHERE clause for filters
$where_conditions = ["bl.status = 'overloading'"];
$params = [];
if (!empty($plate_filter)) {
    $where_conditions[] = "b.plate_number LIKE ?";
    $params[] = "%$plate_filter%";
}
if (!empty($comment_filter)) {
    if ($comment_filter == 'with_comment') {
        $where_conditions[] = "n.comment IS NOT NULL AND n.comment != ''";
    } elseif ($comment_filter == 'without_comment') {
        $where_conditions[] = "(n.comment IS NULL OR n.comment = '')";
    }
}
$where_clause = "WHERE " . implode(" AND ", $where_conditions);

// Build SQL to get overloading events from bus_logs (with pagination and filters)
$sql = "SELECT bl.id, bl.bus_id, bl.event, bl.passenger_count, bl.status, bl.created_at,
               b.plate_number, b.capacity,
               n.notification_id, n.message, n.sent_at, n.comment
        FROM bus_logs bl
        JOIN buses b ON bl.bus_id = b.bus_id
        LEFT JOIN notifications n ON bl.bus_id = n.bus_id AND n.message LIKE '%overloading%'
        $where_clause
        ORDER BY bl.created_at DESC
        LIMIT $per_page OFFSET $offset";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$overloading_events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total count for pagination
$count_sql = "SELECT COUNT(*) as total FROM bus_logs bl
              JOIN buses b ON bl.bus_id = b.bus_id
              LEFT JOIN notifications n ON bl.bus_id = n.bus_id AND n.message LIKE '%overloading%'
              $where_clause";
$count_stmt = $conn->prepare($count_sql);
$count_stmt->execute($params);
$total_count = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_count / $per_page);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Overloading Notifications - Admin</title>
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
    .notification-table {
        background: white;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .comment-form {
        margin-top: 10px;
    }
    .comment-input {
        width: 100%;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 0.9em;
    }
    .comment-btn {
        margin-top: 5px;
        padding: 4px 8px;
        font-size: 0.8em;
    }
</style>
</head>
<body>
<div class="container-fluid">
    <!-- Filter and Export Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label for="plate" class="form-label">Plate Number</label>
                            <input type="text" class="form-control" id="plate" name="plate" value="<?= htmlspecialchars($plate_filter) ?>" placeholder="Search plate number...">
                        </div>
                        <div class="col-md-3">
                            <label for="comment" class="form-label">Comment Status</label>
                            <select class="form-select" id="comment" name="comment">
                                <option value="">All Comments</option>
                                <option value="with_comment" <?= $comment_filter == 'with_comment' ? 'selected' : '' ?>>With Comments</option>
                                <option value="without_comment" <?= $comment_filter == 'without_comment' ? 'selected' : '' ?>>Without Comments</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter"></i> Filter
                                </button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">&nbsp;</label>
                            <div>
                                <a href="/bus-system/liliane%20ishimwe/smart-bus/admin/export_notifications.php?<?= http_build_query($_GET) ?>" class="btn btn-success" target="_blank">
                                    <i class="fas fa-file-excel"></i> Export Excel
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Overloading Events Table -->
    <div class="notification-table">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Plate Number</th>
                        <th>Passengers</th>
                        <th>Time</th>
                        <th>Notification</th>
                        <th>Comment</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($overloading_events): ?>
                        <?php foreach ($overloading_events as $event): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($event['plate_number']) ?></strong></td>
                                <td><span class="badge bg-danger"><?= htmlspecialchars($event['passenger_count']) ?></span></td>
                                <td><?= htmlspecialchars($event['created_at']) ?></td>
                                <td>
                                    <?php if ($event['notification_id']): ?>
                                        <span class="badge bg-success">Created</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning">Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($event['comment'])): ?>
                                        <div class="comment-display" id="comment-display-<?= $event['id'] ?>">
                                            <?= htmlspecialchars($event['comment']) ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="no-comment" id="comment-display-<?= $event['id'] ?>">No comment</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form onsubmit="return saveCommentSimple(event, <?= $event['id'] ?>);">
                                        <div class="input-group input-group-sm">
                                            <input type="text" class="form-control" id="comment-input-<?= $event['id'] ?>" placeholder="Add comment..." required>
                                            <button class="btn btn-success" type="submit">Save</button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <i class="fas fa-check-circle fa-2x text-success mb-2"></i><br>
                                No overloading events found. All buses are operating within capacity.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="row mt-4">
        <div class="col-12">
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= $page - 1 ?>">Previous</a>
                        </li>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= $page + 1 ?>">Next</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <div class="text-center text-muted">
                Showing <?= $offset + 1 ?> to <?= min($offset + $per_page, $total_count) ?> of <?= $total_count ?> overloading events
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
<script>
function saveCommentSimple(e, eventId) {
    e.preventDefault();
    const input = document.getElementById('comment-input-' + eventId);
    const comment = input.value.trim();
    if (!comment) {
        alert('Please enter a comment');
        return false;
    }
    fetch('../update_comment.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'event_id=' + eventId + '&comment=' + encodeURIComponent(comment)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('comment-display-' + eventId).innerHTML = comment;
            document.getElementById('comment-display-' + eventId).className = 'comment-display';
            input.value = '';
            alert('Comment saved successfully!');
        } else {
            alert('Error saving comment: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error saving comment');
    });
    return false;
}
</script>
</body>
</html>
