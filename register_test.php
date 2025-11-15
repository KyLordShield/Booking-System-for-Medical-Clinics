<?php


// Optional: dynamic page title
$page_title = "Patient Registration";

$message = "";
$message_class = "";


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>

    <link rel="stylesheet" href="/Booking-System-For-Medical-Clinics/assets/css/style.css">
</head>

<body>

    <!-- 👍 INSERT HEADER HERE -->
    <?php include __DIR__ . "/partials/header.php"; ?>
    <!-- 👍 REMOVE YOUR OLD HARDCODED HEADER -->

    <div class="register-container">
        <h2 class="form-title">Register</h2>

        <?php if (!empty($message)): ?>
            <div class="message <?= $message_class ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

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
                            <img src="/Booking-System-For-Medical-Clinics/assets/images/eye.png" style="width:20px;height:20px;">
                        </span>
                    </div>

                    <label for="confirm_password">Confirm Password *</label>
                    <input type="password" name="confirm_password" required>
                </div>

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
        icon.innerHTML = '<img src="/Booking-System-For-Medical-Clinics/assets/images/eye-hide.png" style="width:20px;height:20px;">';
    } else {
        passwordInput.type = 'password';
        icon.innerHTML = '<img src="/Booking-System-For-Medical-Clinics/assets/images/eye.png" style="width:20px;height:20px;">';
    }
}
</script>

</body>
</html>
