<?php
require_once 'db.php';

echo "<h1>Password Debugging Script</h1>";

// Test all users in the database
$stmt = $conn->query("SELECT user_id, username, password, role FROM users ORDER BY user_id");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>Current Users in Database:</h2>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>ID</th><th>Username</th><th>Password Hash</th><th>Role</th><th>Hash Type</th></tr>";

foreach ($users as $user) {
    $hash = $user['password'];
    $hashType = 'Unknown';
    
    if (strpos($hash, '$2y$') === 0) {
        $hashType = 'bcrypt';
    } elseif (strpos($hash, '$2a$') === 0) {
        $hashType = 'bcrypt (old)';
    } elseif (strlen($hash) < 20) {
        $hashType = 'plain text';
    } else {
        $hashType = 'other hash';
    }
    
    echo "<tr>";
    echo "<td>{$user['user_id']}</td>";
    echo "<td>{$user['username']}</td>";
    echo "<td style='font-family: monospace; font-size: 12px;'>{$hash}</td>";
    echo "<td>{$user['role']}</td>";
    echo "<td>{$hashType}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h2>Testing Password Verification:</h2>";

// Test with different password combinations
$testPasswords = [
    'admin' => ['admin', 'Admin', 'ADMIN', 'password', '123456'],
    'liliane' => ['liliane', 'Liliane', 'LILIANE', 'password', '123456'],
    'jesus_driver' => ['jesus_driver', 'jesus', 'driver', 'password', '123456'],
    'admin1' => ['admin1', 'Admin1', 'ADMIN1', 'password', '123456']
];

foreach ($users as $user) {
    $username = $user['username'];
    $storedHash = $user['password'];
    
    echo "<h3>Testing user: <strong>{$username}</strong></h3>";
    echo "<p>Stored hash: <code>{$storedHash}</code></p>";
    
    if (isset($testPasswords[$username])) {
        foreach ($testPasswords[$username] as $testPassword) {
            $result = password_verify($testPassword, $storedHash);
            $status = $result ? "✅ MATCH" : "❌ NO MATCH";
            echo "<p>Testing '{$testPassword}': {$status}</p>";
            
            if ($result) {
                echo "<p><strong>SUCCESS! Password for {$username} is: {$testPassword}</strong></p>";
                break;
            }
        }
    }
    
    // Also test if it's plain text
    if ($storedHash === 'admin' || $storedHash === 'liliane' || $storedHash === 'jesus_driver' || $storedHash === 'admin1') {
        echo "<p><strong>PLAIN TEXT MATCH! Password is stored as plain text: {$storedHash}</strong></p>";
    }
    
    echo "<hr>";
}

echo "<h2>Creating New Test Users with Known Passwords:</h2>";

// Create test users with simple passwords
$testUsers = [
    ['username' => 'test_admin', 'password' => 'admin123', 'role' => 'admin'],
    ['username' => 'test_police', 'password' => 'police123', 'role' => 'police'],
    ['username' => 'test_driver', 'password' => 'driver123', 'role' => 'driver']
];

foreach ($testUsers as $testUser) {
    $username = $testUser['username'];
    $password = $testUser['password'];
    $role = $testUser['role'];
    
    // Check if user already exists
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $exists = $stmt->fetch();
    
    if ($exists) {
        echo "<p>User <strong>{$username}</strong> already exists, updating password...</p>";
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
        $stmt->execute([$hashedPassword, $username]);
    } else {
        echo "<p>Creating new user <strong>{$username}</strong>...</p>";
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        $stmt->execute([$username, $hashedPassword, $role]);
    }
    
    // Test the password
    $result = password_verify($password, $hashedPassword);
    echo "<p>Test user <strong>{$username}</strong> / <strong>{$password}</strong> - Verification: " . ($result ? "✅ SUCCESS" : "❌ FAILED") . "</p>";
}

echo "<h2>Test Login Links:</h2>";
echo "<p>Try these test accounts:</p>";
echo "<ul>";
echo "<li><strong>test_admin</strong> / <strong>admin123</strong> - <a href='login.php'>Login</a></li>";
echo "<li><strong>test_police</strong> / <strong>police123</strong> - <a href='login.php'>Login</a></li>";
echo "<li><strong>test_driver</strong> / <strong>driver123</strong> - <a href='login.php'>Login</a></li>";
echo "</ul>";

echo "<h2>Manual Password Reset:</h2>";
echo "<p>If the above doesn't work, you can manually reset passwords using this SQL:</p>";
echo "<pre>";
echo "UPDATE users SET password = '" . password_hash('admin', PASSWORD_DEFAULT) . "' WHERE username = 'admin';\n";
echo "UPDATE users SET password = '" . password_hash('liliane', PASSWORD_DEFAULT) . "' WHERE username = 'liliane';\n";
echo "UPDATE users SET password = '" . password_hash('jesus_driver', PASSWORD_DEFAULT) . "' WHERE username = 'jesus_driver';\n";
echo "UPDATE users SET password = '" . password_hash('admin1', PASSWORD_DEFAULT) . "' WHERE username = 'admin1';";
echo "</pre>";
?> 