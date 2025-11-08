<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once dirname(__DIR__, 2) . '/classes/Doctor.php';
require_once dirname(__DIR__, 2) . '/classes/Specialization.php';

$doctorObj = new Doctor();
$specObj = new Specialization();

$loggedRole = $_SESSION['role'] ?? "";

if ($loggedRole !== "admin" && $loggedRole !== "superadmin") {
    header("Location: ../index.php");
    exit;
}

/* ---------- AJAX INSERT / UPDATE ---------- */
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

    // Server validation
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

    try {
        if ($id === "") {
            $ok = $doctorObj->insert($data);
            echo json_encode(['success' => (bool)$ok, 'message' => $ok ? 'Doctor added' : 'Insert failed']);
        } else {
            $ok = $doctorObj->update($id, $data);
            echo json_encode(['success' => (bool)$ok, 'message' => $ok ? 'Doctor updated' : 'Update failed']);
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
        $ok = $doctorObj->delete($id);
        echo json_encode(['success' => (bool)$ok, 'message' => $ok ? 'Doctor deleted' : 'Delete failed']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Page Data
$specializations = $specObj->getAll();
$search = trim($_GET['q'] ?? '');
$doctors = $doctorObj->getAll(null, $search);

function esc($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin — Doctor Management</title>
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
        <a class="active" href="doctors.php">Doctors</a>
        <a href="admin_specialization.php">Specializations</a>
        <a href="/Booking-System-For-Medical-Clinics/index.php">Logout</a>
    </div>
</div>

<main>
<h2>Doctor Management (Admin)</h2>

<div class="top-controls">
<form method="get" style="display:flex; gap:10px;">
    <input name="q" placeholder="Search..." value="<?=esc($search)?>">
    <button class="btn" type="submit">Search</button>
    
    <?php if (!empty($search)): ?>
        <a href="doctors.php" class="btn" style="background:#777;">Reset</a>
    <?php endif; ?>
</form>


<button class="create-btn" onclick="openAddModal()">+ Add Doctor</button>
</div>

<div class="table-container">
<table>
<thead>
<tr>
    <th>ID</th><th>Name</th><th>Spec</th><th>Contact</th><th>Email</th><th>Actions</th>
</tr>
</thead>
<tbody>
<?php if(empty($doctors)): ?>
<tr><td colspan="6" style="text-align:center;">No results</td></tr>
<?php else: ?>
<?php foreach($doctors as $d):
$mid = trim($d['DOC_MIDDLE_INIT'] ?? '');
$midDot = $mid !== '' ? esc($mid) . '. ' : '';
$name = esc($d['DOC_FIRST_NAME']).' '.$midDot.esc($d['DOC_LAST_NAME']);
?>
<tr>
<td><?= esc($d['DOC_ID']) ?></td>
<td><?= $name ?></td>
<td><?= esc($d['SPEC_NAME'] ?? '') ?></td>
<td><?= esc($d['DOC_CONTACT_NUM']) ?></td>
<td><?= esc($d['DOC_EMAIL']) ?></td>
<td>
<button class="btn" onclick='openEditModal(<?= json_encode($d) ?>)'>Edit</button>
<button class="btn" onclick="deleteDoc('<?= esc($d['DOC_ID']) ?>')">Delete</button>
</td>
</tr>
<?php endforeach; endif; ?>
</tbody>
</table>
</div>
</main>

<!-- Modal -->
<div class="modal" id="doctorModal">
<div class="modal-content">
<span class="close-btn" onclick="closeModal()">&times;</span>
<h2 id="modalTitle"></h2>

<form id="doctorForm">
<input type="hidden" name="DOC_ID" id="DOC_ID">

<label>First Name</label>
<input type="text" id="DOC_FIRST_NAME" name="DOC_FIRST_NAME">

<label>Middle Initial</label>
<input type="text" id="DOC_MIDDLE_INIT" name="DOC_MIDDLE_INIT">

<label>Last Name</label>
<input type="text" id="DOC_LAST_NAME" name="DOC_LAST_NAME">

<label>Email</label>
<input type="email" id="DOC_EMAIL" name="DOC_EMAIL">

<label>Contact</label>
<input type="text" id="DOC_CONTACT_NUM" name="DOC_CONTACT_NUM">

<label>Specialization</label>
<select id="SPEC_ID" name="SPEC_ID">
<?php foreach($specializations as $sp): ?>
<option value="<?= esc($sp['SPEC_ID']) ?>"><?= esc($sp['SPEC_NAME']) ?></option>
<?php endforeach; ?>
</select>

<button class="btn" type="submit">Save</button>
</form>

</div>
</div>

<script>
function showModal(){ document.getElementById('doctorModal').style.display = 'flex'; }
function closeModal(){ document.getElementById('doctorModal').style.display = 'none'; }

function openAddModal(){
    document.getElementById('modalTitle').innerText = "Add Doctor";
    document.getElementById('doctorForm').reset();
    document.getElementById('DOC_ID').value = "";
    showModal();
}

function openEditModal(d){
    document.getElementById('modalTitle').innerText = "Edit Doctor";
    for (const key in d) {
        if (document.getElementById(key)) {
            document.getElementById(key).value = d[key];
        }
    }
    showModal();
}

// SUBMIT FORM
document.getElementById('doctorForm').addEventListener('submit', async (e)=>{
    e.preventDefault();
    const form = new FormData(e.target);
    const res = await fetch(location.pathname,{
        method:"POST",
        headers:{ "X-Requested-With":"XMLHttpRequest" },
        body: form
    });
    const json = await res.json();
    alert(json.message);
    if(json.success){
        closeModal();
        location.reload();
    }
});

// DELETE
async function deleteDoc(id){
    if(!confirm("Delete this doctor?")) return;
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
