<?php
session_start();
require_once 'classes/Login.php';

$login = new Login();
$error_message = "";

// PROCESS LOGIN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_submit'])) {
    $auth = $login->authenticate($_POST['username'], $_POST['password']);

    if ($auth) {
        // STORE SESSION DATA
        $_SESSION['role'] = $auth['role'];
        $_SESSION['USER_ID'] = $auth['USER_ID'];
        $_SESSION['USER_IS_SUPERADMIN'] = $auth['USER_IS_SUPERADMIN'];

        // ROLE REDIRECTS
        switch ($auth['role']) {
            case 'admin':
                header("Location: public/admin_dashboard.php");
                exit;

            case 'patient':
                $_SESSION['PAT_ID'] = $auth['PAT_ID'];
                header("Location: public/patient_dashboard.php");
                exit;

            case 'staff':
                $_SESSION['STAFF_ID'] = $auth['STAFF_ID'];
                header("Location: public/staff_dashboard.php");
                exit;

            case 'doctor':
                $_SESSION['DOC_ID'] = $auth['DOC_ID'];
                header("Location: public/doctor_dashboard.php");
                exit;

            default:
                $error_message = "Unknown role detected!";
        }
    } else {
        // LOGIN FAILED — SHOW ERROR
        $error_message = "Invalid username or password!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link href="https://fonts.googleapis.com/css2?family=Kalnia:wght@400;600;700&display=swap" rel="stylesheet">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/style.css">
    <title>Medicina - Booking System</title>

<style>
:root {
    --color-dark-blue: #1C334A;
    --color-light-blue: #70B8D9;
    --color-input-bg: #EAF3F7;
    --font-serif: "Georgia", "Times New Roman", Times, serif;
    --font-sans: Arial, Helvetica, sans-serif;
}

body {
    margin: 0;
    padding: 0;
    background-color: var(--color-light-blue);
    font-family: var(--font-sans);
    display: flex;
    flex-direction: column;
    min-height: 100vh;
}

/* MAIN CONTAINER */
.main-container {
    flex-grow: 1;
    display: flex;
    flex-wrap: wrap;
    padding: 100 5%;
    align-items: center;
}

/* LEFT COLUMN - LOGIN */
.login-column {
    flex: 1 1 350px;
    max-width: 300px;
    background: var(--color-light-blue);
    padding: 20px;
    margin-top: 50px;
    margin-left: 100px;
}

.login-title {
    font-family: var(--font-serif);
    font-size: 40px;
    margin-bottom: 20px;
    color: var(--color-dark-blue);
}

/* ERROR BOX */
.error-box {
    background-color: #ffcccc;
    color: #b30000;
    padding: 12px;
    border: 1px solid #b30000;
    border-radius: 6px;
    margin-bottom: 15px;
    font-weight: bold;
    text-align: center;
}

.login-form label {
    display: block;
    margin-top: 15px;
    margin-bottom: 5px;
    color: var(--color-dark-blue);
    font-weight: bold;
}

.login-form input {
    width: 100%;
    padding: 12px;
    border: 1px solid #ccc;
    border-radius: 10px;
    background-color: var(--color-input-bg);
    font-size: 16px;
}

.remember-me {
    display: flex;
    align-items: center;
    gap: 4px;
    margin: 15px 0 25px 0;
    color: var(--color-dark-blue);
    font-size: 14px;
}

.remember-me input[type="checkbox"] {
    width: 16px;
    height: 16px;
    accent-color: var(--color-dark-blue);
    margin: 0;
}

.login-button {
    width: 100%;
    padding: 14px;
    background-color: var(--color-dark-blue);
    color: white;
    border: none;
    border-radius: 30px;
    font-size: 18px;
    font-family: var(--font-serif);
    cursor: pointer;
    transition: background-color 0.3s;
}

.login-button:hover {
    background-color: #254b6a;
}

.register-link {
    display: block;
    text-align: center;
    margin-top: 15px;
    color: var(--color-dark-blue);
    text-decoration: underline;
    font-weight: bold;
}

.marketing-column {
    flex: 1 1 400px;
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
    padding-left: 170px;
    padding-right: 40px;
}

.marketing-headline {
    font-family: var(--font-serif);
    font-weight: 600;
    font-size: 56px;
    line-height: 1.1;
    color: var(--color-dark-blue);
    margin-bottom: 20px;
}

.marketing-subtext {
    font-size: 20px;
    color: var(--color-dark-blue);
    line-height: 1.6;
    margin-bottom: 40px;
}

.browse-button {
    display: inline-block;
    width: 250px;
    text-align: center;
    padding: 14px 20px;
    background-color: white;
    color: var(--color-dark-blue);
    border: 2px solid var(--color-dark-blue);
    border-radius: 50px;
    text-decoration: none;
    font-family: var(--font-serif);
    font-size: 16px;
    transition: all 0.3s ease;
}

.browse-button:hover {
    background-color: var(--color-dark-blue);
    color: white;
}
</style>
</head>

<body>

<?php include __DIR__ . '/partials/guest_header.php'; ?>

<div class="main-container">
    <div class="login-column">
        <h1 class="login-title">Log in</h1>

        <!-- SHOW ERROR IF EXISTS -->
        <?php if (!empty($error_message)): ?>
            <div class="error-box">
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="login-form">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required>

            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>

            <div class="remember-me">
                <input type="checkbox" id="remember" name="remember">
                <label for="remember">Remember me</label>
            </div>

            <button type="submit" name="login_submit" class="login-button">Log in</button>
        </form>

        <a href="public/register/register.php" class="register-link">REGISTER HERE</a>
    </div>

    <div class="marketing-column">
        <h2 class="marketing-headline">
            Booking System for <br>Medical Clinics
        </h2>
        <p class="marketing-subtext">
            Easily manage clinic appointments and patient records in one seamless, secure platform.
        </p>
        <a href="#" class="browse-button">Log in to browse ▶</a>
    </div>
</div>

<?php include __DIR__ . "/partials/footer.php"; ?>

</body>
</html>
