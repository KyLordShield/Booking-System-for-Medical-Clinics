<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
</head>
<body style="font-family: Arial; text-align:center; padding-top:50px;">
    <h1>Welcome, Admin!</h1>
    <p>You are logged in as <strong>Administrator</strong>.</p>
    <p>User ID: <?= htmlspecialchars($_SESSION['user_id']); ?></p>
    <a href="../index.php">Logout</a>
</body>
</html>
