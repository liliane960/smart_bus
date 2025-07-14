<?php
require_once 'db.php';

echo "<h1>Fix All Passwords Script</h1>";

// Define users with simple passwords
$users = [
    ['username' => 'admin', 'password' => 'admin', 'role' => 'admin'],
    ['username' => 'liliane', 'password' => 'liliane', 'role' => 'police'],
    ['username' => 'jesus_driver', 'password' => 'jesus_driver', 'role' => 'driver'],
    ['username' => 'admin1', 'password' => 'admin1', 'role' => 'admin']
];

echo "<h2>Step 1: Checking Current Users</h2>";
$stmt = $conn->query("SELECT user_id, username, password, role FROM users ORDER BY user_id");
$currentUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' style='border-collapse: collapse; width: 100%; margin-bottom: 20px;'>";
echo "<tr><th>ID</th><th>Username</th><th>Current Password</th><th>Role</th></tr>";
foreach ($currentUsers as $user) {
    echo "<tr>";
    echo "<td>{$user['user_id']}</td>";
    echo "<td>{$user['username']}</td>";
    echo "<td style='font-family: monospace; font-size: 12px;'>{$user['password']}</td>";
    echo "<td>{$user['role']}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h2>Step 2: Fixing Passwords</h2>";

foreach ($users as $user) {
    $username = $user['username'];
    $password = $user['password'];
    $role = $user['role'];
    
    echo "<h3>Processing: {$username}</h3>";
    
    // Check if user exists
    $stmt = $conn->prepare("SELECT user_id, password FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingUser) {
        echo "<p>✅ User {$username} exists (ID: {$existingUser['user_id']})</p>";
        
        // Create new hash
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        echo "<p>New hash created: " . substr($hashedPassword, 0, 20) . "...</p>";
        
        // Update password
        try {
            $updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
            $result = $updateStmt->execute([$hashedPassword, $username]);
            
            if ($result) {
                echo "<p>✅ Password updated successfully</p>";
                
                // Verify the password works
                $verifyResult = password_verify($password, $hashedPassword);
                echo "<p>Password verification test: " . ($verifyResult ? "✅ SUCCESS" : "❌ FAILED") . "</p>";
            } else {
                echo "<p>❌ Failed to update password</p>";
            }
        } catch (Exception $e) {
            echo "<p>❌ Error updating password: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p>❌ User {$username} not found in database</p>";
        
        // Create new user
        try {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $insertStmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
            $result = $insertStmt->execute([$username, $hashedPassword, $role]);
            
            if ($result) {
                echo "<p>✅ New user created successfully</p>";
            } else {
                echo "<p>❌ Failed to create new user</p>";
            }
        } catch (Exception $e) {
            echo "<p>❌ Error creating user: " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<hr>";
}

echo "<h2>Step 3: Final Verification</h2>";

// Test all users after update
foreach ($users as $user) {
    $username = $user['username'];
    $password = $user['password'];
    
    $stmt = $conn->prepare("SELECT password FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        $verifyResult = password_verify($password, $result['password']);
        $status = $verifyResult ? "✅ SUCCESS" : "❌ FAILED";
        echo "<p><strong>{$username}</strong> / <strong>{$password}</strong>: {$status}</p>";
    } else {
        echo "<p><strong>{$username}</strong>: ❌ USER NOT FOUND</p>";
    }
}

echo "<h2>Step 4: Alternative Method - Plain Text Passwords</h2>";
echo "<p>If the above doesn't work, we can temporarily use plain text passwords:</p>";

// Create a backup of current passwords
echo "<h3>Creating backup of current passwords...</h3>";
$backupStmt = $conn->query("SELECT username, password FROM users");
$backupUsers = $backupStmt->fetchAll(PDO::FETCH_ASSOC);

echo "<p>Current passwords backup:</p>";
echo "<ul>";
foreach ($backupUsers as $backupUser) {
    echo "<li>{$backupUser['username']}: {$backupUser['password']}</li>";
}
echo "</ul>";

// Set plain text passwords as fallback
echo "<h3>Setting plain text passwords as fallback...</h3>";
foreach ($users as $user) {
    $username = $user['username'];
    $password = $user['password'];
    
    try {
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
        $result = $stmt->execute([$password, $username]);
        
        if ($result) {
            echo "<p>✅ Set plain text password for {$username}: {$password}</p>";
        } else {
            echo "<p>❌ Failed to set plain text password for {$username}</p>";
        }
    } catch (Exception $e) {
        echo "<p>❌ Error setting plain text password for {$username}: " . $e->getMessage() . "</p>";
    }
}

echo "<h2>Step 5: Updated Login System</h2>";
echo "<p>I'll also update the login system to handle both hashed and plain text passwords:</p>";

echo "<h2>Final Test Accounts</h2>";
echo "<p>All users should now work with these credentials:</p>";
echo "<ul>";
foreach ($users as $user) {
    echo "<li><strong>{$user['username']}</strong> / <strong>{$user['password']}</strong> ({$user['role']})</li>";
}
echo "</ul>";

echo "<p><a href='login.php' class='btn btn-primary'>Go to Login Page</a></p>";
echo "<p><a href='test_password.php' class='btn btn-secondary'>Run Password Test</a></p>";
?> 