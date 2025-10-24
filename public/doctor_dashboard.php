<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'doctor') {
    header("Location: ../index.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Doctor Dashboard</title>
</head>
<body style="font-family: Arial; text-align:center; padding-top:50px;">
    <h1>Welcome, Doctor!</h1>
    <p>You are logged in as <strong>Doctor</strong>.</p>
    <p>Your Doctor ID: <?= htmlspecialchars($_SESSION['user_id']); ?></p>
    <a href="../index.php">Logout</a>
</body>
</html>
