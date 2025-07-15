<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}
require_once "../db.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Smart Bus System - Admin</title>
<link rel="stylesheet" href="../assets/style.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
<style>
    /* (keep your CSS here unchanged) */
</style>
</head>
<body>
<div class="dashboard">
    <aside class="sidebar">
        <h2><i class="fas fa-cog"></i> Smart Bus System</h2>
        <ul>
            <li><a href="#" data-page="view_logs.php" class="active">
                <i class="fas fa-chart-line"></i> View Logs
            </a></li>
            <li><a href="#" data-page="manage_buses.php">
                <i class="fas fa-bus"></i> Manage Buses
            </a></li>
            <li><a href="#" data-page="manage_drivers.php">
                <i class="fas fa-user-tie"></i> Manage Drivers
            </a></li>
            <li><a href="#" data-page="manage_users.php">
                <i class="fas fa-users"></i> Manage Users
            </a></li>
            <li><a href="#" data-page="view_notifications.php">
                <i class="fas fa-bell"></i> View Notifications
            </a></li>
        </ul>
    </aside>
    <div class="main">
        <header class="header">
            <h1><i class="fas fa-cog"></i> Admin Dashboard</h1>
            <div class="welcome-section">
                <a href="../logout.php" class="logout">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
            <div id="datetime"></div>
        </header>
        
        <!-- Removed Admin Statistics section -->
        
        <main id="main-content">
            <?php include 'view_logs.php'; ?>
        </main>
    </div>
</div>
<script src="../assets/script.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const links = document.querySelectorAll('.sidebar a[data-page]');
    
    links.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            links.forEach(l => l.classList.remove('active'));
            this.classList.add('active');
            const page = this.getAttribute('data-page');
            loadPage(page);
        });
    });
    
    function loadPage(page) {
        fetch(page)
            .then(response => response.text())
            .then(html => {
                document.getElementById('main-content').innerHTML = html;
            })
            .catch(error => {
                console.error('Error loading page:', error);
                document.getElementById('main-content').innerHTML = '<p>Error loading page content.</p>';
            });
    }
    
    function updateDateTime() {
        const now = new Date();
        const dateTimeString = now.toLocaleDateString() + ' ' + now.toLocaleTimeString();
        document.getElementById('datetime').textContent = dateTimeString;
    }
    
    updateDateTime();
    setInterval(updateDateTime, 1000);
});
</script>
</body>
</html>
