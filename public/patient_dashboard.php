<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'patient') {
    header("Location: ../index.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Patient Dashboard</title>
</head>
<body style="font-family: Arial; text-align:center; padding-top:50px;">
    <h1>Welcome, Patient!</h1>
    <p>You are logged in as <strong>Patient</strong>.</p>
    <p>Your Patient ID: <?= htmlspecialchars($_SESSION['user_id']); ?></p>
    <a href="../index.php">Logout</a>
</body>
</html>
