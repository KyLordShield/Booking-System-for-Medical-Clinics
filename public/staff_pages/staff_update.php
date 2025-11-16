<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../index.php");
    exit;
}

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once dirname(__DIR__, 2) . '/config/Database.php';
$db = new Database();
$conn = $db->connect();

// Helper
function esc($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

$loggedStaffId = $_SESSION['STAFF_ID'];

// Fetch logged-in staff for profile
$stmt = $conn->prepare("SELECT * FROM staff WHERE STAFF_ID = ?");
$stmt->execute([$loggedStaffId]);
$myself = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch staff list
$search = $_GET['q'] ?? "";
$sql = "SELECT * FROM staff";
if (!empty($search)) {
    $sql .= " WHERE CONCAT(STAFF_FIRST_NAME,' ',COALESCE(STAFF_MIDDLE_INIT,''),' ',STAFF_LAST_NAME) LIKE :search 
              OR STAFF_CONTACT_NUM LIKE :search
              OR STAFF_EMAIL LIKE :search";
}
$sql .= " ORDER BY STAFF_ID ASC";

$stmt = $conn->prepare($sql);
if (!empty($search)) {
    $searchText = "%$search%";
    $stmt->bindParam(':search', $searchText);
}
$stmt->execute();
$staffList = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Staff Management</title>
<link rel="stylesheet" href="/Booking-System-For-Medical-Clinics/assets/css/style.css">
</head>
<body>

<?php include dirname(__DIR__, 2) . "/partials/header.php"; ?>

<main>
<h2>My Profile</h2>
<div class="table-container">
<table>
<tr><th>ID</th><td><?= esc($myself['STAFF_ID']) ?></td></tr>
<tr>
<th>Name</th>
<td>
<?php
$mid = trim($myself['STAFF_MIDDLE_INIT'] ?? '');
$midDot = $mid !== '' ? esc($mid) . '. ' : '';
echo esc($myself['STAFF_FIRST_NAME']) . ' ' . $midDot . esc($myself['STAFF_LAST_NAME']);
?>
</td>
</tr>
<tr><th>Email</th><td><?= esc($myself['STAFF_EMAIL']) ?></td></tr>
<tr><th>Contact</th><td><?= esc($myself['STAFF_CONTACT_NUM']) ?></td></tr>
</table>

<button class="btn" onclick='openEditModal(<?= json_encode($myself, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>Update My Info</button>
</div>



</main>

<?php include dirname(__DIR__, 2) . "/partials/footer.php"; ?>

<!-- MODAL -->
<div class="modal" id="staffModal">
<div class="modal-content">
<span class="close-btn" onclick="closeModal()">&times;</span>
<h2 id="modalTitle">Add Staff</h2>
<form id="staffForm">
<input type="hidden" name="staff_id" id="staff_id">
<label>First Name</label><input type="text" id="fname" name="STAFF_FIRST_NAME" required>
<label>Middle Initial</label><input type="text" id="mname" name="STAFF_MIDDLE_INIT" maxlength="2">
<label>Last Name</label><input type="text" id="lname" name="STAFF_LAST_NAME" required>
<label>Contact</label><input type="text" id="phone" name="STAFF_CONTACT_NUM" pattern="\d{11}" maxlength="11" required>
<label>Email</label><input type="email" id="email" name="STAFF_EMAIL" pattern="^[a-zA-Z0-9._%+-]+@gmail\.com$" placeholder="example@gmail.com" required>
<button class="btn" type="submit">Save</button>
</form>
</div>
</div>

<script>
function openAddModal() {
    document.getElementById("modalTitle").innerText = "Add Staff";
    document.getElementById("staffForm").reset();
    document.getElementById("staff_id").value = "";
    showModal();
}
function openEditModal(staff) {
    document.getElementById("modalTitle").innerText = "Edit Staff";
    document.getElementById("staff_id").value = staff.STAFF_ID;
    document.getElementById("fname").value = staff.STAFF_FIRST_NAME;
    document.getElementById("mname").value = staff.STAFF_MIDDLE_INIT;
    document.getElementById("lname").value = staff.STAFF_LAST_NAME;
    document.getElementById("phone").value = staff.STAFF_CONTACT_NUM;
    document.getElementById("email").value = staff.STAFF_EMAIL;
    showModal();
}
function showModal(){ document.getElementById("staffModal").style.display = "flex"; }
function closeModal(){ document.getElementById("staffModal").style.display = "none"; }

// AJAX SAVE
document.getElementById("staffForm").addEventListener("submit", async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    const res = await fetch("staff_manage.php", {
        method: "POST",
        body: formData,
        headers: { "X-Requested-With": "XMLHttpRequest" }
    });
    const data = await res.json();
    alert(data.message);
    if(data.success){ closeModal(); location.reload(); }
});

// AJAX DELETE
async function deleteStaff(id){
    if(!confirm("Delete this staff?")) return;
    const res = await fetch(`staff_manage.php?delete=${id}`, { headers: { "X-Requested-With": "XMLHttpRequest" }});
    const data = await res.json();
    alert(data.message);
    if(data.success) location.reload();
}
</script>
</body>
</html>
