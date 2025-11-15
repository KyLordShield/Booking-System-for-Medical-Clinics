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
        $ok = $id === '' ? $mr->add($data) : $mr->update($id, $data);
        echo json_encode([
            'success' => (bool)$ok,
            'message' => $ok ? ($id ? 'Record updated.' : 'Record added.') : 'Operation failed.'
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
    echo json_encode(['success'=>$ok,'message'=>$ok?'Record deleted.':'Delete failed.']);
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

/* ---------- Appointment dropdown ---------- */
$apptOptions = [];
try {
    $stmt = $conn->query("SELECT APPT_ID FROM appointment ORDER BY APPT_ID DESC");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        $apptOptions[] = ['APPT_ID'=>$r['APPT_ID'], 'label'=>'Appt#'.$r['APPT_ID']];
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
</head>
<body class="bg-[var(--secondary)] min-h-screen flex flex-col">

<!-- NAVBAR -->
<!-- ✅ HEADER LINK -->
  <?php include dirname(__DIR__, 2) . "/partials/header.php"; ?>
<!-- ✅ HEADER LINK -->

<main class="flex-1 p-10">
  <h2 class="text-[38px] font-bold text-[var(--primary)] mb-4">Medical Records</h2>

  <!-- Search + Add -->
  <div class="top-controls flex gap-4 items-center mb-4">
    <form method="get" class="flex gap-2 items-center">
      <input name="q" placeholder="Search diagnosis or prescription" value="<?= esc($search) ?>" class="px-3 py-2 border rounded-lg">
      <button class="btn bg-[var(--primary)] text-white px-3 py-2 rounded-lg">Search</button>
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

<!-- Modal (same size as doctor’s) -->
<div class="modal" id="recordModal">
  <div class="modal-content">
    <span class="close-btn" onclick="closeModal()">&times;</span>
    <h2 id="modalTitle">Add Medical Record</h2>

    <form id="recordForm">
      <input type="hidden" id="MED_REC_ID" name="MED_REC_ID">

      <label>Appointment (APPT_ID)</label>
      <select id="APPT_ID" name="APPT_ID" required>
        <option value="">-- Select Appointment --</option>
        <?php foreach ($apptOptions as $opt): ?>
          <option value="<?= esc($opt['APPT_ID']) ?>"><?= esc($opt['label']) ?></option>
        <?php endforeach; ?>
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


<!-- ✅ FOOTER LINK -->
  <?php include dirname(__DIR__, 2) . "/partials/footer.php"; ?>


<script>
const recordModal=document.getElementById('recordModal');
const recordForm=document.getElementById('recordForm');

function showModal(){recordModal.style.display='flex';}
function closeModal(){recordModal.style.display='none';}
function setFormDisabled(disabled){
  document.querySelectorAll('#recordForm input, #recordForm textarea, #recordForm select').forEach(el=>el.disabled=disabled);
  document.getElementById('saveRecordBtn').style.display=disabled?'none':'inline-block';
}
function fillForm(d){
  document.getElementById('MED_REC_ID').value=d.MED_REC_ID||'';
  document.getElementById('APPT_ID').value=d.APPT_ID||'';
  document.getElementById('MED_REC_DIAGNOSIS').value=d.MED_REC_DIAGNOSIS||'';
  document.getElementById('MED_REC_PRESCRIPTION').value=d.MED_REC_PRESCRIPTION||'';
  document.getElementById('MED_REC_VISIT_DATE').value=d.MED_REC_VISIT_DATE||'';
}
function openAddModal(){
  document.getElementById('modalTitle').innerText='Add Medical Record';
  recordForm.reset(); setFormDisabled(false); showModal();
}
function openEditModal(d){
  document.getElementById('modalTitle').innerText='Edit Medical Record';
  fillForm(d); setFormDisabled(false); showModal();
}
function openViewModal(d){
  document.getElementById('modalTitle').innerText='View Medical Record';
  fillForm(d); setFormDisabled(true); showModal();
}

recordForm.addEventListener('submit',async e=>{
  e.preventDefault();
  const btn=document.getElementById('saveRecordBtn');
  btn.disabled=true; btn.textContent='Saving...';
  try{
    const res=await fetch(location.pathname,{method:'POST',headers:{'X-Requested-With':'XMLHttpRequest'},body:new FormData(recordForm)});
    const json=await res.json();
    if(!json.success){alert(json.errors?json.errors.join("\n"):json.message);btn.disabled=false;btn.textContent='Save';return;}
    alert(json.message||'Saved');closeModal();location.reload();
  }catch(err){console.error(err);alert('Network error');btn.disabled=false;btn.textContent='Save';}
});

async function deleteRecord(id){
  if(!confirm('Delete this medical record?'))return;
  const res=await fetch(location.pathname+'?delete='+id,{headers:{'X-Requested-With':'XMLHttpRequest'}});
  const json=await res.json();alert(json.message||'Done');
  if(json.success)location.reload();
}

recordModal.addEventListener('click',e=>{if(e.target===recordModal)closeModal();});
</script>
</body>
</html>
