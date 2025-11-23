<?php
// Relative path from this file to root
$base_url = '..'; // up one folder from partials/
?>

<!-- ===== HEADER ===== -->
<header class="custom-header">
    <!-- Logo -->
    <div class="logo">
        <a href="<?= $base_url ?>/index.php">
            <img src="<?= $base_url ?>/assets/images/logo.png" alt="Medicina Logo">
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
