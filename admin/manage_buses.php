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
        $plate = trim($_POST['plate']);
        $capacity = (int)$_POST['capacity'];
        $driver_id = $_POST['driver_id'] ?: null;
        
        // Check if plate number already exists
        $stmt = $conn->prepare("SELECT COUNT(*) FROM buses WHERE plate_number = ?");
        $stmt->execute([$plate]);
        if ($stmt->fetchColumn() > 0) {
            $message = 'Plate number already exists!';
        } else {
            $stmt = $conn->prepare("INSERT INTO buses(plate_number, capacity, driver_id) VALUES(?, ?, ?)");
            if ($stmt->execute([$plate, $capacity, $driver_id])) {
                $message = 'Bus added successfully!';
            } else {
                $message = 'Error adding bus!';
            }
        }
    }
    
    if (isset($_POST['delete'])) {
        $bus_id = $_POST['bus_id'];
        $stmt = $conn->prepare("DELETE FROM buses WHERE bus_id = ?");
        if ($stmt->execute([$bus_id])) {
            $message = 'Bus deleted successfully!';
        } else {
            $message = 'Error deleting bus!';
        }
    }
    
    if (isset($_POST['edit'])) {
        $bus_id = $_POST['bus_id'];
        $plate = trim($_POST['plate']);
        $capacity = (int)$_POST['capacity'];
        $driver_id = $_POST['driver_id'] ?: null;
        
        // Check if plate number already exists for other buses
        $stmt = $conn->prepare("SELECT COUNT(*) FROM buses WHERE plate_number = ? AND bus_id != ?");
        $stmt->execute([$plate, $bus_id]);
        if ($stmt->fetchColumn() > 0) {
            $message = 'Plate number already exists!';
        } else {
            $stmt = $conn->prepare("UPDATE buses SET plate_number = ?, capacity = ?, driver_id = ? WHERE bus_id = ?");
            if ($stmt->execute([$plate, $capacity, $driver_id, $bus_id])) {
                $message = 'Bus updated successfully!';
            } else {
                $message = 'Error updating bus!';
            }
        }
    }
}

// Fetch buses with driver information
$buses = $conn->query("
    SELECT b.*, d.name as driver_name 
    FROM buses b 
    LEFT JOIN drivers d ON b.driver_id = d.driver_id 
    ORDER BY b.plate_number
")->fetchAll(PDO::FETCH_ASSOC);

// Fetch drivers for dropdown
$drivers = $conn->query("SELECT driver_id, name FROM drivers ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Buses - Admin</title>
<link rel="stylesheet" href="../assets/style.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
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
<div class="container">
    <div class="header">
        <h1><i class="fas fa-bus"></i> Manage Buses</h1>
        <p>Add, edit, and manage bus information including plate numbers, capacity, and assigned drivers.</p>
    </div>

    <?php if ($message): ?>
        <div class="alert <?= strpos($message, 'Error') !== false ? 'alert-danger' : 'alert-success' ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <!-- Statistics -->
    <div class="stats">
        <div class="stat-card">
            <h3><?= count($buses) ?></h3>
            <p>Total Buses</p>
        </div>
        <div class="stat-card">
            <h3><?= count(array_filter($buses, function($b) { return $b['driver_id']; })) ?></h3>
            <p>Assigned Drivers</p>
        </div>
        <div class="stat-card">
            <h3><?= count($drivers) ?></h3>
            <p>Available Drivers</p>
        </div>
    </div>

    <!-- Add Bus Form -->
    <div class="form-section">
        <h2><i class="fas fa-plus"></i> Add New Bus</h2>
        <form method="POST">
            <div class="form-group">
                <label for="plate">Plate Number:</label>
                <input type="text" id="plate" name="plate" required placeholder="e.g., RAC123B">
            </div>
            <div class="form-group">
                <label for="capacity">Capacity:</label>
                <input type="number" id="capacity" name="capacity" required min="1" max="100" placeholder="e.g., 20">
            </div>
            <div class="form-group">
                <label for="driver_id">Assigned Driver (Optional):</label>
                <select id="driver_id" name="driver_id">
                    <option value="">No Driver Assigned</option>
                    <?php foreach ($drivers as $driver): ?>
                        <option value="<?= $driver['driver_id'] ?>"><?= htmlspecialchars($driver['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" name="add" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Bus
            </button>
        </form>
    </div>

    <!-- Buses Table -->
    <div class="table-section">
        <h2><i class="fas fa-list"></i> All Buses</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Plate Number</th>
                    <th>Capacity</th>
                    <th>Assigned Driver</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($buses): ?>
                    <?php foreach ($buses as $bus): ?>
                        <tr>
                            <td><?= htmlspecialchars($bus['bus_id']) ?></td>
                            <td><strong><?= htmlspecialchars($bus['plate_number']) ?></strong></td>
                            <td><?= htmlspecialchars($bus['capacity']) ?> passengers</td>
                            <td>
                                <?php if ($bus['driver_name']): ?>
                                    <span style="color: #28a745;"><?= htmlspecialchars($bus['driver_name']) ?></span>
                                <?php else: ?>
                                    <span style="color: #6c757d; font-style: italic;">No driver assigned</span>
                                <?php endif; ?>
                            </td>
                            <td class="actions">
                                <button onclick="editBus(<?= $bus['bus_id'] ?>, '<?= htmlspecialchars($bus['plate_number']) ?>', <?= $bus['capacity'] ?>, <?= $bus['driver_id'] ?: 'null' ?>)" 
                                        class="btn btn-warning">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this bus?')">
                                    <input type="hidden" name="bus_id" value="<?= $bus['bus_id'] ?>">
                                    <button type="submit" name="delete" class="btn btn-danger">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align: center; color: #6c757d; padding: 20px;">
                            <i class="fas fa-bus fa-2x mb-2"></i><br>
                            No buses found. Add your first bus above.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Edit Bus Modal -->
<div id="editModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px; border-radius: 10px; min-width: 400px;">
        <h3><i class="fas fa-edit"></i> Edit Bus</h3>
        <form method="POST" id="editForm">
            <input type="hidden" name="bus_id" id="edit_bus_id">
            <div class="form-group">
                <label for="edit_plate">Plate Number:</label>
                <input type="text" id="edit_plate" name="plate" required>
            </div>
            <div class="form-group">
                <label for="edit_capacity">Capacity:</label>
                <input type="number" id="edit_capacity" name="capacity" required min="1" max="100">
            </div>
            <div class="form-group">
                <label for="edit_driver_id">Assigned Driver:</label>
                <select id="edit_driver_id" name="driver_id">
                    <option value="">No Driver Assigned</option>
                    <?php foreach ($drivers as $driver): ?>
                        <option value="<?= $driver['driver_id'] ?>"><?= htmlspecialchars($driver['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="text-align: right; margin-top: 20px;">
                <button type="button" onclick="closeEditModal()" class="btn btn-danger">Cancel</button>
                <button type="submit" name="edit" class="btn btn-primary">Update Bus</button>
            </div>
        </form>
    </div>
</div>

<script>
function editBus(busId, plate, capacity, driverId) {
    document.getElementById('edit_bus_id').value = busId;
    document.getElementById('edit_plate').value = plate;
    document.getElementById('edit_capacity').value = capacity;
    document.getElementById('edit_driver_id').value = driverId || '';
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
