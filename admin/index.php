<?php require_once "../db.php"; ?>
<!DOCTYPE html>
<html>
<head><title>Dashboard</title><link rel="stylesheet" href="assets/style.css"></head>
<body>
<h1>Smart Bus Dashboard</h1>
<ul>
    <li><a href="manage_buses.php">Manage Buses</a></li>
    <li><a href="manage_drivers.php">Manage Drivers</a></li>
    <li><a href="view_logs.php">View Logs</a></li>
    <li><a href="view_plate_number.php">View plate number</a></li>
    <li><a href="view_notifications.php">View Notifications</a></li>
</ul>
</body></html>
<?php require_once "../db.php"; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Smart Bus Dashboard</title>
<link rel="stylesheet" href="assets/style.css">
<script>
// Auto-reload main content every 30 seconds
setInterval(() => {
    document.getElementById('main-content').innerHTML = '<p>Refreshing...</p>';
    fetch('view_logs.php')
        .then(response => response.text())
        .then(html => {
            document.getElementById('main-content').innerHTML = html;
        });
}, 30000);
</script>
</head>
<body>
    <div class="dashboard">
        <aside class="sidebar">
            <h2>Smart Bus</h2>
            <ul>
                <li><a href="manage_buses.php">Manage Buses</a></li>
                <li><a href="manage_drivers.php">Manage Drivers</a></li>
                <li><a href="view_logs.php">View Logs</a></li>
                <li><a href="view_plate_number.php">View Plate Number</a></li>
                <li><a href="view_notifications.php">View Notifications</a></li>
            </ul>
        </aside>
        <div class="main">
            <header class="header">
                <h1>Dashboard</h1>
            </header>
            <main id="main-content">
                <?php include 'view_logs.php'; ?>
            </main>
        </div>
    </div>
</body>
</html>
