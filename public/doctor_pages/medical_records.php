<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// AUTH CHECK ONLY DOCTORS ALLOWED
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'doctor') {
    header("Location: ../../index.php");
    exit;
}

/*
  medical_records.php
  - Uses classes/MedicalRecord.php
  - Robustly resolves DOC_ID from session (tries several fallbacks)
  - AJAX add/update/delete with ownership checks
  - UPDATED: Only shows today's appointments without medical records in Add modal
*/

require_once dirname(__DIR__, 2) . '/classes/Medical_Records.php';
require_once dirname(__DIR__, 2) . '/config/Database.php';

$db = new Database();
$conn = $db->connect();
$mr = new MedicalRecord();

/* -------------------------
   Resolve logged-in doctor ID
   ------------------------- */
$loggedDocId = null;

// 1) Preferred: direct session value (most common)
if (!empty($_SESSION['DOC_ID'])) {
    $loggedDocId = intval($_SESSION['DOC_ID']);
}

// 2) If not present, try common session keys (user_id / id)
if (!$loggedDocId) {
    $candidateKeys = ['user_id','USER_ID','id','ID','UID','userID','email','EMAIL'];
    foreach ($candidateKeys as $k) {
        if (!empty($_SESSION[$k])) {
            // If it's an integer user id, try to find a doctor record by a USER_ID column
            if (is_numeric($_SESSION[$k])) {
                $userId = intval($_SESSION[$k]);
                // try lookup: doctor.USER_ID = ?
                try {
                    $stmt = $conn->prepare("SELECT DOC_ID FROM doctor WHERE USER_ID = ? LIMIT 1");
                    $stmt->execute([$userId]);
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    if (!empty($row['DOC_ID'])) {
                        $loggedDocId = intval($row['DOC_ID']);
                        break;
                    }
                } catch (Exception $e) {
                    // table/column may not exist; continue
                }
            }

            // If the session key looks like email, attempt email lookup
            if (filter_var($_SESSION[$k], FILTER_VALIDATE_EMAIL)) {
                $email = trim($_SESSION[$k]);
                try {
                    // try finding doctor by email column (DOC_EMAIL)
                    $stmt = $conn->prepare("SELECT DOC_ID FROM doctor WHERE DOC_EMAIL = ? LIMIT 1");
                    $stmt->execute([$email]);
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    if (!empty($row['DOC_ID'])) {
                        $loggedDocId = intval($row['DOC_ID']);
                        break;
                    }
                } catch (Exception $e) {
                    // continue
                }
            }
        }
    }
}

// 3) If still not found, try to infer via a 'users' table join (best-effort)
if (!$loggedDocId) {
    try {
        // if session has username/email, try to find user then doctor
        $possibleEmail = $_SESSION['email'] ?? $_SESSION['EMAIL'] ?? null;
        if ($possibleEmail && filter_var($possibleEmail, FILTER_VALIDATE_EMAIL)) {
            // try user -> doctor mapping (many projects have users.id -> doctor.USER_ID)
            // attempt to find doctor by DOC_EMAIL first (already tried), then try joining users->doctor if tables exist
            // Attempt join query; ignore failures
            $stmt = $conn->prepare("
                SELECT d.DOC_ID
                FROM doctor d
                LEFT JOIN users u ON (u.id = d.USER_ID OR u.email = d.DOC_EMAIL)
                WHERE (u.email = :em OR d.DOC_EMAIL = :em) LIMIT 1
            ");
            $stmt->execute([':em' => $possibleEmail]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!empty($row['DOC_ID'])) $loggedDocId = intval($row['DOC_ID']);
        }
    } catch (Exception $e) {
        // ignore and continue
    }
}

// Final guard: if no DOC_ID resolved, show clear message and exit
if (!$loggedDocId) {
    // Helpful instruction for your team: set $_SESSION['DOC_ID'] on login
    echo "<!doctype html><html><head><meta charset='utf-8'><title>No Doctor Session</title></head><body style='font-family:Arial,sans-serif;padding:30px;'>
          <h2>Cannot determine logged-in doctor</h2>
          <p>Please ensure your login code sets <code>\$_SESSION['DOC_ID']</code> to the doctor's ID after login.</p>
          <p>Alternatively, tell me how your login session stores user info (session keys), and I'll adapt this page to use that.</p>
          </body></html>";
    exit;
}

/* -------------- At this point $loggedDocId is set -------------- */

/* ---------- Server handlers (AJAX) ---------- */

// POST add/update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json; charset=utf-8');

    $id = trim($_POST['MED_REC_ID'] ?? '');
    $data = [
        'diagnosis'    => trim($_POST['MED_REC_DIAGNOSIS'] ?? ''),
        'prescription' => trim($_POST['MED_REC_PRESCRIPTION'] ?? ''),
        'visit_date'   => trim($_POST['MED_REC_VISIT_DATE'] ?? ''),
        'appt_id'      => trim($_POST['APPT_ID'] ?? '')
    ];

    // basic validation
    $errors = [];
    if ($data['appt_id'] === '') $errors[] = 'Appointment is required.';
    if ($data['diagnosis'] === '') $errors[] = 'Diagnosis is required.';
    if ($data['visit_date'] === '') $errors[] = 'Visit date is required.';
    if (!empty($errors)) {
        echo json_encode(['success'=>false,'errors'=>$errors]);
        exit;
    }

    // verify appointment belongs to the doctor (if appointment table exists)
    try {
        $chk = $conn->prepare("SELECT DOC_ID FROM appointment WHERE APPT_ID = ? LIMIT 1");
        $chk->execute([$data['appt_id']]);
        $owner = $chk->fetchColumn();
        if ($owner === false) {
            // appointment not found — block to be safe
            echo json_encode(['success'=>false,'message'=>'Appointment not found.']);
            exit;
        }
        if (intval($owner) !== intval($loggedDocId)) {
            echo json_encode(['success'=>false,'message'=>'Selected appointment does not belong to you.']);
            exit;
        }
    } catch (Exception $e) {
        echo json_encode(['success'=>false,'message'=>'Unable to validate appointment ownership (DB mismatch).']);
        exit;
    }

    try {
        if ($id === '') {
            // Check if this appointment already has a medical record
            $chkRec = $conn->prepare("SELECT MED_REC_ID FROM medical_record WHERE APPT_ID = ? LIMIT 1");
            $chkRec->execute([$data['appt_id']]);
            if ($chkRec->fetch()) {
                echo json_encode(['success'=>false,'message'=>'This appointment already has a medical record.']);
                exit;
            }

            // create via class
            $ok = $mr->add([
                'diagnosis'=>$data['diagnosis'],
                'prescription'=>$data['prescription'],
                'visit_date'=>$data['visit_date'],
                'appt_id'=>$data['appt_id']
            ]);
            echo json_encode(['success'=>(bool)$ok,'message'=>$ok ? 'Record added.' : 'Insert failed.']);
            exit;
        } else {
            // ownership verify & update
            if (!$mr->verifyOwnership($id, $loggedDocId)) {
                echo json_encode(['success'=>false,'message'=>'Not allowed to update this record.']);
                exit;
            }
            
            $ok = $mr->update($id, [
                'diagnosis'=>$data['diagnosis'],
                'prescription'=>$data['prescription'],
                'visit_date'=>$data['visit_date']
            ]);
            echo json_encode(['success'=>(bool)$ok,'message'=>$ok ? 'Record updated.' : 'Update failed.']);
            exit;
        }
    } catch (Exception $e) {
        echo json_encode(['success'=>false,'message'=>'DB error: '.$e->getMessage()]);
        exit;
    }
}

// DELETE (AJAX)
if (isset($_GET['delete']) && isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json; charset=utf-8');
    $id = intval($_GET['delete'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['success'=>false,'message'=>'Invalid ID']);
        exit;
    }
    
    // Verify ownership
    $ownerCheck = $conn->prepare("
        SELECT a.DOC_ID 
        FROM medical_record mr
        JOIN appointment a ON a.APPT_ID = mr.APPT_ID
        WHERE mr.MED_REC_ID = ?
    ");
    $ownerCheck->execute([$id]);
    $ownerDocId = $ownerCheck->fetchColumn();
    
    if (!$ownerDocId || intval($ownerDocId) !== intval($loggedDocId)) {
        echo json_encode(['success'=>false,'message'=>'Not allowed to delete this record.']);
        exit;
    }
    
    $deleteStmt = $conn->prepare("DELETE FROM medical_record WHERE MED_REC_ID = ?");
    $ok = $deleteStmt->execute([$id]);
    echo json_encode(['success'=>(bool)$ok,'message'=>$ok ? 'Record deleted.' : 'Delete failed.']);
    exit;
}

/* ---------- Fetch records for listing (search) ---------- */
$search = trim($_GET['q'] ?? '');
$records = $mr->getRecordsByDoctor($loggedDocId, $search);

/* ---------- Appointment options for ADD (ONLY TODAY'S WITHOUT MEDICAL RECORDS) ---------- */
$apptOptionsAdd = [];
try {
    $today = date('Y-m-d');
    
    $stmt = $conn->prepare("
        SELECT 
            a.APPT_ID,
            a.APPT_DATE,
            CONCAT(p.PAT_FIRST_NAME, ' ', p.PAT_LAST_NAME) AS patient_name
        FROM appointment a
        LEFT JOIN patient p ON p.PAT_ID = a.PAT_ID
        LEFT JOIN medical_record mr ON mr.APPT_ID = a.APPT_ID
        WHERE a.DOC_ID = ?
          AND a.APPT_DATE = ?
          AND mr.MED_REC_ID IS NULL
        ORDER BY a.APPT_DATE ASC
    ");
    $stmt->execute([$loggedDocId, $today]);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $r) {
        $label = "Appt#" . $r['APPT_ID'];
        if (!empty($r['patient_name'])) {
            $label .= " — " . $r['patient_name'];
        }
        $apptOptionsAdd[] = [
            'APPT_ID' => $r['APPT_ID'],
            'APPT_DATE' => $r['APPT_DATE'],
            'label'   => $label
        ];
    }
} catch (Exception $e) {
    // leave empty
}

/* ---------- Appointment options for EDIT (ALL DOCTOR'S APPOINTMENTS) ---------- */
$apptOptionsEdit = [];
try {
    $stmt = $conn->prepare("
        SELECT 
            a.APPT_ID,
            a.APPT_DATE,
            CONCAT(p.PAT_FIRST_NAME, ' ', p.PAT_LAST_NAME) AS patient_name
        FROM appointment a
        LEFT JOIN patient p ON p.PAT_ID = a.PAT_ID
        WHERE a.DOC_ID = ?
        ORDER BY a.APPT_DATE DESC
    ");
    $stmt->execute([$loggedDocId]);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $r) {
        $label = "Appt#" . $r['APPT_ID'];
        if (!empty($r['patient_name'])) {
            $label .= " — " . $r['patient_name'];
        }
        $label .= " (" . $r['APPT_DATE'] . ")";
        $apptOptionsEdit[] = [
            'APPT_ID' => $r['APPT_ID'],
            'APPT_DATE' => $r['APPT_DATE'],
            'label'   => $label
        ];
    }
} catch (Exception $e) {
    // leave empty
}


/* ---------- Helpers ---------- */
function esc($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Medical Records | Medicina</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="../../assets/css/style.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-[var(--secondary)] min-h-screen flex flex-col">

<!-- NAVBAR -->
<?php include dirname(__DIR__, 2) . "/partials/header.php"; ?>

<main class="flex-1 p-10">
  <h2 class="text-[38px] font-bold text-[var(--primary)] mb-4">Medical Records</h2>

  <div class="top-controls flex gap-4 items-center mb-4">
    <form method="get" class="flex gap-2 items-center">
      <input name="q" placeholder="Search diagnosis or prescription" value="<?= esc($search) ?>" class="px-3 py-2 border rounded-lg">
      <button class="btn bg-[var(--primary)] text-white px-3 py-2 rounded-lg">Search</button>
    </form>

    <div class="ml-auto small-muted">Showing records for doctor #<?= esc($loggedDocId) ?></div>
    <button class="create-btn ml-4" onclick="openAddModal()">+ Add Record</button>
  </div>

  <div class="table-container bg-[var(--light)] p-6 rounded-[25px] shadow-md">
    <table class="w-full text-[var(--primary)]">
      <thead>
        <tr class="border-b border-gray-300">
          <th class="py-3 px-3">Record</th>
          <th class="py-3 px-3">Appointment</th>
          <th class="py-3 px-3">Diagnosis</th>
          <th class="py-3 px-3">Prescription</th>
          <th class="py-3 px-3">Visit</th>
          <th class="py-3 px-3">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($records)): ?>
          <tr><td colspan="6" class="py-6 text-center opacity-70">No records found.</td></tr>
        <?php else: foreach ($records as $r): ?>
          <tr class="border-b hover:bg-gray-50">
            <td class="py-3 px-3">#<?= esc($r['MED_REC_ID']) ?></td>
            <td class="py-3 px-3"><?= esc($r['patient_name'] ?? ('Appt#'.($r['APPT_ID'] ?? ''))) ?></td>
            <td class="py-3 px-3">
              <?= esc(strlen($r['MED_REC_DIAGNOSIS']) > 50 ? substr($r['MED_REC_DIAGNOSIS'], 0, 50) . '...' : $r['MED_REC_DIAGNOSIS']) ?> </td>
            <td class="py-3 px-3">
              <?= esc(strlen($r['MED_REC_PRESCRIPTION']) > 50 ? substr($r['MED_REC_PRESCRIPTION'], 0, 50) . '...' : $r['MED_REC_PRESCRIPTION']) ?> </td>
            <td class="py-3 px-3"><?= esc($r['MED_REC_VISIT_DATE']) ?></td>
            <td class="py-3 px-3">
              <div class="table-actions">
                <button class="btn" onclick='openViewModal(<?= json_encode($r, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>View</button>
                <button class="btn" onclick='openEditModal(<?= json_encode($r, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>Edit</button>
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

      <label>Appointment (Today's appointments only)</label>
      <select id="APPT_ID" name="APPT_ID" required>
        <option value="">-- Select Appointment --</option>
        <?php if (empty($apptOptionsAdd)): ?>
          <option value="" disabled>No available appointments for today</option>
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
      <input type="date" id="MED_REC_VISIT_DATE" name="MED_REC_VISIT_DATE" required readonly>

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

function showModal(){ recordModal.style.display = 'flex'; }
function closeModal(){ recordModal.style.display = 'none'; }

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

function openAddModal(){
  document.getElementById('modalTitle').innerText = 'Add Medical Record';
  recordForm.reset();
  document.getElementById('MED_REC_ID').value = '';
  
  // Use today's appointments only
  populateApptSelect(apptOptionsAdd);
  
  // enable inputs
  setFormDisabled(false);
  
  // Check if there are any appointments available
  if (apptOptionsAdd.length === 0) {
    Swal.fire({
      icon: 'info',
      title: 'No Appointments Available',
      text: 'There are no appointments scheduled for today that need medical records.',
      confirmButtonText: 'OK'
    });
    return;
  }
  
  showModal();
}

function openEditModal(data){
  document.getElementById('modalTitle').innerText = 'Edit Medical Record';
  
  // Use all appointments for edit
  populateApptSelect(apptOptionsEdit, data.APPT_ID);
  
  fillForm(data);
  setFormDisabled(false);
  showModal();
}

function openViewModal(data){
  document.getElementById('modalTitle').innerText = 'View Medical Record';
  
  // Use all appointments for view
  populateApptSelect(apptOptionsEdit, data.APPT_ID);
  
  fillForm(data);
  setFormDisabled(true);
  showModal();
}

function setFormDisabled(disabled){
  document.querySelectorAll('#recordForm input, #recordForm textarea, #recordForm select').forEach(el=>{
    el.disabled = disabled;
  });
  document.getElementById('saveRecordBtn').style.display = disabled ? 'none' : 'inline-block';
}

function fillForm(d){
  document.getElementById('MED_REC_ID').value = d.MED_REC_ID || '';
  document.getElementById('APPT_ID').value = d.APPT_ID || '';
  document.getElementById('MED_REC_DIAGNOSIS').value = d.MED_REC_DIAGNOSIS || '';
  document.getElementById('MED_REC_PRESCRIPTION').value = d.MED_REC_PRESCRIPTION || '';
  document.getElementById('MED_REC_VISIT_DATE').value = d.MED_REC_VISIT_DATE || '';
}

recordForm.addEventListener('submit', async function(e){
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
    
    if(!json.success){
      if(json.errors) {
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

async function deleteRecord(id){
  const result = await Swal.fire({
    title: 'Are you sure?',
    text: "Delete this medical record?",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Yes, delete it!',
    cancelButtonText: 'Cancel'
  });
  
  if(!result.isConfirmed) return;
  
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
      if(json.success) location.reload();
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

// Auto-fill visit date when appointment is selected
document.getElementById('APPT_ID').addEventListener('change', function() {
    const selected = this.options[this.selectedIndex];
    const date = selected.getAttribute('data-date');
    if (date) {
        // Extract just the date part (YYYY-MM-DD)
        const dateOnly = date.split(' ')[0];
        document.getElementById('MED_REC_VISIT_DATE').value = dateOnly;
    } else {
        document.getElementById('MED_REC_VISIT_DATE').value = '';
    }
});

// close on backdrop
recordModal.addEventListener('click', function(e){
  if (e.target === this) closeModal();
});
</script>
</body>
</html>