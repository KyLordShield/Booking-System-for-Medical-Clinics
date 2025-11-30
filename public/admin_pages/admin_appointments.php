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
require_once dirname(__DIR__, 2) . '/classes/Service.php';

$db = new Database();
$conn = $db->connect();

$appt = new Appointment();
$doctor = new Doctor();
$patient = new Patient();
$service = new Service();

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

    if (!$data['PAT_ID'] || !$data['DOC_ID'] || !$data['SERV_ID'] || !$data['APPT_DATE'] || !$data['APPT_TIME']) {
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
if (isset($_GET['delete'])) {
    // Clean output buffer to prevent any stray output
    if (ob_get_level()) ob_clean();
    
    header('Content-Type: application/json');
    
    $id = $_GET['delete'] ?? '';
    
    if (empty($id)) {
        echo json_encode(['success' => false, 'message' => 'Invalid appointment ID.']);
        exit;
    }
    
    try {
        $ok = $appt->delete($id);
        echo json_encode(['success' => $ok, 'message' => $ok ? 'Deleted successfully.' : 'Delete failed.']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

/* ---------- 4. FETCH DATA ---------- */
$appointments = $appt->getAll();
$doctors = $doctor->getAll();
$patients = $patient->getAllPatients();
$services = $service->getAllServices();

function esc($v) { return htmlspecialchars($v ?? '', ENT_QUOTES); }
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Appointments | Medicina</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="../../assets/css/style.css">

<!-- ✅ SweetAlert2 CDN -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

</head>

<body class="bg-[var(--secondary)] min-h-screen flex flex-col">

<!-- NAVBAR -->
<!-- ✅ HEADER LINK -->
  <?php include dirname(__DIR__, 2) . "/partials/header.php"; ?>
<!-- ✅ HEADER LINK -->

<!-- ✅ MAIN -->
<main class="flex-1 p-10">
  <div class="flex justify-between items-center mb-6">
    <h2 class="text-[38px] font-bold text-[var(--primary)]">Appointments</h2>
    <button onclick="openModal()" class="bg-[var(--primary)] text-white rounded-lg px-6 py-3 hover:opacity-90 transition">+ New Appointment</button>
  </div>

  <!-- ✅ FILTER & SEARCH BAR -->
<div class="tabs">
  <button class="tab-btn filter-btn active" data-filter="all">All</button>
  <button class="tab-btn filter-btn" data-filter="today">Today</button>
  <button class="tab-btn filter-btn" data-filter="upcoming">Upcoming</button>
  <button class="tab-btn filter-btn" data-filter="completed">Completed</button>
  <button class="tab-btn filter-btn" data-filter="cancelled">Cancelled</button>
  <button class="tab-btn filter-btn" data-filter="missed">Missed</button>


  <!-- Search box -->
  <input id="searchInput" type="text" placeholder="Search patient, doctor, or service..."
         style="margin-left:auto; padding:10px 15px; border-radius:25px; border:1px solid #ccc; min-width:250px;">
</div>

  <!-- ✅ Appointment Table -->
  <div class="bg-[var(--light)] p-6 rounded-[25px] shadow-md mb-6 overflow-x-auto">
    <table id="apptTable" class="w-full text-[var(--primary)]">
      <thead>
        <tr class="border-b font-semibold">
          <th class="py-3 px-3">ID</th>
          <th class="py-3 px-3">Patient</th>
          <th class="py-3 px-3">Doctor</th>
          <th class="py-3 px-3">Service</th>
          <th class="py-3 px-3">Date</th>
          <th class="py-3 px-3">Time</th>
          <th class="py-3 px-3">Status</th>
          <th class="py-3 px-3">Action</th>
        </tr>
      </thead>
      <tbody id="apptBody">
          <?php if (empty($appointments)): ?>
            <tr><td colspan="8" class="py-5 text-center">No appointments found.</td></tr>
          <?php else: foreach ($appointments as $a): ?>
            <tr class="border-b hover:bg-gray-50"
                data-status="<?= strtolower($a['APPT_STATUS']) ?>"
                data-date="<?= esc($a['APPT_DATE']) ?>"
                data-patient="<?= strtolower($a['PATIENT_NAME']) ?>"
                data-doctor="<?= strtolower($a['DOCTOR_NAME']) ?>"
                data-service="<?= strtolower($a['SERVICE_NAME']) ?>">
              <td><?= esc($a['APPT_ID']) ?></td>
              <td><?= esc($a['PATIENT_NAME'] ?? '') ?></td>
              <td><?= esc($a['DOCTOR_NAME'] ?? '') ?></td>
              <td><?= esc($a['SERVICE_NAME'] ?? '') ?></td>
              <td><?= esc($a['APPT_DATE']) ?></td>
              <td><?= esc($a['APPT_TIME']) ?></td>
              <td><?= esc($a['APPT_STATUS']) ?></td>
              <td class="flex gap-2">
              <?php if ($a['APPT_DATE'] === date('Y-m-d') && $a['APPT_STATUS'] !== 'Completed'): ?>
                <button onclick="markAsCompleted('<?= esc($a['APPT_ID']) ?>')" 
                        class="btn" style="background:#27ae60;">✔</button>
              <?php endif; ?>
              
              <button onclick='editAppt(<?= json_encode($a) ?>)' class="btn">Edit</button>
              <button onclick="deleteAppt('<?= esc($a['APPT_ID']) ?>')" class="btn" style="background:#c0392b;">Delete</button>
          </td>

            </tr>
          <?php endforeach; endif; ?>
        </tbody>

    </table>
  </div>
</main>

<!-- ✅ MODAL -->
<div id="apptModal" class="fixed inset-0 bg-black/50 hidden justify-center items-center z-50">
  <div class="bg-white rounded-2xl p-8 w-full max-w-2xl relative">
    <h2 class="text-2xl font-bold text-[var(--primary)] mb-6">New Appointment</h2>

    <form id="modalForm" class="grid md:grid-cols-2 gap-4">
      <input type="hidden" name="APPT_ID" id="APPT_ID">

      <div>
        <label class="font-semibold">Patient</label>
        <select name="PAT_ID" id="MODAL_PAT_ID" required class="border rounded-lg w-full px-2 py-2">
          <option value="">Select Patient</option>
          <?php foreach ($patients as $p): ?>
            <option value="<?= esc($p['PAT_ID']) ?>"><?= esc($p['PAT_FIRST_NAME'] . ' ' . $p['PAT_LAST_NAME']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label class="font-semibold">Service</label>
        <select name="SERV_ID" id="MODAL_SERV_ID" required class="border rounded-lg w-full px-2 py-2">
          <option value="">Select Service</option>
          <?php foreach ($services as $s): ?>
            <option value="<?= esc($s['SERV_ID']) ?>"><?= esc($s['SERV_NAME']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label class="font-semibold">Doctor</label>
        <select name="DOC_ID" id="MODAL_DOC_ID" required class="border rounded-lg w-full px-2 py-2">
          <option value="">Select Doctor</option>
        </select>
      </div>

      <div>
        <label class="font-semibold">Date</label>
        <input type="date" name="APPT_DATE" id="MODAL_APPT_DATE" required class="border rounded-lg w-full px-2 py-2" min="<?= date('Y-m-d') ?>">
      </div>

      <div>
        <label class="font-semibold">Time</label>
        <select name="APPT_TIME" id="MODAL_APPT_TIME" required class="border rounded-lg w-full px-2 py-2">
          <option value="">-- Choose Time --</option>
        </select>
      </div>

      <div class="col-span-2 flex justify-end gap-3 mt-6">
        <button type="button" onclick="closeModal()" class="bg-gray-300 px-6 py-2 rounded-lg">Cancel</button>
        <button type="submit" class="bg-[var(--primary)] text-white px-6 py-2 rounded-lg">Save</button>
      </div>
    </form>
  </div>
</div>

<!-- ✅ FOOTER LINK -->
  <?php include dirname(__DIR__, 2) . "/partials/footer.php"; ?>

<!-- ✅ SCRIPT -->
<script>
const modal = document.getElementById('apptModal');
const modalForm = document.getElementById('modalForm');

// ✅ Get CSS variable for primary color
const primaryColor = getComputedStyle(document.documentElement).getPropertyValue('--primary').trim() || '#1976d2';

function openModal() {
  modalForm.reset();
  document.getElementById('MODAL_DOC_ID').innerHTML = '<option value="">Select Doctor</option>';
  document.getElementById('MODAL_APPT_TIME').innerHTML = '<option value="">-- Choose Time --</option>';
  document.getElementById('APPT_ID').value = '';
  modal.classList.remove('hidden'); modal.classList.add('flex');
}
function closeModal() { modal.classList.add('hidden'); }

// ✅ Load doctors for selected service
async function loadDoctorsForService(servId) {
  const docSelect = document.getElementById('MODAL_DOC_ID');
  docSelect.innerHTML = '<option>Loading...</option>';
  try {
    const res = await fetch('../../ajax/get_doctors_by_service.php?serv_id=' + encodeURIComponent(servId));
    const data = await res.json();
    docSelect.innerHTML = '<option value="">-- Choose Doctor --</option>';
    data.forEach(doc => {
      const opt = document.createElement('option');
      opt.value = doc.DOC_ID;
      opt.textContent = `${doc.DOC_FIRST_NAME} ${doc.DOC_LAST_NAME}`;
      docSelect.appendChild(opt);
    });
  } catch (err) {
    docSelect.innerHTML = '<option value="">Error loading doctors</option>';
  }
}

// ✅ Load available times for selected doctor and date
async function loadAvailableTimesForDoctorDate(docId, date) {
  const timeSelect = document.getElementById('MODAL_APPT_TIME');
  timeSelect.innerHTML = '<option>Loading...</option>';
  if (!docId || !date) {
    timeSelect.innerHTML = '<option value="">-- Choose Time --</option>';
    return;
  }
  try {
    const res = await fetch(`../../ajax/get_available_times.php?doc_id=${encodeURIComponent(docId)}&date=${encodeURIComponent(date)}`);
    const data = await res.json();
    timeSelect.innerHTML = '<option value="">-- Choose Time --</option>';
    data.forEach(slot => {
      const opt = document.createElement('option');
      opt.value = slot.time;
      opt.textContent = slot.endTime ? `${slot.time} - ${slot.endTime}` : slot.time;
      timeSelect.appendChild(opt);
    });
  } catch (err) {
    timeSelect.innerHTML = '<option value="">Error loading times</option>';
  }
}

// ✅ Event listeners for dynamic selects
document.getElementById('MODAL_SERV_ID').addEventListener('change', function() {
  if (!this.value) return;
  loadDoctorsForService(this.value);
});

document.getElementById('MODAL_DOC_ID').addEventListener('change', function() {
  loadAvailableTimesForDoctorDate(this.value, document.getElementById('MODAL_APPT_DATE').value);
});
document.getElementById('MODAL_APPT_DATE').addEventListener('change', function() {
  loadAvailableTimesForDoctorDate(document.getElementById('MODAL_DOC_ID').value, this.value);
});

// ✅ FORM SUBMIT WITH SWEETALERT
modalForm.onsubmit = async e => {
  e.preventDefault();
  const data = new FormData(modalForm);
  
  try {
    const res = await fetch(location.pathname, {
      method: 'POST',
      body: data,
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    });
    const json = await res.json();
    
    if (json.success) {
      await Swal.fire({
        icon: 'success',
        title: 'Success!',
        text: json.message,
        confirmButtonColor: primaryColor,
        timer: 2000,
        showConfirmButton: true
      });
      closeModal();
      location.reload();
    } else {
      Swal.fire({
        icon: 'error',
        title: 'Oops...',
        text: json.message,
        confirmButtonColor: primaryColor
      });
    }
  } catch (err) {
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'Something went wrong!',
      confirmButtonColor: primaryColor
    });
  }
};

// ✅ DELETE WITH SWEETALERT
async function deleteAppt(id) {
  const result = await Swal.fire({
    title: 'Are you sure?',
    text: "You won't be able to revert this!",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#c0392b',
    cancelButtonColor: '#95a5a6',
    confirmButtonText: 'Yes, delete it!',
    cancelButtonText: 'Cancel'
  });

  if (!result.isConfirmed) return;

  try {
    const res = await fetch(location.pathname + '?delete=' + encodeURIComponent(id));
    const json = await res.json();
    
    if (json.success) {
      await Swal.fire({
        icon: 'success',
        title: 'Deleted!',
        text: json.message,
        confirmButtonColor: primaryColor,
        showConfirmButton: true
      });
      location.reload();
    } else {
      Swal.fire({
        icon: 'error',
        title: 'Failed',
        text: json.message || 'Delete operation failed',
        confirmButtonColor: primaryColor
      });
    }
  } catch (err) {
    console.error('Delete error:', err);
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'Failed to delete appointment: ' + err.message,
      confirmButtonColor: primaryColor
    });
  }
}

// ✅ EDIT APPOINTMENT
async function editAppt(a) {
  openModal();
  document.getElementById('APPT_ID').value = a.APPT_ID || '';
  document.getElementById('MODAL_PAT_ID').value = a.PAT_ID || '';
  document.getElementById('MODAL_SERV_ID').value = a.SERV_ID || '';
  if (a.SERV_ID) await loadDoctorsForService(a.SERV_ID);
  if (a.DOC_ID) document.getElementById('MODAL_DOC_ID').value = a.DOC_ID;
  if (a.APPT_DATE) document.getElementById('MODAL_APPT_DATE').value = a.APPT_DATE;
  if (a.DOC_ID && a.APPT_DATE) {
    await loadAvailableTimesForDoctorDate(a.DOC_ID, a.APPT_DATE);
    const timeSelect = document.getElementById('MODAL_APPT_TIME');
    if (![...timeSelect.options].some(o => o.value === a.APPT_TIME)) {
      const opt = document.createElement('option');
      opt.value = a.APPT_TIME;
      opt.textContent = a.APPT_TIME + ' (current)';
      timeSelect.appendChild(opt);
    }
    timeSelect.value = a.APPT_TIME;
  }
}

// ✅ MARK AS COMPLETED WITH SWEETALERT
async function markAsCompleted(id) {
  const result = await Swal.fire({
    title: 'Mark as Completed?',
    text: "This appointment will be marked as completed",
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#27ae60',
    cancelButtonColor: '#95a5a6',
    confirmButtonText: 'Yes, complete it!',
    cancelButtonText: 'Cancel'
  });

  if (!result.isConfirmed) return;

  const formData = new FormData();
  formData.append("appt_id", id);
  formData.append("status", "Completed");

  try {
    const res = await fetch('../../ajax/update_appointment_status.php', {
      method: 'POST',
      body: formData
    });
    const text = await res.text();
    
    await Swal.fire({
      icon: 'success',
      title: 'Completed!',
      text: text,
      confirmButtonColor: primaryColor,
      timer: 2000,
      showConfirmButton: true
    });
    location.reload();
  } catch (err) {
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'Failed to update appointment status',
      confirmButtonColor: primaryColor
    });
  }
}

/* ✅ FILTER + SEARCH */
document.addEventListener('DOMContentLoaded', function() {
  const filterButtons = document.querySelectorAll('.filter-btn');
  const rows = document.querySelectorAll('#apptBody tr');
  const searchInput = document.getElementById('searchInput');
  let currentFilter = 'all';

  function applyFilters() {
    const now = new Date();
    const today = now.getFullYear() + '-' + 
                  String(now.getMonth() + 1).padStart(2, '0') + '-' +
                  String(now.getDate()).padStart(2, '0');

    const term = searchInput.value.toLowerCase();

    rows.forEach(row => {
      const status = row.dataset.status;
      const date = row.dataset.date;
      const patient = row.dataset.patient;
      const doctor = row.dataset.doctor;
      const service = row.dataset.service;

      let show = true;

      if (currentFilter === 'today') {
        show = (date === today);
      } 
      else if (currentFilter === 'upcoming') {
        show = (date > today && !['completed','cancelled','missed'].includes(status));
      } 
      else if (currentFilter === 'missed') {
        show = (status === 'missed');
      }
      else if (currentFilter !== 'all') {
        show = (status === currentFilter);
      }

      // SEARCH
      const combined = (patient + ' ' + doctor + ' ' + service).toLowerCase();
      const matchesSearch = combined.includes(term);

      if (!matchesSearch) show = false;

      row.style.display = show ? '' : 'none';
    });
  }

  // Filter button clicks
  filterButtons.forEach(btn => {
    btn.addEventListener('click', () => {
      filterButtons.forEach(b => b.classList.remove('bg-[var(--primary)]', 'text-white'));
      btn.classList.add('bg-[var(--primary)]', 'text-white');
      currentFilter = btn.dataset.filter;
      applyFilters();
    });
  });

  // Live search
  if (searchInput) searchInput.addEventListener('input', applyFilters);

  // Initialize (show all)
  document.querySelector('.filter-btn[data-filter="all"]')?.click();
});
</script>
</body>
</html>