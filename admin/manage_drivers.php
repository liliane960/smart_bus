<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}
require_once "../db.php";

$message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add'])) {
        $name = trim($_POST['name']);
        $phone = trim($_POST['phone']);
        
        // Check if driver name already exists
        $stmt = $conn->prepare("SELECT COUNT(*) FROM drivers WHERE name = ?");
        $stmt->execute([$name]);
        if ($stmt->fetchColumn() > 0) {
            $message = 'Driver name already exists!';
        } else {
            $stmt = $conn->prepare("INSERT INTO drivers(name, phone) VALUES(?, ?)");
            if ($stmt->execute([$name, $phone])) {
                $message = 'Driver added successfully!';
            } else {
                $message = 'Error adding driver!';
            }
        }
    }
    
    if (isset($_POST['delete'])) {
        $driver_id = $_POST['driver_id'];
        
        // Check if driver is assigned to any bus
        $stmt = $conn->prepare("SELECT COUNT(*) FROM buses WHERE driver_id = ?");
        $stmt->execute([$driver_id]);
        if ($stmt->fetchColumn() > 0) {
            $message = 'Cannot delete driver who is assigned to a bus!';
        } else {
            $stmt = $conn->prepare("DELETE FROM drivers WHERE driver_id = ?");
            if ($stmt->execute([$driver_id])) {
                $message = 'Driver deleted successfully!';
            } else {
                $message = 'Error deleting driver!';
            }
        }
    }
    
    if (isset($_POST['edit'])) {
        $driver_id = $_POST['driver_id'];
        $name = trim($_POST['name']);
        $phone = trim($_POST['phone']);
        
        // Check if driver name already exists for other drivers
        $stmt = $conn->prepare("SELECT COUNT(*) FROM drivers WHERE name = ? AND driver_id != ?");
        $stmt->execute([$name, $driver_id]);
        if ($stmt->fetchColumn() > 0) {
            $message = 'Driver name already exists!';
        } else {
            $stmt = $conn->prepare("UPDATE drivers SET name = ?, phone = ? WHERE driver_id = ?");
            if ($stmt->execute([$name, $phone, $driver_id])) {
                $message = 'Driver updated successfully!';
            } else {
                $message = 'Error updating driver!';
            }
        }
    }
}

// Fetch all drivers with bus assignment info
$drivers = $conn->query("
    SELECT d.*, b.plate_number, b.bus_id 
    FROM drivers d 
    LEFT JOIN buses b ON d.driver_id = b.driver_id 
    ORDER BY d.name
")->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$total_drivers = count($drivers);
$assigned_drivers = count(array_filter($drivers, function($d) { return $d['bus_id']; }));
$unassigned_drivers = $total_drivers - $assigned_drivers;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Drivers - Admin</title>
<link rel="stylesheet" href="../assets/style.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
<style>
    body { padding: 20px; background-color: #f8f9fa; }
    .container { max-width: 1200px; margin: 0 auto; }
    .header { background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .form-section { background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .table-section { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
    th { background-color: #f8f9fa; font-weight: bold; }
    .btn { padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; margin: 2px; }
    .btn-primary { background: #007bff; color: white; }
    .btn-danger { background: #dc3545; color: white; }
    .btn-warning { background: #ffc107; color: #000; }
    .btn:hover { opacity: 0.8; }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
    .form-group input { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
    .alert { padding: 15px; border-radius: 4px; margin-bottom: 20px; }
    .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    .actions { display: flex; gap: 5px; flex-wrap: wrap; }
    .stats { display: flex; gap: 20px; margin-bottom: 20px; }
    .stat-card { background: white; padding: 20px; border-radius: 10px; flex: 1; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .stat-card h3 { margin: 0; color: #007bff; font-size: 2rem; }
    .assigned { color: #28a745; font-weight: bold; }
    .unassigned { color: #6c757d; font-style: italic; }
</style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1><i class="fas fa-user-tie"></i> Manage Drivers</h1>
        <p>Add, edit, and manage driver information including their bus assignments.</p>
    </div>

    <?php if ($message): ?>
        <div class="alert <?= strpos($message, 'Error') !== false ? 'alert-danger' : 'alert-success' ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <!-- Statistics -->
    <div class="stats">
        <div class="stat-card">
            <h3><?= $total_drivers ?></h3>
            <p>Total Drivers</p>
        </div>
        <div class="stat-card">
            <h3><?= $assigned_drivers ?></h3>
            <p>Assigned to Buses</p>
        </div>
        <div class="stat-card">
            <h3><?= $unassigned_drivers ?></h3>
            <p>Unassigned</p>
        </div>
    </div>

    <!-- Add Driver Form -->
    <div class="form-section">
        <h2><i class="fas fa-plus"></i> Add New Driver</h2>
        <form method="POST">
            <div class="form-group">
                <label for="name">Driver Name:</label>
                <input type="text" id="name" name="name" required placeholder="e.g., John Doe">
            </div>
            <div class="form-group">
                <label for="phone">Phone Number:</label>
                <input type="tel" id="phone" name="phone" required placeholder="e.g., +1234567890">
            </div>
            <button type="submit" name="add" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Driver
            </button>
        </form>
    </div>

    <!-- Drivers Table -->
    <div class="table-section">
        <h2><i class="fas fa-list"></i> All Drivers</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Assigned Bus</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($drivers): ?>
                    <?php foreach ($drivers as $driver): ?>
                        <tr>
                            <td><?= htmlspecialchars($driver['driver_id']) ?></td>
                            <td><strong><?= htmlspecialchars($driver['name']) ?></strong></td>
                            <td><?= htmlspecialchars($driver['phone']) ?></td>
                            <td>
                                <?php if ($driver['plate_number']): ?>
                                    <span class="assigned">
                                        <i class="fas fa-bus"></i> <?= htmlspecialchars($driver['plate_number']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="unassigned">No bus assigned</span>
                                <?php endif; ?>
                            </td>
                            <td class="actions">
                                <button onclick="editDriver(<?= $driver['driver_id'] ?>, '<?= htmlspecialchars($driver['name']) ?>', '<?= htmlspecialchars($driver['phone']) ?>')" 
                                        class="btn btn-warning">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <?php if (!$driver['plate_number']): ?>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this driver?')">
                                        <input type="hidden" name="driver_id" value="<?= $driver['driver_id'] ?>">
                                        <button type="submit" name="delete" class="btn btn-danger">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <button class="btn btn-danger" disabled title="Cannot delete driver assigned to a bus">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align: center; color: #6c757d; padding: 20px;">
                            <i class="fas fa-user-tie fa-2x mb-2"></i><br>
                            No drivers found. Add your first driver above.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Edit Driver Modal -->
<div id="editModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px; border-radius: 10px; min-width: 400px;">
        <h3><i class="fas fa-edit"></i> Edit Driver</h3>
        <form method="POST" id="editForm">
            <input type="hidden" name="driver_id" id="edit_driver_id">
            <div class="form-group">
                <label for="edit_name">Driver Name:</label>
                <input type="text" id="edit_name" name="name" required>
            </div>
            <div class="form-group">
                <label for="edit_phone">Phone Number:</label>
                <input type="tel" id="edit_phone" name="phone" required>
            </div>
            <div style="text-align: right; margin-top: 20px;">
                <button type="button" onclick="closeEditModal()" class="btn btn-danger">Cancel</button>
                <button type="submit" name="edit" class="btn btn-primary">Update Driver</button>
            </div>
        </form>
    </div>
</div>

<script>
function editDriver(driverId, name, phone) {
    document.getElementById('edit_driver_id').value = driverId;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_phone').value = phone;
    document.getElementById('editModal').style.display = 'block';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

// Close modal when clicking outside
document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeEditModal();
    }
});
</script>
</body>
</html>
