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

<?php include dirname(__DIR__) . "/partials/header.php"; ?>

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

<?php include dirname(__DIR__) . "/partials/footer.php"; ?>

</body>
</html>
