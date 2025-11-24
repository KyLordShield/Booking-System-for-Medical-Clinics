<?php
session_start();
require_once __DIR__ . '/../classes/Patient.php';
require_once __DIR__ . '/../classes/Appointment.php';

// Verify patient session
if (!isset($_SESSION['PAT_ID']) || $_SESSION['role'] !== 'patient') {
    header("Location: ../index.php");
    exit;
}

$pat_id = $_SESSION['PAT_ID'];

$patientObj = new Patient();
$appointmentObj = new Appointment();

$message = "";
$messageType = "";

// Handle update form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_info'])) {
    $fname   = trim($_POST['PAT_FIRST_NAME']);
    $mname   = trim($_POST['PAT_MIDDLE_INIT']);
    $lname   = trim($_POST['PAT_LAST_NAME']);
    $dob     = trim($_POST['PAT_DOB']);
    $gender  = trim($_POST['PAT_GENDER']);
    $contact = trim($_POST['PAT_CONTACT_NUM']);
    $email   = trim($_POST['PAT_EMAIL']);
    $address = trim($_POST['PAT_ADDRESS']);

    $message = $patientObj->updatePatient($pat_id, $fname, $mname, $lname, $dob, $gender, $contact, $email, $address);
    $messageType = (strpos($message, 'success') !== false) ? 'success' : 'error';
    $patient = $patientObj->getPatientById($pat_id);
} else {
    $patient = $patientObj->getPatientById($pat_id);
}

// Fetch appointments
$appointments = $appointmentObj->getAppointmentsByPatient($pat_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Patient Dashboard | Medicina</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Use existing global CSS (your palette and rules) -->
    <link rel="stylesheet" href="../assets/css/style.css">
    
    <!-- ✅ SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <style>
      /* Small page-specific tweaks to enhance corporate feel without changing your palette */
      .dashboard-wrapper { max-width: 1200px; margin: 0 auto; }
      .top-row { display:flex; gap:30px; align-items:center; justify-content:space-between; }
      .profile-summary { display:flex; gap:24px; align-items:center; }
      .profile-avatar { width:120px; height:120px; border-radius:18px; background:var(--white); display:flex; align-items:center; justify-content:center; box-shadow:0 6px 18px rgba(0,0,0,0.08); }
      .profile-avatar img{ width:88px; }
      .welcome { font-size:20px; color:var(--primary); }
      .patient-name { font-size:28px; font-weight:700; color:var(--primary); }
      .patient-meta { color:#214b58; font-weight:600; }
      .patient-actions { display:flex; gap:12px; margin-top:12px; }
      .muted { color:#315a68; }
      .status-pill { padding:6px 10px; border-radius:999px; font-weight:700; font-size:13px; }
      .status-pending { background:var(--light); color:var(--primary); }
      .table-actions .btn { padding:8px 12px; border-radius:18px; }
      .card-legend { display:flex; gap:12px; align-items:center; justify-content:flex-end; }
      .small { font-size:13px; color:#12323a; }
    </style>
</head>
<body>

  <!-- Header (keeps your partial so server-side nav works) -->
  <?php include dirname(__DIR__) . "/partials/header.php"; ?>

  <main class="dashboard-wrapper" style="padding:40px 20px 80px;">

    <!-- TOP ROW: Profile + Quick Actions -->
    <section class="top-row mb-8">
      <div class="profile-summary">
        <div class="profile-avatar">
          <img src="https://cdn-icons-png.flaticon.com/512/3177/3177440.png" alt="Patient Avatar">

        </div>
        <div>
          <div class="welcome">Welcome back,</div>
          <div class="patient-name"><?= htmlspecialchars(($patient['PAT_FIRST_NAME'] ?? '') . ' ' . ($patient['PAT_LAST_NAME'] ?? '')) ?></div>
          <div class="muted">Patient ID: <strong class="patient-meta"><?= htmlspecialchars($pat_id) ?></strong></div>

          <div class="patient-actions">
            <button id="openUpdate" class="btn" aria-haspopup="dialog">Update Profile</button>
            <a href="patient_pages/create_appointment.php" class="btn ">Create Appointment</a>
          </div>
        </div>
      </div>

      <!-- Cards: quick stats -->
      <div class="dashboard-cards" style="max-width:520px; width:100%;">
        <div class="card">
  <div class="small">Active/Missed Appointments</div>
 <h2>
<?php
$activeCount = 0;
if (is_array($appointments)) {
    foreach ($appointments as $a) {
        if (isset($a['STAT_NAME']) && !in_array($a['STAT_NAME'], ['Cancelled','Completed'])) {
            $activeCount++;
        }
    }
}
echo $activeCount;
?>
</h2>
  <p class="small">Appointments that require your attention</p>
</div>
        <div class="card">
          <div class="small">Total Appointments</div>
          <h2><?= count($appointments) ?></h2>
          <p class="small">All-time scheduled appointments</p>
        </div>
      </div>
    </section>

    <!-- PATIENT DETAILS (read-only) -->
    <section class="table-container mb-8">
      <h2 style="margin-bottom:18px;">My Profile</h2>
      <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
        <div>
          <p><strong>Email</strong></p>
          <p class="muted"><?= htmlspecialchars($patient['PAT_EMAIL'] ?? '') ?></p>
        </div>
        <div>
          <p><strong>Contact</strong></p>
          <p class="muted"><?= htmlspecialchars($patient['PAT_CONTACT_NUM'] ?? '') ?></p>
        </div>
        <div>
          <p><strong>Address</strong></p>
          <p class="muted"><?= htmlspecialchars($patient['PAT_ADDRESS'] ?? '') ?></p>
        </div>
        <div>
          <p><strong>Gender</strong></p>
          <p class="muted"><?= htmlspecialchars($patient['PAT_GENDER'] ?? '') ?></p>
        </div>
        <div>
          <p><strong>Date of Birth</strong></p>
          <p class="muted"><?= htmlspecialchars($patient['PAT_DOB'] ?? '') ?></p>
        </div>
      </div>
    </section>

    <!-- APPOINTMENTS -->
    <section>
      <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:12px;">
        <h2>Your Appointments</h2>
        <div class="card-legend">
          <span class="status-pill status-pending">Pending</span>
        </div>
      </div>

      <?php if (!empty($appointments)): ?>
        <div class="table-container">
          <table aria-describedby="appointments-desc">
            <thead>
              <tr>
                <th>ID</th>
                <th>Date</th>
                <th>Time</th>
                <th>Service</th>
                <th>Doctor</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($appointments as $appt): ?>
                <tr data-doc-id="<?= $appt['DOC_ID'] ?>">
                  <td><?= htmlspecialchars($appt['APPT_ID']) ?></td>
                  <td><?= htmlspecialchars($appt['APPT_DATE']) ?></td>
                  <td><?= htmlspecialchars($appt['APPT_TIME']) ?></td>
                  <td><?= htmlspecialchars($appt['SERV_NAME'] ?? 'N/A') ?></td>
                  <td><?= htmlspecialchars($appt['DOCTOR_NAME'] ?? 'N/A') ?></td>
                  <td><?= htmlspecialchars($appt['STAT_NAME'] ?? 'Pending') ?></td>
                  <td class="table-actions">
                    <button class="btn appt-update">Reschedule</button>
                    <button class="btn appt-cancel" data-appt-id="<?= $appt['APPT_ID'] ?>" <?= in_array($appt['STAT_NAME'], ['Completed', 'Cancelled']) ? 'disabled style="opacity:0.5;cursor:not-allowed;"' : '' ?>>Cancel</button>
                  </td>
                </tr>
              <?php endforeach; ?>
              <tr>
                <td colspan="7" style="text-align:center; padding:18px; color:#12323a;">End of appointment list.</td>
              </tr>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="table-container">
          <p class="muted">You currently have no appointments. Use the Create Appointment button to schedule one.</p>
        </div>
      <?php endif; ?>
    </section>

  </main>

  <!-- Footer (keeps your partial) -->
  <?php include dirname(__DIR__) . "/partials/footer.php"; ?>

  <!-- UPDATE PROFILE MODAL (keeps the same form names so backend remains unchanged) -->
  <div id="updateModal" class="modal" aria-hidden="true">
    <div class="modal-content" role="dialog" aria-modal="true" aria-labelledby="updateTitle">
      <span class="close-btn" id="closeModal">×</span>
      <h2 id="updateTitle">Update Profile</h2>
      <form method="post" action="" id="updateForm">
        <label>First Name
          <input type="text" name="PAT_FIRST_NAME" value="<?= htmlspecialchars($patient['PAT_FIRST_NAME'] ?? '') ?>" required>
        </label>
        <label>Middle Initial
          <input type="text" name="PAT_MIDDLE_INIT" value="<?= htmlspecialchars($patient['PAT_MIDDLE_INIT'] ?? '') ?>">
        </label>
        <label>Last Name
          <input type="text" name="PAT_LAST_NAME" value="<?= htmlspecialchars($patient['PAT_LAST_NAME'] ?? '') ?>" required>
        </label>
        <label>Email
          <input type="email" name="PAT_EMAIL" value="<?= htmlspecialchars($patient['PAT_EMAIL'] ?? '') ?>" required>
        </label>
        <label>Contact Number
          <input type="text" name="PAT_CONTACT_NUM" value="<?= htmlspecialchars($patient['PAT_CONTACT_NUM'] ?? '') ?>" required>
        </label>
        <label>Address
          <input type="text" name="PAT_ADDRESS" value="<?= htmlspecialchars($patient['PAT_ADDRESS'] ?? '') ?>" required>
        </label>
        <label>Gender
          <select name="PAT_GENDER" required>
            <option value="Male" <?= ($patient['PAT_GENDER'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
            <option value="Female" <?= ($patient['PAT_GENDER'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
          </select>
        </label>
        <label>Date of Birth
          <input type="date" name="PAT_DOB" value="<?= htmlspecialchars($patient['PAT_DOB'] ?? '') ?>" required>
        </label>

        <button type="submit" name="update_info" class="create-btn">Save Changes</button>
      </form>
    </div>
  </div>

<script>
// ✅ Get primary color from CSS variable
const primaryColor = getComputedStyle(document.documentElement).getPropertyValue('--primary').trim() || '#002339';

// ✅ Show SweetAlert if there's a message from PHP
<?php if ($message): ?>
Swal.fire({
  icon: '<?= $messageType === "success" ? "success" : "error" ?>',
  title: '<?= $messageType === "success" ? "Profile Updated!" : "Update Failed" ?>',
  text: '<?= addslashes($message) ?>',
  confirmButtonColor: primaryColor
});
<?php endif; ?>

// Modal controls
const openBtn = document.getElementById('openUpdate');
const modal = document.getElementById('updateModal');
const closeModalBtn = document.getElementById('closeModal');
openBtn && openBtn.addEventListener('click', () => { modal.style.display = 'flex'; modal.setAttribute('aria-hidden', 'false'); });
closeModalBtn && closeModalBtn.addEventListener('click', () => { modal.style.display = 'none'; modal.setAttribute('aria-hidden', 'true'); });
window.addEventListener('click', (e) => { if (e.target === modal) { modal.style.display = 'none'; modal.setAttribute('aria-hidden', 'true'); } });

// Disable actions for completed/cancelled rows on load
document.querySelectorAll('tbody tr').forEach(row => {
  const statusCell = row.querySelector('td:nth-child(6)');
  const cancelBtn = row.querySelector('.appt-cancel');
  const updateBtn = row.querySelector('.appt-update');
  if (statusCell && cancelBtn && updateBtn) {
    const status = statusCell.textContent.trim();
    if (status === 'Cancelled' || status === 'Completed') {
      cancelBtn.disabled = true; updateBtn.disabled = true;
      cancelBtn.style.opacity = 0.5; cancelBtn.style.cursor = 'not-allowed';
      updateBtn.style.opacity = 0.5; updateBtn.style.cursor = 'not-allowed';
    }
  }
});

// ✅ Cancel appointment with SweetAlert
document.querySelectorAll('.appt-cancel').forEach(btn => {
  btn.addEventListener('click', async function() {
    const row = btn.closest('tr');
    const apptId = btn.getAttribute('data-appt-id');
    
    const result = await Swal.fire({
      title: 'Cancel Appointment?',
      text: `Are you sure you want to cancel appointment ID ${apptId}?`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#c0392b',
      cancelButtonColor: '#95a5a6',
      confirmButtonText: 'Yes, cancel it!',
      cancelButtonText: 'No, keep it'
    });

    if (!result.isConfirmed) return;

    try {
      const res = await fetch('../ajax/cancel_appointment.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'APPT_ID=' + encodeURIComponent(apptId)
      });
      const msg = await res.text();
      
      await Swal.fire({
        icon: 'success',
        title: 'Cancelled!',
        text: msg,
        confirmButtonColor: primaryColor,
        timer: 2000
      });
      
      const statusCell = row.querySelector('td:nth-child(6)');
      if (statusCell) statusCell.textContent = 'Cancelled';
      btn.disabled = true; btn.style.opacity = 0.5; btn.style.cursor = 'not-allowed';
      const updateBtn = row.querySelector('.appt-update'); 
      if (updateBtn) { updateBtn.disabled = true; updateBtn.style.opacity = 0.5; updateBtn.style.cursor = 'not-allowed'; }
    } catch(err) {
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: 'Error cancelling appointment.',
        confirmButtonColor: primaryColor
      });
    }
  });
});

// ✅ Reschedule flow with SweetAlert
document.querySelectorAll('.appt-update').forEach(btn => {
  btn.addEventListener('click', function() {
    if (btn.disabled) return;
    const row = btn.closest('tr');
    document.querySelectorAll('.reschedule-row').forEach(r => r.remove());
    const apptId = row.querySelector('td').textContent.trim();
    const docId = row.dataset.docId;

    const resRow = document.createElement('tr');
    resRow.className = 'reschedule-row';
    resRow.innerHTML = `\
      <td colspan="7">\
        <form class="reschedule-form" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">\
          <label class="small">New Date:<input type="date" name="APPT_DATE" min="<?= date('Y-m-d') ?>" required></label>\
          <label class="small">New Time:<select name="APPT_TIME" required><option value="">-- Choose --</option></select></label>\
          <button type="submit" class="btn">Save</button>\
          <button type="button" class="btn" id="cancelRes">Cancel</button>\
        </form>\
      </td>`;

    row.insertAdjacentElement('afterend', resRow);
    const form = resRow.querySelector('.reschedule-form');
    const dateInput = form.querySelector('input[name="APPT_DATE"]');
    const timeSelect = form.querySelector('select[name="APPT_TIME"]');

    dateInput.addEventListener('change', () => {
      if (!dateInput.value) return;
      timeSelect.innerHTML = '<option>Loading...</option>';
      fetch(`../ajax/get_available_times.php?doc_id=${docId}&date=${dateInput.value}`)
        .then(res => res.json())
        .then(times => {
          timeSelect.innerHTML = '<option value="">-- Choose --</option>';
          if (times.length === 0) timeSelect.innerHTML = '<option disabled>No times available</option>';
          times.forEach(slot => { const opt=document.createElement('option'); opt.value=slot.time; opt.textContent = slot.time + ' - ' + (slot.endTime||''); timeSelect.appendChild(opt); });
        })
        .catch(() => timeSelect.innerHTML = '<option disabled>Error loading times</option>');
    });

    resRow.querySelector('#cancelRes').addEventListener('click', () => resRow.remove());

    form.addEventListener('submit', async e => {
      e.preventDefault();
      const newDate = dateInput.value; 
      const newTime = timeSelect.value;
      
      if (!newDate || !newTime) {
        Swal.fire({
          icon: 'warning',
          title: 'Missing Information',
          text: 'Please select new date and time.',
          confirmButtonColor: primaryColor
        });
        return;
      }
      
      const result = await Swal.fire({
        title: 'Confirm Reschedule',
        text: `Reschedule to ${newDate} at ${newTime}?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: primaryColor,
        cancelButtonColor: '#95a5a6',
        confirmButtonText: 'Yes, reschedule it!',
        cancelButtonText: 'Cancel'
      });

      if (!result.isConfirmed) return;

      try {
        const res = await fetch('../ajax/reschedule_appointment.php', {
          method: 'POST',
          headers: {'Content-Type':'application/x-www-form-urlencoded'},
          body: `APPT_ID=${apptId}&APPT_DATE=${newDate}&APPT_TIME=${newTime}`
        });
        const msg = await res.text();
        
        await Swal.fire({
          icon: 'success',
          title: 'Rescheduled!',
          text: msg,
          confirmButtonColor: primaryColor,
          timer: 2000
        });
        location.reload();
      } catch(err) {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'Error updating appointment.',
          confirmButtonColor: primaryColor
        });
      }
    });
  });
});
</script>

</body>
</html>