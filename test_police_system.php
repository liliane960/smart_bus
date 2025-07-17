<?php
session_start();
require_once "database/db.php";

echo "<h2>Police System Comprehensive Test</h2>";

// Test 1: Database Connection
echo "<h3>1. Database Connection Test</h3>";
try {
    $stmt = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'police'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✅ Database connected successfully<br>";
    echo "Police users found: " . $result['count'] . "<br>";
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

// Test 2: Police Files Existence
echo "<h3>2. Police Files Test</h3>";
$files = [
    'dashboard/police_dashboard.php',
    'police/notification_dashboard.php',
    'police/overloading_notifications.php',
    'includes/police_aside.php',
    'includes/footer.php',
    'includes/toggle_sidebar.js'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "✅ $file exists<br>";
    } else {
        echo "❌ $file missing<br>";
    }
}

// Test 3: Database Tables
echo "<h3>3. Database Tables Test</h3>";
$tables = ['notifications', 'buses', 'bus_logs', 'users'];
foreach ($tables as $table) {
    try {
        $stmt = $conn->query("SELECT COUNT(*) as count FROM $table");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "✅ Table '$table' exists with " . $result['count'] . " records<br>";
    } catch (Exception $e) {
        echo "❌ Table '$table' error: " . $e->getMessage() . "<br>";
    }
}

// Test 4: Current Session
echo "<h3>4. Current Session Test</h3>";
if (isset($_SESSION['role'])) {
    echo "✅ Session active - Role: " . $_SESSION['role'] . "<br>";
    echo "User ID: " . ($_SESSION['user_id'] ?? 'Not set') . "<br>";
    echo "Username: " . ($_SESSION['username'] ?? 'Not set') . "<br>";
} else {
    echo "❌ No session found<br>";
}

// Test 5: Police Dashboard Access
echo "<h3>5. Police Dashboard Access Test</h3>";
echo "<a href='dashboard/police_dashboard.php' target='_blank'>Test Police Dashboard</a><br>";

// Test 6: Direct File Access
echo "<h3>6. Direct File Access Test</h3>";
echo "<a href='police/notification_dashboard.php' target='_blank'>Test Notification Dashboard</a><br>";
echo "<a href='police/overloading_notifications.php' target='_blank'>Test Overloading Notifications</a><br>";

// Test 7: Login Test
echo "<h3>7. Login Test</h3>";
echo "<a href='index.php'>Go to Login Page</a><br>";
echo "<p>Use a police account to test the functionality.</p>";

// Test 8: Sample Data Check
echo "<h3>8. Sample Data Check</h3>";
try {
    // Check notifications
    $stmt = $conn->query("SELECT COUNT(*) as count FROM notifications WHERE message LIKE '%overloading%'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Overloading notifications: " . $result['count'] . "<br>";
    
    // Check bus logs
    $stmt = $conn->query("SELECT COUNT(*) as count FROM bus_logs WHERE status = 'overloading'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Overloading bus logs: " . $result['count'] . "<br>";
    
    // Check buses
    $stmt = $conn->query("SELECT COUNT(*) as count FROM buses");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Total buses: " . $result['count'] . "<br>";
    
} catch (Exception $e) {
    echo "❌ Data check error: " . $e->getMessage() . "<br>";
}

echo "<h3>9. Troubleshooting</h3>";
echo "<p>If police functionality is not working:</p>";
echo "<ul>";
echo "<li>Make sure you're logged in as a police user</li>";
echo "<li>Check that all files exist (see test 2)</li>";
echo "<li>Verify database connection (see test 1)</li>";
echo "<li>Ensure sample data exists (see test 8)</li>";
echo "<li>Try accessing files directly (see test 6)</li>";
echo "</ul>";

echo "<h3>10. Quick Fix Commands</h3>";
echo "<p>If you need to create a police user:</p>";
echo "<code>INSERT INTO users (username, password, role) VALUES ('police', 'police', 'police');</code><br>";
echo "<p>If you need to create sample data:</p>";
echo "<code>INSERT INTO buses (plate_number, capacity) VALUES ('TEST123', 20);</code><br>";
echo "<code>INSERT INTO bus_logs (bus_id, event, passenger_count, status) VALUES (1, 'boarding', 25, 'overloading');</code><br>";
?> 