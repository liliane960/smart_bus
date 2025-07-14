<?php
require_once 'db.php';

echo "<h1>Password Verification Test</h1>";

// Test 1: Check if password_verify function works at all
echo "<h2>Test 1: Basic password_verify function test</h2>";
$testPassword = "test123";
$testHash = password_hash($testPassword, PASSWORD_DEFAULT);
$testResult = password_verify($testPassword, $testHash);
echo "<p>Testing password_verify function:</p>";
echo "<p>Password: '{$testPassword}'</p>";
echo "<p>Hash: '{$testHash}'</p>";
echo "<p>Result: " . ($testResult ? "✅ TRUE" : "❌ FALSE") . "</p>";

// Test 2: Check specific user
echo "<h2>Test 2: Check specific user (admin)</h2>";
$stmt = $conn->prepare("SELECT username, password FROM users WHERE username = 'admin'");
$stmt->execute();
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if ($admin) {
    echo "<p>Admin user found:</p>";
    echo "<p>Username: {$admin['username']}</p>";
    echo "<p>Stored password hash: {$admin['password']}</p>";
    
    // Test different passwords
    $testPasswords = ['admin', 'Admin', 'ADMIN', 'password', '123456', 'test'];
    
    foreach ($testPasswords as $testPwd) {
        $result = password_verify($testPwd, $admin['password']);
        echo "<p>Testing '{$testPwd}': " . ($result ? "✅ MATCH" : "❌ NO MATCH") . "</p>";
    }
    
    // Check if it's plain text
    if ($admin['password'] === 'admin') {
        echo "<p><strong>PLAIN TEXT MATCH! Password is stored as plain text.</strong></p>";
    }
} else {
    echo "<p>❌ Admin user not found in database</p>";
}

// Test 3: Check all users
echo "<h2>Test 3: Check all users</h2>";
$stmt = $conn->query("SELECT username, password, role FROM users");
$allUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($allUsers as $user) {
    echo "<h3>User: {$user['username']} ({$user['role']})</h3>";
    echo "<p>Password hash: {$user['password']}</p>";
    
    // Test with username as password
    $result = password_verify($user['username'], $user['password']);
    echo "<p>Testing with username '{$user['username']}': " . ($result ? "✅ MATCH" : "❌ NO MATCH") . "</p>";
    
    // Test with common passwords
    $commonPasswords = ['password', '123456', 'admin', 'user'];
    foreach ($commonPasswords as $pwd) {
        $result = password_verify($pwd, $user['password']);
        if ($result) {
            echo "<p><strong>✅ MATCH with '{$pwd}'!</strong></p>";
        }
    }
}

// Test 4: Create a new test user
echo "<h2>Test 4: Create test user with known password</h2>";
$testUsername = "testuser";
$testUserPassword = "testpass123";

// Check if test user exists
$stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
$stmt->execute([$testUsername]);
$exists = $stmt->fetch();

if ($exists) {
    echo "<p>Test user already exists, updating password...</p>";
    $hashedPassword = password_hash($testUserPassword, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
    $stmt->execute([$hashedPassword, $testUsername]);
} else {
    echo "<p>Creating new test user...</p>";
    $hashedPassword = password_hash($testUserPassword, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'admin')");
    $stmt->execute([$testUsername, $hashedPassword]);
}

// Test the new user
$result = password_verify($testUserPassword, $hashedPassword);
echo "<p>Test user verification: " . ($result ? "✅ SUCCESS" : "❌ FAILED") . "</p>";
echo "<p>Test user: <strong>{$testUsername}</strong> / <strong>{$testUserPassword}</strong></p>";

echo "<h2>Summary</h2>";
echo "<p>If the basic password_verify test works but user logins don't, the issue is with the stored password hashes in the database.</p>";
echo "<p>Try the test user: <strong>{$testUsername}</strong> / <strong>{$testUserPassword}</strong></p>";
echo "<p><a href='login.php'>Go to Login Page</a></p>";
?>
