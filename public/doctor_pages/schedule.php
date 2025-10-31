<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// ✅ Only allow logged-in doctors
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'doctor') {
    header("Location: ../index.php");
    exit;
}

require_once dirname(__DIR__, 2) . '/config/Database.php';
require_once dirname(__DIR__, 2) . '/classes/Schedule.php';

$db = new Database();
$conn = $db->connect();
$schedule = new Schedule();

$doc_id = intval($_SESSION['DOC_ID']);

// ✅ Handle Add & Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $day = trim($_POST['day']);
    $start = trim($_POST['start_time']);
    $end = trim($_POST['end_time']);
    $sched_id = $_POST['sched_id'] ?? '';

    if ($sched_id === '') {
        // INSERT
        $sql = "INSERT INTO schedule
                (SCHED_DAYS, SCHED_START_TIME, SCHED_END_TIME, DOC_ID,
                SCHED_CREATED_AT, SCHED_UPDATED_AT)
                VALUES (?, ?, ?, ?, NOW(), NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$day, $start, $end, $doc_id]);
    } else {
        // UPDATE
        $sql = "UPDATE schedule
                SET SCHED_DAYS=?, SCHED_START_TIME=?, SCHED_END_TIME=?, SCHED_UPDATED_AT=NOW()
                WHERE SCHED_ID=? AND DOC_ID=?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$day, $start, $end, $sched_id, $doc_id]);
    }

    header("Location: schedule.php");
    exit;
}

// ✅ Handle Delete (GET)
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM schedule WHERE SCHED_ID=? AND DOC_ID=?");
    $stmt->execute([$id, $doc_id]);
    header("Location: schedule.php");
    exit;
}

// ✅ Fetch schedules
$schedules = $schedule->getScheduleByDoctor($doc_id);

function esc($v) {
    return htmlspecialchars($v ?? '', ENT_QUOTES);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Doctor Schedule | Medicina</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="/Booking-System-For-Medical-Clinics/assets/css/style.css">
</head>

<body class="bg-[var(--secondary)] min-h-screen flex flex-col">

  <!-- ✅ NAVIGATION -->
  <div class="navbar flex justify-between items-center px-10 py-4 rounded-b-[35px] bg-[var(--primary)] shadow-lg">
    <div class="navbar-brand flex items-center text-white text-2xl font-bold">
      <img src="https://cdn-icons-png.flaticon.com/512/3209/3209999.png" class="w-10 mr-3">
      Medicina
    </div>
    <div class="nav-links flex gap-6">
      <a href="/Booking-System-For-Medical-Clinics/public/doctor_dashboard.php">Home</a>
      <a href="doctor_manage.php">Doctor</a>
      <a class="active" href="schedule.php">Schedule</a>
      <a href="appointments.php">Appointment</a>
      <a href="medical_records.php">Medical Records</a>
      <a href="/Booking-System-For-Medical-Clinics/index.php">Log out</a>
    </div>
  </div>

  <!-- ✅ MAIN CONTENT -->
  <main class="flex-1 p-10">
    <h2 class="text-[38px] font-bold text-[var(--primary)] mb-6">My Schedule</h2>

    <!-- ADD / EDIT Form -->
    <div class="bg-[var(--light)] p-6 rounded-[25px] shadow-md">
      <form id="scheduleForm" action="" method="POST" class="flex flex-wrap gap-4 items-center">
        <input type="hidden" name="sched_id" id="sched_id">

        <div class="flex flex-col">
          <label class="font-semibold text-[var(--primary)] mb-1">Day</label>
          <select name="day" id="day" required class="border rounded-lg px-4 py-2">
            <option value="">Select</option>
            <?php foreach(['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'] as $d): ?>
              <option value="<?= $d ?>"><?= $d ?></option>
            <?php endforeach ?>
          </select>
        </div>

        <div class="flex flex-col">
          <label class="font-semibold text-[var(--primary)] mb-1">Start Time</label>
          <input type="time" name="start_time" id="start_time" required class="border rounded-lg px-4 py-2">
        </div>

        <div class="flex flex-col">
          <label class="font-semibold text-[var(--primary)] mb-1">End Time</label>
          <input type="time" name="end_time" id="end_time" required class="border rounded-lg px-4 py-2">
        </div>

        <button type="submit"
          class="bg-[var(--primary)] text-white px-6 py-2 rounded-xl hover:opacity-90 transition">
          Save Schedule
        </button>
      </form>
    </div>

    <!-- ✅ SCHEDULE LIST -->
    <div class="bg-[var(--light)] p-6 rounded-[25px] shadow-md mt-8">
      <table class="w-full text-[var(--primary)]">
        <thead>
          <tr class="border-b border-gray-300 text-left">
            <th class="py-3 px-4 text-lg">Day</th>
            <th class="py-3 px-4 text-lg">Start</th>
            <th class="py-3 px-4 text-lg">End</th>
            <th class="py-3 px-4 text-lg">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if(!$schedules): ?>
          <tr><td colspan="4" class="py-3 px-4 text-center opacity-70">No schedule added</td></tr>
          <?php endif; ?>

          <?php foreach ($schedules as $s): ?>
          <tr class="border-b border-gray-300 hover:bg-gray-100">
            <td class="py-3 px-4"><?= esc($s['SCHED_DAYS']) ?></td>
            <td class="py-3 px-4"><?= esc($s['SCHED_START_TIME']) ?></td>
            <td class="py-3 px-4"><?= esc($s['SCHED_END_TIME']) ?></td>
            <td class="py-3 px-4">
              <button onclick='editSchedule(<?= json_encode($s) ?>)'
                class="bg-blue-600 text-white px-4 py-1 rounded-lg mr-2 hover:opacity-90">Edit</button>

              <a href="?delete=<?= esc($s['SCHED_ID']) ?>"
                onclick="return confirm('Delete this schedule?')"
                class="bg-red-600 text-white px-4 py-1 rounded-lg hover:opacity-90">
                Delete
              </a>
            </td>
          </tr>
          <?php endforeach ?>
        </tbody>
      </table>
    </div>
  </main>

  <footer class="bg-[var(--primary)] text-white text-center py-4 rounded-t-[35px]">
    © 2025 Medicina Clinic | All Rights Reserved
  </footer>

  <script>
    function editSchedule(data) {
      document.getElementById('sched_id').value = data.SCHED_ID;
      document.getElementById('day').value = data.SCHED_DAYS;
      document.getElementById('start_time').value = data.SCHED_START_TIME;
      document.getElementById('end_time').value = data.SCHED_END_TIME;
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }
  </script>

</body>
</html>
