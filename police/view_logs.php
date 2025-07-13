<?php
require_once '../db.php';

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
    table { border-collapse: collapse; width: 100%; }
    th, td { padding: 8px 12px; border: 1px solid #ddd; text-align: center; }
    .status-normal { color: green; font-weight: bold; }
    .status-full { color: orange; font-weight: bold; }
    .status-overloading { color: red; font-weight: bold; }
    .filters, .pagination, .search { margin: 10px 0; }
    .pagination a { margin: 0 5px; text-decoration: none; }
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

<!-- Pagination -->
<div class="pagination">
    <?php if ($totalPages > 1): ?>
        <?php for ($p=1; $p<=$totalPages; $p++): ?>
            <a href="?page=<?= $p ?>
                <?= $filter ? '&filter=' . urlencode($filter) : '' ?>
                <?= $search ? '&search=' . urlencode($search) : '' ?>"
                <?= $p==$page ? 'style="font-weight:bold;"' : '' ?>>
                <?= $p ?>
            </a>
        <?php endfor; ?>
    <?php endif; ?>
</div>
</body>
</html>
