<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../index.php");
    exit;
}

require_once dirname(__DIR__, 1) . '/config/Database.php';
$db = new Database();
$conn = $db->connect();

// ✅ Get current staff info
$staff_id = intval($_SESSION['STAFF_ID']);

$stmt = $conn->prepare("
    SELECT STAFF_FIRST_NAME, STAFF_MIDDLE_INIT, STAFF_LAST_NAME
    FROM staff
    WHERE STAFF_ID = ?
");
$stmt->execute([$staff_id]);
$staff = $stmt->fetch(PDO::FETCH_ASSOC);

$first = htmlspecialchars($staff['STAFF_FIRST_NAME']);
$mid = htmlspecialchars($staff['STAFF_MIDDLE_INIT']);
$last = htmlspecialchars($staff['STAFF_LAST_NAME']);
$middle = $mid ? "$mid. " : "";

$fullName = "$first $middle$last";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Staff Dashboard | Medicina</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="/Booking-System-For-Medical-Clinics/assets/css/style.css">
</head>

<body class="bg-[var(--secondary)] min-h-screen flex flex-col font-[Georgia]">

<div class="navbar flex justify-between items-center px-10 py-5 bg-[var(--primary)] rounded-b-[35px] shadow-lg">
  <div class="navbar-brand flex items-center text-white text-2xl font-bold">
    <img src="https://cdn-icons-png.flaticon.com/512/3209/3209999.png" class="w-11 mr-3">
    Medicina
  </div>

  <div class="nav-links flex flex-wrap gap-4">
    <a class="active" href="/Booking-System-For-Medical-Clinics/public/staff_dashboard.php">Home</a>
    <a href="/Booking-System-For-Medical-Clinics/public/staff_pages/staff_manage.php">Staff</a>
    <a href="/Booking-System-For-Medical-Clinics/public/staff_pages/services.php">Services</a>
    <a href="/Booking-System-For-Medical-Clinics/public/staff_pages/status.php">Status</a>
    <a href="/Booking-System-For-Medical-Clinics/public/staff_pages/payments.php">Payments</a>
    <a href="/Booking-System-For-Medical-Clinics/public/staff_pages/specialization.php">Specialization</a>
    <a href="/Booking-System-For-Medical-Clinics/public/staff_pages/smedical_records.php">Medical Records</a>
    <a href="/Booking-System-For-Medical-Clinics/index.php">Log out</a>
  </div>
</div>

<main class="flex flex-1 items-center px-20 py-16 gap-14">

  <div class="profile-card bg-[var(--light)] w-[250px] h-[250px] rounded-[40px] flex justify-center items-center shadow-md">
    <img src="https://cdn-icons-png.flaticon.com/512/2922/2922561.png" class="w-[130px]">
  </div>

  <div class="staff-info">
    <h1 class="text-[45px] font-bold text-[var(--primary)]">Welcome <?= $last ?></h1>
    <p class="text-[20px] mt-1 text-gray-800"><?= $fullName ?></p>
    <p class="text-[18px] mt-1 text-gray-700">Staff ID: <?= $staff_id ?></p>

    <a href="/Booking-System-For-Medical-Clinics/public/staff_pages/staff_update.php">
      <button class="btn-update mt-5 bg-[var(--light)] px-8 py-2 rounded-full font-semibold hover:bg-[#bfe1eb] transition">
        UPDATE INFO
      </button>
    </a>
  </div>

</main>

<footer class="bg-[var(--primary)] text-[var(--white)] text-center py-4 text-sm rounded-t-[35px]">
  &copy; 2025 Medicina Clinic | All Rights Reserved
</footer>

</body>
</html>
