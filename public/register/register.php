<?php
require_once __DIR__ . '/../../classes/User.php';

$message = "";
$message_class = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = new User();

    // Safely read input values
    $fname = trim($_POST['fname'] ?? '');
    $mname = trim($_POST['mname'] ?? '');
    $lname = trim($_POST['lname'] ?? '');
    $dob = $_POST['dob'] ?? null;
    $gender = $_POST['gender'] ?? '';
    $contact = trim($_POST['contact'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // ✅ Client-side validations
    if (empty($fname) || empty($lname) || empty($username) || empty($password) || empty($confirm_password)) {
        $message = "❌ Please fill in all required fields.";
        $message_class = "error";
    } elseif ($password !== $confirm_password) {
        $message = "❌ Passwords do not match.";
        $message_class = "error";
    } else {
        // Run registration
        $result = $user->registerPatient(
            $fname, $mname, $lname, $dob, $gender,
            $contact, $email, $address, $username, $password
        );

        // ✅ Check result message to determine style
        if (strpos($result, 'successful') !== false) {
            $message_class = "success";
        } else {
            $message_class = "error";
        }

        $message = $result;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Registration</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f2f5f7;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 450px;
            margin: 50px auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            padding: 30px;
        }
        h2 {
            text-align: center;
            margin-bottom: 25px;
            color: #2c3e50;
        }
        form label {
            display: block;
            margin-bottom: 6px;
            color: #333;
            font-weight: bold;
        }
        form input, form select, form textarea {
            width: 100%;
            padding: 8px 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }
        form button {
            width: 100%;
            background: #3498db;
            color: #fff;
            border: none;
            padding: 10px;
            font-size: 16px;
            border-radius: 6px;
            cursor: pointer;
        }
        form button:hover {
            background: #2980b9;
        }
        .message {
            margin-top: 15px;
            text-align: center;
            font-weight: bold;
            padding: 10px;
            border-radius: 6px;
        }
        .success {
            color: #155724;
            background: #d4edda;
            border: 1px solid #c3e6cb;
        }
        .error {
            color: #721c24;
            background: #f8d7da;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>Patient Registration</h2>

    <?php if (!empty($message)): ?>
        <div class="message <?= $message_class ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <label for="fname">First Name *</label>
        <input type="text" name="fname" value="<?= htmlspecialchars($_POST['fname'] ?? '') ?>" required>

        <label for="mname">Middle Initial</label>
        <input type="text" name="mname" maxlength="1" value="<?= htmlspecialchars($_POST['mname'] ?? '') ?>">

        <label for="lname">Last Name *</label>
        <input type="text" name="lname" value="<?= htmlspecialchars($_POST['lname'] ?? '') ?>" required>

        <label for="dob">Date of Birth</label>
        <input type="date" name="dob" value="<?= htmlspecialchars($_POST['dob'] ?? '') ?>">

        <label for="gender">Gender</label>
        <select name="gender">
            <option value="">--Select--</option>
            <option value="Male" <?= (($_POST['gender'] ?? '') === 'Male') ? 'selected' : '' ?>>Male</option>
            <option value="Female" <?= (($_POST['gender'] ?? '') === 'Female') ? 'selected' : '' ?>>Female</option>
        </select>

        <label for="contact">Contact Number</label>
        <input type="text" name="contact" value="<?= htmlspecialchars($_POST['contact'] ?? '') ?>">

        <label for="email">Email</label>
        <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">

        <label for="address">Address</label>
        <textarea name="address" rows="2"><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>

        <label for="username">Username *</label>
        <input type="text" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>

        <label for="password">Password *</label>
        <input type="password" name="password" required>

        <label for="confirm_password">Confirm Password *</label>
        <input type="password" name="confirm_password" required>

        <button type="submit">Register</button>
    </form>
</div>
</body>
</html>
