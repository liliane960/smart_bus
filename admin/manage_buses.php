<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}
require_once "../database/db.php";

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

        // Check if plate number already exists for another bus
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

// Fetch buses with driver info
$buses = $conn->query("
    SELECT b.*, d.name as driver_name 
    FROM buses b 
    LEFT JOIN drivers d ON b.driver_id = d.driver_id 
    ORDER BY b.plate_number
")->fetchAll(PDO::FETCH_ASSOC);

// Fetch drivers for dropdown
$drivers = $conn->query("SELECT driver_id, name FROM drivers ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
?>
<!-- Modern Manage Buses UI using project styles -->
<div class="main-content-section">
    <div class="header-row" style="display:flex;align-items:center;gap:10px;margin-bottom:24px;">
        <span style="font-size:2rem;color:#007bff;"><i class="fas fa-bus"></i></span>
        <h2 style="margin:0;color:#222;font-weight:700;">Manage Buses</h2>
    </div>
    <?php if ($message): ?>
        <div class="alert alert-info" style="margin-bottom:20px;"> <?= htmlspecialchars($message) ?> </div>
    <?php endif; ?>
    <div class="card" style="background:#fff;border-radius:10px;padding:24px 20px;margin-bottom:32px;box-shadow:0 2px 8px rgba(0,0,0,0.06);">
        <form method="POST" style="display:flex;flex-wrap:wrap;gap:16px;align-items:flex-end;">
            <div style="flex:1;min-width:180px;">
                <label for="plate" style="font-weight:600;">Plate Number</label>
                <input type="text" id="plate" name="plate" class="form-control" required placeholder="e.g., RAC123B" style="width:100%;padding:8px 10px;margin-top:4px;">
            </div>
            <div style="flex:1;min-width:120px;">
                <label for="capacity" style="font-weight:600;">Capacity</label>
                <input type="number" id="capacity" name="capacity" class="form-control" required min="1" max="100" placeholder="e.g., 20" style="width:100%;padding:8px 10px;margin-top:4px;">
            </div>
            <div style="flex:1;min-width:180px;">
                <label for="driver_id" style="font-weight:600;">Assigned Driver (Optional)</label>
                <select id="driver_id" name="driver_id" class="form-control" style="width:100%;padding:8px 10px;margin-top:4px;">
                    <option value="">No Driver Assigned</option>
                    <?php foreach ($drivers as $driver): ?>
                        <option value="<?= $driver['driver_id'] ?>"><?= htmlspecialchars($driver['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="min-width:120px;">
                <button type="submit" name="add" class="btn btn-primary" style="width:100%;padding:10px 0;font-weight:600;">
                    <i class="fas fa-plus"></i> Add Bus
                </button>
            </div>
        </form>
    </div>
    <div class="card" style="background:#fff;border-radius:10px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,0.06);">
        <h3 style="margin-top:0;margin-bottom:18px;color:#007bff;font-weight:600;"><i class="fas fa-list"></i> All Buses</h3>
        <div class="table-responsive">
            <table class="table" style="width:100%;border-collapse:collapse;">
                <thead style="background:#007bff;color:#fff;">
                    <tr>
                        <th>bus_id</th>
                        <th>plate_number</th>
                        <th>capacity</th>
                        <th>status</th>
                        <th>driver_id</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (count($buses) > 0): ?>
                    <?php foreach ($buses as $bus): ?>
                        <tr style="border-bottom:1px solid #eee;">
                            <td><?= htmlspecialchars($bus['bus_id']) ?></td>
                            <td style="font-weight:600;"><?= htmlspecialchars($bus['plate_number']) ?></td>
                            <td><?= htmlspecialchars($bus['capacity']) ?></td>
                            <td><?= htmlspecialchars($bus['status'] ?? '') ?></td>
                            <td><?= htmlspecialchars($bus['driver_id'] ?? '') ?></td>
                            <td style="display:flex;gap:6px;">
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="bus_id" value="<?= $bus['bus_id'] ?>">
                                    <button type="submit" name="delete" class="btn btn-danger btn-sm" title="Delete" onclick="return confirm('Delete this bus?')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            <i class="fas fa-bus fa-2x mb-2"></i><br>
                            No buses found. Add your first bus above.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<!-- Edit Modal Removed -->
<script>
</script>
