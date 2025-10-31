<?php
session_start();
require_once __DIR__ . '/../../classes/Appointment.php';

// ✅ Restrict to Doctor Role Only
if (!isset($_SESSION['DOC_ID']) || $_SESSION['role'] !== 'doctor') {
    header("Location: /Booking-System-For-Medical-Clinics/index.php");
    exit;
}

$doctorId = $_SESSION['DOC_ID'];
$conn = (new Database())->connect();

$sql = "SELECT 
            A.APPT_ID, 
            A.APPT_DATE, 
            A.APPT_TIME,
            CONCAT(P.PAT_FIRST_NAME, ' ', P.PAT_LAST_NAME) AS PATIENT_NAME,
            S.SERV_NAME,
            ST.STAT_NAME
        FROM APPOINTMENT A
        JOIN PATIENT P ON A.PAT_ID = P.PAT_ID
        JOIN SERVICE S ON A.SERV_ID = S.SERV_ID
        JOIN STATUS ST ON A.STAT_ID = ST.STAT_ID
        WHERE A.DOC_ID = :doc_id
        ORDER BY A.APPT_DATE ASC, A.APPT_TIME ASC";

$stmt = $conn->prepare($sql);
$stmt->bindParam(':doc_id', $doctorId);
$stmt->execute();
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ✅ Separate appointments
$today = date('Y-m-d');
$todayAppointments = [];
$upcomingAppointments = [];
$completedAppointments = [];

foreach ($appointments as $appt) {
    if ($appt['STAT_NAME'] === 'Completed') {
        $completedAppointments[] = $appt;
    } elseif ($appt['APPT_DATE'] === $today) {
        $todayAppointments[] = $appt;
    } elseif ($appt['APPT_DATE'] > $today) {
        $upcomingAppointments[] = $appt;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Appointments | Medicina</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- ✅ Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- ✅ Shared Styles -->
  <link rel="stylesheet" href="/Booking-System-For-Medical-Clinics/assets/css/style.css">
</head>
<body class="bg-[var(--secondary)] flex flex-col min-h-screen font-[Georgia]">

  <!-- ✅ NAVBAR -->
  <div class="navbar flex justify-between items-center bg-[var(--primary)] px-10 py-4 rounded-b-[35px] shadow-lg">
    <div class="navbar-brand flex items-center text-[var(--white)] text-2xl font-bold">
      <img src="https://cdn-icons-png.flaticon.com/512/3209/3209999.png" alt="Medicina Logo" class="w-10 mr-3">
      Medicina
    </div>
    <div class="nav-links flex gap-6">
      <a href="/Booking-System-For-Medical-Clinics/public/doctor_dashboard.php">Home</a>
      <a href="doctor_manage.php">Doctor</a>
      <a href="/Booking-System-For-Medical-Clinics/public/doctor_pages/schedule.php">Schedule</a>
      <a class="active" href="/Booking-System-For-Medical-Clinics/public/doctor_pages/appointments.php">Appointments</a>
      <a href="/Booking-System-For-Medical-Clinics/public/doctor_pages/medical_records.php">Medical Records</a>
      <a href="/Booking-System-For-Medical-Clinics/index.php">Logout</a>
    </div>
  </div>

  <!-- ✅ MAIN CONTENT -->
  <main class="flex-1 p-10">
    <h1 class="text-[38px] font-bold text-[var(--primary)] mb-8">Appointments</h1>

    <!-- ✅ Tabs -->
    <div class="tabs flex flex-wrap gap-4 mb-6">
      <button class="tab-btn active" data-tab="today" onclick="showTab('today')">Today</button>
      <button class="tab-btn" data-tab="upcoming" onclick="showTab('upcoming')">Upcoming</button>
      <button class="tab-btn" data-tab="completed" onclick="showTab('completed')">Completed</button>
    </div>

    <!-- ✅ TODAY -->
    <div id="today" class="table-container bg-[var(--light)] p-6 rounded-[25px] shadow-md">
      <h2 class="text-[var(--primary)] mb-4 text-2xl font-semibold">Today’s Appointments</h2>
      <table class="w-full border-collapse text-[var(--primary)]">
        <thead>
          <tr class="border-b border-gray-300">
            <th class="py-3 px-4 text-left">Time</th>
            <th class="py-3 px-4 text-left">Patient</th>
            <th class="py-3 px-4 text-left">Service</th>
            <th class="py-3 px-4 text-left">Status</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($todayAppointments)): ?>
            <?php foreach ($todayAppointments as $appt): ?>
              <tr class="border-b border-gray-300 hover:bg-gray-50">
                <td class="py-3 px-4"><?= htmlspecialchars($appt['APPT_TIME']) ?></td>
                <td class="py-3 px-4"><?= htmlspecialchars($appt['PATIENT_NAME']) ?></td>
                <td class="py-3 px-4"><?= htmlspecialchars($appt['SERV_NAME']) ?></td>
                <td class="py-3 px-4"><?= htmlspecialchars($appt['STAT_NAME']) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="4" class="py-3 px-4 text-center text-gray-500">No appointments for today.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- ✅ UPCOMING -->
    <div id="upcoming" class="table-container bg-[var(--light)] p-6 rounded-[25px] shadow-md mt-6 hidden">
      <h2 class="text-[var(--primary)] mb-4 text-2xl font-semibold">Upcoming Appointments</h2>
      <table class="w-full border-collapse text-[var(--primary)]">
        <thead>
          <tr class="border-b border-gray-300">
            <th class="py-3 px-4 text-left">Date</th>
            <th class="py-3 px-4 text-left">Time</th>
            <th class="py-3 px-4 text-left">Patient</th>
            <th class="py-3 px-4 text-left">Service</th>
            <th class="py-3 px-4 text-left">Status</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($upcomingAppointments)): ?>
            <?php foreach ($upcomingAppointments as $appt): ?>
              <tr class="border-b border-gray-300 hover:bg-gray-50">
                <td class="py-3 px-4"><?= htmlspecialchars($appt['APPT_DATE']) ?></td>
                <td class="py-3 px-4"><?= htmlspecialchars($appt['APPT_TIME']) ?></td>
                <td class="py-3 px-4"><?= htmlspecialchars($appt['PATIENT_NAME']) ?></td>
                <td class="py-3 px-4"><?= htmlspecialchars($appt['SERV_NAME']) ?></td>
                <td class="py-3 px-4"><?= htmlspecialchars($appt['STAT_NAME']) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="5" class="py-3 px-4 text-center text-gray-500">No upcoming appointments.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- ✅ COMPLETED -->
    <div id="completed" class="table-container bg-[var(--light)] p-6 rounded-[25px] shadow-md mt-6 hidden">
      <h2 class="text-[var(--primary)] mb-4 text-2xl font-semibold">Completed Appointments</h2>
      <table class="w-full border-collapse text-[var(--primary)]">
        <thead>
          <tr class="border-b border-gray-300">
            <th class="py-3 px-4 text-left">Date</th>
            <th class="py-3 px-4 text-left">Time</th>
            <th class="py-3 px-4 text-left">Patient</th>
            <th class="py-3 px-4 text-left">Service</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($completedAppointments)): ?>
            <?php foreach ($completedAppointments as $appt): ?>
              <tr class="border-b border-gray-300 hover:bg-gray-50">
                <td class="py-3 px-4"><?= htmlspecialchars($appt['APPT_DATE']) ?></td>
                <td class="py-3 px-4"><?= htmlspecialchars($appt['APPT_TIME']) ?></td>
                <td class="py-3 px-4"><?= htmlspecialchars($appt['PATIENT_NAME']) ?></td>
                <td class="py-3 px-4"><?= htmlspecialchars($appt['SERV_NAME']) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="4" class="py-3 px-4 text-center text-gray-500">No completed appointments.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </main>

  <!-- ✅ FOOTER -->
  <footer class="bg-[var(--primary)] text-[var(--white)] text-center py-4 rounded-t-[35px] text-sm mt-6">
    &copy; 2025 Medicina Clinic | All Rights Reserved
  </footer>

  <!-- ✅ JS for Tabs -->
  <script>
    function showTab(tabName) {
      document.querySelectorAll('.table-container').forEach(c => c.classList.add('hidden'));
      document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
      document.getElementById(tabName).classList.remove('hidden');
      document.querySelector(`[data-tab="${tabName}"]`).classList.add('active');
    }
    window.onload = () => showTab('today');
  </script>
</body>
</html>
