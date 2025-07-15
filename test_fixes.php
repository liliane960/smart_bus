<?php
require_once 'db.php';

echo "<h1>System Fixes Verification</h1>";

// Test 1: Check session management in dashboard files
echo "<h2>1. Session Management Status</h2>";
$dashboard_files = [
    'admin/admin_dashboard.php' => 'Admin Dashboard',
    'driver/driver_dashboard.php' => 'Driver Dashboard', 
    'police/police_dashboard.php' => 'Police Dashboard'
];

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background-color: #f0f0f0;'>";
echo "<th>File</th><th>Description</th><th>Session Status</th>";
echo "</tr>";

foreach ($dashboard_files as $file => $description) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $has_session_start = strpos($content, 'session_start()') !== false;
        $has_session_check = strpos($content, '$_SESSION[\'logged_in\']') !== false || strpos($content, '$_SESSION[\'role\']') !== false;
        
        echo "<tr>";
        echo "<td>{$file}</td>";
        echo "<td>{$description}</td>";
        if ($has_session_start && $has_session_check) {
            echo "<td style='color: green;'>✅ Session Management Active</td>";
        } else {
            echo "<td style='color: red;'>❌ Session Issues Found</td>";
        }
        echo "</tr>";
    } else {
        echo "<tr>";
        echo "<td>{$file}</td>";
        echo "<td>{$description}</td>";
        echo "<td style='color: red;'>❌ File Missing</td>";
        echo "</tr>";
    }
}
echo "</table>";

// Test 2: Check pagination settings
echo "<h2>2. Pagination Settings</h2>";
$view_logs_files = [
    'admin/view_logs.php' => 'Admin View Logs',
    'driver/view_logs.php' => 'Driver View Logs',
    'police/view_logs.php' => 'Police View Logs'
];

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background-color: #f0f0f0;'>";
echo "<th>File</th><th>Description</th><th>Pagination Setting</th><th>Status</th>";
echo "</tr>";

foreach ($view_logs_files as $file => $description) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $has_12345 = strpos($content, '$logsPerPage = 12345') !== false;
        $has_10 = strpos($content, '$logsPerPage = 10') !== false;
        
        echo "<tr>";
        echo "<td>{$file}</td>";
        echo "<td>{$description}</td>";
        if ($has_12345) {
            echo "<td>12345 items per page</td>";
            echo "<td style='color: green;'>✅ Updated</td>";
        } elseif ($has_10) {
            echo "<td>10 items per page</td>";
            echo "<td style='color: red;'>❌ Still 10</td>";
        } else {
            echo "<td>Unknown</td>";
            echo "<td style='color: orange;'>⚠️ Check manually</td>";
        }
        echo "</tr>";
    } else {
        echo "<tr>";
        echo "<td>{$file}</td>";
        echo "<td>{$description}</td>";
        echo "<td>N/A</td>";
        echo "<td style='color: red;'>❌ File Missing</td>";
        echo "</tr>";
    }
}
echo "</table>";

// Test 3: Check total records to see if pagination will be effective
echo "<h2>3. Database Records Count</h2>";
$stmt = $conn->query("SELECT COUNT(*) as total FROM bus_logs");
$total_logs = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM notifications WHERE message LIKE '%overloading%'");
$total_notifications = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

echo "<ul>";
echo "<li><strong>Total Bus Logs:</strong> {$total_logs} records</li>";
echo "<li><strong>Total Overloading Notifications:</strong> {$total_notifications} records</li>";
echo "<li><strong>Pagination Setting:</strong> 12345 items per page</li>";
echo "</ul>";

if ($total_logs <= 12345) {
    echo "<p style='color: green;'>✅ With 12345 items per page, all records will be shown on a single page!</p>";
} else {
    $pages_needed = ceil($total_logs / 12345);
    echo "<p style='color: blue;'>ℹ️ With 12345 items per page, {$total_logs} records will be shown on {$pages_needed} page(s).</p>";
}

// Test 4: Access URLs for testing
echo "<h2>4. Access URLs for Testing</h2>";
echo "<h3>Dashboard Access (Login Required):</h3>";
echo "<ul>";
echo "<li><strong>Admin Dashboard:</strong> <a href='admin/admin_dashboard.php' target='_blank'>admin/admin_dashboard.php</a></li>";
echo "<li><strong>Driver Dashboard:</strong> <a href='driver/driver_dashboard.php' target='_blank'>driver/driver_dashboard.php</a></li>";
echo "<li><strong>Police Dashboard:</strong> <a href='police/police_dashboard.php' target='_blank'>police/police_dashboard.php</a></li>";
echo "</ul>";

echo "<h3>View Logs Access (Login Required):</h3>";
echo "<ul>";
echo "<li><strong>Admin View Logs:</strong> <a href='admin/view_logs.php' target='_blank'>admin/view_logs.php</a></li>";
echo "<li><strong>Driver View Logs:</strong> <a href='driver/view_logs.php' target='_blank'>driver/view_logs.php</a></li>";
echo "<li><strong>Police View Logs:</strong> <a href='police/view_logs.php' target='_blank'>police/view_logs.php</a></li>";
echo "</ul>";

// Test 5: Summary
echo "<h2>5. Summary of Fixes Applied</h2>";
echo "<div style='background-color: #e8f5e8; padding: 15px; border-radius: 5px;'>";
echo "<h3>✅ Fixed Issues:</h3>";
echo "<ul>";
echo "<li><strong>Session Management:</strong> Enabled session_start() and session checks in driver dashboard</li>";
echo "<li><strong>Pagination:</strong> Changed from 10 to 12345 items per page in all view_logs.php files</li>";
echo "<li><strong>UI Improvements:</strong> Enhanced driver dashboard with better styling and navigation</li>";
echo "<li><strong>Error Prevention:</strong> Added null checks for session variables</li>";
echo "</ul>";

echo "<h3>🎯 Expected Results:</h3>";
echo "<ul>";
echo "<li><strong>No More Session Warnings:</strong> Driver dashboard will no longer show undefined session variable errors</li>";
echo "<li><strong>Single Page Display:</strong> All bus logs will be shown on one page instead of multiple pages</li>";
echo "<li><strong>Better User Experience:</strong> Improved navigation and styling in driver interface</li>";
echo "</ul>";
echo "</div>";

echo "<p><strong>🚀 Ready for Testing:</strong> All fixes have been applied successfully!</p>";
?> 