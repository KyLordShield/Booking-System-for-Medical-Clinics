<?php
// Auto-detect: Heroku vs Localhost
if (strpos($_SERVER['HTTP_HOST'], 'herokuapp.com') !== false) {
    $base_url = ''; // Heroku root
} else {
    $base_url = '/Booking-System-for-Medical-Clinics'; // Local folder name
}
?>

<!-- ===== HEADER ===== -->
<header class="custom-header">
    <!-- Logo -->
    <div class="logo">
        <a href="<?= $base_url ?>/index.php">
            <img src="https://res.cloudinary.com/dcgd4x4eo/image/upload/v1764521297/logo_qeoieu.png" alt="Medicina Logo">
        </a>
        <h1>Medicina</h1>
    </div>

    <!-- Navigation links -->
    <nav class="nav-links">
        <a href="<?= $base_url ?>/index.php">Home</a>
        <a href="<?= $base_url ?>/login_page.php">Login</a>
        <a href="<?= $base_url ?>/public/register/register.php">Signup</a>
    </nav>
</header>
