<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../database/db.php';

// Check if user is logged in and is police
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'police') {
    header('Location: ../index.php');
    exit();
}

// Build SQL to get REAL-TIME overloading events from bus_logs
$sql = "SELECT bl.id, bl.bus_id, bl.event, bl.passenger_count, bl.status, bl.created_at,
               b.plate_number, b.capacity,
               n.notification_id, n.message, n.sent_at, n.comment
        FROM bus_logs bl
        JOIN buses b ON bl.bus_id = b.bus_id
        LEFT JOIN notifications n ON bl.bus_id = n.bus_id AND n.message LIKE '%overloading%'
        WHERE bl.status = 'overloading'
        ORDER BY bl.created_at DESC";

$stmt = $conn->query($sql);
$overloading_events = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

// Get recent overloading events (last 24 hours)
$recent_sql = "SELECT bl.id, b.plate_number, bl.passenger_count, bl.event, bl.created_at, bl.status
               FROM bus_logs bl
               JOIN buses b ON bl.bus_id = b.bus_id
               WHERE bl.status = 'overloading'
               AND bl.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
               ORDER BY bl.created_at DESC
               LIMIT 10";

$recent_stmt = $conn->query($recent_sql);
$recent_events = $recent_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Real-Time Overloading Dashboard - Police</title>
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
            <h1 class="mb-3"><i class="fas fa-shield-alt"></i> Real-Time Overloading Dashboard</h1>
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



    <!-- Real-Time Overloading Events -->
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
                                        <div class="comment-display">
                                            <?= htmlspecialchars($event['comment']) ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="no-comment">No comment</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-4">
                                <i class="fas fa-check-circle fa-2x text-success mb-2"></i><br>
                                No overloading events found. All buses are operating within capacity.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>


</div>

<script>
// Check for new overloading events and create notifications
function checkOverloadingEvents() {
    fetch('../api/check_overloading_events.php')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.created_notifications > 0) {
                console.log('Created ' + data.data.created_notifications + ' new notifications');
                // Optionally show a notification to the user
                showNotification('New overloading events detected!', 'success');
            }
        })
        .catch(error => {
            console.error('Error checking overloading events:', error);
        });
}

// Show notification to user
function showNotification(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    const container = document.querySelector('.container-fluid');
    container.insertBefore(alertDiv, container.firstChild);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

// Check for new events every 15 seconds
setInterval(checkOverloadingEvents, 15000);

// Initial check for overloading events
document.addEventListener('DOMContentLoaded', function() {
    checkOverloadingEvents();
});
</script>
</body>
</html> 