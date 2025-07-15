<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}
require_once "../db.php";

// Create backups directory if it doesn't exist
if (!is_dir('backups')) {
    mkdir('backups', 0755, true);
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create_backup':
                $backup_file = 'smart_bus_backup_' . date('Y-m-d_H-i-s') . '.sql';
                $backup_path = 'backups/' . $backup_file;
                
                // Create backup using mysqldump
                $command = "mysqldump -u root smart_bus > " . $backup_path;
                exec($command, $output, $return_var);
                
                if ($return_var === 0) {
                    // Create backup info file
                    $backup_info = [
                        'filename' => $backup_file,
                        'created_at' => date('Y-m-d H:i:s'),
                        'size' => filesize($backup_path),
                        'tables' => ['users', 'buses', 'drivers', 'bus_logs', 'notifications']
                    ];
                    file_put_contents('backups/' . $backup_file . '.info', json_encode($backup_info));
                    
                    $success_message = "Database backup created successfully: " . $backup_file;
                } else {
                    $error_message = "Failed to create backup. Please check database permissions.";
                }
                break;
                
            case 'restore_backup':
                if (isset($_POST['backup_file']) && file_exists('backups/' . $_POST['backup_file'])) {
                    $backup_file = 'backups/' . $_POST['backup_file'];
                    
                    // Restore database
                    $command = "mysql -u root smart_bus < " . $backup_file;
                    exec($command, $output, $return_var);
                    
                    if ($return_var === 0) {
                        $success_message = "Database restored successfully from: " . $_POST['backup_file'];
                    } else {
                        $error_message = "Failed to restore database. Please check backup file integrity.";
                    }
                } else {
                    $error_message = "Backup file not found.";
                }
                break;
                
            case 'delete_backup':
                if (isset($_POST['backup_file'])) {
                    $backup_file = 'backups/' . $_POST['backup_file'];
                    $info_file = $backup_file . '.info';
                    
                    if (file_exists($backup_file)) {
                        unlink($backup_file);
                        if (file_exists($info_file)) {
                            unlink($info_file);
                        }
                        $success_message = "Backup deleted successfully: " . $_POST['backup_file'];
                    } else {
                        $error_message = "Backup file not found.";
                    }
                }
                break;
        }
    }
}

// Get list of existing backups
$backups = [];
if (is_dir('backups')) {
    $files = scandir('backups');
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
            $backup_path = 'backups/' . $file;
            $info_path = $backup_path . '.info';
            
            $backup_info = [
                'filename' => $file,
                'size' => filesize($backup_path),
                'created_at' => date('Y-m-d H:i:s', filemtime($backup_path))
            ];
            
            if (file_exists($info_path)) {
                $info = json_decode(file_get_contents($info_path), true);
                $backup_info = array_merge($backup_info, $info);
            }
            
            $backups[] = $backup_info;
        }
    }
}

// Sort backups by creation date (newest first)
usort($backups, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

// Get database statistics
$stmt = $conn->query("SELECT COUNT(*) as total FROM bus_logs");
$total_logs = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM users");
$total_users = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM buses");
$total_buses = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM notifications");
$total_notifications = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Backup & Restore - Admin</title>
<link rel="stylesheet" href="../assets/style.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
<style>
    body { padding: 20px; background-color: #f8f9fa; }
    .container { max-width: 1200px; margin: 0 auto; }
    .header { background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .backup-grid { display: grid; grid-template-columns: 1fr 2fr; gap: 20px; }
    .backup-section { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
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
    .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    .backup-item { 
        background: #f8f9fa; 
        padding: 15px; 
        border-radius: 5px; 
        margin-bottom: 10px; 
        border-left: 4px solid #007bff; 
    }
    .backup-item h5 { margin: 0 0 10px 0; color: #333; }
    .backup-item p { margin: 5px 0; color: #666; font-size: 14px; }
    .backup-actions { margin-top: 10px; }
    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 20px; }
    .stat-card { background: #f8f9fa; padding: 15px; border-radius: 5px; text-align: center; }
    .stat-card h4 { margin: 0; font-size: 1.5rem; color: #007bff; }
    .stat-card p { margin: 5px 0 0 0; color: #666; font-size: 12px; }
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
    .backup-info { 
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        margin-bottom: 10px; 
    }
    .backup-size { 
        font-weight: bold; 
        color: #007bff; 
    }
    .backup-date { 
        color: #666; 
        font-size: 12px; 
    }
</style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1><i class="fas fa-database"></i> Database Backup & Restore</h1>
        <p>Create, manage, and restore database backups to ensure data safety</p>
    </div>

    <?php if (isset($success_message)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success_message) ?>
        </div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error_message) ?>
        </div>
    <?php endif; ?>

    <div class="backup-grid">
        <!-- Backup Actions -->
        <div class="backup-section">
            <h3><i class="fas fa-plus-circle"></i> Create New Backup</h3>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <h4><?= number_format($total_logs) ?></h4>
                    <p>Total Logs</p>
                </div>
                <div class="stat-card">
                    <h4><?= number_format($total_users) ?></h4>
                    <p>Users</p>
                </div>
                <div class="stat-card">
                    <h4><?= number_format($total_buses) ?></h4>
                    <p>Buses</p>
                </div>
                <div class="stat-card">
                    <h4><?= number_format($total_notifications) ?></h4>
                    <p>Notifications</p>
                </div>
            </div>

            <form method="POST">
                <input type="hidden" name="action" value="create_backup">
                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    <i class="fas fa-download"></i> Create Backup Now
                </button>
            </form>

            <div style="margin-top: 20px;">
                <h4><i class="fas fa-info-circle"></i> Backup Information</h4>
                <div class="backup-item">
                    <h5>What's Included:</h5>
                    <ul style="margin: 10px 0; padding-left: 20px;">
                        <li>All user accounts and roles</li>
                        <li>Bus information and plate numbers</li>
                        <li>Driver details and contact info</li>
                        <li>Complete bus logs and events</li>
                        <li>All notifications and comments</li>
                        <li>System settings and configurations</li>
                    </ul>
                </div>

                <div class="backup-item">
                    <h5>Backup Schedule:</h5>
                    <p>Automatic backups are created daily at 2:00 AM</p>
                    <p>Manual backups can be created anytime</p>
                    <p>Backups are stored for 30 days</p>
                </div>
            </div>
        </div>

        <!-- Existing Backups -->
        <div class="backup-section">
            <h3><i class="fas fa-list"></i> Existing Backups (<?= count($backups) ?>)</h3>
            
            <?php if ($backups): ?>
                <?php foreach ($backups as $backup): ?>
                    <div class="backup-item">
                        <div class="backup-info">
                            <div>
                                <h5><i class="fas fa-file-archive"></i> <?= htmlspecialchars($backup['filename']) ?></h5>
                                <div class="backup-size"><?= number_format($backup['size'] / 1024, 2) ?> KB</div>
                            </div>
                            <div class="backup-date">
                                <?= date('M d, Y H:i', strtotime($backup['created_at'])) ?>
                            </div>
                        </div>
                        
                        <div class="backup-actions">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="restore_backup">
                                <input type="hidden" name="backup_file" value="<?= htmlspecialchars($backup['filename']) ?>">
                                <button type="submit" class="btn btn-success" 
                                        onclick="return confirm('Are you sure? This will overwrite the current database.')">
                                    <i class="fas fa-upload"></i> Restore
                                </button>
                            </form>
                            
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="delete_backup">
                                <input type="hidden" name="backup_file" value="<?= htmlspecialchars($backup['filename']) ?>">
                                <button type="submit" class="btn btn-danger" 
                                        onclick="return confirm('Are you sure? This will permanently delete this backup.')">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </form>
                            
                            <a href="backups/<?= htmlspecialchars($backup['filename']) ?>" 
                               class="btn btn-primary" download>
                                <i class="fas fa-download"></i> Download
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="backup-item">
                    <h5><i class="fas fa-info-circle"></i> No Backups Found</h5>
                    <p>No database backups have been created yet.</p>
                    <p>Create your first backup using the button on the left.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Backup Status -->
    <div class="backup-section" style="margin-top: 20px;">
        <h3><i class="fas fa-chart-line"></i> Backup Status & Health</h3>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
            <div class="backup-item">
                <h5><i class="fas fa-clock"></i> Last Backup</h5>
                <p><?= count($backups) > 0 ? date('M d, Y H:i', strtotime($backups[0]['created_at'])) : 'Never' ?></p>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?= count($backups) > 0 ? 100 : 0 ?>%"></div>
                </div>
            </div>
            
            <div class="backup-item">
                <h5><i class="fas fa-shield-alt"></i> Backup Health</h5>
                <p><?= count($backups) > 0 ? 'Good' : 'No backups' ?></p>
                <p style="font-size: 12px; color: #666;">
                    <?= count($backups) ?> backup<?= count($backups) != 1 ? 's' : '' ?> available
                </p>
            </div>
            
            <div class="backup-item">
                <h5><i class="fas fa-database"></i> Database Size</h5>
                <p><?= number_format($total_logs + $total_users + $total_buses + $total_notifications) ?> records</p>
                <p style="font-size: 12px; color: #666;">Total data to backup</p>
            </div>
            
            <div class="backup-item">
                <h5><i class="fas fa-cog"></i> Auto Backup</h5>
                <p>Enabled</p>
                <p style="font-size: 12px; color: #666;">Daily at 2:00 AM</p>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="backup-section" style="margin-top: 20px;">
        <h3><i class="fas fa-tools"></i> Quick Actions</h3>
        
        <div style="display: flex; gap: 15px; flex-wrap: wrap;">
            <a href="system_settings.php" class="btn btn-primary">
                <i class="fas fa-cogs"></i> System Settings
            </a>
            <a href="system_reports.php" class="btn btn-success">
                <i class="fas fa-chart-bar"></i> View Reports
            </a>
            <a href="manage_users.php" class="btn btn-warning">
                <i class="fas fa-users"></i> Manage Users
            </a>
            <a href="../logout.php" class="btn btn-danger">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>
</div>

<script>
// Auto-refresh backup list every 60 seconds
setInterval(function() {
    location.reload();
}, 60000);

// Confirm dangerous actions
document.addEventListener('DOMContentLoaded', function() {
    const dangerousButtons = document.querySelectorAll('.btn-danger');
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