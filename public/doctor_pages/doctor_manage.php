<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once dirname(__DIR__, 2) . '/classes/Doctor.php';
require_once dirname(__DIR__, 2) . '/classes/Specialization.php';

$doctorObj = new Doctor();
$specObj = new Specialization();

$loggedRole = $_SESSION['role'] ?? "";
$loggedDocId = $_SESSION['DOC_ID'] ?? null;

if ($loggedRole !== "doctor") {
    header("Location: ../index.php");
    exit;
}

/* ---------- AJAX INSERT / UPDATE (uses classes) ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json');

    $id = $_POST['DOC_ID'] ?? "";
    $data = [
        'first'   => trim($_POST['DOC_FIRST_NAME'] ?? ''),
        'middle'  => trim($_POST['DOC_MIDDLE_INIT'] ?? ''),
        'last'    => trim($_POST['DOC_LAST_NAME'] ?? ''),
        'contact' => trim($_POST['DOC_CONTACT_NUM'] ?? ''),
        'email'   => trim($_POST['DOC_EMAIL'] ?? ''),
        'spec'    => trim($_POST['SPEC_ID'] ?? '')
    ];

    // Validation (server-side)
    $errors = [];
    if ($data['first'] === '') $errors[] = 'First name required';
    if ($data['last'] === '')  $errors[] = 'Last name required';
    if ($data['contact'] === '') $errors[] = 'Contact required';
    if ($data['email'] === '' || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email required';
    if ($data['spec'] === '') $errors[] = 'Specialization required';

    if (!empty($errors)) {
        echo json_encode(['success' => false, 'errors' => $errors]);
        exit;
    }

    // Block editing others (doctors can only update themselves)
    if ($loggedRole === "doctor" && $id !== "" && $id != $loggedDocId) {
        echo json_encode(['success' => false, 'message' => 'Not allowed to update other doctors']);
        exit;
    }

    try {
        if ($id === "") {
            $ok = $doctorObj->insert($data);
            echo json_encode(['success' => (bool)$ok, 'message' => $ok ? 'Doctor added' : 'Insert failed']);
        } else {
            $ok = $doctorObj->update($id, $data);
            echo json_encode(['success' => (bool)$ok, 'message' => $ok ? 'Profile updated' : 'Update failed']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

/* ---------- AJAX DELETE BLOCKED FOR DOCTORS ---------- */
if (isset($_GET['delete']) && isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    // doctors cannot delete
    echo json_encode(['success' => false, 'message' => 'Doctors cannot delete accounts']);
    exit;
}

/* ---------- Data for page (use classes) ---------- */
$specializations = $specObj->getAll();

// Logged-in doctor's full record (includes SPEC_NAME if class returns it)
$myself = $doctorObj->getById($loggedDocId);

// Search param for other doctors
$search = trim($_GET['q'] ?? '');
// Get other doctors excluding self
$others = $doctorObj->getAll($loggedDocId, $search);

// helper
function esc($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Doctor Management</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="/Booking-System-For-Medical-Clinics/assets/css/style.css">
</head>
<body>

<!-- NAVBAR -->
<!-- ✅ HEADER LINK -->
  <?php include dirname(__DIR__, 2) . "/partials/header.php"; ?>
<!-- ✅ HEADER LINK -->

<main>
    <h2>My Profile</h2>

    <div class="table-container" style="margin-bottom:18px;">
        <table>
            <tr><th>ID</th><td><?= esc($myself['DOC_ID']) ?></td></tr>
            <tr>
                <th>Name</th>
                <td>
                    <?php
                        $mid = trim($myself['DOC_MIDDLE_INIT'] ?? '');
                        $midDot = $mid !== '' ? esc($mid) . '. ' : '';
                        echo esc($myself['DOC_FIRST_NAME'] ?? '') . ' ' . $midDot . esc($myself['DOC_LAST_NAME'] ?? '');
                    ?>
                </td>
            </tr>
            <tr><th>Email</th><td><?= esc($myself['DOC_EMAIL']) ?></td></tr>
            <tr><th>Contact</th><td><?= esc($myself['DOC_CONTACT_NUM']) ?></td></tr>
            <tr><th>Specialization</th><td><?= esc($myself['SPEC_NAME'] ?? $myself['SPEC_ID'] ?? '') ?></td></tr>
        </table>

        <br>
        <!-- Edit only own profile -->
        <button class="btn" onclick='openEditModal(<?= json_encode($myself, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>Update My Info</button>
    </div>

    <br><hr><br>

    <h2>Other Doctors</h2>

    <div class="top-controls" style="align-items:center; margin-bottom:12px;">
        <form method="get" style="display:flex; gap:8px; align-items:center;">
            <input name="q" placeholder="Search doctors by name, email, contact" value="<?=esc($search)?>">
            <button class="btn" type="submit">Search</button>
        </form>

        <button class="create-btn" onclick="openAddModal()">+ Add Doctor</button>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th><th>Name</th><th>Specialization</th><th>Contact</th><th>Email</th><th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($others)): ?>
                    <tr><td colspan="6" style="text-align:center;">No doctors found</td></tr>
                <?php else: ?>
                    <?php foreach($others as $d): 
                        $mid = trim($d['DOC_MIDDLE_INIT'] ?? '');
                        $midDot = $mid !== '' ? esc($mid) . '. ' : '';
                        $full = esc($d['DOC_FIRST_NAME']) . ' ' . $midDot . esc($d['DOC_LAST_NAME']);
                    ?>
                    <tr>
                        <td><?= esc($d['DOC_ID']) ?></td>
                        <td><?= $full ?></td>
                        <td><?= esc($d['SPEC_NAME'] ?? $d['SPEC_ID'] ?? '') ?></td>
                        <td><?= esc($d['DOC_CONTACT_NUM']) ?></td>
                        <td><?= esc($d['DOC_EMAIL']) ?></td>
                        <td>
                            <!-- view only for others -->
                            <button class="btn" onclick='openViewModal(<?= json_encode($d, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>View</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

<!-- MODAL (used for Add & Edit own profile; when viewing others we use view modal alert) -->
<!-- MODAL (Add / Edit / View) -->
<div class="modal" id="doctorModal" aria-hidden="true">
  <div class="modal-content" role="dialog" aria-labelledby="modalTitle">
    <span class="close-btn" onclick="closeModal()">&times;</span>
    <h2 id="modalTitle">Doctor Details</h2>

    <form id="doctorForm">
      <input type="hidden" id="DOC_ID" name="DOC_ID">

      <label>First Name</label>
      <input type="text" id="DOC_FIRST_NAME" name="DOC_FIRST_NAME" required>

      <label>Middle Init</label>
      <input type="text" id="DOC_MIDDLE_INIT" name="DOC_MIDDLE_INIT" maxlength="2">

      <label>Last Name</label>
      <input type="text" id="DOC_LAST_NAME" name="DOC_LAST_NAME" required>

      <label>Email</label>
      <input type="email" id="DOC_EMAIL" name="DOC_EMAIL" required>

      <label>Contact Number</label>
      <input type="text" id="DOC_CONTACT_NUM" name="DOC_CONTACT_NUM" required>

      <label>Specialization</label>
      <select id="SPEC_ID" name="SPEC_ID" required>
        <?php foreach($specializations as $sp): ?>
            <option value="<?= esc($sp['SPEC_ID']) ?>"><?= esc($sp['SPEC_NAME']) ?></option>
        <?php endforeach; ?>
      </select>

      <button type="submit" class="btn" id="saveButton">Save</button>
    </form>
  </div>
</div>



<!-- ✅ FOOTER LINK -->
  <?php include dirname(__DIR__, 2) . "/partials/footer.php"; ?>


<script>
function showModal(){ 
    document.getElementById('doctorModal').style.display = 'flex'; 
}
function closeModal(){ 
    document.getElementById('doctorModal').style.display = 'none'; 
}

// Helper to enable/disable form
function setFormDisabled(disabled) {
    document.querySelectorAll('#doctorForm input, #doctorForm select').forEach(el => {
        el.disabled = disabled;
    });
    document.getElementById('saveButton').style.display = disabled ? 'none' : 'block';
}

function openAddModal(){
    document.getElementById('modalTitle').innerText = 'Add Doctor';
    document.getElementById('doctorForm').reset();
    document.getElementById('DOC_ID').value = "";

    setFormDisabled(false);
    showModal();
}

function openEditModal(data){
    document.getElementById('modalTitle').innerText = 'Edit My Profile';
    fillForm(data);

    setFormDisabled(false);
    showModal();
}

function openViewModal(data){
    document.getElementById('modalTitle').innerText = 'View Doctor Details';
    fillForm(data);

    setFormDisabled(true);
    showModal();
}

function fillForm(d){
    document.getElementById('DOC_ID').value = d.DOC_ID || '';
    document.getElementById('DOC_FIRST_NAME').value = d.DOC_FIRST_NAME || '';
    document.getElementById('DOC_MIDDLE_INIT').value = d.DOC_MIDDLE_INIT || '';
    document.getElementById('DOC_LAST_NAME').value = d.DOC_LAST_NAME || '';
    document.getElementById('DOC_EMAIL').value = d.DOC_EMAIL || '';
    document.getElementById('DOC_CONTACT_NUM').value = d.DOC_CONTACT_NUM || '';
    document.getElementById('SPEC_ID').value  = d.SPEC_ID || '';
}

// Submit Add / Update
document.getElementById('doctorForm').addEventListener('submit', async function(e){
    e.preventDefault();
    const form = new FormData(this);

    const res = await fetch(location.pathname, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: form
    });

    const json = await res.json();
    alert(json.message);

    if(json.success){
        closeModal();
        location.reload();
    }
});

// Click backdrop to close
document.getElementById('doctorModal').addEventListener('click', function(e){
    if(e.target === this) closeModal();
});

</script>

</body>
</html>
