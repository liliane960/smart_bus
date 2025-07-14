<?php
require_once 'db.php';

echo "<h1>Password Reset Script</h1>";

// Define the new passwords for each user
$users = [
    ['username' => 'admin', 'password' => 'admin', 'role' => 'admin'],
    ['username' => 'liliane', 'password' => 'liliane', 'role' => 'police'],
    ['username' => 'jesus_driver', 'password' => 'jesus_driver', 'role' => 'driver'],
    ['username' => 'admin1', 'password' => 'admin1', 'role' => 'admin']
];

echo "<h2>Updating User Passwords...</h2>";

foreach ($users as $user) {
    $username = $user['username'];
    $password = $user['password'];
    $role = $user['role'];
    
    // Hash the password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    try {
        // Update the user's password
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
        $result = $stmt->execute([$hashedPassword, $username]);
        
        if ($result) {
            echo "<p>✅ Updated password for <strong>{$username}</strong> ({$role})</p>";
        } else {
            echo "<p>❌ Failed to update password for <strong>{$username}</strong></p>";
        }
    } catch (Exception $e) {
        echo "<p>❌ Error updating <strong>{$username}</strong>: " . $e->getMessage() . "</p>";
    }
}

echo "<h2>Password Reset Complete!</h2>";
echo "<p>All users now have simple passwords that match their usernames:</p>";
echo "<ul>";
echo "<li><strong>admin</strong> / <strong>admin</strong></li>";
echo "<li><strong>liliane</strong> / <strong>liliane</strong></li>";
echo "<li><strong>jesus_driver</strong> / <strong>jesus_driver</strong></li>";
echo "<li><strong>admin1</strong> / <strong>admin1</strong></li>";
echo "</ul>";

echo "<p><a href='login.php' class='btn btn-primary'>Go to Login Page</a></p>";

// Test the passwords
echo "<h2>Testing Passwords...</h2>";
foreach ($users as $user) {
    $username = $user['username'];
    $password = $user['password'];
    
    // Get the hashed password from database
    $stmt = $conn->prepare("SELECT password FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result && password_verify($password, $result['password'])) {
        echo "<p>✅ Password verification successful for <strong>{$username}</strong></p>";
    } else {
        echo "<p>❌ Password verification failed for <strong>{$username}</strong></p>";
    }
}
?> 