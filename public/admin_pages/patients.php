<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
// ---------- 1. AUTH CHECK ----------
if (
    empty($_SESSION['USER_IS_SUPERADMIN']) ||
    $_SESSION['USER_IS_SUPERADMIN'] != 1 ||
    $_SESSION['role'] !== 'admin'
) {
   header("Location: ../../index.php");
    exit;
}
require_once dirname(__DIR__, 2) . '/classes/Patient.php';

$patientObj = new Patient();
$loggedRole = $_SESSION['role'] ?? "";

if ($loggedRole !== "admin" && $loggedRole !== "superadmin") {
    header("Location: ../index.php");
    exit;
}

/* ---------- AJAX INSERT / UPDATE ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json');

    $id = $_POST['PAT_ID'] ?? "";
    $data = [
        'first'   => trim($_POST['PAT_FIRST_NAME'] ?? ''),
        'middle'  => trim($_POST['PAT_MIDDLE_INIT'] ?? ''),
        'last'    => trim($_POST['PAT_LAST_NAME'] ?? ''),
        'dob'     => trim($_POST['PAT_DOB'] ?? ''),
        'gender'  => trim($_POST['PAT_GENDER'] ?? ''),
        'contact' => trim($_POST['PAT_CONTACT_NUM'] ?? ''),
        'email'   => trim($_POST['PAT_EMAIL'] ?? ''),
        'address' => trim($_POST['PAT_ADDRESS'] ?? '')
    ];

    // Server-side validation
    $errors = [];
    if ($data['first'] === '') $errors[] = 'First name required';
    if ($data['last'] === '')  $errors[] = 'Last name required';
    if ($data['dob'] === '')   $errors[] = 'Date of birth required';
    if ($data['gender'] === '') $errors[] = 'Gender required';
    if ($data['contact'] === '') $errors[] = 'Contact required';
    if ($data['email'] === '' || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email required';
    if ($data['address'] === '') $errors[] = 'Address required';

    if (!empty($errors)) {
        echo json_encode(['success' => false, 'errors' => $errors]);
        exit;
    }

    try {
        if ($id === "") {
            $newId = $patientObj->insertPatient($data);
            echo json_encode(['success' => (bool)$newId, 'message' => $newId ? 'Patient added' : 'Insert failed']);
        } else {
            $ok = $patientObj->updatePatient($id, $data);
            echo json_encode(['success' => (bool)$ok, 'message' => $ok ? 'Patient updated' : 'Update failed']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

/* ---------- AJAX DELETE ---------- */
if (isset($_GET['delete']) && isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json');
    $id = $_GET['delete'];

    try {
        $ok = $patientObj->deletePatient($id);
        echo json_encode(['success' => (bool)$ok, 'message' => $ok ? 'Patient deleted' : 'Delete failed']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Page Data
$search = trim($_GET['q'] ?? '');
$patients = $search ? $patientObj->searchPatients($search) : $patientObj->getAllPatients();

function esc($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin — Patient Management</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="/Booking-System-For-Medical-Clinics/assets/css/style.css">
</head>
<body>

<div class="navbar">
    <div class="navbar-brand">
        <img src="https://cdn-icons-png.flaticon.com/512/3209/3209999.png">Medicina
    </div>
    <div class="nav-links">
        <a href="/Booking-System-For-Medical-Clinics/public/admin_dashboard.php">Dashboard</a>
        <a class="active" href="patients_admin.php">Patients</a>
        <a href="/Booking-System-For-Medical-Clinics/index.php">Logout</a>
    </div>
</div>

<main>
<h2>Patient Management (Admin)</h2>

<div class="top-controls">
<form method="get" style="display:flex; gap:10px;">
    <input name="q" placeholder="Search..." value="<?=esc($search)?>">
    <button class="btn" type="submit">Search</button>
    <?php if (!empty($search)): ?>
        <a href="patients.php" class="btn" style="background:#777;">Reset</a>
    <?php endif; ?>
</form>

<button class="create-btn" onclick="openAddModal()">+ Add Patient</button>
</div>

<div class="table-container">
<table>
<thead>
<tr>
    <th>ID</th><th>Name</th><th>DOB</th><th>Gender</th><th>Contact</th><th>Email</th><th>Address</th><th>Actions</th>
</tr>
</thead>
<tbody>
<?php if(empty($patients)): ?>
<tr><td colspan="8" style="text-align:center;">No results</td></tr>
<?php else: ?>
<?php foreach($patients as $p):
$mid = trim($p['PAT_MIDDLE_INIT'] ?? '');
$midDot = $mid !== '' ? esc($mid) . '. ' : '';
$name = esc($p['PAT_FIRST_NAME']).' '.$midDot.esc($p['PAT_LAST_NAME']);
?>
<tr>
<td><?= esc($p['PAT_ID']) ?></td>
<td><?= $name ?></td>
<td><?= esc($p['PAT_DOB']) ?></td>
<td><?= esc($p['PAT_GENDER']) ?></td>
<td><?= esc($p['PAT_CONTACT_NUM']) ?></td>
<td><?= esc($p['PAT_EMAIL']) ?></td>
<td><?= esc($p['PAT_ADDRESS']) ?></td>
<td>
<button class="btn" onclick='openEditModal(<?= json_encode($p) ?>)'>Edit</button>
<button class="btn" onclick="deletePatient('<?= esc($p['PAT_ID']) ?>')">Delete</button>
</td>
</tr>
<?php endforeach; endif; ?>
</tbody>
</table>
</div>
</main>

<!-- Modal -->
<div class="modal" id="patientModal">
<div class="modal-content">
<span class="close-btn" onclick="closeModal()">&times;</span>
<h2 id="modalTitle"></h2>

<form id="patientForm">
<input type="hidden" name="PAT_ID" id="PAT_ID">

<label>First Name</label>
<input type="text" id="PAT_FIRST_NAME" name="PAT_FIRST_NAME">

<label>Middle Initial</label>
<input type="text" id="PAT_MIDDLE_INIT" name="PAT_MIDDLE_INIT">

<label>Last Name</label>
<input type="text" id="PAT_LAST_NAME" name="PAT_LAST_NAME">

<label>Date of Birth</label>
<input type="date" id="PAT_DOB" name="PAT_DOB">

<label>Gender</label>
<select id="PAT_GENDER" name="PAT_GENDER">
    <option value="">Select</option>
    <option value="Male">Male</option>
    <option value="Female">Female</option>
</select>

<label>Contact</label>
<input type="text" id="PAT_CONTACT_NUM" name="PAT_CONTACT_NUM">

<label>Email</label>
<input type="email" id="PAT_EMAIL" name="PAT_EMAIL">

<label>Address</label>
<input type="text" id="PAT_ADDRESS" name="PAT_ADDRESS">

<button class="btn" type="submit">Save</button>
</form>

</div>
</div>

<script>
function showModal(){ document.getElementById('patientModal').style.display = 'flex'; }
function closeModal(){ document.getElementById('patientModal').style.display = 'none'; }

function openAddModal(){
    document.getElementById('modalTitle').innerText = "Add Patient";
    document.getElementById('patientForm').reset();
    document.getElementById('PAT_ID').value = "";
    showModal();
}

function openEditModal(p){
    document.getElementById('modalTitle').innerText = "Edit Patient";
    for(const key in p){
        if(document.getElementById(key)){
            document.getElementById(key).value = p[key];
        }
    }
    showModal();
}

// SUBMIT FORM
document.getElementById('patientForm').addEventListener('submit', async (e)=>{
    e.preventDefault();
    const form = new FormData(e.target);
    const res = await fetch(location.pathname,{
        method:"POST",
        headers:{ "X-Requested-With":"XMLHttpRequest" },
        body: form
    });
    const json = await res.json();
    if(json.errors){
        alert(json.errors.join("\n"));
        return;
    }
    alert(json.message);
    if(json.success){
        closeModal();
        location.reload();
    }
});

// DELETE
async function deletePatient(id){
    if(!confirm("Delete this patient?")) return;
    const res = await fetch(`?delete=${id}`,{
        headers:{ "X-Requested-With":"XMLHttpRequest" }
    });
    const json = await res.json();
    alert(json.message);
    if(json.success) location.reload();
}
</script>

</body>
</html>
