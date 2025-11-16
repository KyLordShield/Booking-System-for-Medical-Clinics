<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once dirname(__DIR__, 2) . '/config/Database.php';
$db = new Database();
$conn = $db->connect();

$loggedRole = $_SESSION['role'] ?? "";
$loggedDocId = $_SESSION['DOC_ID'] ?? null;

if ($loggedRole !== "doctor") {
    header("Location: ../index.php");
    exit;
}

/* ==========================
   AJAX INSERT / UPDATE
=========================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json');

    $id = $_POST['DOC_ID'] ?? "";
    $fname = trim($_POST['DOC_FIRST_NAME']);
    $mname = trim($_POST['DOC_MIDDLE_INIT']);
    $lname = trim($_POST['DOC_LAST_NAME']);
    $contact = trim($_POST['DOC_CONTACT_NUM']);
    $email = trim($_POST['DOC_EMAIL']);
    $spec = trim($_POST['SPEC_ID']);

    // Validation
    $errors = [];
    if ($fname === '') $errors[] = 'First name required';
    if ($lname === '') $errors[] = 'Last name required';
    if (!preg_match('/^\d{11}$/', $contact)) $errors[] = "Contact must be exactly 11 digits";
    if (!preg_match('/^[a-zA-Z0-9._%+-]+@gmail\.com$/', $email)) $errors[] = "Email must be a valid Gmail address ending with @gmail.com";
    if ($spec === '') $errors[] = 'Specialization required';

    // Duplicate check
    $dupStmt = $conn->prepare("SELECT DOC_ID FROM doctor WHERE (DOC_EMAIL = ? OR DOC_CONTACT_NUM = ?) AND DOC_ID != ?");
    $dupStmt->execute([$email, $contact, $id ?: 0]);
    if ($dupStmt->fetch(PDO::FETCH_ASSOC)) $errors[] = "Email or contact already exists in the database!";

    if (!empty($errors)) {
        echo json_encode(["success" => false, "message" => implode(", ", $errors)]);
        exit;
    }

    try {
        if ($id === "") {
            $stmt = $conn->prepare("INSERT INTO doctor (DOC_FIRST_NAME, DOC_MIDDLE_INIT, DOC_LAST_NAME, DOC_CONTACT_NUM, DOC_EMAIL, SPEC_ID, DOC_CREATED_AT, DOC_UPDATED_AT)
                                    VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())");
            $success = $stmt->execute([$fname, $mname, $lname, $contact, $email, $spec]);
            $message = $success ? "Doctor added successfully!" : "Insert failed!";
        } else {
            $stmt = $conn->prepare("UPDATE doctor SET DOC_FIRST_NAME=?, DOC_MIDDLE_INIT=?, DOC_LAST_NAME=?, DOC_CONTACT_NUM=?, DOC_EMAIL=?, SPEC_ID=?, DOC_UPDATED_AT=NOW() WHERE DOC_ID=?");
            $success = $stmt->execute([$fname, $mname, $lname, $contact, $email, $spec, $id]);
            $message = $success ? "Profile updated successfully!" : "Update failed!";
        }
    } catch (Exception $e) {
        $success = false;
        $message = $e->getMessage();
    }

    echo json_encode(["success" => $success, "message" => $message]);
    exit;
}

/* ==========================
   FETCH DATA
=========================== */
$search = $_GET['q'] ?? "";
$sql = "SELECT d.*, s.SPEC_NAME FROM doctor d LEFT JOIN specialization s ON d.SPEC_ID = s.SPEC_ID WHERE d.DOC_ID != ?";
$params = [$loggedDocId];
if ($search !== '') {
    $sql .= " AND (CONCAT(d.DOC_FIRST_NAME, ' ', COALESCE(d.DOC_MIDDLE_INIT,''), ' ', d.DOC_LAST_NAME) LIKE ? OR d.DOC_EMAIL LIKE ? OR d.DOC_CONTACT_NUM LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
}
$sql .= " ORDER BY d.DOC_ID ASC";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);

$specStmt = $conn->query("SELECT * FROM specialization ORDER BY SPEC_NAME ASC");
$specializations = $specStmt->fetchAll(PDO::FETCH_ASSOC);

function esc($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Doctor Management</title>
<link rel="stylesheet" href="/Booking-System-For-Medical-Clinics/assets/css/style.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<?php include dirname(__DIR__, 2) . "/partials/header.php"; ?>

<main>
<h2>Doctor Management</h2>

<div class="top-controls">
    <form method="get" style="display:flex; gap:10px;">
        <input type="text" name="q" placeholder="Search doctors..." value="<?= esc($search) ?>">
        <button class="btn" type="submit">Search</button>
    </form>
    <button class="create-btn" onclick="openAddModal()">+ Add Doctor</button>
</div>

<div class="table-container">
<table>
<thead>
<tr>
<th>ID</th><th>Name</th><th>Contact</th><th>Email</th><th>Specialization</th><th>Action</th>
</tr>
</thead>
<tbody>
<?php if (empty($doctors)): ?>
<tr><td colspan="6" style="text-align:center;">No results found</td></tr>
<?php else: foreach($doctors as $d):
$mid = trim($d['DOC_MIDDLE_INIT'] ?? '');
$midDot = $mid !== '' ? esc($mid).'. ' : '';
$full = esc($d['DOC_FIRST_NAME']) . ' ' . $midDot . esc($d['DOC_LAST_NAME']);
?>
<tr>
<td><?= esc($d['DOC_ID']) ?></td>
<td><?= $full ?></td>
<td><?= esc($d['DOC_CONTACT_NUM']) ?></td>
<td><?= esc($d['DOC_EMAIL']) ?></td>
<td><?= esc($d['SPEC_NAME'] ?? '') ?></td>
<td>
<button class="btn" 
    onclick='openEditModal(<?= json_encode($d, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>Edit</button>

</td>
</tr>
<?php endforeach; endif; ?>
</tbody>
</table>
</div>
</main>

<div class="modal" id="doctorModal">
<div class="modal-content">
<span class="close-btn" onclick="closeModal()">&times;</span>
<h2 id="modalTitle">Add Doctor</h2>

<form id="doctorForm">
<input type="hidden" name="DOC_ID" id="DOC_ID">

<label>First Name</label>
<input type="text" id="DOC_FIRST_NAME" name="DOC_FIRST_NAME" required>

<label>Middle Initial</label>
<input type="text" id="DOC_MIDDLE_INIT" name="DOC_MIDDLE_INIT" maxlength="2">

<label>Last Name</label>
<input type="text" id="DOC_LAST_NAME" name="DOC_LAST_NAME" required>

<label>Contact</label>
<input type="text" id="DOC_CONTACT_NUM" name="DOC_CONTACT_NUM" pattern="\d{11}" maxlength="11" required>

<label>Email</label>
<input type="email" id="DOC_EMAIL" name="DOC_EMAIL" pattern="^[a-zA-Z0-9._%+-]+@gmail\.com$" placeholder="example@gmail.com" required>

<label>Specialization</label>
<select name="SPEC_ID" id="SPEC_ID" required>
<?php foreach($specializations as $sp): ?>
<option value="<?= esc($sp['SPEC_ID']) ?>"><?= esc($sp['SPEC_NAME']) ?></option>
<?php endforeach; ?>
</select>

<button class="btn" type="submit" onclick="closeModal()">Save</button>
</form>
</div>
</div>

<script>
function openAddModal(){
    document.getElementById('doctorForm').reset();
    document.getElementById('DOC_ID').value = '';
    document.getElementById('modalTitle').innerText = 'Add Doctor';
    document.getElementById('doctorModal').style.display = 'flex';
}

function openEditModal(d){
    document.getElementById('DOC_ID').value = d.DOC_ID;
    document.getElementById('DOC_FIRST_NAME').value = d.DOC_FIRST_NAME;
    document.getElementById('DOC_MIDDLE_INIT').value = d.DOC_MIDDLE_INIT;
    document.getElementById('DOC_LAST_NAME').value = d.DOC_LAST_NAME;
    document.getElementById('DOC_CONTACT_NUM').value = d.DOC_CONTACT_NUM;
    document.getElementById('DOC_EMAIL').value = d.DOC_EMAIL;
    document.getElementById('SPEC_ID').value = d.SPEC_ID;
    document.getElementById('modalTitle').innerText = 'Edit Doctor';
    document.getElementById('doctorModal').style.display = 'flex';
}

function closeModal(){ document.getElementById('doctorModal').style.display = 'none'; }

document.getElementById('doctorForm').addEventListener('submit', async e=>{
    e.preventDefault();
    const formData = new FormData(e.target);
    const res = await fetch(location.pathname, {
        method:'POST',
        headers:{'X-Requested-With':'XMLHttpRequest'},
        body: formData
    });
    const data = await res.json();
    Swal.fire({
        icon: data.success?'success':'error',
        title: data.success?'Success':'Oops!',
        text: data.message
    }).then(()=>{ if(data.success) location.reload(); });
});

</script>
</body>
</html>
