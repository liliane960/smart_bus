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
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Pagination settings
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 50; // Limit to 50 records per page
$offset = ($page - 1) * $per_page;

// Build WHERE clause for filters
$where_conditions = [];
$params = [];

if (!empty($status_filter)) {
    $where_conditions[] = "bl.status = ?";
    $params[] = $status_filter;
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = "WHERE " . implode(" AND ", $where_conditions);
}

// Build SQL to get hardware data from bus_logs (with pagination and filters)
$sql = "SELECT bl.id, bl.bus_id, bl.event, bl.passenger_count, bl.status, bl.created_at,
               b.plate_number, b.capacity
        FROM bus_logs bl
        JOIN buses b ON bl.bus_id = b.bus_id
        $where_clause
        ORDER BY bl.created_at DESC
        LIMIT $per_page OFFSET $offset";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$hardware_events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total count for pagination
$count_sql = "SELECT COUNT(*) as total FROM bus_logs bl
              JOIN buses b ON bl.bus_id = b.bus_id
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
<title>Hardware Data Dashboard - Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
<style>
    body { padding: 20px; background-color: #f8f9fa; }
    .table-responsive { max-height: 500px; overflow-y: auto; }
    .stat-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
        text-align: center;
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
    .hardware-table {
        background: white;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .event-entry { color: #28a745; }
    .event-exit { color: #dc3545; }
    .status-normal { color: #28a745; }
    .status-full { color: #ffc107; }
    .status-overloading { color: #dc3545; }
    .passenger-count {
        font-weight: bold;
        font-size: 1.1em;
    }
    .real-time-indicator {
        animation: pulse 2s infinite;
    }
    @keyframes pulse {
        0% { opacity: 1; }
        50% { opacity: 0.5; }
        100% { opacity: 1; }
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
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">All Status</option>
                                <option value="normal" <?= $status_filter == 'normal' ? 'selected' : '' ?>>Normal</option>
                                <option value="full" <?= $status_filter == 'full' ? 'selected' : '' ?>>Full</option>
                                <option value="overloading" <?= $status_filter == 'overloading' ? 'selected' : '' ?>>Overloading</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter"></i> Filter
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">&nbsp;</label>
                            <div>
                                <a href="/bus-system/liliane%20ishimwe/smart-bus/admin/export_hardware_data.php?<?= http_build_query($_GET) ?>" class="btn btn-success" target="_blank">
                                    <i class="fas fa-file-excel"></i> Export Excel
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>





    <!-- Hardware Events Table -->
    <div class="hardware-table">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Plate Number</th>
                        <th>Event</th>
                        <th>Passengers</th>
                        <th>Status</th>
                        <th>Timestamp</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($hardware_events): ?>
                        <?php foreach ($hardware_events as $event): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($event['plate_number']) ?></strong></td>
                                <td>
                                    <?php if ($event['event'] == 'entry'): ?>
                                        <span class="event-entry">
                                            <i class="fas fa-sign-in-alt"></i> Entry
                                        </span>
                                    <?php else: ?>
                                        <span class="event-exit">
                                            <i class="fas fa-sign-out-alt"></i> Exit
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-primary fs-6"><?= htmlspecialchars($event['passenger_count']) ?></span>
                                </td>
                                <td>
                                    <?php if ($event['status'] == 'normal'): ?>
                                        <span class="badge bg-success">Normal</span>
                                    <?php elseif ($event['status'] == 'full'): ?>
                                        <span class="badge bg-warning">Full</span>
                                    <?php elseif ($event['status'] == 'overloading'): ?>
                                        <span class="badge bg-danger">Overloading</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><?= htmlspecialchars($event['status']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($event['created_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-4">
                                <i class="fas fa-info-circle fa-2x text-info mb-2"></i><br>
                                No hardware events found. Waiting for sensor data...
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
                Showing <?= $offset + 1 ?> to <?= min($offset + $per_page, $total_count) ?> of <?= $total_count ?> hardware events
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
// Add some visual feedback for data display
document.addEventListener('DOMContentLoaded', function() {
    const rows = document.querySelectorAll('tbody tr');
    rows.forEach((row, index) => {
        if (index < 5) { // Highlight the 5 most recent events
            row.style.backgroundColor = '#f8f9fa';
            setTimeout(() => {
                row.style.backgroundColor = '';
            }, 2000);
        }
    });
});
</script>
</body>
</html>
