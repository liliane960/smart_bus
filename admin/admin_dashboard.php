<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}
require_once "../db.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Smart Bus Dashboard - Admin</title>
<link rel="stylesheet" href="../assets/style.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
<style>
    .sidebar ul li a {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 15px;
        text-decoration: none;
        color: #333;
        border-radius: 5px;
        margin-bottom: 5px;
        transition: all 0.3s ease;
    }
    .sidebar ul li a:hover, .sidebar ul li a.active {
        background-color: #007bff;
        color: white;
    }
    .sidebar ul li a i {
        width: 20px;
        text-align: center;
    }
    .header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px;
        background: white;
        border-bottom: 1px solid #ddd;
    }
    .welcome-section {
        display: flex;
        align-items: center;
        gap: 20px;
    }
    .logout {
        background: #dc3545;
        color: white;
        padding: 8px 16px;
        text-decoration: none;
        border-radius: 5px;
        transition: background 0.3s ease;
    }
    .logout:hover {
        background: #c82333;
        color: white;
        text-decoration: none;
    }
    .admin-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin: 20px 0;
    }
    .stat-card {
        background: white;
        padding: 20px;
        border-radius: 10px;
        text-align: center;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        transition: transform 0.3s ease;
    }
    .stat-card:hover {
        transform: translateY(-5px);
    }
    .stat-card h3 {
        margin: 0;
        font-size: 2rem;
        color: #007bff;
    }
    .stat-card p {
        margin: 5px 0 0 0;
        color: #666;
    }
    .quick-actions {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin: 20px 0;
    }
    .action-card {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        text-align: center;
        transition: transform 0.3s ease;
    }
    .action-card:hover {
        transform: translateY(-5px);
    }
    .action-card i {
        font-size: 2.5rem;
        color: #007bff;
        margin-bottom: 15px;
    }
    .action-card h3 {
        margin: 0 0 10px 0;
        color: #333;
    }
    .action-card p {
        margin: 0 0 15px 0;
        color: #666;
    }
    .action-btn {
        background: #007bff;
        color: white;
        padding: 10px 20px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
        transition: background 0.3s ease;
    }
    .action-btn:hover {
        background: #0056b3;
        color: white;
        text-decoration: none;
    }
    .recent-activity {
        background: white;
        padding: 15px;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin: 20px 0;
    }
    .activity-item {
        display: flex;
        align-items: center;
        padding: 8px 0;
        border-bottom: 1px solid #eee;
    }
    .activity-item:last-child {
        border-bottom: none;
    }
    .activity-icon {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        background: #007bff;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 10px;
    }
    .activity-content {
        flex: 1;
    }
    .activity-time {
        color: #666;
        font-size: 0.9em;
    }
    .system-status {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin: 20px 0;
    }
    .status-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 0;
        border-bottom: 1px solid #eee;
    }
    .status-item:last-child {
        border-bottom: none;
    }
    .status-indicator {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: #28a745;
    }
    .status-indicator.warning {
        background: #ffc107;
    }
    .status-indicator.danger {
        background: #dc3545;
    }
</style>
</head>
<body>
<div class="dashboard">
    <aside class="sidebar">
        <h2><i class="fas fa-cog"></i> Smart Bus Admin</h2>
        <ul>
            <li><a href="#" data-page="view_logs.php" class="active">
                <i class="fas fa-chart-line"></i> View Logs
            </a></li>
            <li><a href="#" data-page="manage_buses.php">
                <i class="fas fa-bus"></i> Manage Buses
            </a></li>
            <li><a href="#" data-page="manage_drivers.php">
                <i class="fas fa-user-tie"></i> Manage Drivers
            </a></li>
            <li><a href="#" data-page="manage_users.php">
                <i class="fas fa-users"></i> Manage Users
            </a></li>
            <li><a href="#" data-page="view_notifications.php">
                <i class="fas fa-bell"></i> View Notifications
            </a></li>
            <li><a href="#" data-page="export_reports.php">
                <i class="fas fa-chart-bar"></i> Reports
            </a></li>
        </ul>
    </aside>
    <div class="main">
        <header class="header">
            <h1><i class="fas fa-cog"></i> Admin Dashboard</h1>
            <div class="welcome-section">
                <div>
                    <h3>Welcome <?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?> 
                        <span style="color: #007bff;">(<?= htmlspecialchars($_SESSION['role']) ?>)</span>
                    </h3>
                </div>
                <a href="../logout.php" class="logout">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
            <div id="datetime"></div>
        </header>
        
        <!-- Admin Statistics -->
        <div class="admin-stats">
            <?php
            // Get comprehensive statistics
            $stmt = $conn->query("SELECT COUNT(*) as total FROM bus_logs");
            $total_logs = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            $stmt = $conn->query("SELECT COUNT(*) as total FROM notifications WHERE message LIKE '%overloading%'");
            $total_notifications = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            $stmt = $conn->query("SELECT COUNT(*) as total FROM buses");
            $total_buses = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            $stmt = $conn->query("SELECT COUNT(*) as total FROM users");
            $total_users = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            $stmt = $conn->query("SELECT COUNT(*) as total FROM bus_logs WHERE status = 'overloading'");
            $overloading_events = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            $stmt = $conn->query("SELECT COUNT(*) as total FROM drivers");
            $total_drivers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            ?>
            <div class="stat-card">
                <h3><?= $total_logs ?></h3>
                <p><i class="fas fa-chart-line"></i> Total Logs</p>
            </div>
            <div class="stat-card">
                <h3><?= $total_notifications ?></h3>
                <p><i class="fas fa-bell"></i> Notifications</p>
            </div>
            <div class="stat-card">
                <h3><?= $total_buses ?></h3>
                <p><i class="fas fa-bus"></i> Buses</p>
            </div>
            <div class="stat-card">
                <h3><?= $total_users ?></h3>
                <p><i class="fas fa-users"></i> Users</p>
            </div>
            <div class="stat-card">
                <h3><?= $overloading_events ?></h3>
                <p><i class="fas fa-exclamation-triangle"></i> Overloading Events</p>
            </div>
            <div class="stat-card">
                <h3><?= $total_drivers ?></h3>
                <p><i class="fas fa-user-tie"></i> Drivers</p>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
            <!-- Recent Activity -->
            <div class="recent-activity">
                <h3><i class="fas fa-history"></i> Recent Activity</h3>
                <?php
                // Get recent bus logs (reduced to 3 items)
                $stmt = $conn->query("
                    SELECT bl.*, b.plate_number 
                    FROM bus_logs bl 
                    JOIN buses b ON bl.bus_id = b.bus_id 
                    ORDER BY bl.created_at DESC 
                    LIMIT 3
                ");
                $recent_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if ($recent_logs):
                    foreach ($recent_logs as $log):
                        $icon = $log['status'] === 'overloading' ? 'fas fa-exclamation-triangle' : 
                               ($log['status'] === 'full' ? 'fas fa-user-friends' : 'fas fa-user');
                        $color = $log['status'] === 'overloading' ? '#dc3545' : 
                                ($log['status'] === 'full' ? '#ffc107' : '#28a745');
                ?>
                    <div class="activity-item" style="padding: 8px 0;">
                        <div class="activity-icon" style="background: <?= $color ?>; width: 30px; height: 30px; margin-right: 10px;">
                            <i class="<?= $icon ?>" style="font-size: 12px;"></i>
                        </div>
                        <div class="activity-content" style="flex: 1;">
                            <div style="font-size: 14px;"><strong><?= htmlspecialchars($log['plate_number']) ?></strong> - <?= htmlspecialchars($log['event']) ?></div>
                            <div class="activity-time" style="font-size: 11px;"><?= date('M d, H:i', strtotime($log['created_at'])) ?></div>
                        </div>
                        <div>
                            <span style="color: <?= $color ?>; font-weight: bold; font-size: 12px;"><?= htmlspecialchars($log['passenger_count']) ?> pax</span>
                        </div>
                    </div>
                <?php 
                    endforeach;
                else:
                ?>
                    <p style="text-align: center; color: #666; font-size: 14px;">No recent activity</p>
                <?php endif; ?>
            </div>

            <!-- System Status -->
            <div class="system-status">
                <h3><i class="fas fa-server"></i> System Status</h3>
    
            </div>
        </div>
        
        <main id="main-content">
            <?php include 'view_logs.php'; ?>
        </main>
    </div>
</div>
<script src="../assets/script.js"></script>
<script>
// Update navigation to handle all admin views
document.addEventListener('DOMContentLoaded', function() {
    const links = document.querySelectorAll('.sidebar a[data-page]');
    
    links.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Remove active class from all links
            links.forEach(l => l.classList.remove('active'));
            
            // Add active class to clicked link
            this.classList.add('active');
            
            // Load the page content
            const page = this.getAttribute('data-page');
            loadPage(page);
        });
    });
    
    function loadPage(page) {
        fetch(page)
            .then(response => response.text())
            .then(html => {
                document.getElementById('main-content').innerHTML = html;
            })
            .catch(error => {
                console.error('Error loading page:', error);
                document.getElementById('main-content').innerHTML = '<p>Error loading page content.</p>';
            });
    }
    
    // Update datetime
    function updateDateTime() {
        const now = new Date();
        const dateTimeString = now.toLocaleDateString() + ' ' + now.toLocaleTimeString();
        document.getElementById('datetime').textContent = dateTimeString;
    }
    
    updateDateTime();
    setInterval(updateDateTime, 1000);
});
</script>
</body>
</html>
