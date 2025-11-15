<?php
session_start();

// ---------- 1. AUTH CHECK ----------
if (
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'admin' ||
    empty($_SESSION['USER_IS_SUPERADMIN'])
) {
    header("Location: ../index.php");
    exit;
}


// ---------- 2. CONNECT TO DATABASE ----------
require_once dirname(__DIR__) . "/config/Database.php";
$db = new Database();
$conn = $db->connect();

// ---------- 3. FETCH DASHBOARD COUNTS ----------
try {
    $stmt_doctors = $conn->query("SELECT COUNT(*) AS total FROM DOCTOR");
    $stmt_patients = $conn->query("SELECT COUNT(*) AS total FROM PATIENT");
    $stmt_staff = $conn->query("SELECT COUNT(*) AS total FROM STAFF");
    $stmt_pending = $conn->query("
        SELECT COUNT(*) AS total 
        FROM APPOINTMENT a
        JOIN STATUS s ON a.STAT_ID = s.STAT_ID
        WHERE s.STAT_NAME = 'Pending'
    ");

    $total_doctors = $stmt_doctors->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    $total_patients = $stmt_patients->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    $total_staff = $stmt_staff->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    $pending_appointments = $stmt_pending->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
} catch (PDOException $e) {
    die("Error fetching counts: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Admin Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Try both common locations (browser will load the one that exists).
       If your global style.css lives elsewhere, replace these hrefs with the correct path. -->
  <link rel="stylesheet" href="/Booking-System-For-Medical-Clinics/public/css/style.css">
  <link rel="stylesheet" href="/Booking-System-For-Medical-Clinics/assets/css/style.css">

  <!-- Minimal rule to support toggling dropdowns via a class (does not change existing styles) -->
  

  </style>
</head>
<body>

<!-- NAVBAR -->
<!-- ✅ HEADER LINK -->
  <?php include dirname(__DIR__) . "/partials/header.php"; ?>
<!-- ✅ HEADER LINK -->

<!-- MAIN CONTENT -->
<main>
  <h1>Welcome, Super Admin</h1>

  <!-- DASHBOARD STAT CARDS -->
  <div class="dashboard-cards">
    <div class="card">
      <h2><?= $total_doctors ?></h2>
      <p>Total Doctors</p>
    </div>
    <div class="card">
      <h2><?= $total_patients ?></h2>
      <p>Total Patients</p>
    </div>
    <div class="card">
      <h2><?= $total_staff ?></h2>
      <p>Staff Members</p>
    </div>
    <div class="card">
      <h2><?= $pending_appointments ?></h2>
      <p>Pending Appointments</p>
    </div>
  </div>

  <p class="welcome-text" style="font-size:18px; color:#002339; text-align:center;">
    Use the navigation above to manage users, appointments, and clinic data.
  </p>
</main>

<!-- FOOTER -->
  <?php include dirname(__DIR__) . "/partials/footer.php"; ?>


<!-- JS: toggles .open on dropdown when its toggle is clicked; closes when clicking outside -->
<script>
  document.addEventListener('DOMContentLoaded', function () {
    // open/close dropdowns on click for mobile & keyboard users
    const toggles = document.querySelectorAll('.dropdown > .dropdown-toggle');
    toggles.forEach(toggle => {
      toggle.addEventListener('click', function (ev) {
        ev.preventDefault();
        const parent = this.closest('.dropdown');

        // close other open dropdowns
        document.querySelectorAll('.dropdown.open').forEach(d => {
          if (d !== parent) d.classList.remove('open');
        });

        // toggle this
        const isOpen = parent.classList.toggle('open');
        this.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
      });
    });

    // close dropdowns when clicking outside
    document.addEventListener('click', function (ev) {
      if (!ev.target.closest('.dropdown')) {
        document.querySelectorAll('.dropdown.open').forEach(d => d.classList.remove('open'));
        document.querySelectorAll('.dropdown-toggle').forEach(t => t.setAttribute('aria-expanded', 'false'));
      }
    });

    // close with Escape key
    document.addEventListener('keydown', function (ev) {
      if (ev.key === 'Escape') {
        document.querySelectorAll('.dropdown.open').forEach(d => d.classList.remove('open'));
        document.querySelectorAll('.dropdown-toggle').forEach(t => t.setAttribute('aria-expanded', 'false'));
      }
    });
  });
</script>
</body>
</html>
