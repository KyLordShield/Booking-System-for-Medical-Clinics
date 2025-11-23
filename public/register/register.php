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

    // ✅ Validations
    if (empty($fname) || empty($lname) || empty($username) || empty($password) || empty($confirm_password)) {
        $message = "❌ Please fill in all required fields.";
        $message_class = "error";
    } elseif ($password !== $confirm_password) {
        $message = "❌ Passwords do not match.";
        $message_class = "error";
    } else {
        // Register user
        $result = $user->registerPatient(
            $fname, $mname, $lname, $dob, $gender,
            $contact, $email, $address, $username, $password
        );

        // ✅ Style message
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
    <meta name="viewport" content="width=device-width , initial-scale=1.0">
    <title>Patient Registration</title>
    <link rel="stylesheet" href="register.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        /* fallback message styles if not included in CSS file */
        .message {
            margin-top: 10px;
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
    <!-- #for header section -->
    <?php
// Include the guest header at the very top
include dirname(__DIR__, 2) . '/partials/guest_header.php';
?>

    <!-- #for main registration container -->
    <div class="register-container">
        <h2 class="form-title">Register</h2>

        <!-- PHP message -->
        <?php if (!empty($message)): ?>
            <div class="message <?= $message_class ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <!-- Registration form -->
        <form method="POST" action="">
            <div class="form-grid">
                <!-- LEFT column -->
                <div class="form-left">
                    <label for="lname">Last Name *</label>
                    <input type="text" name="lname" value="<?= htmlspecialchars($_POST['lname'] ?? '') ?>" required>

                    <label for="fname">First Name *</label>
                    <input type="text" name="fname" value="<?= htmlspecialchars($_POST['fname'] ?? '') ?>" required>

                    <label for="mname">M.I</label>
                    <input type="text" name="mname" maxlength="1" value="<?= htmlspecialchars($_POST['mname'] ?? '') ?>">

                    <div class="dob-sex">
                        <div class="dob-field">
                            <label for="dob">DOB</label>
                            <input type="date" name="dob" value="<?= htmlspecialchars($_POST['dob'] ?? '') ?>">
                        </div>
                        <div class="sex-field">
                            <label for="gender">Sex</label>
                            <select name="gender">
                                <option value="">--Select--</option>
                                <option value="Male" <?= (($_POST['gender'] ?? '') === 'Male') ? 'selected' : '' ?>>Male</option>
                                <option value="Female" <?= (($_POST['gender'] ?? '') === 'Female') ? 'selected' : '' ?>>Female</option>
                            </select>
                        </div>
                    </div>

                    <label for="address">Address</label>
                    <textarea name="address" rows="2"><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>
                </div>

                <!-- RIGHT column -->
                <div class="form-right">
                    <label for="contact">Contact No.</label>
                    <input type="text" name="contact" value="<?= htmlspecialchars($_POST['contact'] ?? '') ?>">

                    <label for="email">Email</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">

                    <label for="username">Username *</label>
                    <input type="text" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>

                    <label for="password">Password *</label>
                    <div class="password-container">
                        <input type="password" name="password" id="password" required>
                        <span class="toggle-password" onclick="togglePassword()">
                            <img src="eye.png" alt="Show password" style="width: 20px; height: 20px;">
                        </span>
                    </div>

                    <label for="confirm_password">Confirm Password *</label>
                    <input type="password" name="confirm_password" required>
                </div>

                <!-- Register button -->
                <div class="register-btn">
                    <button type="submit">Register</button>
                </div>
            </div>
        </form>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const icon = document.querySelector('.toggle-password');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.innerHTML = '<img src="eye hide.png" alt="Hide password" style="width: 20px; height: 20px;">';
            } else {
                passwordInput.type = 'password';
                icon.innerHTML = '<img src="eye.png" alt="Show password" style="width: 20px; height: 20px;">';
            }
        }
    </script>
</body>
</html>
