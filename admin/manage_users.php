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
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $role = $_POST['role'];
        $password = $username; // Default password is username
        
        // Check if username already exists
        $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetchColumn() > 0) {
            $message = 'Username already exists!';
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$username, $email, $hashed_password, $role])) {
                $message = 'User added successfully! Password is: ' . $password;
            } else {
                $message = 'Error adding user!';
            }
        }
    }
    
    if (isset($_POST['delete'])) {
        $user_id = $_POST['user_id'];
        // Prevent admin from deleting themselves
        if ($user_id == $_SESSION['user_id']) {
            $message = 'You cannot delete your own account!';
        } else {
            $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
            if ($stmt->execute([$user_id])) {
                $message = 'User deleted successfully!';
            } else {
                $message = 'Error deleting user!';
            }
        }
    }
    
    if (isset($_POST['edit'])) {
        $user_id = $_POST['user_id'];
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $role = $_POST['role'];
        
        // Check if username already exists for other users
        $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = ? AND user_id != ?");
        $stmt->execute([$username, $user_id]);
        if ($stmt->fetchColumn() > 0) {
            $message = 'Username already exists!';
        } else {
            $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, role = ? WHERE user_id = ?");
            if ($stmt->execute([$username, $email, $role, $user_id])) {
                $message = 'User updated successfully!';
            } else {
                $message = 'Error updating user!';
            }
        }
    }
    
    if (isset($_POST['reset_password'])) {
        $user_id = $_POST['user_id'];
        $username = $_POST['username'];
        $new_password = $username; // Reset to username
        
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        if ($stmt->execute([$hashed_password, $user_id])) {
            $message = 'Password reset successfully! New password is: ' . $new_password;
        } else {
            $message = 'Error resetting password!';
        }
    }
}

// Fetch all users
$users = $conn->query("SELECT user_id, username, email, role, created_at FROM users ORDER BY role, username")->fetchAll(PDO::FETCH_ASSOC);

// Get role counts
$role_counts = ['admin' => 0, 'driver' => 0, 'police' => 0];
foreach ($users as $user) {
    $role_counts[$user['role']]++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Users - Admin</title>
<link rel="stylesheet" href="../assets/style.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
<style>
    body { padding: 20px; background-color: #f8f9fa; }
    .container { max-width: 1200px; margin: 0 auto; }
    .header { background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .stats { display: flex; gap: 20px; margin-bottom: 20px; }
    .stat-card { background: white; padding: 20px; border-radius: 10px; flex: 1; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .stat-card h3 { margin: 0; color: #007bff; font-size: 2rem; }
    .form-section { background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .table-section { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
    th { background-color: #f8f9fa; font-weight: bold; }
    .role-admin { color: #dc3545; font-weight: bold; }
    .role-driver { color: #007bff; font-weight: bold; }
    .role-police { color: #28a745; font-weight: bold; }
    .btn { padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; margin: 2px; }
    .btn-primary { background: #007bff; color: white; }
    .btn-danger { background: #dc3545; color: white; }
    .btn-warning { background: #ffc107; color: #000; }
    .btn-success { background: #28a745; color: white; }
    .btn:hover { opacity: 0.8; }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
    .form-group input, .form-group select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
    .alert { padding: 15px; border-radius: 4px; margin-bottom: 20px; }
    .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    .actions { display: flex; gap: 5px; flex-wrap: wrap; }
    .current-user { background-color: #fff3cd; }
</style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1><i class="fas fa-users"></i> Manage Users</h1>
        <p>Manage all system users including admins, drivers, and police officers.</p>
    </div>

    <?php if ($message): ?>
        <div class="alert <?= strpos($message, 'Error') !== false ? 'alert-danger' : 'alert-success' ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <!-- Statistics -->
    <div class="stats">
        <div class="stat-card">
            <h3><?= count($users) ?></h3>
            <p>Total Users</p>
        </div>
        <div class="stat-card">
            <h3><?= $role_counts['admin'] ?></h3>
            <p>Admins</p>
        </div>
        <div class="stat-card">
            <h3><?= $role_counts['driver'] ?></h3>
            <p>Drivers</p>
        </div>
        <div class="stat-card">
            <h3><?= $role_counts['police'] ?></h3>
            <p>Police</p>
        </div>
    </div>

    <!-- Add User Form -->
    <div class="form-section">
        <h2><i class="fas fa-plus"></i> Add New User</h2>
        <form method="POST">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="role">Role:</label>
                <select id="role" name="role" required>
                    <option value="">Select Role</option>
                    <option value="admin">Admin</option>
                    <option value="driver">Driver</option>
                    <option value="police">Police</option>
                </select>
            </div>
            <button type="submit" name="add" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add User
            </button>
        </form>
        <p><small><i class="fas fa-info-circle"></i> Default password will be the username. Users can change it later.</small></p>
    </div>

    <!-- Users Table -->
    <div class="table-section">
        <h2><i class="fas fa-list"></i> All Users</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr class="<?= $user['user_id'] == $_SESSION['user_id'] ? 'current-user' : '' ?>">
                        <td><?= htmlspecialchars($user['user_id']) ?></td>
                        <td><?= htmlspecialchars($user['username']) ?></td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td>
                            <span class="role-<?= $user['role'] ?>">
                                <?= ucfirst(htmlspecialchars($user['role'])) ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($user['created_at']) ?></td>
                        <td class="actions">
                            <button onclick="editUser(<?= $user['user_id'] ?>, '<?= htmlspecialchars($user['username']) ?>', '<?= htmlspecialchars($user['email']) ?>', '<?= $user['role'] ?>')" 
                                    class="btn btn-warning" <?= $user['user_id'] == $_SESSION['user_id'] ? 'disabled' : '' ?>>
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Reset password to username?')">
                                <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">
                                <input type="hidden" name="username" value="<?= htmlspecialchars($user['username']) ?>">
                                <button type="submit" name="reset_password" class="btn btn-success">
                                    <i class="fas fa-key"></i> Reset Password
                                </button>
                            </form>
                            <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this user?')">
                                    <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">
                                    <button type="submit" name="delete" class="btn btn-danger">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Edit User Modal -->
<div id="editModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px; border-radius: 10px; min-width: 400px;">
        <h3><i class="fas fa-edit"></i> Edit User</h3>
        <form method="POST" id="editForm">
            <input type="hidden" name="user_id" id="edit_user_id">
            <div class="form-group">
                <label for="edit_username">Username:</label>
                <input type="text" id="edit_username" name="username" required>
            </div>
            <div class="form-group">
                <label for="edit_email">Email:</label>
                <input type="email" id="edit_email" name="email" required>
            </div>
            <div class="form-group">
                <label for="edit_role">Role:</label>
                <select id="edit_role" name="role" required>
                    <option value="admin">Admin</option>
                    <option value="driver">Driver</option>
                    <option value="police">Police</option>
                </select>
            </div>
            <div style="text-align: right; margin-top: 20px;">
                <button type="button" onclick="closeEditModal()" class="btn btn-danger">Cancel</button>
                <button type="submit" name="edit" class="btn btn-primary">Update User</button>
            </div>
        </form>
    </div>
</div>

<script>
function editUser(userId, username, email, role) {
    document.getElementById('edit_user_id').value = userId;
    document.getElementById('edit_username').value = username;
    document.getElementById('edit_email').value = email;
    document.getElementById('edit_role').value = role;
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
