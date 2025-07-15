<?php
require_once '../database/db.php';

// Defaults
$search = $_GET['search'] ?? '';
$filter = $_GET['filter'] ?? ''; // e.g., normal, full, overloading
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$logsPerPage = 10;
$offset = ($page - 1) * $logsPerPage;

// Build dynamic WHERE clause
$where = "WHERE 1";
$params = [];

if ($search) {
    $where .= " AND b.plate_number LIKE ?";
    $params[] = '%' . $search . '%';
}

if (in_array($filter, ['normal', 'full', 'overloading'])) {
    $where .= " AND bl.status = ?";
    $params[] = $filter;
}

// Count total logs for pagination
$countStmt = $conn->prepare("
    SELECT COUNT(*) FROM bus_logs bl
    JOIN buses b ON bl.bus_id = b.bus_id
    $where
");
$countStmt->execute($params);
$totalLogs = $countStmt->fetchColumn();
$totalPages = ceil($totalLogs / $logsPerPage);

// Get logs with limit & offset
$sql = "
    SELECT bl.id, b.plate_number, bl.event, bl.passenger_count, bl.status, bl.created_at
    FROM bus_logs bl
    JOIN buses b ON bl.bus_id = b.bus_id
    $where
    ORDER BY bl.created_at DESC
    LIMIT $logsPerPage OFFSET $offset
";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Bus Logs</title>
<link rel="stylesheet" href="../assets/style.css" />
<style>
    .table-responsive { width: 100%; overflow-x: auto; }
    table { border-collapse: collapse; width: 100%; min-width: 700px; background: #fff; }
    th, td { padding: 8px 12px; border: 1px solid #ddd; text-align: center; }
    .status-normal { color: green; font-weight: bold; }
    .status-full { color: orange; font-weight: bold; }
    .status-overloading { color: red; font-weight: bold; }
    .filters, .pagination, .search { margin: 10px 0; }
    .pagination { display: flex; flex-wrap: wrap; gap: 4px; justify-content: center; }
    .pagination a, .pagination span {
        padding: 6px 12px;
        background: #f1f1f1;
        color: #007bff;
        border-radius: 4px;
        text-decoration: none;
        border: 1px solid #ddd;
        transition: background 0.2s, color 0.2s;
        margin: 0 2px;
    }
    .pagination a:hover, .pagination a.active, .pagination a[style*='font-weight:bold'] {
        background: #007bff;
        color: #fff;
        font-weight: bold;
        border-color: #007bff;
    }
    .pagination .disabled {
        pointer-events: none;
        color: #aaa;
        background: #eee;
        border-color: #eee;
    }
    @media (max-width: 800px) {
        .table-responsive { min-width: 0; }
        table { min-width: 500px; }
        th, td { font-size: 14px; padding: 6px 6px; }
    }
    @media (max-width: 600px) {
        .table-responsive { min-width: 0; }
        table { min-width: 350px; }
        th, td { font-size: 12px; padding: 4px 2px; }
    }
</style>
</head>
<body>
<h1>Bus Logs</h1>

<!-- Search form -->
<form method="get" class="search">
    <input type="text" name="search" placeholder="Search by plate number" value="<?= htmlspecialchars($search) ?>" />
    <input type="submit" value="Search" />
</form>
<div class="export">
    <a href="export_logs.php?<?= http_build_query(['search' => $search, 'filter' => $filter]) ?>" 
       style="padding:6px 12px; background:#4CAF50; color:white; text-decoration:none; border-radius:4px;">
       Export to Excel
    </a>
</div>

<!-- Filter buttons -->
<div class="filters">
    <a href="?">All</a>
    <a href="?filter=normal<?= $search ? '&search=' . urlencode($search) : '' ?>">Normal</a>
    <a href="?filter=full<?= $search ? '&search=' . urlencode($search) : '' ?>">Full</a>
    <a href="?filter=overloading<?= $search ? '&search=' . urlencode($search) : '' ?>">Overloading</a>
</div>

<div class="table-responsive">
<table>
<thead>
    <tr>
        <th>ID</th>
        <th>Plate Number</th>
        <th>Event</th>
        <th>Passenger Count</th>
        <th>Status</th>
        <th>Created At</th>
    </tr>
</thead>
<tbody>
<?php if ($logs): ?>
    <?php foreach ($logs as $log): ?>
        <?php
            $statusClass = '';
            if ($log['status'] == 'normal') $statusClass = 'status-normal';
            elseif ($log['status'] == 'full') $statusClass = 'status-full';
            elseif ($log['status'] == 'overloading') $statusClass = 'status-overloading';
        ?>
        <tr>
            <td><?= htmlspecialchars($log['id']) ?></td>
            <td><?= htmlspecialchars($log['plate_number']) ?></td>
            <td><?= htmlspecialchars($log['event']) ?></td>
            <td><?= htmlspecialchars($log['passenger_count']) ?></td>
            <td class="<?= $statusClass ?>"><?= htmlspecialchars($log['status']) ?></td>
            <td><?= htmlspecialchars($log['created_at']) ?></td>
        </tr>
    <?php endforeach; ?>
<?php else: ?>
    <tr><td colspan="6">No logs found.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>

<!-- Pagination -->
<div class="pagination">
    <?php if ($totalPages > 1): ?>
        <?php
        $queryBase = '?';
        if ($filter) $queryBase .= 'filter=' . urlencode($filter) . '&';
        if ($search) $queryBase .= 'search=' . urlencode($search) . '&';
        $window = 3; // Number of page numbers to show around current page
        $start = max(1, $page - 1);
        $end = min($totalPages, $page + 1);
        ?>
        <!-- Previous link -->
        <?php if ($page > 1): ?>
            <a href="<?= $queryBase . 'page=' . ($page-1) ?>">Previous</a>
        <?php else: ?>
            <span class="disabled">Previous</span>
        <?php endif; ?>
        <!-- Always show first page -->
        <a href="<?= $queryBase . 'page=1' ?>" <?= $page==1 ? 'class="active"' : '' ?>>1</a>
        <?php if ($start > 2): ?>
            <span>...</span>
        <?php endif; ?>
        <?php
        for ($p = $start; $p <= $end; $p++) {
            if ($p != 1 && $p != $totalPages) {
                echo '<a href="' . $queryBase . 'page=' . $p . '"' . ($p==$page ? ' class="active"' : '') . '>' . $p . '</a>';
            }
        }
        ?>
        <?php if ($end < $totalPages - 1): ?>
            <span>...</span>
        <?php endif; ?>
        <?php if ($totalPages > 1): ?>
            <a href="<?= $queryBase . 'page=' . $totalPages ?>" <?= $page==$totalPages ? 'class="active"' : '' ?>><?= $totalPages ?></a>
        <?php endif; ?>
        <!-- Next link -->
        <?php if ($page < $totalPages): ?>
            <a href="<?= $queryBase . 'page=' . ($page+1) ?>">Next</a>
        <?php else: ?>
            <span class="disabled">Next</span>
        <?php endif; ?>
    <?php endif; ?>
</div>
</body>
</html>
