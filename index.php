<?php
session_start();
require_once 'classes/Login.php'; // ✅ correct path for your setup

$login = new Login();
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $auth = $login->authenticate($username, $password);

    if ($auth) {
        $_SESSION['role'] = $auth['role'];
        $_SESSION['user_id'] = $auth['id'];

        // Redirect to their dashboards
        switch ($auth['role']) {
            case 'admin':
                header("Location: dashboards/admin_dashboard.php");
                break;
            case 'patient':
                header("Location: dashboards/patient_dashboard.php");
                break;
            case 'staff':
                header("Location: dashboards/staff_dashboard.php");
                break;
            case 'doctor':
                header("Location: dashboards/doctor_dashboard.php");
                break;
            default:
                $error = "User role not recognized.";
                break;
        }
        exit;
    } else {
        $error = "Invalid username or password!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Booking Login</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #e8f0fe;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }
        .login-box {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.2);
            width: 350px;
        }
        .login-box h2 {
            text-align: center;
            margin-bottom: 1rem;
        }
        .login-box input {
            width: 100%;
            padding: 10px;
            margin-bottom: 1rem;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .login-box button {
            width: 100%;
            padding: 10px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
        }
        .error {
            color: red;
            text-align: center;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>Welcome to Medical Booking</h2>

        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>

        <p style="text-align:center;margin-top:1rem;">
            Not registered? <a href="register.php">Create an account</a>
        </p>
    </div>
</body>
</html>
