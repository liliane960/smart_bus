<?php
// session_start();
// if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
//     header("Location: ../login.php");
//     exit;
// }
// ?>
<?php require_once "../db.php"; ?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Smart Bus Dashboard</title>
<link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<div class="dashboard">
    <aside class="sidebar">
        <h2>Smart Bus</h2>
        <ul>
            <li><a href="#" data-page="view_logs.php" class="active">View Logs</a></li>
            <li><a href="#" data-page="manage_buses.php">Manage Buses</a></li>
            <li><a href="#" data-page="manage_drivers.php">Manage Drivers</a></li>
            <li><a href="#" data-page="view_plate_number.php">View Plate Number</a></li>
            <li><a href="#" data-page="view_notifications.php">View Notifications</a></li>
        </ul>
    </aside>
    <div class="main">
        <header class="header">
            <h1>Dashboard</h1>
            <div>
                <!-- <h2>Welcome <?= htmlspecialchars($_SESSION['username']) ?> (<?= htmlspecialchars($_SESSION['role']) ?>)</h2> -->
                <!-- <a href="../logout.php">Logout</a> -->
                <a href="../logout.php" class="logout">Logout</a>
            </div>
            <div id="datetime"></div>
        </header>
        <main id="main-content">
            <?php include 'view_logs.php'; ?>
        </main>
    </div>
</div>
<script src="../assets/script.js"></script>
</body>
</html>
