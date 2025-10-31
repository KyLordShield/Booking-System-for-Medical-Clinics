<?php
session_start();

// ✅ Only allow logged-in doctors
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'doctor') {
    header("Location: ../index.php");
    exit;
}

require_once dirname(__DIR__, 1) . '/config/Database.php';
$db = new Database();
$conn = $db->connect();

// ✅ Get doctor details based on session DOC_ID
$doc_id = $_SESSION['DOC_ID'];

$stmt = $conn->prepare("
    SELECT DOC_FIRST_NAME, DOC_MIDDLE_INIT, DOC_LAST_NAME 
    FROM doctor 
    WHERE DOC_ID = ?
");
$stmt->execute([$doc_id]);
$doctor = $stmt->fetch(PDO::FETCH_ASSOC);

$first = htmlspecialchars($doctor['DOC_FIRST_NAME']);
$mid = htmlspecialchars($doctor['DOC_MIDDLE_INIT']);
$last = htmlspecialchars($doctor['DOC_LAST_NAME']);
$middle = $mid ? "$mid. " : "";

$fullName = "$first $middle$last";
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Doctor Dashboard | Medicina</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- ✅ Tailwind CDN -->
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- ✅ Global Custom CSS -->
  <link rel="stylesheet" href="/Booking-System-For-Medical-Clinics/assets/css/style.css">
</head>

<body class="bg-[var(--secondary)] min-h-screen flex flex-col font-[Georgia]">

  <!-- ✅ NAVIGATION BAR -->
  <div class="navbar flex justify-between items-center px-10 py-5 bg-[var(--primary)] rounded-b-[35px] shadow-lg">
    <div class="navbar-brand flex items-center text-white text-2xl font-bold">
      <img src="https://cdn-icons-png.flaticon.com/512/3209/3209999.png" class="w-11 mr-3">
      Medicina
    </div>

    <div class="nav-links flex gap-6">
      <a class="active" href="doctor_dashboard.php">Home</a>
      <a href="doctor_pages/doctor_manage.php">Doctor</a>
      <a href="doctor_pages/schedule.php">Schedule</a>
      <a href="doctor_pages/appointments.php">Appointment</a>
      <a href="doctor_pages/medical_records.php">Medical Records</a>
      <a href="../index.php">Log out</a>
    </div>
  </div>

  <!-- ✅ MAIN CONTENT -->
  <main class="flex flex-1 items-center px-20 py-16">
  
    <!-- Profile Card -->
    <div class="profile-card bg-[var(--light)] w-[250px] h-[250px] rounded-[40px] flex justify-center items-center shadow-md">
      <img src="https://cdn-icons-png.flaticon.com/512/387/387561.png" class="w-[130px]">
    </div>

    <!-- Doctor Info -->
    <div class="doctor-info ml-14">
      <h1 class="text-[45px] font-bold text-[var(--primary)]">Welcome Dr. <?= $last ?></h1>
      <p class="text-[20px] mt-1 text-gray-800"><?= $fullName ?></p>
      <p class="text-[18px] mt-1 text-gray-700">Doctor ID: <?= $doc_id ?></p>

      <a href="/Booking-System-For-Medical-Clinics/public/doctor_pages/doctor_manage.php">
        <button class="btn-update mt-5 bg-[var(--light)] px-8 py-2 rounded-full font-semibold hover:bg-[#bfe1eb] transition">
          UPDATE INFO
        </button>
      </a>
    </div>
  </main>

  <!-- ✅ FOOTER -->
  <footer class="bg-[var(--primary)] text-[var(--white)] text-center py-4 text-sm rounded-t-[35px]">
    &copy; 2025 Medicina Clinic | All Rights Reserved
  </footer>

</body>
</html>
