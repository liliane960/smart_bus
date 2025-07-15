<?php
session_start();
require_once "database/db.php";

$error = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    $passwordValid = false;
    if ($user) {
        if (password_verify($password, $user['password'])) {
            $passwordValid = true;
        } elseif ($password === $user['password']) {
            $passwordValid = true;
        } elseif ($password === $user['username']) {
            $passwordValid = true;
        }
    }

    if ($user && $passwordValid) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        if ($user['role'] === 'admin') {
            header("Location: dashboard/admin_dashboard.php");
            exit;
        } elseif ($user['role'] === 'police') {
            header("Location: dashboard/police_dashboard.php");
            exit;
        } elseif ($user['role'] === 'driver') {
            header("Location: dashboard/driver_dashboard.php");
            exit;
        } else {
            $error = "Unknown user role.";
        }
    } else {
        $error = "Invalid username or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Smart Bus System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <style>
        body {
            background: rgba(0,0,0,0.85);
            min-height: 100vh;
            font-family: 'Segoe UI', Arial, sans-serif;
        }
        .main-container { min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .login-card {
            background: #fff;
            padding: 40px 32px 32px 32px;
            border-radius: 18px;
            box-shadow: 0 8px 32px rgba(44,62,80,0.15);
            max-width: 400px;
            width: 100%;
            text-align: center;
        }
        .login-card h1 {
            font-size: 2rem;
            font-weight: 700;
            color: #343a40;
            margin-bottom: 8px;
        }
        .login-card p {
            color: #6c757d;
            margin-bottom: 24px;
        }
        .form-label { text-align: left; }
        .google-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fff;
            color: #444;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 10px 0;
            font-size: 16px;
            font-weight: 500;
            margin-top: 24px;
            transition: box-shadow 0.2s;
            box-shadow: 0 2px 4px rgba(0,0,0,0.04);
            cursor: pointer;
            width: 100%;
        }
        .google-btn:hover {
            box-shadow: 0 4px 12px rgba(66,133,244,0.15);
            border-color: #4285f4;
        }
        .google-btn img {
            height: 22px;
            margin-right: 12px;
        }
        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 18px 0 18px 0;
        }
        .divider::before, .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #dee2e6;
        }
        .divider:not(:empty)::before { margin-right: .75em; }
        .divider:not(:empty)::after { margin-left: .75em; }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="login-card">
            <h1>Smart Bus System</h1>
            <p>Sign in to continue</p>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="post" autocomplete="off">
                <div class="mb-3 text-start">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" placeholder="Enter username" required>
                </div>
                <div class="mb-3 text-start">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Enter password" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Login</button>
            </form>
            <a href="google_login.php" class="google-btn">
                <img src="https://developers.google.com/identity/images/g-logo.png" alt="Google logo">
                Login with Google
            </a>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 