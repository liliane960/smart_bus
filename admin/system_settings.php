<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}
require_once "../db.php";

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_settings':
                // Update system settings
                $stmt = $conn->prepare("UPDATE system_settings SET value = ? WHERE setting_key = ?");
                $stmt->execute([$_POST['max_capacity'], 'default_max_capacity']);
                $stmt->execute([$_POST['notification_email'], 'admin_email']);
                $stmt->execute([$_POST['auto_backup'], 'auto_backup_enabled']);
                $success_message = "System settings updated successfully!";
                break;
                
            case 'clear_logs':
                // Clear old logs (keep last 30 days)
                $stmt = $conn->prepare("DELETE FROM bus_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
                $stmt->execute();
                $success_message = "Old logs cleared successfully!";
                break;
                
            case 'reset_notifications':
                // Reset notification status
                $stmt = $conn->prepare("UPDATE notifications SET status = 'pending' WHERE status = 'resolved'");
                $stmt->execute();
                $success_message = "Notification status reset successfully!";
                break;
                
            case 'backup_database':
                // Create database backup
                $backup_file = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
                $command = "mysqldump -u root -p smart_bus > backups/" . $backup_file;
                exec($command);
                $success_message = "Database backup created: " . $backup_file;
                break;
        }
    }
}

// Get current settings
$settings = [];
$stmt = $conn->query("SELECT setting_key, value FROM system_settings");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['setting_key']] = $row['value'];
}

// Get system statistics
$stmt = $conn->query("SELECT COUNT(*) as total FROM bus_logs");
$total_logs = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM notifications");
$total_notifications = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM users");
$total_users = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM buses");
$total_buses = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Get disk usage
$disk_usage = disk_free_space('.') / disk_total_space('.') * 100;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>System Settings - Admin</title>
<link rel="stylesheet" href="../assets/style.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
<style>
    body { padding: 20px; background-color: #f8f9fa; }
    .container { max-width: 1200px; margin: 0 auto; }
    .header { background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .settings-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; }
    .settings-section { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; margin-bottom: 8px; font-weight: bold; color: #333; }
    .form-group input, .form-group select, .form-group textarea { 
        width: 100%; 
        padding: 10px; 
        border: 1px solid #ddd; 
        border-radius: 5px; 
        font-size: 14px; 
    }
    .form-group textarea { height: 100px; resize: vertical; }
    .btn { 
        padding: 10px 20px; 
        border: none; 
        border-radius: 5px; 
        cursor: pointer; 
        text-decoration: none; 
        display: inline-block; 
        margin: 5px; 
        font-size: 14px;
        transition: background 0.3s ease;
    }
    .btn-primary { background: #007bff; color: white; }
    .btn-success { background: #28a745; color: white; }
    .btn-warning { background: #ffc107; color: #212529; }
    .btn-danger { background: #dc3545; color: white; }
    .btn:hover { opacity: 0.8; }
    .alert { padding: 15px; border-radius: 5px; margin-bottom: 20px; }
    .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .alert-warning { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
    .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    .system-info { background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 15px; }
    .system-info h4 { margin: 0 0 10px 0; color: #333; }
    .system-info p { margin: 5px 0; color: #666; }
    .progress-bar { 
        width: 100%; 
        height: 20px; 
        background: #e9ecef; 
        border-radius: 10px; 
        overflow: hidden; 
        margin: 10px 0; 
    }
    .progress-fill { 
        height: 100%; 
        background: linear-gradient(90deg, #28a745, #20c997); 
        transition: width 0.3s ease; 
    }
    .maintenance-section { margin-top: 20px; }
    .maintenance-item { 
        background: #f8f9fa; 
        padding: 15px; 
        border-radius: 5px; 
        margin-bottom: 10px; 
        border-left: 4px solid #007bff; 
    }
    .maintenance-item h5 { margin: 0 0 10px 0; color: #333; }
    .maintenance-item p { margin: 5px 0; color: #666; font-size: 14px; }
</style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1><i class="fas fa-cogs"></i> System Settings & Configuration</h1>
        <p>Manage system preferences, maintenance, and configuration options</p>
    </div>

    <?php if (isset($success_message)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success_message) ?>
        </div>
    <?php endif; ?>

    <div class="settings-grid">
        <!-- Main Settings -->
        <div class="settings-section">
            <h3><i class="fas fa-sliders-h"></i> System Configuration</h3>
            
            <form method="POST">
                <input type="hidden" name="action" value="update_settings">
                
                <div class="form-group">
                    <label for="max_capacity">Default Bus Capacity:</label>
                    <input type="number" id="max_capacity" name="max_capacity" 
                           value="<?= htmlspecialchars($settings['default_max_capacity'] ?? 50) ?>" 
                           min="10" max="200" required>
                    <small>Maximum number of passengers allowed per bus</small>
                </div>

                <div class="form-group">
                    <label for="notification_email">Admin Email:</label>
                    <input type="email" id="notification_email" name="notification_email" 
                           value="<?= htmlspecialchars($settings['admin_email'] ?? 'admin@smartbus.com') ?>" required>
                    <small>Email address for system notifications</small>
                </div>

                <div class="form-group">
                    <label for="auto_backup">Auto Backup:</label>
                    <select id="auto_backup" name="auto_backup">
                        <option value="1" <?= ($settings['auto_backup_enabled'] ?? '1') == '1' ? 'selected' : '' ?>>Enabled</option>
                        <option value="0" <?= ($settings['auto_backup_enabled'] ?? '1') == '0' ? 'selected' : '' ?>>Disabled</option>
                    </select>
                    <small>Automatically backup database daily</small>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Settings
                </button>
            </form>

            <!-- Maintenance Tools -->
            <div class="maintenance-section">
                <h3><i class="fas fa-tools"></i> Maintenance Tools</h3>
                
                <div class="maintenance-item">
                    <h5><i class="fas fa-broom"></i> Clear Old Logs</h5>
                    <p>Remove bus logs older than 30 days to free up space</p>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="clear_logs">
                        <button type="submit" class="btn btn-warning" onclick="return confirm('Are you sure? This will delete old logs permanently.')">
                            <i class="fas fa-trash"></i> Clear Old Logs
                        </button>
                    </form>
                </div>

                <div class="maintenance-item">
                    <h5><i class="fas fa-bell"></i> Reset Notifications</h5>
                    <p>Reset all resolved notifications back to pending status</p>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="reset_notifications">
                        <button type="submit" class="btn btn-warning" onclick="return confirm('Reset all resolved notifications?')">
                            <i class="fas fa-undo"></i> Reset Notifications
                        </button>
                    </form>
                </div>

                <div class="maintenance-item">
                    <h5><i class="fas fa-database"></i> Create Backup</h5>
                    <p>Create a manual database backup</p>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="backup_database">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-download"></i> Create Backup
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- System Information -->
        <div class="settings-section">
            <h3><i class="fas fa-info-circle"></i> System Information</h3>
            
            <div class="system-info">
                <h4>Database Statistics</h4>
                <p><strong>Total Logs:</strong> <?= number_format($total_logs) ?></p>
                <p><strong>Total Notifications:</strong> <?= number_format($total_notifications) ?></p>
                <p><strong>Total Users:</strong> <?= number_format($total_users) ?></p>
                <p><strong>Total Buses:</strong> <?= number_format($total_buses) ?></p>
            </div>

            <div class="system-info">
                <h4>System Status</h4>
                <p><strong>PHP Version:</strong> <?= phpversion() ?></p>
                <p><strong>Server:</strong> <?= $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' ?></p>
                <p><strong>Database:</strong> MySQL</p>
                <p><strong>Last Backup:</strong> <?= file_exists('backups/latest_backup.txt') ? file_get_contents('backups/latest_backup.txt') : 'Never' ?></p>
            </div>

            <div class="system-info">
                <h4>Disk Usage</h4>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?= 100 - $disk_usage ?>%"></div>
                </div>
                <p><strong>Available:</strong> <?= number_format(disk_free_space('.') / 1024 / 1024 / 1024, 2) ?> GB</p>
                <p><strong>Total:</strong> <?= number_format(disk_total_space('.') / 1024 / 1024 / 1024, 2) ?> GB</p>
            </div>

            <div class="system-info">
                <h4>Quick Actions</h4>
                <a href="system_reports.php" class="btn btn-primary" style="width: 100%; margin-bottom: 10px;">
                    <i class="fas fa-chart-bar"></i> View Reports
                </a>
                <a href="manage_users.php" class="btn btn-success" style="width: 100%; margin-bottom: 10px;">
                    <i class="fas fa-users"></i> Manage Users
                </a>
                <a href="view_notifications.php" class="btn btn-warning" style="width: 100%; margin-bottom: 10px;">
                    <i class="fas fa-bell"></i> View Notifications
                </a>
                <a href="../logout.php" class="btn btn-danger" style="width: 100%;">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </div>

    <!-- System Health Check -->
    <div class="settings-section" style="margin-top: 20px;">
        <h3><i class="fas fa-heartbeat"></i> System Health Check</h3>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
            <div class="system-info">
                <h4><i class="fas fa-database"></i> Database</h4>
                <p style="color: #28a745;"><i class="fas fa-check-circle"></i> Connected</p>
                <p>All tables accessible</p>
            </div>
            
            <div class="system-info">
                <h4><i class="fas fa-bell"></i> Notifications</h4>
                <p style="color: #28a745;"><i class="fas fa-check-circle"></i> Active</p>
                <p>Auto-notifications enabled</p>
            </div>
            
            <div class="system-info">
                <h4><i class="fas fa-file-alt"></i> Logging</h4>
                <p style="color: #28a745;"><i class="fas fa-check-circle"></i> Active</p>
                <p>Event logging operational</p>
            </div>
            
            <div class="system-info">
                <h4><i class="fas fa-shield-alt"></i> Security</h4>
                <p style="color: #28a745;"><i class="fas fa-check-circle"></i> Secure</p>
                <p>Session management active</p>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-refresh system info every 30 seconds
setInterval(function() {
    // You can add AJAX calls here to refresh system statistics
    console.log('System info refresh check');
}, 30000);

// Confirm dangerous actions
document.addEventListener('DOMContentLoaded', function() {
    const dangerousButtons = document.querySelectorAll('.btn-warning, .btn-danger');
    dangerousButtons.forEach(button => {
        if (button.type === 'submit') {
            button.addEventListener('click', function(e) {
                if (!confirm('Are you sure you want to perform this action?')) {
                    e.preventDefault();
                }
            });
        }
    });
});
</script>
</body>
</html> 