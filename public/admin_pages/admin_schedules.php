<?php 
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// ✅ Only admin can access
// ---------- 1. AUTH CHECK ----------
if (
    empty($_SESSION['USER_IS_SUPERADMIN']) ||
    $_SESSION['USER_IS_SUPERADMIN'] != 1 ||
    $_SESSION['role'] !== 'admin'
) {
    header("Location: ../../index.php");
    exit;
}

require_once dirname(__DIR__, 2) . '/config/Database.php';
require_once dirname(__DIR__, 2) . '/classes/Schedule.php';

$db = new Database();
$conn = $db->connect();
$schedule = new Schedule();

// ✅ Fetch all doctors for dropdown
$doctors = $conn->query("SELECT DOC_ID, CONCAT(DOC_FIRST_NAME, ' ', DOC_LAST_NAME) AS name FROM DOCTOR ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// ✅ Add / Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $days = isset($_POST['day']) ? implode(', ', $_POST['day']) : '';
    $start = $_POST['start_time'] . ":00";
    $end = $_POST['end_time'] . ":00";
    $sched_id = $_POST['sched_id'] ?? '';
    $doc_id = intval($_POST['doc_id']);

    if ($sched_id == '') {
        $sql = "INSERT INTO schedule 
                (SCHED_DAYS, SCHED_START_TIME, SCHED_END_TIME, DOC_ID, SCHED_CREATED_AT, SCHED_UPDATED_AT)
                VALUES (?, ?, ?, ?, NOW(), NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$days, $start, $end, $doc_id]);
    } else {
        $sql = "UPDATE schedule 
                SET SCHED_DAYS=?, SCHED_START_TIME=?, SCHED_END_TIME=?, DOC_ID=?, SCHED_UPDATED_AT=NOW()
                WHERE SCHED_ID=?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$days, $start, $end, $doc_id, $sched_id]);
    }
    header("Location: admin_schedule.php");
    exit;
}

// ✅ Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM schedule WHERE SCHED_ID=?");
    $stmt->execute([$id]);
    header("Location: admin_schedule.php");
    exit;
}

// ✅ Fetch all schedules + doctor names
$sql = "SELECT s.*, CONCAT(d.DOC_FIRST_NAME, ' ', d.DOC_LAST_NAME) AS doctor_name 
        FROM schedule s 
        JOIN doctor d ON s.DOC_ID = d.DOC_ID
        ORDER BY d.DOC_LAST_NAME";
$schedules = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);

function esc($v) { return htmlspecialchars($v ?? '', ENT_QUOTES); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin | Doctor Schedules</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="/Booking-System-For-Medical-Clinics/assets/css/style.css">
</head>

<body class="bg-[var(--secondary)] min-h-screen flex flex-col">

<!-- ✅ NAVBAR -->
<div class="navbar flex justify-between items-center px-10 py-5 bg-[var(--primary)] rounded-b-[35px] shadow-lg">
  <div class="navbar-brand flex items-center text-white text-2xl font-bold">
    <img src="https://cdn-icons-png.flaticon.com/512/3209/3209999.png" class="w-11 mr-3">Medicina
  </div>

  <div class="nav-links flex gap-4">
    <a href="/Booking-System-For-Medical-Clinics/public/admin_dashboard.php">Dashboard</a>
    <a href="/Booking-System-For-Medical-Clinics/public/admin_pages/admin_specialization.php">Specialization</a>
    <a href="/Booking-System-For-Medical-Clinics/public/admin_pages/admin_services.php">Services</a>
    <a href="/Booking-System-For-Medical-Clinics/public/admin_pages/admin_status.php">Status</a>
    <a class="active" href="/Booking-System-For-Medical-Clinics/public/admin_pages/admin_schedules.php">Schedules</a>
    <a href="/Booking-System-For-Medical-Clinics/public/admin_pages/admin_medical_records.php">Medical Records</a>
    <a href="/Booking-System-For-Medical-Clinics/public/admin_pages/admin_payments.php">Payments</a>
    <a href="/Booking-System-For-Medical-Clinics/index.php">Log out</a>
  </div>
</div>


<!-- ✅ CONTENT -->
<main class="flex-1 p-10">
  <h2 class="text-3xl font-bold text-[var(--primary)] mb-6">Doctor Schedules</h2>

  <!-- ✅ FORM -->
  <div class="bg-[var(--light)] p-6 rounded-[25px] shadow-md">
    <form method="POST" class="grid grid-cols-1 md:grid-cols-5 gap-4">
      <input type="hidden" name="sched_id" id="sched_id">

      <!-- Doctor select -->
      <div>
        <label class="font-semibold text-[var(--primary)]">Doctor</label>
        <select name="doc_id" id="doc_id" required class="border rounded-lg w-full px-2 py-2">
          <option value="">Select Doctor</option>
          <?php foreach ($doctors as $doc): ?>
            <option value="<?= $doc['DOC_ID'] ?>"><?= esc($doc['name']) ?></option>
          <?php endforeach ?>
        </select>
      </div>

      <!-- Days -->
      <div>
        <label class="font-semibold text-[var(--primary)]">Days</label>
        <select name="day[]" id="day" multiple required class="border rounded-lg w-full min-h-[140px] px-2 py-2">
          <?php foreach(['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'] as $d): ?>
            <option value="<?= $d ?>"><?= $d ?></option>
          <?php endforeach ?>
        </select>
      </div>

      <!-- Start Hour -->
      <div>
        <label class="font-semibold text-[var(--primary)]">Start</label>
        <select name="start_time" id="start_time" required class="border rounded-lg w-full px-2 py-2">
          <?php for($h=7;$h<=18;$h++): ?>
            <option value="<?= sprintf('%02d', $h) ?>"><?= sprintf('%02d:00', $h) ?></option>
          <?php endfor ?>
        </select>
      </div>

      <!-- End Hour -->
      <div>
        <label class="font-semibold text-[var(--primary)]">End</label>
        <select name="end_time" id="end_time" required class="border rounded-lg w-full px-2 py-2">
          <?php for($h=8;$h<=21;$h++): ?>
            <option value="<?= sprintf('%02d', $h) ?>"><?= sprintf('%02d:00', $h) ?></option>
          <?php endfor ?>
        </select>
      </div>

      <button type="submit" class="bg-[var(--primary)] text-white px-6 py-3 rounded-xl hover:opacity-90 transition h-fit">
        Save
      </button>
    </form>
  </div>

  <!-- ✅ TABLE -->
  <div class="bg-[var(--light)] p-6 rounded-[25px] shadow-md mt-6">
    <table class="w-full text-[var(--primary)]">
      <thead>
        <tr class="border-b text-left font-bold">
          <th class="py-3 px-4">Doctor</th>
          <th class="py-3 px-4">Days</th>
          <th class="py-3 px-4">Start</th>
          <th class="py-3 px-4">End</th>
          <th class="py-3 px-4">Action</th>
        </tr>
      </thead>

      <tbody>
      <?php if(!$schedules): ?>
          <tr><td colspan="5" class="text-center py-3">No schedules yet</td></tr>
      <?php endif; ?>

      <?php foreach ($schedules as $s): ?>
        <tr class="border-b hover:bg-gray-100">
          <td class="py-3 px-4"><?= esc($s['doctor_name']) ?></td>
          <td class="py-3 px-4"><?= esc($s['SCHED_DAYS']) ?></td>
          <td class="py-3 px-4"><?= substr(esc($s['SCHED_START_TIME']),0,5) ?></td>
          <td class="py-3 px-4"><?= substr(esc($s['SCHED_END_TIME']),0,5) ?></td>
          <td class="py-3 px-4 flex gap-2">
            <button onclick='editSchedule(<?= json_encode($s) ?>)' class="btn bg-blue-600 text-white px-4 py-1 rounded-lg">Edit</button>
            <a href="?delete=<?= esc($s['SCHED_ID']) ?>" class="btn bg-red-600 text-white px-4 py-1 rounded-lg" onclick="return confirm('Remove schedule?')">Delete</a>
          </td>
        </tr>
      <?php endforeach ?>
      </tbody>
    </table>
  </div>

</main>

<footer class="bg-[var(--primary)] text-white text-center py-4 rounded-t-[35px]">
© 2025 Medicina Admin | All Rights Reserved
</footer>

<script>
function editSchedule(s) {
  document.getElementById("sched_id").value = s.SCHED_ID;
  document.getElementById("doc_id").value = s.DOC_ID;

  // Multi-select restore
  let savedDays = s.SCHED_DAYS.split(',').map(d => d.trim());
  [...document.getElementById("day").options].forEach(opt => {
    opt.selected = savedDays.includes(opt.value);
  });

  document.getElementById("start_time").value = s.SCHED_START_TIME.substring(0,2);
  document.getElementById("end_time").value = s.SCHED_END_TIME.substring(0,2);
  window.scrollTo({ top: 0, behavior: "smooth" });
}
</script>

</body>
</html>
