<?php
require_once "db.php";

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

   

if ($user && $password === $user['password']) {
    // Start session if you want to keep user logged in later
    // session_start();
    // $_SESSION['username'] = $user['username'];
    // $_SESSION['role'] = $user['role'];

    // Redirect by role
    if ($user['role'] === 'admin') {
        header("Location: admin/admin_dashboard.php");
        exit;
    } elseif ($user['role'] === 'police') {
        header("Location: police/police_dashboard.php");
        exit;
    } elseif ($user['role'] === 'driver') {
        header("Location: driver/driver_dashboard.php");
        exit;
    } else {
        echo "Unknown user role.";
    }
} else {
    $error = "Invalid username or password.";
}
}

?>
