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

/* ---------- AJAX INSERT / UPDATE ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json');

    $id = $_POST['DOC_ID'] ?? "";
    $first = trim($_POST['DOC_FIRST_NAME']);
    $middle = trim($_POST['DOC_MIDDLE_INIT']);
    $last = trim($_POST['DOC_LAST_NAME']);
    $contact = trim($_POST['DOC_CONTACT_NUM']);
    $email = trim($_POST['DOC_EMAIL']);
    $spec = trim($_POST['SPEC_ID']);

    // block editing other doctors
    if ($loggedRole === "doctor" && $id !== "" && $id != $loggedDocId) {
        echo json_encode(['success'=>false,'message'=>'Not allowed to update others']);
        exit;
    }

    try {
        if ($id === "") { // Add new doctor
            $stmt = $conn->prepare("INSERT INTO doctor
                (DOC_FIRST_NAME, DOC_MIDDLE_INIT, DOC_LAST_NAME, DOC_CONTACT_NUM, DOC_EMAIL, DOC_CREATED_AT, DOC_UPDATED_AT, SPEC_ID)
                VALUES (?,?,?,?,?,NOW(),NOW(),?)");
            $stmt->execute([$first,$middle,$last,$contact,$email,$spec]);
            echo json_encode(['success'=>true,'message'=>'Doctor added']);
        } else { // Update self only
            $stmt = $conn->prepare("UPDATE doctor SET
                DOC_FIRST_NAME=?, DOC_MIDDLE_INIT=?, DOC_LAST_NAME=?, DOC_CONTACT_NUM=?, DOC_EMAIL=?, SPEC_ID=?, DOC_UPDATED_AT=NOW()
                WHERE DOC_ID=?");
            $stmt->execute([$first,$middle,$last,$contact,$email,$spec,$id]);
            echo json_encode(['success'=>true,'message'=>'Profile updated']);
        }
    } catch(PDOException $e){
        echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
    }
    exit;
}

/* ---------- AJAX DELETE BLOCKED FOR DOCTORS ---------- */
if (isset($_GET['delete']) && isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    echo json_encode(['success'=>false,'message'=>'Doctors cannot delete accounts']);
    exit;
}

/* ---------- FETCH SPECIALIZATIONS ---------- */
$specStmt = $conn->prepare("SELECT SPEC_ID, SPEC_NAME FROM specialization ORDER BY SPEC_NAME ASC");
$specStmt->execute();
$specializations = $specStmt->fetchAll(PDO::FETCH_ASSOC);

/* ---------- FETCH LOGGED-IN DOCTOR DETAILS ---------- */
$stmt = $conn->prepare("
    SELECT d.*, s.SPEC_NAME
    FROM doctor d
    LEFT JOIN specialization s ON d.SPEC_ID = s.SPEC_ID
    WHERE d.DOC_ID = ?
");
$stmt->execute([$loggedDocId]);
$myself = $stmt->fetch(PDO::FETCH_ASSOC);

/* ---------- FETCH OTHER DOCTORS (excluding self) ---------- */
$search = trim($_GET['q'] ?? '');
$sql = "SELECT d.*, s.SPEC_NAME FROM doctor d
        LEFT JOIN specialization s ON d.SPEC_ID=s.SPEC_ID
        WHERE d.DOC_ID != ?";

$params = [$loggedDocId];

if ($search !== "") {
    $sql .= " AND (d.DOC_FIRST_NAME LIKE ? OR d.DOC_LAST_NAME LIKE ? OR d.DOC_EMAIL LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY d.DOC_ID ASC";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$others = $stmt->fetchAll(PDO::FETCH_ASSOC);

function esc($s){ return htmlspecialchars($s ?? '',ENT_QUOTES); }
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Doctor Management</title>
<link rel="stylesheet" href="/Booking-System-For-Medical-Clinics/assets/css/style.css">
</head>
<body>

<div class="navbar">
    <div class="navbar-brand">
        <img src="https://cdn-icons-png.flaticon.com/512/3209/3209999.png">Medicina
    </div>
    <div class="nav-links">
        <a href="/Booking-System-For-Medical-Clinics/public/doctor_dashboard.php">Home</a>
      <a class="active" href="doctor_pages/doctor_manage.php">Doctor</a>
      <a href="schedule.php">Schedule</a>
      <a href="appointments.php">Appointment</a>
      <a href="medical_records.php">Medical Records</a>
      <a href="/Booking-System-For-Medical-Clinics/index.php">Log out</a>
    </div>
</div>

<main>
    <h2>My Profile</h2>
    <div class="table-container">
        <table>
            <tr><th>ID</th><td><?= esc($myself['DOC_ID']) ?></td></tr>
            <tr><th>Name</th><td><?= esc($myself['DOC_FIRST_NAME'].' '.$myself['DOC_LAST_NAME']) ?></td></tr>
            <tr><th>Email</th><td><?= esc($myself['DOC_EMAIL']) ?></td></tr>
            <tr><th>Contact</th><td><?= esc($myself['DOC_CONTACT_NUM']) ?></td></tr>
            <tr><th>Specialization</th><td><?= esc($myself['SPEC_NAME']) ?></td></tr>
        </table>
        <br>
        <button class="btn" onclick='openEditModal(<?= json_encode($myself) ?>)'>Update My Info</button>
    </div>

    <br><hr><br>

    <h2>Other Doctors</h2>

    <div class="top-controls">
        <form method="get">
            <input name="q" placeholder="Search doctors" value="<?=esc($search)?>">
            <button class="btn">Search</button>
        </form>
        <button class="btn" onclick="openAddModal()">+ Add Doctor</button>
    </div>

    <div class="table-container">
        <table>
            <thead><tr>
            <th>ID</th><th>Name</th><th>Specialization</th><th>Contact</th><th>Email</th><th>Action</th>
            </tr></thead>
            <tbody>
            <?php foreach($others as $d): ?>
            <tr>
                <td><?= esc($d['DOC_ID']) ?></td>
                <td><?= esc($d['DOC_FIRST_NAME'].' '.$d['DOC_LAST_NAME']) ?></td>
                <td><?= esc($d['SPEC_NAME']) ?></td>
                <td><?= esc($d['DOC_CONTACT_NUM']) ?></td>
                <td><?= esc($d['DOC_EMAIL']) ?></td>
                <td>
                    <button class="btn" onclick='openViewModal(<?= json_encode($d) ?>)'>View</button>
                </td>
            </tr>
        <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>

<!-- ✅ MODAL -->
<div class="modal" id="doctorModal">
  <div class="modal-content">
    <span class="close-btn" onclick="closeModal()">&times;</span>
    <h2 id="modalTitle">Edit Profile</h2>

    <form id="doctorForm">
      <input type="hidden" id="DOC_ID" name="DOC_ID">

      <label>First Name</label><input id="DOC_FIRST_NAME" name="DOC_FIRST_NAME">
      <label>Middle Init</label><input id="DOC_MIDDLE_INIT" name="DOC_MIDDLE_INIT">
      <label>Last Name</label><input id="DOC_LAST_NAME" name="DOC_LAST_NAME">
      <label>Email</label><input id="DOC_EMAIL" name="DOC_EMAIL">
      <label>Contact</label><input id="DOC_CONTACT_NUM" name="DOC_CONTACT_NUM">
      <label>Specialization</label>
      <select id="SPEC_ID" name="SPEC_ID">
        <?php foreach($specializations as $sp): ?>
            <option value="<?=$sp['SPEC_ID']?>"><?=$sp['SPEC_NAME']?></option>
        <?php endforeach;?>
      </select>

      <button type="submit" class="btn">Save</button>
    </form>
  </div>
</div>

<footer>&copy; 2025 Medicina Clinic</footer>

<script>
function showModal(){document.getElementById('doctorModal').style.display='flex';}
function closeModal(){document.getElementById('doctorModal').style.display='none';}

function openAddModal(){
    document.getElementById('doctorForm').reset();
    document.getElementById('DOC_ID').value="";
    document.getElementById('modalTitle').innerText="Add Doctor";
    showModal();
}

function openEditModal(d){
    document.getElementById('modalTitle').innerText="Edit My Profile";
    for(const k in d){if(document.getElementById(k))document.getElementById(k).value=d[k];}
    showModal();
}

function openViewModal(d){
    alert(
        "Doctor: "+d.DOC_FIRST_NAME+" "+d.DOC_LAST_NAME+
        "\nEmail: "+d.DOC_EMAIL+
        "\nContact: "+d.DOC_CONTACT_NUM+
        "\nSpecialization: "+d.SPEC_NAME
    );
}

document.getElementById('doctorForm').addEventListener('submit', async e=>{
    e.preventDefault();
    const res = await fetch(location.pathname,{
        method:"POST",
        headers:{"X-Requested-With":"XMLHttpRequest"},
        body:new FormData(e.target)
    });
    const j = await res.json();
    alert(j.message);
    if(j.success) location.reload();
});
</script>

</body>
</html>
