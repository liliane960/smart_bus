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
            case 'add_bus':
                $plate_number = trim($_POST['plate_number']);
                $capacity = (int)$_POST['capacity'];
                
                // Check if plate number already exists
                $stmt = $conn->prepare("SELECT COUNT(*) FROM buses WHERE plate_number = ?");
                $stmt->execute([$plate_number]);
                if ($stmt->fetchColumn() > 0) {
                    $error_message = "Plate number already exists!";
                } else {
                    $stmt = $conn->prepare("INSERT INTO buses (plate_number, capacity) VALUES (?, ?)");
                    if ($stmt->execute([$plate_number, $capacity])) {
                        $success_message = "Bus added successfully!";
                    } else {
                        $error_message = "Failed to add bus.";
                    }
                }
                break;
                
            case 'update_bus':
                $bus_id = (int)$_POST['bus_id'];
                $plate_number = trim($_POST['plate_number']);
                $capacity = (int)$_POST['capacity'];
                
                $stmt = $conn->prepare("UPDATE buses SET plate_number = ?, capacity = ? WHERE bus_id = ?");
                if ($stmt->execute([$plate_number, $capacity, $bus_id])) {
                    $success_message = "Bus updated successfully!";
                } else {
                    $error_message = "Failed to update bus.";
                }
                break;
                
            case 'delete_bus':
                $bus_id = (int)$_POST['bus_id'];
                
                // Check if bus has logs
                $stmt = $conn->prepare("SELECT COUNT(*) FROM bus_logs WHERE bus_id = ?");
                $stmt->execute([$bus_id]);
                if ($stmt->fetchColumn() > 0) {
                    $error_message = "Cannot delete bus with existing logs. Please delete logs first.";
                } else {
                    $stmt = $conn->prepare("DELETE FROM buses WHERE bus_id = ?");
                    if ($stmt->execute([$bus_id])) {
                        $success_message = "Bus deleted successfully!";
                    } else {
                        $error_message = "Failed to delete bus.";
                    }
                }
                break;
        }
    }
}

// Get all buses with statistics
$stmt = $conn->query("
    SELECT 
        b.*,
        COUNT(bl.id) as total_logs,
        COUNT(CASE WHEN bl.status = 'overloading' THEN 1 END) as overloading_count,
        MAX(bl.passenger_count) as max_passengers,
        AVG(bl.passenger_count) as avg_passengers,
        MAX(bl.created_at) as last_activity
    FROM buses b
    LEFT JOIN bus_logs bl ON b.bus_id = bl.bus_id
    GROUP BY b.bus_id, b.plate_number, b.capacity, b.created_at
    ORDER BY b.plate_number
");
$buses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stmt = $conn->query("SELECT COUNT(*) as total FROM buses");
$total_buses = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM bus_logs");
$total_logs = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM bus_logs WHERE status = 'overloading'");
$total_overloading = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Plate Number Management - Admin</title>
<link rel="stylesheet" href="../assets/style.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
<style>
    body { padding: 20px; background-color: #f8f9fa; }
    .container { max-width: 1400px; margin: 0 auto; }
    .header { background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 20px; }
    .stat-card { background: white; padding: 20px; border-radius: 10px; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .stat-card h3 { margin: 0; font-size: 2rem; color: #007bff; }
    .stat-card p { margin: 5px 0 0 0; color: #666; }
    .btn { 
        padding: 8px 16px; 
        border: none; 
        border-radius: 4px; 
        cursor: pointer; 
        text-decoration: none; 
        display: inline-block; 
        margin: 2px; 
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
    .bus-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
    .bus-card { 
        background: white; 
        padding: 20px; 
        border-radius: 10px; 
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        border-left: 4px solid #007bff;
        transition: transform 0.3s ease;
    }
    .bus-card:hover { transform: translateY(-5px); }
    .bus-header { 
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        margin-bottom: 15px; 
    }
    .plate-number { 
        font-size: 1.5rem; 
        font-weight: bold; 
        color: #007bff; 
    }
    .bus-stats { 
        display: grid; 
        grid-template-columns: 1fr 1fr; 
        gap: 10px; 
        margin: 15px 0; 
    }
    .stat-item { 
        background: #f8f9fa; 
        padding: 10px; 
        border-radius: 5px; 
        text-align: center; 
    }
    .stat-item h4 { margin: 0; font-size: 1.2rem; color: #333; }
    .stat-item p { margin: 5px 0 0 0; color: #666; font-size: 12px; }
    .bus-actions { margin-top: 15px; }
    .modal { 
        display: none; 
        position: fixed; 
        z-index: 1000; 
        left: 0; 
        top: 0; 
        width: 100%; 
        height: 100%; 
        background-color: rgba(0,0,0,0.5); 
    }
    .modal-content { 
        background-color: white; 
        margin: 10% auto; 
        padding: 20px; 
        border-radius: 10px; 
        width: 80%; 
        max-width: 500px; 
    }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
    .form-group input { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
    .close { 
        color: #aaa; 
        float: right; 
        font-size: 28px; 
        font-weight: bold; 
        cursor: pointer; 
    }
    .close:hover { color: #000; }
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
        <h1><i class="fas fa-id-card"></i> Plate Number Management</h1>
        <p>Manage bus plate numbers, capacities, and view performance statistics</p>
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

    <!-- Statistics -->
    <div class="stats-grid">
        <div class="stat-card">
            <h3><?= number_format($total_buses) ?></h3>
            <p><i class="fas fa-bus"></i> Total Buses</p>
        </div>
        <div class="stat-card">
            <h3><?= number_format($total_logs) ?></h3>
            <p><i class="fas fa-chart-line"></i> Total Logs</p>
        </div>
        <div class="stat-card">
            <h3><?= number_format($total_overloading) ?></h3>
            <p><i class="fas fa-exclamation-triangle"></i> Overloading Events</p>
        </div>
        <div class="stat-card">
            <h3><?= $total_logs > 0 ? number_format(($total_overloading / $total_logs) * 100, 1) : 0 ?>%</h3>
            <p><i class="fas fa-percentage"></i> Overloading Rate</p>
        </div>
    </div>

    <!-- Add New Bus Button -->
    <div style="margin-bottom: 20px;">
        <button class="btn btn-primary" onclick="openAddModal()">
            <i class="fas fa-plus"></i> Add New Bus
        </button>
    </div>

    <!-- Bus Cards -->
    <div class="bus-grid">
        <?php if ($buses): ?>
            <?php foreach ($buses as $bus): ?>
                <?php
                $overloading_rate = $bus['total_logs'] > 0 ? ($bus['overloading_count'] / $bus['total_logs']) * 100 : 0;
                $performance_class = $overloading_rate == 0 ? 'performance-good' : 
                                   ($overloading_rate < 10 ? 'performance-warning' : 'performance-danger');
                $performance_text = $overloading_rate == 0 ? 'Excellent' : 
                                  ($overloading_rate < 10 ? 'Good' : 'Needs Attention');
                ?>
                <div class="bus-card">
                    <div class="bus-header">
                        <div class="plate-number"><?= htmlspecialchars($bus['plate_number']) ?></div>
                        <span class="performance-indicator <?= $performance_class ?>">
                            <?= $performance_text ?>
                        </span>
                    </div>
                    
                    <div class="bus-stats">
                        <div class="stat-item">
                            <h4><?= number_format($bus['capacity']) ?></h4>
                            <p>Capacity</p>
                        </div>
                        <div class="stat-item">
                            <h4><?= number_format($bus['total_logs']) ?></h4>
                            <p>Total Events</p>
                        </div>
                        <div class="stat-item">
                            <h4><?= number_format($bus['overloading_count']) ?></h4>
                            <p>Overloading</p>
                        </div>
                        <div class="stat-item">
                            <h4><?= number_format($bus['avg_passengers'], 1) ?></h4>
                            <p>Avg Passengers</p>
                        </div>
                    </div>
                    
                    <div style="margin: 10px 0; font-size: 12px; color: #666;">
                        <strong>Max Passengers:</strong> <?= number_format($bus['max_passengers']) ?><br>
                        <strong>Last Activity:</strong> <?= $bus['last_activity'] ? date('M d, Y H:i', strtotime($bus['last_activity'])) : 'Never' ?>
                    </div>
                    
                    <div class="bus-actions">
                        <button class="btn btn-warning" onclick="openEditModal(<?= $bus['bus_id'] ?>, '<?= htmlspecialchars($bus['plate_number']) ?>', <?= $bus['capacity'] ?>)">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button class="btn btn-danger" onclick="deleteBus(<?= $bus['bus_id'] ?>, '<?= htmlspecialchars($bus['plate_number']) ?>')">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                        <a href="view_logs.php?bus_id=<?= $bus['bus_id'] ?>" class="btn btn-primary">
                            <i class="fas fa-eye"></i> View Logs
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="bus-card" style="grid-column: 1 / -1; text-align: center;">
                <h3><i class="fas fa-info-circle"></i> No Buses Found</h3>
                <p>No buses have been registered yet.</p>
                <button class="btn btn-primary" onclick="openAddModal()">
                    <i class="fas fa-plus"></i> Add First Bus
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Bus Modal -->
<div id="addModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('addModal')">&times;</span>
        <h3><i class="fas fa-plus"></i> Add New Bus</h3>
        <form method="POST">
            <input type="hidden" name="action" value="add_bus">
            <div class="form-group">
                <label for="plate_number">Plate Number:</label>
                <input type="text" id="plate_number" name="plate_number" required>
            </div>
            <div class="form-group">
                <label for="capacity">Capacity:</label>
                <input type="number" id="capacity" name="capacity" min="10" max="200" value="50" required>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Add Bus
            </button>
        </form>
    </div>
</div>

<!-- Edit Bus Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('editModal')">&times;</span>
        <h3><i class="fas fa-edit"></i> Edit Bus</h3>
        <form method="POST">
            <input type="hidden" name="action" value="update_bus">
            <input type="hidden" id="edit_bus_id" name="bus_id">
            <div class="form-group">
                <label for="edit_plate_number">Plate Number:</label>
                <input type="text" id="edit_plate_number" name="plate_number" required>
            </div>
            <div class="form-group">
                <label for="edit_capacity">Capacity:</label>
                <input type="number" id="edit_capacity" name="capacity" min="10" max="200" required>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Update Bus
            </button>
        </form>
    </div>
</div>

<!-- Delete Bus Form -->
<form id="deleteForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="delete_bus">
    <input type="hidden" id="delete_bus_id" name="bus_id">
</form>

<script>
function openAddModal() {
    document.getElementById('addModal').style.display = 'block';
}

function openEditModal(busId, plateNumber, capacity) {
    document.getElementById('edit_bus_id').value = busId;
    document.getElementById('edit_plate_number').value = plateNumber;
    document.getElementById('edit_capacity').value = capacity;
    document.getElementById('editModal').style.display = 'block';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

function deleteBus(busId, plateNumber) {
    if (confirm('Are you sure you want to delete bus ' + plateNumber + '? This action cannot be undone.')) {
        document.getElementById('delete_bus_id').value = busId;
        document.getElementById('deleteForm').submit();
    }
}

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
}
</script>
</body>
</html> 