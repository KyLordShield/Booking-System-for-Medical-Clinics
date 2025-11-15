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

/* ✅ AJAX INSERT / UPDATE */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json');

    $id = $_POST['staff_id'] ?? "";
    $fname = trim($_POST['STAFF_FIRST_NAME']);
    $mname = trim($_POST['STAFF_MIDDLE_INIT']);
    $lname = trim($_POST['STAFF_LAST_NAME']);
    $contact = trim($_POST['STAFF_CONTACT_NUM']);
    $email = trim($_POST['STAFF_EMAIL']);

    if ($id === "") {
        $query = "INSERT INTO staff 
        (STAFF_FIRST_NAME, STAFF_MIDDLE_INIT, STAFF_LAST_NAME, STAFF_CONTACT_NUM, STAFF_EMAIL, STAFF_CREATED_AT, STAFF_UPDATED_AT)
        VALUES (?, ?, ?, ?, ?, NOW(), NOW())";
        $stmt = $conn->prepare($query);
        $success = $stmt->execute([$fname, $mname, $lname, $contact, $email]);

        echo json_encode(["success" => $success, "message" => "Staff added successfully!"]);
    } else {
        $query = "UPDATE staff SET 
        STAFF_FIRST_NAME=?, STAFF_MIDDLE_INIT=?, STAFF_LAST_NAME=?,
        STAFF_CONTACT_NUM=?, STAFF_EMAIL=?, STAFF_UPDATED_AT=NOW()
        WHERE STAFF_ID=?";
        $stmt = $conn->prepare($query);
        $success = $stmt->execute([$fname, $mname, $lname, $contact, $email, $id]);

        echo json_encode(["success" => $success, "message" => "Staff updated successfully!"]);
    }
    exit;
}

/* ✅ AJAX DELETE */
if (isset($_GET['delete']) && isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json');
    $id = intval($_GET['delete']);

    $stmt = $conn->prepare("DELETE FROM staff WHERE STAFF_ID = ?");
    $success = $stmt->execute([$id]);

    echo json_encode(["success" => $success, "message" => "Staff deleted successfully!"]);
    exit;
}

/* ✅ FETCH TABLE DATA */
$search = $_GET['q'] ?? "";
$sql = "SELECT * FROM staff";

if (!empty($search)) {
    $sql .= " WHERE 
        CONCAT(STAFF_FIRST_NAME, ' ', COALESCE(STAFF_MIDDLE_INIT,''), ' ', STAFF_LAST_NAME) LIKE :search
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
$staff = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Staff Management</title>
<link rel="stylesheet" href="/Booking-System-For-Medical-Clinics/assets/css/style.css">
</head>

<body>

<div class="navbar">
    <div class="navbar-brand">
        <img src="https://cdn-icons-png.flaticon.com/512/3209/3209999.png">Medicina
    </div>
    <div class="nav-links">
        <a href="/Booking-System-For-Medical-Clinics/public/staff_dashboard.php">Home</a>
      <a class="active" href="#">Staff</a>
      <a href="services.php">Services</a>
      <a href="status.php">Status</a>
      <a href="payments.php">Payments</a>
      <a href="specialization.php">Specialization</a>
      <a href="smedical_records.php">Medical Records</a>
      <a href="/Booking-System-For-Medical-Clinics/index.php">Log out</a>
    </div>
</div>

<main>
<h2>Staff Management</h2>

<div class="top-controls">
    <form method="get" style="display:flex; gap:10px;">
        <input class="modal-input" type="text" name="q"
        placeholder="Search staff..."
        value="<?= htmlspecialchars($search) ?>">
        <button class="btn" type="submit">Search</button>
    </form>

    <button class="create-btn" onclick="openAddModal()">+ Add Staff</button>
</div>


<div class="table-container">
<table>
<thead>
<tr>
<th>ID</th>
<th>Name</th>
<th>Contact</th>
<th>Email</th>
<th>Action</th>
</tr>
</thead>
<tbody>
<?php if (empty($staff)): ?>
<tr><td colspan="5" style="text-align:center;">No results found</td></tr>
<?php else: ?>
<?php foreach ($staff as $row): ?>
<tr>
<td><?= $row['STAFF_ID'] ?></td>
<td><?= $row['STAFF_FIRST_NAME'] . " " . $row['STAFF_MIDDLE_INIT'] . ". " . $row['STAFF_LAST_NAME'] ?></td>
<td><?= $row['STAFF_CONTACT_NUM'] ?></td>
<td><?= $row['STAFF_EMAIL'] ?></td>
<td>
<button class="btn" onclick='openEditModal(<?= json_encode($row) ?>)'>Edit</button>
</td>
</tr>
<?php endforeach; endif; ?>
</tbody>
</table>
</div>

</main>

<!-- ✅ MODAL -->
<div class="modal" id="staffModal">
<div class="modal-content">
<span class="close-btn" onclick="closeModal()">&times;</span>
<h2 id="modalTitle">Add Staff</h2>

<form id="staffForm">
<input type="hidden" name="staff_id" id="staff_id">

<label>First Name</label>
<input type="text" id="fname" name="STAFF_FIRST_NAME" required>

<label>Middle Initial</label>
<input type="text" id="mname" name="STAFF_MIDDLE_INIT" maxlength="2">

<label>Last Name</label>
<input type="text" id="lname" name="STAFF_LAST_NAME" required>

<label>Contact</label>
<input type="text" id="phone" name="STAFF_CONTACT_NUM" required>

<label>Email</label>
<input type="email" id="email" name="STAFF_EMAIL" required>

<button class="btn" type="submit">Save</button>
</form>
</div>
</div>

<footer>&copy; 2025 Medicina Clinic | All Rights Reserved</footer>

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

function showModal() {
    document.getElementById("staffModal").style.display = "flex";
}
function closeModal() {
    document.getElementById("staffModal").style.display = "none";
}

// ✅ AJAX SAVE
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

    if (data.success) {
        closeModal();
        location.reload();
    }
});

// ✅ AJAX DELETE
async function deleteStaff(id) {
    if (!confirm("Delete this staff?")) return;
    const res = await fetch(`staff_manage.php?delete=${id}`, {
        headers: { "X-Requested-With": "XMLHttpRequest" }
    });
    const data = await res.json();
    alert(data.message);
    if (data.success) location.reload();
}
</script>

</body>
</html>
