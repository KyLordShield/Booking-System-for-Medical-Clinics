<?php
session_start();

/* ---------- 1. AUTH CHECK ---------- */
if (
    empty($_SESSION['USER_IS_SUPERADMIN']) ||
    $_SESSION['USER_IS_SUPERADMIN'] != 1 ||
    $_SESSION['role'] !== 'admin'
) {
    header("Location: ../../index.php");
    exit;
}

require_once dirname(__DIR__, 2) . '/config/Database.php';
require_once dirname(__DIR__, 2) . '/classes/Appointment.php';
require_once dirname(__DIR__, 2) . '/classes/Doctor.php';
require_once dirname(__DIR__, 2) . '/classes/Patient.php';

$db = new Database();
$conn = $db->connect();

$appt = new Appointment();
$doctor = new Doctor();
$patient = new Patient();

/* ---------- 2. CRUD HANDLING (AJAX) ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json');

    $id = trim($_POST['APPT_ID'] ?? '');
    $data = [
        'PAT_ID' => $_POST['PAT_ID'] ?? '',
        'DOC_ID' => $_POST['DOC_ID'] ?? '',
        'SERV_ID' => $_POST['SERV_ID'] ?? '',
        'APPT_DATE' => $_POST['APPT_DATE'] ?? '',
        'APPT_TIME' => $_POST['APPT_TIME'] ?? '',
        'APPT_STATUS' => $_POST['APPT_STATUS'] ?? 'Pending',
    ];

    if (!$data['PAT_ID'] || !$data['DOC_ID'] || !$data['APPT_DATE'] || !$data['APPT_TIME']) {
        echo json_encode(['success' => false, 'message' => 'All fields are required.']);
        exit;
    }

    if ($id == '') {
        $ok = $appt->add($data);
        echo json_encode(['success' => $ok, 'message' => $ok ? 'Appointment added.' : 'Insert failed.']);
    } else {
        $ok = $appt->update($id, $data);
        echo json_encode(['success' => $ok, 'message' => $ok ? 'Appointment updated.' : 'Update failed.']);
    }
    exit;
}

/* ---------- 3. DELETE ---------- */
if (isset($_GET['delete']) && isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json');
    $id = $_GET['delete'] ?? '';
    $ok = $appt->delete($id);
    echo json_encode(['success' => $ok, 'message' => $ok ? 'Deleted successfully.' : 'Delete failed.']);
    exit;
}

/* ---------- 4. FETCH DATA ---------- */
$appointments = $appt->getAll();
$doctors = $doctor->getAll();
$patients = $patient->getAllPatients();
function esc($v) { return htmlspecialchars($v ?? '', ENT_QUOTES); }
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Appointments | Medicina</title>
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
    <a href="/Booking-System-For-Medical-Clinics/public/admin_pages/admin_schedules.php">Schedules</a>
    <a class="active" href="#">Appointments</a>
    <a href="/Booking-System-For-Medical-Clinics/public/admin_pages/admin_medical_records.php">Medical Records</a>
    <a href="/Booking-System-For-Medical-Clinics/public/admin_pages/admin_payments.php">Payments</a>
    <a href="/Booking-System-For-Medical-Clinics/index.php">Log out</a>
  </div>
</div>

<!-- ✅ MAIN -->
<main class="flex-1 p-10">
  <h2 class="text-[38px] font-bold text-[var(--primary)] mb-6">Appointments</h2>

  <!-- ✅ Add/Edit Form -->
  <div class="bg-[var(--light)] p-6 rounded-[25px] shadow-md mb-6">
    <form id="apptForm" class="grid md:grid-cols-5 gap-4">
      <input type="hidden" name="APPT_ID" id="APPT_ID">

      <div>
        <label class="font-semibold">Patient</label>
        <select name="PAT_ID" id="PAT_ID" required class="border rounded-lg w-full px-2 py-2">
          <option value="">Select Patient</option>
          <?php foreach ($patients as $p): ?>
            <option value="<?= esc($p['PAT_ID']) ?>"><?= esc($p['PAT_FIRST_NAME'] . ' ' . $p['PAT_LAST_NAME']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label class="font-semibold">Doctor</label>
        <select name="DOC_ID" id="DOC_ID" required class="border rounded-lg w-full px-2 py-2">
          <option value="">Select Doctor</option>
          <?php foreach ($doctors as $d): ?>
            <option value="<?= esc($d['DOC_ID']) ?>"><?= esc('Dr. ' . $d['DOC_FIRST_NAME'] . ' ' . $d['DOC_LAST_NAME']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label class="font-semibold">Date</label>
        <input type="date" name="APPT_DATE" id="APPT_DATE" required class="border rounded-lg w-full px-2 py-2">
      </div>

      <div>
        <label class="font-semibold">Time</label>
        <input type="time" name="APPT_TIME" id="APPT_TIME" required class="border rounded-lg w-full px-2 py-2">
      </div>

      <button type="submit" class="bg-[var(--primary)] text-white rounded-lg px-5 py-3 h-fit mt-auto hover:opacity-90 transition">
        Save
      </button>
    </form>
  </div>

  <!-- ✅ Appointment Table -->
  <div class="bg-[var(--light)] p-6 rounded-[25px] shadow-md">
    <table class="w-full text-[var(--primary)]">
      <thead>
        <tr class="border-b font-semibold">
          <th class="py-3 px-3">ID</th>
          <th class="py-3 px-3">Patient</th>
          <th class="py-3 px-3">Doctor</th>
          <th class="py-3 px-3">Date</th>
          <th class="py-3 px-3">Time</th>
          <th class="py-3 px-3">Status</th>
          <th class="py-3 px-3">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($appointments)): ?>
          <tr><td colspan="7" class="py-5 text-center">No appointments found.</td></tr>
        <?php else: foreach ($appointments as $a): ?>
          <tr class="border-b hover:bg-gray-50">
            <td class="py-3 px-3"><?= esc($a['APPT_ID']) ?></td>
            <td class="py-3 px-3"><?= esc($a['PATIENT_NAME'] ?? '') ?></td>
            <td class="py-3 px-3"><?= esc($a['DOCTOR_NAME'] ?? '') ?></td>
            <td class="py-3 px-3"><?= esc($a['APPT_DATE']) ?></td>
            <td class="py-3 px-3"><?= esc($a['APPT_TIME']) ?></td>
            <td class="py-3 px-3"><?= esc($a['APPT_STATUS']) ?></td>
            <td class="py-3 px-3 flex gap-2">
              <button onclick='editAppt(<?= json_encode($a) ?>)' class="btn bg-blue-600 text-white px-3 py-1 rounded-lg">Edit</button>
              <button onclick="deleteAppt('<?= esc($a['APPT_ID']) ?>')" class="btn bg-red-600 text-white px-3 py-1 rounded-lg">Delete</button>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</main>

<footer class="bg-[var(--primary)] text-white text-center py-4 rounded-t-[35px] mt-10">
  © 2025 Medicina Clinic
</footer>

<script>
const form = document.getElementById('apptForm');
form.onsubmit = async e => {
  e.preventDefault();
  const data = new FormData(form);
  const res = await fetch(location.pathname, {
    method: 'POST',
    body: data,
    headers: { 'X-Requested-With': 'XMLHttpRequest' }
  });
  const json = await res.json();
  alert(json.message);
  if (json.success) location.reload();
};

function editAppt(a) {
  for (let k in a) if (form[k]) form[k].value = a[k];
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

async function deleteAppt(id) {
  if (!confirm('Delete this appointment?')) return;
  const res = await fetch(location.pathname + '?delete=' + id, {
    headers: { 'X-Requested-With': 'XMLHttpRequest' }
  });
  const json = await res.json();
  alert(json.message);
  if (json.success) location.reload();
}
</script>
</body>
</html>
