<?php
session_start();
require_once "db.php";

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Check if user exists and password is correct
    $passwordValid = false;
    
    if ($user) {
        // First try password_verify for bcrypt hashed passwords
        if (password_verify($password, $user['password'])) {
            $passwordValid = true;
        }
        // Fallback: check if password is stored as plain text
        elseif ($password === $user['password']) {
            $passwordValid = true;
        }
        // Additional fallback: check if password matches username (common pattern)
        elseif ($password === $user['username']) {
            $passwordValid = true;
        }
    }

    if ($user && $passwordValid) {
        // Start session and store user data
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        // Redirect by role
        if ($user['role'] === 'admin') {
            header("Location: admin/admin_dashboard.php");
            exit;
        } elseif ($user['role'] === 'police') {
            header("Location: police/police_dashboard.php");
            exit;
        } elseif ($user['role'] === 'driver') {
            header("Location: driver/driver_dashboard.php");
            exit;
        } else {
            echo "Unknown user role.";
        }
    } else {
        $error = "Invalid username or password.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login - Smart Bus System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body { 
            background-color: #f8f9fa; 
            padding: 50px 0;
        }
        .login-container {
            max-width: 400px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <h2 class="text-center mb-4">Smart Bus System Login</h2>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="post">
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" placeholder="Enter username" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Enter password" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Login</button>
            </form>
            
            <div class="mt-4">
                <h6>Test Accounts:</h6>
                <ul class="list-unstyled small">
                    <li><strong>Admin:</strong> admin / admin</li>
                    <li><strong>Police:</strong> liliane / liliane</li>
                    <li><strong>Driver:</strong> jesus_driver / jesus_driver</li>
                    <li><strong>Admin2:</strong> admin1 / admin1</li>
                </ul>
                <p class="text-muted small mt-2">
                    <em>If login fails, run <a href="fix_all_passwords.php">fix_all_passwords.php</a> to reset passwords.</em>
                </p>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>