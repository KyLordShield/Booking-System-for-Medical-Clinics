<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

/* ---------- AUTH: only admin/superadmin ---------- */
if (
    empty($_SESSION['USER_IS_SUPERADMIN']) ||
    $_SESSION['USER_IS_SUPERADMIN'] != 1 ||
    $_SESSION['role'] !== 'admin'
) {
   header("Location: ../../index.php");
    exit;
}

require_once dirname(__DIR__, 2) . '/classes/Medical_Records.php';
require_once dirname(__DIR__, 2) . '/config/Database.php';

$db = new Database();
$conn = $db->connect();
$mr = new MedicalRecord();

/* ---------- AJAX HANDLERS (add/edit) ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json; charset=utf-8');

    $id = trim($_POST['MED_REC_ID'] ?? '');
    $data = [
        'diagnosis'    => trim($_POST['MED_REC_DIAGNOSIS'] ?? ''),
        'prescription' => trim($_POST['MED_REC_PRESCRIPTION'] ?? ''),
        'visit_date'   => trim($_POST['MED_REC_VISIT_DATE'] ?? ''),
        'appt_id'      => trim($_POST['APPT_ID'] ?? '')
    ];

    $errors = [];
    if ($data['appt_id'] === '') $errors[] = 'Appointment is required.';
    if ($data['diagnosis'] === '') $errors[] = 'Diagnosis is required.';
    if ($data['visit_date'] === '') $errors[] = 'Visit date is required.';

    if (!empty($errors)) {
        echo json_encode(['success'=>false,'errors'=>$errors]);
        exit;
    }

    try {
        // Check for duplicate if adding new
        if ($id === '') {
            $chkRec = $conn->prepare("SELECT MED_REC_ID FROM medical_record WHERE APPT_ID = ? LIMIT 1");
            $chkRec->execute([$data['appt_id']]);
            if ($chkRec->fetch()) {
                echo json_encode(['success'=>false,'message'=>'This appointment already has a medical record.']);
                exit;
            }
        }

        $ok = $id === '' ? $mr->add($data) : $mr->update($id, $data);
        echo json_encode([
            'success' => (bool)$ok,
            'message' => $ok ? ($id ? 'Record updated successfully!' : 'Record added successfully!') : 'Operation failed.'
        ]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success'=>false,'message'=>'DB error: '.$e->getMessage()]);
        exit;
    }
}

/* ---------- AJAX DELETE ---------- */
if (isset($_GET['delete']) && isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json; charset=utf-8');
    $id = intval($_GET['delete'] ?? 0);
    $ok = $id > 0 ? $mr->delete($id) : false;
    echo json_encode(['success'=>$ok,'message'=>$ok?'Record deleted successfully!':'Delete failed.']);
    exit;
}

/* ---------- FETCH ALL RECORDS (search support) ---------- */
$search = trim($_GET['q'] ?? '');
$records = [];
try {
    if ($search) {
        $stmt = $conn->prepare("
            SELECT mr.*, 
                   COALESCE(CONCAT(p.PAT_FIRST_NAME, ' ', p.PAT_LAST_NAME), CONCAT('Appt#', a.APPT_ID)) AS patient_name
            FROM medical_record mr
            JOIN appointment a ON mr.APPT_ID = a.APPT_ID
            LEFT JOIN patient p ON a.PAT_ID = p.PAT_ID
            WHERE (mr.MED_REC_DIAGNOSIS LIKE :s 
               OR mr.MED_REC_PRESCRIPTION LIKE :s 
               OR CONCAT(p.PAT_FIRST_NAME, ' ', p.PAT_LAST_NAME) LIKE :s)
            ORDER BY mr.MED_REC_VISIT_DATE DESC, mr.MED_REC_CREATED_AT DESC
        ");
        $stmt->execute([':s' => "%$search%"]);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $records = $mr->getAll();
    }
} catch (Exception $e) {
    $records = [];
}

/* ---------- Appointment options for ADD (without medical records) ---------- */
$apptOptionsAdd = [];
try {
    $stmt = $conn->prepare("
        SELECT 
            a.APPT_ID,
            a.APPT_DATE,
            CONCAT(p.PAT_FIRST_NAME, ' ', p.PAT_LAST_NAME) AS patient_name,
            CONCAT(d.DOC_FIRST_NAME, ' ', d.DOC_LAST_NAME) AS doctor_name
        FROM appointment a
        LEFT JOIN patient p ON a.PAT_ID = p.PAT_ID
        LEFT JOIN doctor d ON a.DOC_ID = d.DOC_ID
        LEFT JOIN medical_record mr ON mr.APPT_ID = a.APPT_ID
        WHERE mr.MED_REC_ID IS NULL
        ORDER BY a.APPT_DATE DESC
    ");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($rows as $r) {
        $label = "Appt#" . $r['APPT_ID'];
        if (!empty($r['patient_name'])) {
            $label .= " — " . $r['patient_name'];
        }
        if (!empty($r['doctor_name'])) {
            $label .= " (Dr. " . $r['doctor_name'] . ")";
        }
        if (!empty($r['APPT_DATE'])) {
            $label .= " [" . $r['APPT_DATE'] . "]";
        }
        $apptOptionsAdd[] = [
            'APPT_ID' => $r['APPT_ID'],
            'APPT_DATE' => $r['APPT_DATE'],
            'label' => $label
        ];
    }
} catch (Exception $e) {}

/* ---------- All appointments for EDIT ---------- */
$apptOptionsEdit = [];
try {
    $stmt = $conn->prepare("
        SELECT 
            a.APPT_ID,
            a.APPT_DATE,
            CONCAT(p.PAT_FIRST_NAME, ' ', p.PAT_LAST_NAME) AS patient_name,
            CONCAT(d.DOC_FIRST_NAME, ' ', d.DOC_LAST_NAME) AS doctor_name
        FROM appointment a
        LEFT JOIN patient p ON a.PAT_ID = p.PAT_ID
        LEFT JOIN doctor d ON a.DOC_ID = d.DOC_ID
        ORDER BY a.APPT_DATE DESC
    ");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($rows as $r) {
        $label = "Appt#" . $r['APPT_ID'];
        if (!empty($r['patient_name'])) {
            $label .= " — " . $r['patient_name'];
        }
        if (!empty($r['doctor_name'])) {
            $label .= " (Dr. " . $r['doctor_name'] . ")";
        }
        if (!empty($r['APPT_DATE'])) {
            $label .= " [" . $r['APPT_DATE'] . "]";
        }
        $apptOptionsEdit[] = [
            'APPT_ID' => $r['APPT_ID'],
            'APPT_DATE' => $r['APPT_DATE'],
            'label' => $label
        ];
    }
} catch (Exception $e) {}

function esc($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Medical Records | Admin | Medicina</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="/Booking-System-For-Medical-Clinics/assets/css/style.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
    .modal { display: none; justify-content: center; align-items: center; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,.5); z-index: 1000; }
    .modal-content { background: #fff; padding: 20px; border-radius: 8px; max-width: 420px; width: 90%; position: relative; }
    .close-btn { position: absolute; top: 8px; right: 12px; font-size: 24px; cursor: pointer; }
</style>
</head>
<body class="bg-[var(--secondary)] min-h-screen flex flex-col">

<?php include dirname(__DIR__, 2) . "/partials/header.php"; ?>

<main class="flex-1 p-10">
  <h2 class="text-[38px] font-bold text-[var(--primary)] mb-4">Medical Records (Admin)</h2>

  <!-- Search + Add -->
  <div class="top-controls flex gap-4 items-center mb-4">
    <form method="get" class="flex gap-2 items-center">
      <input name="q" placeholder="Search diagnosis or prescription" value="<?= esc($search) ?>" class="px-3 py-2 border rounded-lg">
      <button class="btn bg-[var(--primary)] text-white px-3 py-2 rounded-lg">Search</button>
      <?php if (!empty($search)): ?>
        <a href="admin_medical_records.php" class="btn" style="background:#777;">Reset</a>
      <?php endif; ?>
    </form>

    <div class="ml-auto small-muted">Showing all records</div>
    <button class="create-btn ml-4" onclick="openAddModal()">+ Add Record</button>
  </div>

  <!-- Table -->
  <div class="table-container bg-[var(--light)] p-6 rounded-[25px] shadow-md">
    <table class="w-full text-[var(--primary)]">
      <thead>
        <tr class="border-b border-gray-300">
          <th class="py-3 px-3">#</th>
          <th class="py-3 px-3">Patient</th>
          <th class="py-3 px-3">Diagnosis</th>
          <th class="py-3 px-3">Prescription</th>
          <th class="py-3 px-3">Visit Date</th>
          <th class="py-3 px-3">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($records)): ?>
          <tr><td colspan="6" class="py-6 text-center opacity-70">No records found.</td></tr>
        <?php else: foreach ($records as $r): ?>
          <tr class="border-b hover:bg-gray-50">
            <td class="py-3 px-3">#<?= esc($r['MED_REC_ID']) ?></td>
            <td class="py-3 px-3"><?= esc($r['patient_name'] ?? ('Appt#'.($r['APPT_ID'] ?? ''))) ?></td>
            <td class="py-3 px-3"><?= esc($r['MED_REC_DIAGNOSIS']) ?></td>
            <td class="py-3 px-3"><?= esc($r['MED_REC_PRESCRIPTION']) ?></td>
            <td class="py-3 px-3"><?= esc($r['MED_REC_VISIT_DATE']) ?></td>
            <td class="py-3 px-3">
              <div class="table-actions">
                <button class="btn" onclick='openViewModal(<?= json_encode($r, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>View</button>
                <button class="btn" onclick='openEditModal(<?= json_encode($r, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>Edit</button>
                <button class="btn" style="background:#a30000" onclick="deleteRecord(<?= (int)$r['MED_REC_ID'] ?>)">Delete</button>
              </div>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</main>

<!-- Modal -->
<div class="modal" id="recordModal">
  <div class="modal-content">
    <span class="close-btn" onclick="closeModal()">&times;</span>
    <h2 id="modalTitle">Add Medical Record</h2>

    <form id="recordForm">
      <input type="hidden" id="MED_REC_ID" name="MED_REC_ID">

      <label>Appointment</label>
      <select id="APPT_ID" name="APPT_ID" required>
        <option value="">-- Select Appointment --</option>
        <?php if (empty($apptOptionsAdd)): ?>
          <option value="" disabled>No available appointments</option>
        <?php else: ?>
          <?php foreach ($apptOptionsAdd as $opt): ?>
            <option 
              value="<?= esc($opt['APPT_ID']) ?>"
              data-date="<?= esc($opt['APPT_DATE']) ?>"
            >
              <?= esc($opt['label']) ?>
            </option>
          <?php endforeach; ?>
        <?php endif; ?>
      </select>

      <label>Diagnosis</label>
      <textarea id="MED_REC_DIAGNOSIS" name="MED_REC_DIAGNOSIS" rows="3" required></textarea>

      <label>Prescription</label>
      <textarea id="MED_REC_PRESCRIPTION" name="MED_REC_PRESCRIPTION" rows="2"></textarea>

      <label>Visit Date</label>
      <input type="date" id="MED_REC_VISIT_DATE" name="MED_REC_VISIT_DATE" required>

      <div style="margin-top:12px;">
        <button type="button" class="btn-outline" onclick="closeModal()">Cancel</button>
        <button type="submit" class="btn" id="saveRecordBtn">Save</button>
      </div>
    </form>
  </div>
</div>

<?php include dirname(__DIR__, 2) . "/partials/footer.php"; ?>

<script>
const recordModal = document.getElementById('recordModal');
const recordForm = document.getElementById('recordForm');
const apptSelect = document.getElementById('APPT_ID');

// Store both sets of options
const apptOptionsAdd = <?= json_encode($apptOptionsAdd) ?>;
const apptOptionsEdit = <?= json_encode($apptOptionsEdit) ?>;

function showModal() { recordModal.style.display = 'flex'; }
function closeModal() { recordModal.style.display = 'none'; }

function populateApptSelect(options, selectedId = null) {
  apptSelect.innerHTML = '<option value="">-- Select Appointment --</option>';
  
  if (options.length === 0) {
    apptSelect.innerHTML += '<option value="" disabled>No appointments available</option>';
  } else {
    options.forEach(opt => {
      const option = document.createElement('option');
      option.value = opt.APPT_ID;
      option.textContent = opt.label;
      option.setAttribute('data-date', opt.APPT_DATE);
      if (selectedId && opt.APPT_ID == selectedId) {
        option.selected = true;
      }
      apptSelect.appendChild(option);
    });
  }
}

function setFormDisabled(disabled) {
  document.querySelectorAll('#recordForm input, #recordForm textarea, #recordForm select').forEach(el => {
    el.disabled = disabled;
  });
  document.getElementById('saveRecordBtn').style.display = disabled ? 'none' : 'inline-block';
}

function fillForm(d) {
  document.getElementById('MED_REC_ID').value = d.MED_REC_ID || '';
  document.getElementById('APPT_ID').value = d.APPT_ID || '';
  document.getElementById('MED_REC_DIAGNOSIS').value = d.MED_REC_DIAGNOSIS || '';
  document.getElementById('MED_REC_PRESCRIPTION').value = d.MED_REC_PRESCRIPTION || '';
  document.getElementById('MED_REC_VISIT_DATE').value = d.MED_REC_VISIT_DATE || '';
}

function openAddModal() {
  document.getElementById('modalTitle').innerText = 'Add Medical Record';
  recordForm.reset();
  document.getElementById('MED_REC_ID').value = '';
  
  // Use appointments without records
  populateApptSelect(apptOptionsAdd);
  
  setFormDisabled(false);
  
  if (apptOptionsAdd.length === 0) {
    Swal.fire({
      icon: 'info',
      title: 'No Appointments Available',
      text: 'All appointments already have medical records.',
      confirmButtonText: 'OK'
    });
    return;
  }
  
  showModal();
}

function openEditModal(d) {
  document.getElementById('modalTitle').innerText = 'Edit Medical Record';
  
  // Use all appointments for edit
  populateApptSelect(apptOptionsEdit, d.APPT_ID);
  
  fillForm(d);
  setFormDisabled(false);
  showModal();
}

function openViewModal(d) {
  document.getElementById('modalTitle').innerText = 'View Medical Record';
  
  // Use all appointments for view
  populateApptSelect(apptOptionsEdit, d.APPT_ID);
  
  fillForm(d);
  setFormDisabled(true);
  showModal();
}

// Auto-fill visit date when appointment is selected
apptSelect.addEventListener('change', function() {
  const selected = this.options[this.selectedIndex];
  const date = selected.getAttribute('data-date');
  if (date) {
    const dateOnly = date.split(' ')[0];
    document.getElementById('MED_REC_VISIT_DATE').value = dateOnly;
  } else {
    document.getElementById('MED_REC_VISIT_DATE').value = '';
  }
});

recordForm.addEventListener('submit', async function(e) {
  e.preventDefault();
  const btn = document.getElementById('saveRecordBtn');
  btn.disabled = true;
  btn.textContent = 'Saving...';
  
  try {
    const res = await fetch(location.pathname, {
      method: 'POST',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      body: new FormData(this)
    });
    const json = await res.json();
    
    if (!json.success) {
      if (json.errors) {
        Swal.fire({
          icon: 'error',
          title: 'Validation Error',
          html: json.errors.join("<br>")
        });
      } else {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: json.message || 'An error occurred'
        });
      }
      btn.disabled = false;
      btn.textContent = 'Save';
      return;
    }
    
    Swal.fire({
      icon: 'success',
      title: 'Success',
      text: json.message || 'Medical record saved successfully!'
    }).then(() => {
      closeModal();
      location.reload();
    });
  } catch (err) {
    console.error(err);
    Swal.fire({
      icon: 'error',
      title: 'Network Error',
      text: 'Could not connect to server'
    });
    btn.disabled = false;
    btn.textContent = 'Save';
  }
});

async function deleteRecord(id) {
  const result = await Swal.fire({
    title: 'Are you sure?',
    text: "Delete this medical record?",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Yes, delete it!',
    cancelButtonText: 'Cancel'
  });
  
  if (!result.isConfirmed) return;
  
  try {
    const res = await fetch(location.pathname + '?delete=' + id, {
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    });
    const json = await res.json();
    
    Swal.fire({
      icon: json.success ? 'success' : 'error',
      title: json.success ? 'Deleted!' : 'Error',
      text: json.message || 'Done'
    }).then(() => {
      if (json.success) location.reload();
    });
  } catch (err) {
    console.error(err);
    Swal.fire({
      icon: 'error',
      title: 'Network Error',
      text: 'Could not connect to server'
    });
  }
}

// Close on backdrop click
recordModal.addEventListener('click', function(e) {
  if (e.target === recordModal) closeModal();
});
</script>
</body>
</html>