<?php
require_once 'db.php';

echo "<h1>Simple Password Fix</h1>";

// Set all passwords to plain text (simple but effective)
$users = [
    'admin' => 'admin',
    'liliane' => 'liliane', 
    'jesus_driver' => 'jesus_driver',
    'admin1' => 'admin1'
];

echo "<h2>Setting Plain Text Passwords</h2>";

foreach ($users as $username => $password) {
    try {
        // Check if user exists
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Update existing user
            $updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
            $result = $updateStmt->execute([$password, $username]);
            
            if ($result) {
                echo "<p>✅ Updated password for <strong>{$username}</strong> to <strong>{$password}</strong></p>";
            } else {
                echo "<p>❌ Failed to update {$username}</p>";
            }
        } else {
            // Create new user
            $insertStmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
            $role = ($username === 'admin' || $username === 'admin1') ? 'admin' : 
                   ($username === 'liliane' ? 'police' : 'driver');
            $result = $insertStmt->execute([$username, $password, $role]);
            
            if ($result) {
                echo "<p>✅ Created new user <strong>{$username}</strong> with password <strong>{$password}</strong></p>";
            } else {
                echo "<p>❌ Failed to create {$username}</p>";
            }
        }
    } catch (Exception $e) {
        echo "<p>❌ Error with {$username}: " . $e->getMessage() . "</p>";
    }
}

echo "<h2>Verification</h2>";

// Verify all users work
foreach ($users as $username => $password) {
    $stmt = $conn->prepare("SELECT password FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $result = $stmt->fetch();
    
    if ($result && $result['password'] === $password) {
        echo "<p>✅ <strong>{$username}</strong> / <strong>{$password}</strong> - READY TO LOGIN</p>";
    } else {
        echo "<p>❌ <strong>{$username}</strong> - NOT READY</p>";
    }
}

echo "<h2>Test Login</h2>";
echo "<p>Now try these exact credentials:</p>";
echo "<ul>";
foreach ($users as $username => $password) {
    echo "<li><strong>{$username}</strong> / <strong>{$password}</strong></li>";
}
echo "</ul>";

echo "<p><a href='login.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Login Page</a></p>";

echo "<h2>If Still Not Working</h2>";
echo "<p>Try this manual SQL in phpMyAdmin:</p>";
echo "<pre>";
echo "UPDATE users SET password = 'admin' WHERE username = 'admin';\n";
echo "UPDATE users SET password = 'liliane' WHERE username = 'liliane';\n";
echo "UPDATE users SET password = 'jesus_driver' WHERE username = 'jesus_driver';\n";
echo "UPDATE users SET password = 'admin1' WHERE username = 'admin1';";
echo "</pre>";
?> 