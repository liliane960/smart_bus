<?php
session_start();
require_once "database/db.php";

echo "<h2>Police Functionality Test</h2>";

// Test database connection
echo "<h3>1. Database Connection Test</h3>";
try {
    $stmt = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'police'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✅ Database connected successfully<br>";
    echo "Police users found: " . $result['count'] . "<br>";
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

// Test police files existence
echo "<h3>2. Police Files Test</h3>";
$files = [
    'dashboard/police_dashboard.php',
    'police/notification_dashboard.php',
    'police/view_logs.php',
    'police/overloading_notifications.php',
    'includes/police_aside.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "✅ $file exists<br>";
    } else {
        echo "❌ $file missing<br>";
    }
}

// Test police dashboard access
echo "<h3>3. Police Dashboard Access</h3>";
echo "<a href='dashboard/police_dashboard.php' target='_blank'>Test Police Dashboard</a><br>";

// Show current session
echo "<h3>4. Current Session</h3>";
if (isset($_SESSION['role'])) {
    echo "Current role: " . $_SESSION['role'] . "<br>";
    echo "User ID: " . ($_SESSION['user_id'] ?? 'Not set') . "<br>";
} else {
    echo "No session found<br>";
}

echo "<h3>5. Login as Police</h3>";
echo "<a href='index.php'>Go to Login Page</a><br>";
echo "<p>Use a police account to test the functionality.</p>";
?> 