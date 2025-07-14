<?php
session_start();
require_once "db.php";

echo "<h1>Login Debug Script</h1>";

// Test database connection
echo "<h2>1. Database Connection Test</h2>";
try {
    $testStmt = $conn->query("SELECT 1");
    echo "<p>✅ Database connection successful</p>";
} catch (Exception $e) {
    echo "<p>❌ Database connection failed: " . $e->getMessage() . "</p>";
    exit;
}

// Check if users table exists and has data
echo "<h2>2. Users Table Check</h2>";
try {
    $stmt = $conn->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>✅ Users table exists with {$result['count']} users</p>";
    
    // Show all users
    $stmt = $conn->query("SELECT user_id, username, password, role FROM users ORDER BY user_id");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
    echo "<tr><th>ID</th><th>Username</th><th>Password</th><th>Role</th></tr>";
    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>{$user['user_id']}</td>";
        echo "<td>{$user['username']}</td>";
        echo "<td style='font-family: monospace; font-size: 12px;'>{$user['password']}</td>";
        echo "<td>{$user['role']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "<p>❌ Error checking users table: " . $e->getMessage() . "</p>";
}

// Test specific user login
echo "<h2>3. Test User Login Process</h2>";

$testUsers = ['admin', 'liliane', 'jesus_driver', 'admin1'];

foreach ($testUsers as $testUsername) {
    echo "<h3>Testing user: {$testUsername}</h3>";
    
    // Get user from database
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$testUsername]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo "<p>❌ User '{$testUsername}' not found in database</p>";
        continue;
    }
    
    echo "<p>✅ User found: ID={$user['user_id']}, Role={$user['role']}</p>";
    echo "<p>Stored password: {$user['password']}</p>";
    
    // Test different password combinations
    $testPasswords = [
        $testUsername, // username as password
        'password',    // common password
        '123456',      // common password
        'admin',       // admin password
        'test'         // test password
    ];
    
    foreach ($testPasswords as $testPassword) {
        // Test bcrypt verification
        $bcryptResult = password_verify($testPassword, $user['password']);
        
        // Test plain text comparison
        $plainResult = ($testPassword === $user['password']);
        
        // Test username as password
        $usernameResult = ($testPassword === $user['username']);
        
        if ($bcryptResult || $plainResult || $usernameResult) {
            echo "<p>✅ <strong>PASSWORD MATCH!</strong> '{$testPassword}' works for {$testUsername}</p>";
            echo "<ul>";
            echo "<li>Bcrypt verify: " . ($bcryptResult ? "YES" : "NO") . "</li>";
            echo "<li>Plain text match: " . ($plainResult ? "YES" : "NO") . "</li>";
            echo "<li>Username match: " . ($usernameResult ? "YES" : "NO") . "</li>";
            echo "</ul>";
            break;
        } else {
            echo "<p>❌ '{$testPassword}' does not work</p>";
        }
    }
    
    echo "<hr>";
}

// Test the actual login logic
echo "<h2>4. Test Login Logic</h2>";

$testUsername = 'admin';
$testPassword = 'admin';

echo "<p>Testing login logic with: {$testUsername} / {$testPassword}</p>";

// Simulate the login process
$stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute([$testUsername]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    echo "<p>✅ User found in database</p>";
    
    // Test password validation
    $passwordValid = false;
    $validationSteps = [];
    
    // Step 1: Bcrypt verification
    if (password_verify($testPassword, $user['password'])) {
        $passwordValid = true;
        $validationSteps[] = "Bcrypt verification: SUCCESS";
    } else {
        $validationSteps[] = "Bcrypt verification: FAILED";
    }
    
    // Step 2: Plain text comparison
    if ($testPassword === $user['password']) {
        $passwordValid = true;
        $validationSteps[] = "Plain text comparison: SUCCESS";
    } else {
        $validationSteps[] = "Plain text comparison: FAILED";
    }
    
    // Step 3: Username as password
    if ($testPassword === $user['username']) {
        $passwordValid = true;
        $validationSteps[] = "Username as password: SUCCESS";
    } else {
        $validationSteps[] = "Username as password: FAILED";
    }
    
    echo "<p>Password validation steps:</p>";
    echo "<ul>";
    foreach ($validationSteps as $step) {
        echo "<li>{$step}</li>";
    }
    echo "</ul>";
    
    echo "<p><strong>Final result: " . ($passwordValid ? "✅ LOGIN WOULD SUCCEED" : "❌ LOGIN WOULD FAIL") . "</strong></p>";
    
} else {
    echo "<p>❌ User not found in database</p>";
}

// Test session functionality
echo "<h2>5. Session Test</h2>";
if (session_status() === PHP_SESSION_ACTIVE) {
    echo "<p>✅ Sessions are working</p>";
    $_SESSION['test'] = 'test_value';
    echo "<p>✅ Session write test: " . (isset($_SESSION['test']) ? "SUCCESS" : "FAILED") . "</p>";
} else {
    echo "<p>❌ Sessions are not working</p>";
}

echo "<h2>6. Quick Fix</h2>";
echo "<p>If login is still not working, try this simple fix:</p>";
echo "<p><a href='simple_fix.php' class='btn btn-primary'>Run Simple Password Fix</a></p>";
echo "<p><a href='login.php' class='btn btn-secondary'>Go to Login Page</a></p>";
?> 