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
<title>Smart Bus System</title>
<link rel="stylesheet" href="../assets/style.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
<style>
    body {
        margin: 0;
        font-family: Arial, sans-serif;
        background: #f8f9fa;
    }
    .dashboard {
        display: flex;
        min-height: 100vh;
    }
    .sidebar {
        width: 220px;
        background-color: #343a40;
        color: white;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }
    .sidebar h2 {
        text-align: center;
        padding: 20px 0;
        margin: 0;
        background-color: #212529;
        font-size: 18px;
    }
    .sidebar ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    .sidebar ul li {
        border-top: 1px solid #495057;
    }
    .sidebar ul li a {
        display: block;
        padding: 12px 20px;
        color: #adb5bd;
        text-decoration: none;
        transition: background 0.3s, color 0.3s;
    }
    .sidebar ul li a.active, .sidebar ul li a:hover {
        background-color: #495057;
        color: white;
    }
    .sidebar .logout-link {
        border-top: 1px solid #495057;
    }
    .sidebar .logout-link a {
        display: block;
        padding: 12px 20px;
        color: #f8d7da;
        text-decoration: none;
    }
    .sidebar .logout-link a:hover {
        background-color: #dc3545;
        color: white;
    }
    .main {
        flex: 1;
        display: flex;
        flex-direction: column;
    }
    .header {
        background-color: #ffffff;
        padding: 10px 20px;
        border-bottom: 1px solid #dee2e6;
    }
    main#main-content {
        padding: 20px;
        flex: 1;
        overflow-y: auto;
        background: #ffffff;
    }
</style>
</head>
<body>
<div class="dashboard">
    <aside class="sidebar">
        <div>
            <h2><i class="fas fa-cog"></i> Smart Bus System</h2>
            <ul>
                <li><a href="#" data-page="view_logs.php" class="active">
                    <i class="fas fa-chart-line"></i> View Logs
                </a></li>
                <li><a href="#" data-page="manage_buses.php">
                    <i class="fas fa-bus"></i> Manage Bus
                </a></li>
                <li><a href="#" data-page="manage_drivers.php">
                    <i class="fas fa-user-tie"></i> Manage Driver
                </a></li>
                <li><a href="#" data-page="view_notifications.php">
                    <i class="fas fa-bell"></i> View Notifications
                </a></li>
            </ul>
        </div>
        <div class="logout-link">
            <a href="../logout.php">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </aside>
    <div class="main">
        <header class="header">
            <!-- Removed datetime display -->
        </header>
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
});
</script>
</body>
</html>
