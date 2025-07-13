<?php
session_start();
require_once "db.php";

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Find user by username
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        // Correct password
        $_SESSION['logged_in'] = true;
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        // Redirect by role
        if ($user['role'] == 'admin') {
            header("Location: admin/admin_dashboard.php");
        } elseif ($user['role'] == 'police') {
            header("Location: police/police_dashboard.php");
        } elseif ($user['role'] == 'driver') {
            header("Location: driver/driver_dashboard.php");
        } else {
            $error = "Unknown user role!";
        }
        exit;
    } else {
        $error = "Invalid username or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Login</title>
<link rel="stylesheet" href="assets/style.css" />
<style>
.login-container {
    max-width: 400px;
    margin: 100px auto;
    padding: 20px;
    background: #fff;
    border: 1px solid #ddd;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
}
.login-container h2 {
    text-align: center;
}
.login-container input[type="text"],
.login-container input[type="password"] {
    width: 100%;
    padding: 10px;
    margin: 8px 0;
}
.login-container input[type="submit"] {
    width: 100%;
    padding: 10px;
    background: #2c3e50;
    color: white;
    border: none;
    cursor: pointer;
}
.error { color: red; text-align: center; }
</style>
</head>
<body>
<div class="login-container">
    <h2>Login</h2>
    <?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
    <form method="post">
        <input type="text" name="username" placeholder="Username" required />
        <input type="password" name="password" placeholder="Password" required />
        <input type="submit" value="Login" />
    </form>
</div>
</body>
</html>
