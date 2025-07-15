<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'driver') {
    header("Location: ../login.php");
    exit;
}
require_once "../db.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Smart Bus Dashboard - Driver</title>
<link rel="stylesheet" href="../assets/style.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
<style>
    .sidebar ul li a {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 15px;
        text-decoration: none;
        color: #333;
        border-radius: 5px;
        margin-bottom: 5px;
        transition: all 0.3s ease;
    }
    .sidebar ul li a:hover, .sidebar ul li a.active {
        background-color: #007bff;
        color: white;
    }
    .sidebar ul li a i {
        width: 20px;
        text-align: center;
    }
    .header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px;
        background: white;
        border-bottom: 1px solid #ddd;
    }
    .welcome-section {
        display: flex;
        align-items: center;
        gap: 20px;
    }
    .logout {
        background: #dc3545;
        color: white;
        padding: 8px 16px;
        text-decoration: none;
        border-radius: 5px;
        transition: background 0.3s ease;
    }
    .logout:hover {
        background: #c82333;
        color: white;
        text-decoration: none;
    }
</style>
</head>
<body>
<div class="dashboard">
    <aside class="sidebar">
        <h2><i class="fas fa-bus"></i> Smart Bus Driver</h2>
        <ul>
            <li><a href="#" data-page="view_logs.php" class="active">
                <i class="fas fa-chart-line"></i> Passenger Movement
            </a></li>
            <li><a href="#" data-page="view_notifications.php">
                <i class="fas fa-bell"></i> View Notifications
            </a></li>
            <li><a href="#" data-page="overloading_notifications.php">
                <i class="fas fa-exclamation-triangle"></i> Overloading Notifications
            </a></li>
        </ul>
    </aside>
    <div class="main">
        <header class="header">
            <h1><i class="fas fa-bus"></i> Driver Dashboard</h1>
            <div class="welcome-section">
                <div>
                    <h3>Welcome <?= htmlspecialchars($_SESSION['username'] ?? 'Driver') ?> 
                        <span style="color: #007bff;">(<?= htmlspecialchars($_SESSION['role']) ?>)</span>
                    </h3>
                </div>
                <a href="../logout.php" class="logout">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
            <div id="datetime"></div>
        </header>
        <main id="main-content">
            <?php include 'view_logs.php'; ?>
        </main>
    </div>
</div>
<script src="../assets/script.js"></script>
<script>
// Update navigation to handle the driver views
document.addEventListener('DOMContentLoaded', function() {
    const links = document.querySelectorAll('.sidebar a[data-page]');
    
    links.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Remove active class from all links
            links.forEach(l => l.classList.remove('active'));
            
            // Add active class to clicked link
            this.classList.add('active');
            
            // Load the page content
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
});
</script>
</body>
</html>
