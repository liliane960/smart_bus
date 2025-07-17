<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'police') {
    header("Location: ../index.php");
    exit;
}
require_once "../database/db.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Smart Bus System - Police Dashboard</title>
<link rel="stylesheet" href="../assets/style.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
<style>
    body { margin: 0; font-family: Arial, sans-serif; background: #f8f9fa; }
    .dashboard { display: flex; min-height: 100vh; }
    .sidebar { width: 220px; background-color: #343a40; color: white; transition: width 0.3s; }
    .sidebar.collapsed { width: 0; overflow: hidden; }
    .main { flex: 1; display: flex; flex-direction: column; }
    .header { background-color: #fff; padding: 10px 20px; border-bottom: 1px solid #dee2e6; display: flex; align-items: center; }
    #toggle-sidebar-btn { background: none; border: none; font-size: 1.5rem; margin-right: 16px; cursor: pointer; color: #343a40; }
    main#main-content { padding: 20px; flex: 1; overflow-y: auto; background: #fff; }
</style>
</head>
<body>
<div class="dashboard">
    <?php include '../includes/police_aside.php'; ?>
    <div class="main">
        <header class="header">
            <button id="toggle-sidebar-btn" title="Toggle menu">&#9776;</button>
            <span>Police Dashboard</span>
        </header>
        <main id="main-content">
            <?php include '../police/notification_dashboard.php'; ?>
        </main>
        <?php include '../includes/footer.php'; ?>
    </div>
</div>
<script src="../includes/toggle_sidebar.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const links = document.querySelectorAll('.sidebar a[data-page]');
    links.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            links.forEach(l => l.classList.remove('active'));
            this.classList.add('active');
            const page = this.getAttribute('data-page');
            fetch('../police/' + page)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('main-content').innerHTML = html;
                })
                .catch(error => {
                    document.getElementById('main-content').innerHTML = '<p>Error loading page content.</p>';
                });
        });
    });
});
</script>
</body>
</html>
