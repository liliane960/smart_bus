<?php
session_start();
require_once '../database/db.php';

// Check if user is logged in and is driver
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'driver') {
    echo '<div class="alert alert-danger">Access denied. Please log in as a driver.</div>';
    exit();
}

// Pagination settings
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 50; // Limit to 50 records per page
$offset = ($page - 1) * $per_page;

// Build SQL to get overloading events from bus_logs with notifications (with pagination)
$sql = "SELECT bl.id, bl.bus_id, bl.event, bl.passenger_count, bl.status, bl.created_at,
               b.plate_number, b.capacity,
               n.notification_id, n.message, n.sent_at, n.comment
        FROM bus_logs bl
        JOIN buses b ON bl.bus_id = b.bus_id
        LEFT JOIN notifications n ON bl.bus_id = n.bus_id AND n.message LIKE '%overloading%'
        WHERE bl.status = 'overloading'
        ORDER BY bl.created_at DESC
        LIMIT $per_page OFFSET $offset";

$stmt = $conn->query($sql);
$overloading_events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total count for pagination
$count_sql = "SELECT COUNT(*) as total FROM bus_logs bl WHERE bl.status = 'overloading'";
$count_stmt = $conn->query($count_sql);
$total_count = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_count / $per_page);

// Get statistics
$stats_sql = "SELECT 
    COUNT(*) as total_overloading,
    COUNT(CASE WHEN n.notification_id IS NOT NULL THEN 1 END) as with_notifications,
    COUNT(CASE WHEN n.comment IS NOT NULL AND n.comment != '' THEN 1 END) as with_comments,
    ROUND(AVG(bl.passenger_count), 1) as avg_passengers
FROM bus_logs bl
LEFT JOIN notifications n ON bl.bus_id = n.bus_id AND n.message LIKE '%overloading%'
WHERE bl.status = 'overloading'";

$stats_stmt = $conn->query($stats_sql);
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Overloading Events Dashboard - Driver</title>
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
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="mb-3"><i class="fas fa-shield-alt"></i> Overloading Events Dashboard</h1>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <h3><?= $stats['total_overloading'] ?></h3>
                <p><i class="fas fa-exclamation-triangle"></i> Total Overloading Events</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                <h3><?= $stats['with_notifications'] ?></h3>
                <p><i class="fas fa-bell"></i> With Notifications</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                <h3><?= $stats['with_comments'] ?></h3>
                <p><i class="fas fa-comments"></i> With Comments</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                <h3><?= $stats['avg_passengers'] ?></h3>
                <p><i class="fas fa-users"></i> Avg Passengers</p>
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
                        <th>Actions</th>
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
                                    <div class="btn-group" role="group">
                                        <button class="btn btn-sm btn-primary comment-btn" 
                                                onclick="showCommentForm(<?= $event['id'] ?>)">
                                            <i class="fas fa-comment"></i> Add
                                        </button>
                                        <?php if (!empty($event['comment'])): ?>
                                            <button class="btn btn-sm btn-warning comment-btn" 
                                                    onclick="showCommentForm(<?= $event['id'] ?>, '<?= htmlspecialchars(addslashes($event['comment'])) ?>')">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                                        </tr>
                            <tr id="comment-form-<?= $event['id'] ?>" style="display: none;">
                                <td colspan="6">
                                    <div class="comment-form">
                                        <textarea class="comment-input" id="comment-text-<?= $event['id'] ?>" 
                                                  placeholder="Enter your comment..."><?= htmlspecialchars($event['comment'] ?? '') ?></textarea>
                                        <button class="btn btn-sm btn-success comment-btn" 
                                                onclick="saveComment(<?= $event['id'] ?>)">
                                            <i class="fas fa-save"></i> Save Comment
                                        </button>
                                        <button class="btn btn-sm btn-secondary comment-btn" 
                                                onclick="hideCommentForm(<?= $event['id'] ?>)">
                                            <i class="fas fa-times"></i> Cancel
                                        </button>
                                    </div>
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
function showCommentForm(eventId, existingComment = '') {
    console.log('showCommentForm called with eventId:', eventId, 'existingComment:', existingComment);
    
    const formRow = document.getElementById('comment-form-' + eventId);
    const textarea = document.getElementById('comment-text-' + eventId);
    
    if (!formRow) {
        console.error('Form row not found for eventId:', eventId);
        alert('Error: Form not found');
        return;
    }
    
    if (!textarea) {
        console.error('Textarea not found for eventId:', eventId);
        alert('Error: Textarea not found');
        return;
    }
    
    // Set the existing comment in the textarea if editing
    if (existingComment) {
        textarea.value = existingComment;
    } else {
        textarea.value = '';
    }
    
    formRow.style.display = 'table-row';
    console.log('Form displayed for eventId:', eventId);
}

function hideCommentForm(eventId) {
    document.getElementById('comment-form-' + eventId).style.display = 'none';
}

function saveComment(eventId) {
    console.log('saveComment called with eventId:', eventId);
    
    const textarea = document.getElementById('comment-text-' + eventId);
    if (!textarea) {
        console.error('Textarea not found for eventId:', eventId);
        alert('Error: Textarea not found');
        return;
    }
    
    const commentText = textarea.value.trim();
    console.log('Comment text:', commentText);
    
    if (!commentText) {
        alert('Please enter a comment');
        return;
    }
    
    fetch('../update_comment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'event_id=' + eventId + '&comment=' + encodeURIComponent(commentText)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update the comment display without reloading the page
            const commentDisplay = document.getElementById('comment-display-' + eventId);
            commentDisplay.innerHTML = commentText;
            commentDisplay.className = 'comment-display';
            
            // Update the button group to show both Add and Edit
            const actionCell = commentDisplay.closest('tr').nextElementSibling.querySelector('td:last-child');
            actionCell.innerHTML = `
                <div class="btn-group" role="group">
                    <button class="btn btn-sm btn-primary comment-btn" 
                            onclick="showCommentForm(${eventId})">
                        <i class="fas fa-comment"></i> Add
                    </button>
                    <button class="btn btn-sm btn-warning comment-btn" 
                            onclick="showCommentForm(${eventId}, '${commentText.replace(/'/g, "\\'")}')">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                </div>
            `;
            
            // Hide the form
            hideCommentForm(eventId);
            
            alert('Comment saved successfully!');
        } else {
            alert('Error saving comment: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error saving comment');
    });
}
</script>
</body>
</html>
