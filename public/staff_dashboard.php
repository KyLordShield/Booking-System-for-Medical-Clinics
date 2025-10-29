<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../index.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Staff Dashboard</title>
</head>
<body style="font-family: Arial; text-align:center; padding-top:50px;">
    <h1>Welcome, Staff!</h1>
    <p>You are logged in as <strong>Staff Member</strong>.</p>
    <p>Your Staff ID: <?= htmlspecialchars($_SESSION['user_id']); ?></p>
    <a href="../index.php">Logout</a>
</body>
</html>
