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

        $stmt = $conn->prepare("SELECT COUNT(*) FROM drivers WHERE name = ?");
        $stmt->execute([$name]);
        if ($stmt->fetchColumn() > 0) {
            $message = 'Driver name already exists!';
        } else {
            $stmt = $conn->prepare("INSERT INTO drivers(name, phone) VALUES(?, ?)");
            $message = $stmt->execute([$name, $phone]) ? 'Driver added successfully!' : 'Error adding driver!';
        }
    }

    if (isset($_POST['delete'])) {
        $driver_id = $_POST['driver_id'];

        $stmt = $conn->prepare("SELECT COUNT(*) FROM buses WHERE driver_id = ?");
        $stmt->execute([$driver_id]);
        if ($stmt->fetchColumn() > 0) {
            $message = 'Cannot delete driver who is assigned to a bus!';
        } else {
            $stmt = $conn->prepare("DELETE FROM drivers WHERE driver_id = ?");
            $message = $stmt->execute([$driver_id]) ? 'Driver deleted successfully!' : 'Error deleting driver!';
        }
    }

    if (isset($_POST['edit'])) {
        $driver_id = $_POST['driver_id'];
        $name = trim($_POST['name']);
        $phone = trim($_POST['phone']);

        $stmt = $conn->prepare("SELECT COUNT(*) FROM drivers WHERE name = ? AND driver_id != ?");
        $stmt->execute([$name, $driver_id]);
        if ($stmt->fetchColumn() > 0) {
            $message = 'Driver name already exists!';
        } else {
            $stmt = $conn->prepare("UPDATE drivers SET name = ?, phone = ? WHERE driver_id = ?");
            $message = $stmt->execute([$name, $phone, $driver_id]) ? 'Driver updated successfully!' : 'Error updating driver!';
        }
    }
}

$drivers = $conn->query("
    SELECT d.*, b.plate_number, b.bus_id 
    FROM drivers d 
    LEFT JOIN buses b ON d.driver_id = b.driver_id 
    ORDER BY d.name
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Drivers - Admin</title>
<link rel="stylesheet" href="../assets/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
/* Table styling */
table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    margin-top: 20px;
}
table th, table td {
    padding: 12px 15px;
    text-align: center;
    font-size: 14px;
}
table thead {
    background-color: #007bff;
    color: white;
}
table tr:nth-child(even) {
    background-color: #f9f9f9;
}
table tr:hover {
    background-color: #f1f1f1;
}

/* Form styling */
.form-section {
    background: white;
    padding: 20px;
    border-radius: 10px;
    margin: 20px 0;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}
.form-section h2 {
    color: #007bff;
    margin-bottom: 15px;
}
.form-group {
    margin-bottom: 15px;
}
.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
}
.form-group input[type="text"],
.form-group input[type="tel"] {
    width: 100%;
    padding: 8px 10px;
    border: 1px solid #ccc;
    border-radius: 5px;
    font-size: 14px;
}

/* Buttons */
.btn {
    display: inline-block;
    padding: 6px 12px;
    font-size: 14px;
    font-weight: 500;
    text-decoration: none;
    cursor: pointer;
    border: none;
    border-radius: 4px;
    transition: background 0.3s ease;
}
.btn-primary {
    background-color: #007bff;
    color: white;
}
.btn-primary:hover { background-color: #0056b3; }
.btn-warning {
    background-color: #ffc107;
    color: #212529;
}
.btn-warning:hover { background-color: #e0a800; }
.btn-danger {
    background-color: #dc3545;
    color: white;
}
.btn-danger:hover { background-color: #c82333; }

/* Table section title */
.table-section h2 {
    color: #007bff;
    margin-top: 30px;
    margin-bottom: 10px;
}

/* Modal styling */
#editModal {
    display: none;
    position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
}
#editModal > div {
    background: white;
    padding: 25px;
    border-radius: 10px;
    width: 400px;
    max-width: 90%;
    margin: 100px auto;
    box-shadow: 0 2px 10px rgba(0,0,0,0.2);
}
#editModal h3 {
    color: #007bff;
    margin-bottom: 15px;
}

/* Status text */
.assigned {
    color: #28a745;
    font-weight: bold;
}
.unassigned {
    color: #6c757d;
    font-style: italic;
}

/* Action buttons in table */
.actions button {
    margin-right: 5px;
}
</style>
</head>
<body>

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

<!-- Edit Driver Modal -->
<div id="editModal">
    <div>
        <h3><i class="fas fa-edit"></i> Edit Driver</h3>
        <form method="POST">
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
document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) { closeEditModal(); }
});
</script>
</body>
</html>
