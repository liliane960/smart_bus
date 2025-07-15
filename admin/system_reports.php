<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}
require_once "../db.php";

// Get date range for filtering
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Get comprehensive statistics
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_logs,
        COUNT(CASE WHEN status = 'normal' THEN 1 END) as normal_count,
        COUNT(CASE WHEN status = 'full' THEN 1 END) as full_count,
        COUNT(CASE WHEN status = 'overloading' THEN 1 END) as overloading_count,
        AVG(passenger_count) as avg_passengers,
        MAX(passenger_count) as max_passengers
    FROM bus_logs 
    WHERE DATE(created_at) BETWEEN ? AND ?
");
$stmt->execute([$start_date, $end_date]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Get bus performance data
$stmt = $conn->prepare("
    SELECT 
        b.plate_number,
        COUNT(bl.id) as total_events,
        COUNT(CASE WHEN bl.status = 'overloading' THEN 1 END) as overloading_events,
        AVG(bl.passenger_count) as avg_passengers,
        MAX(bl.passenger_count) as max_passengers
    FROM buses b
    LEFT JOIN bus_logs bl ON b.bus_id = bl.bus_id 
        AND DATE(bl.created_at) BETWEEN ? AND ?
    GROUP BY b.bus_id, b.plate_number
    ORDER BY overloading_events DESC
");
$stmt->execute([$start_date, $end_date]);
$bus_performance = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get daily statistics
$stmt = $conn->prepare("
    SELECT 
        DATE(created_at) as date,
        COUNT(*) as total_events,
        COUNT(CASE WHEN status = 'overloading' THEN 1 END) as overloading_events,
        AVG(passenger_count) as avg_passengers
    FROM bus_logs 
    WHERE DATE(created_at) BETWEEN ? AND ?
    GROUP BY DATE(created_at)
    ORDER BY date DESC
");
$stmt->execute([$start_date, $end_date]);
$daily_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get notification statistics
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_notifications,
        COUNT(CASE WHEN comment IS NOT NULL AND comment != '' THEN 1 END) as with_comments,
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
        COUNT(CASE WHEN status = 'resolved' THEN 1 END) as resolved
    FROM notifications 
    WHERE message LIKE '%overloading%' 
    AND DATE(sent_at) BETWEEN ? AND ?
");
$stmt->execute([$start_date, $end_date]);
$notification_stats = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>System Reports - Admin</title>
<link rel="stylesheet" href="../assets/style.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
    body { padding: 20px; background-color: #f8f9fa; }
    .container { max-width: 1400px; margin: 0 auto; }
    .header { background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .filter-section { background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 20px; }
    .stat-card { background: white; padding: 20px; border-radius: 10px; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .stat-card h3 { margin: 0; font-size: 2rem; color: #007bff; }
    .stat-card p { margin: 5px 0 0 0; color: #666; }
    .chart-section { background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .table-section { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
    th { background-color: #f8f9fa; font-weight: bold; }
    .btn { padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; margin: 2px; }
    .btn-primary { background: #007bff; color: white; }
    .btn-success { background: #28a745; color: white; }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
    .form-group input { padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
    .chart-container { position: relative; height: 400px; margin: 20px 0; }
    .performance-indicator { 
        padding: 4px 8px; 
        border-radius: 4px; 
        font-size: 0.8em; 
        font-weight: bold; 
    }
    .performance-good { background: #d4edda; color: #155724; }
    .performance-warning { background: #fff3cd; color: #856404; }
    .performance-danger { background: #f8d7da; color: #721c24; }
</style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1><i class="fas fa-chart-bar"></i> System Reports & Analytics</h1>
        <p>Comprehensive reports and analytics for the smart bus system from <?= date('M d, Y', strtotime($start_date)) ?> to <?= date('M d, Y', strtotime($end_date)) ?></p>
    </div>

    <!-- Date Filter -->
    <div class="filter-section">
        <h3><i class="fas fa-filter"></i> Filter by Date Range</h3>
        <form method="GET" style="display: flex; gap: 15px; align-items: end;">
            <div class="form-group">
                <label for="start_date">Start Date:</label>
                <input type="date" id="start_date" name="start_date" value="<?= $start_date ?>">
            </div>
            <div class="form-group">
                <label for="end_date">End Date:</label>
                <input type="date" id="end_date" name="end_date" value="<?= $end_date ?>">
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-search"></i> Generate Report
            </button>
            <a href="system_reports.php" class="btn btn-success">
                <i class="fas fa-sync"></i> Reset
            </a>
        </form>
    </div>

    <!-- Key Statistics -->
    <div class="stats-grid">
        <div class="stat-card">
            <h3><?= number_format($stats['total_logs']) ?></h3>
            <p><i class="fas fa-chart-line"></i> Total Events</p>
        </div>
        <div class="stat-card">
            <h3><?= number_format($stats['overloading_count']) ?></h3>
            <p><i class="fas fa-exclamation-triangle"></i> Overloading Events</p>
        </div>
        <div class="stat-card">
            <h3><?= number_format($stats['avg_passengers'], 1) ?></h3>
            <p><i class="fas fa-users"></i> Avg Passengers</p>
        </div>
        <div class="stat-card">
            <h3><?= number_format($stats['max_passengers']) ?></h3>
            <p><i class="fas fa-user-plus"></i> Max Passengers</p>
        </div>
        <div class="stat-card">
            <h3><?= number_format($notification_stats['total_notifications']) ?></h3>
            <p><i class="fas fa-bell"></i> Notifications</p>
        </div>
        <div class="stat-card">
            <h3><?= $stats['total_logs'] > 0 ? number_format(($stats['overloading_count'] / $stats['total_logs']) * 100, 1) : 0 ?>%</h3>
            <p><i class="fas fa-percentage"></i> Overloading Rate</p>
        </div>
    </div>

    <!-- Charts Section -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
        <!-- Event Status Chart -->
        <div class="chart-section">
            <h3><i class="fas fa-pie-chart"></i> Event Status Distribution</h3>
            <div class="chart-container">
                <canvas id="statusChart"></canvas>
            </div>
        </div>

        <!-- Daily Events Chart -->
        <div class="chart-section">
            <h3><i class="fas fa-chart-line"></i> Daily Events Trend</h3>
            <div class="chart-container">
                <canvas id="dailyChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Bus Performance Table -->
    <div class="table-section">
        <h3><i class="fas fa-bus"></i> Bus Performance Analysis</h3>
        <table>
            <thead>
                <tr>
                    <th>Plate Number</th>
                    <th>Total Events</th>
                    <th>Overloading Events</th>
                    <th>Avg Passengers</th>
                    <th>Max Passengers</th>
                    <th>Performance</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($bus_performance): ?>
                    <?php foreach ($bus_performance as $bus): ?>
                        <?php
                        $overloading_rate = $bus['total_events'] > 0 ? ($bus['overloading_events'] / $bus['total_events']) * 100 : 0;
                        $performance_class = $overloading_rate == 0 ? 'performance-good' : 
                                           ($overloading_rate < 10 ? 'performance-warning' : 'performance-danger');
                        $performance_text = $overloading_rate == 0 ? 'Excellent' : 
                                          ($overloading_rate < 10 ? 'Good' : 'Needs Attention');
                        ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($bus['plate_number']) ?></strong></td>
                            <td><?= number_format($bus['total_events']) ?></td>
                            <td><?= number_format($bus['overloading_events']) ?></td>
                            <td><?= number_format($bus['avg_passengers'], 1) ?></td>
                            <td><?= number_format($bus['max_passengers']) ?></td>
                            <td>
                                <span class="performance-indicator <?= $performance_class ?>">
                                    <?= $performance_text ?> (<?= number_format($overloading_rate, 1) ?>%)
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center; color: #666;">No bus performance data available</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Export Options -->
    <div class="chart-section">
        <h3><i class="fas fa-file-export"></i> Export Reports</h3>
        <div style="display: flex; gap: 15px; flex-wrap: wrap;">
            <a href="export_reports.php?type=summary&start=<?= $start_date ?>&end=<?= $end_date ?>" class="btn btn-primary">
                <i class="fas fa-file-excel"></i> Export Summary Report
            </a>
            <a href="export_reports.php?type=detailed&start=<?= $start_date ?>&end=<?= $end_date ?>" class="btn btn-primary">
                <i class="fas fa-file-excel"></i> Export Detailed Report
            </a>
            <a href="export_reports.php?type=performance&start=<?= $start_date ?>&end=<?= $end_date ?>" class="btn btn-primary">
                <i class="fas fa-file-excel"></i> Export Performance Report
            </a>
            <a href="export_reports.php?type=notifications&start=<?= $start_date ?>&end=<?= $end_date ?>" class="btn btn-primary">
                <i class="fas fa-file-excel"></i> Export Notifications Report
            </a>
        </div>
    </div>
</div>

<script>
// Status Distribution Chart
const statusCtx = document.getElementById('statusChart').getContext('2d');
new Chart(statusCtx, {
    type: 'doughnut',
    data: {
        labels: ['Normal', 'Full', 'Overloading'],
        datasets: [{
            data: [<?= $stats['normal_count'] ?>, <?= $stats['full_count'] ?>, <?= $stats['overloading_count'] ?>],
            backgroundColor: ['#28a745', '#ffc107', '#dc3545'],
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});

// Daily Events Chart
const dailyCtx = document.getElementById('dailyChart').getContext('2d');
const dailyData = <?= json_encode(array_reverse($daily_stats)) ?>;
new Chart(dailyCtx, {
    type: 'line',
    data: {
        labels: dailyData.map(item => new Date(item.date).toLocaleDateString()),
        datasets: [{
            label: 'Total Events',
            data: dailyData.map(item => item.total_events),
            borderColor: '#007bff',
            backgroundColor: 'rgba(0, 123, 255, 0.1)',
            tension: 0.4
        }, {
            label: 'Overloading Events',
            data: dailyData.map(item => item.overloading_events),
            borderColor: '#dc3545',
            backgroundColor: 'rgba(220, 53, 69, 0.1)',
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'top'
            }
        },
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});
</script>
</body>
</html> 