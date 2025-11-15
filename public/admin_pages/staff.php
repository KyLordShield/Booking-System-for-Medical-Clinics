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
require_once dirname(__DIR__, 2) . '/config/Database.php';
$db = new Database();
$conn = $db->connect();

/* ---------- AJAX INSERT / UPDATE ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json');

    $id = $_POST['STAFF_ID'] ?? "";
    $data = [
        'first'   => trim($_POST['STAFF_FIRST_NAME'] ?? ''),
        'middle'  => trim($_POST['STAFF_MIDDLE_INIT'] ?? ''),
        'last'    => trim($_POST['STAFF_LAST_NAME'] ?? ''),
        'contact' => trim($_POST['STAFF_CONTACT_NUM'] ?? ''),
        'email'   => trim($_POST['STAFF_EMAIL'] ?? '')
    ];

    // Validation
    $errors = [];
    if ($data['first'] === '') $errors[] = 'First name required';
    if ($data['last'] === '') $errors[] = 'Last name required';
    if ($data['contact'] === '') $errors[] = 'Contact required';
    if ($data['email'] === '' || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email required';

    if (!empty($errors)) {
        echo json_encode(['success' => false, 'errors' => $errors]);
        exit;
    }

    try {
        if ($id === "") {
            $stmt = $conn->prepare("INSERT INTO staff 
                (STAFF_FIRST_NAME, STAFF_MIDDLE_INIT, STAFF_LAST_NAME, STAFF_CONTACT_NUM, STAFF_EMAIL, STAFF_CREATED_AT, STAFF_UPDATED_AT)
                VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
            $success = $stmt->execute([$data['first'], $data['middle'], $data['last'], $data['contact'], $data['email']]);
            echo json_encode(['success' => $success, 'message' => $success ? 'Staff added successfully!' : 'Insert failed']);
        } else {
            $stmt = $conn->prepare("UPDATE staff SET
                STAFF_FIRST_NAME=?, STAFF_MIDDLE_INIT=?, STAFF_LAST_NAME=?,
                STAFF_CONTACT_NUM=?, STAFF_EMAIL=?, STAFF_UPDATED_AT=NOW()
                WHERE STAFF_ID=?");
            $success = $stmt->execute([$data['first'], $data['middle'], $data['last'], $data['contact'], $data['email'], $id]);
            echo json_encode(['success' => $success, 'message' => $success ? 'Staff updated successfully!' : 'Update failed']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

/* ---------- AJAX DELETE ---------- */
if (isset($_GET['delete']) && isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json');
    $id = intval($_GET['delete']);

    try {
        $stmt = $conn->prepare("DELETE FROM staff WHERE STAFF_ID=?");
        $success = $stmt->execute([$id]);
        echo json_encode(['success' => $success, 'message' => $success ? 'Staff deleted successfully!' : 'Delete failed']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

/* ---------- FETCH TABLE DATA ---------- */
$search = trim($_GET['q'] ?? '');
$sql = "SELECT * FROM staff";
$params = [];

if (!empty($search)) {
    $sql .= " WHERE CONCAT(STAFF_FIRST_NAME, ' ', COALESCE(STAFF_MIDDLE_INIT,''), ' ', STAFF_LAST_NAME) LIKE ? 
              OR STAFF_CONTACT_NUM LIKE ? 
              OR STAFF_EMAIL LIKE ?";
    $like = "%$search%";
    $params = [$like, $like, $like];
}

$sql .= " ORDER BY STAFF_ID ASC";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$staff = $stmt->fetchAll(PDO::FETCH_ASSOC);

function esc($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Staff Management | Admin</title>
<link rel="stylesheet" href="/Booking-System-For-Medical-Clinics/assets/css/style.css">
</head>
<body>

<!-- NAVBAR -->
<!-- ✅ HEADER LINK -->
  <?php include dirname(__DIR__, 2) . "/partials/header.php"; ?>
<!-- ✅ HEADER LINK -->

<main>
<h2>Staff Management (Admin)</h2>

<div class="top-controls">
    <form method="get" style="display:flex; gap:10px;">
        <input type="text" name="q" placeholder="Search staff..." value="<?=esc($search)?>">
        <button class="btn" type="submit">Search</button>
        <?php if (!empty($search)): ?>
            <a href="staff.php" class="btn" style="background:#777;">Reset</a>
        <?php endif; ?>
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
<th>Actions</th>
</tr>
</thead>
<tbody>
<?php if(empty($staff)): ?>
<tr><td colspan="5" style="text-align:center;">No results</td></tr>
<?php else: ?>
<?php foreach($staff as $s): 
    $midDot = trim($s['STAFF_MIDDLE_INIT']) !== '' ? esc($s['STAFF_MIDDLE_INIT']).'. ' : '';
    $name = esc($s['STAFF_FIRST_NAME']).' '.$midDot.esc($s['STAFF_LAST_NAME']);
?>
<tr>
<td><?= esc($s['STAFF_ID']) ?></td>
<td><?= $name ?></td>
<td><?= esc($s['STAFF_CONTACT_NUM']) ?></td>
<td><?= esc($s['STAFF_EMAIL']) ?></td>
<td>
<button class="btn" onclick='openEditModal(<?= json_encode($s) ?>)'>Edit</button>
<button class="btn" onclick="deleteStaff('<?= esc($s['STAFF_ID']) ?>')">Delete</button>
</td>
</tr>
<?php endforeach; endif; ?>
</tbody>
</table>
</div>
</main>

<!-- Modal -->
<div class="modal" id="staffModal">
<div class="modal-content">
<span class="close-btn" onclick="closeModal()">&times;</span>
<h2 id="modalTitle">Add Staff</h2>

<form id="staffForm">
<input type="hidden" name="STAFF_ID" id="STAFF_ID">

<label>First Name</label>
<input type="text" id="STAFF_FIRST_NAME" name="STAFF_FIRST_NAME" required>

<label>Middle Initial</label>
<input type="text" id="STAFF_MIDDLE_INIT" name="STAFF_MIDDLE_INIT" maxlength="2">

<label>Last Name</label>
<input type="text" id="STAFF_LAST_NAME" name="STAFF_LAST_NAME" required>

<label>Contact</label>
<input type="text" id="STAFF_CONTACT_NUM" name="STAFF_CONTACT_NUM" required>

<label>Email</label>
<input type="email" id="STAFF_EMAIL" name="STAFF_EMAIL" required>

<button class="btn" type="submit">Save</button>
</form>
</div>
</div>


<!-- ✅ FOOTER LINK -->
  <?php include dirname(__DIR__, 2) . "/partials/footer.php"; ?>


<script>
function openAddModal() {
    document.getElementById("modalTitle").innerText = "Add Staff";
    document.getElementById("staffForm").reset();
    document.getElementById("STAFF_ID").value = "";
    showModal();
}

function openEditModal(staff) {
    document.getElementById("modalTitle").innerText = "Edit Staff";
    for (const key in staff) {
        if (document.getElementById(key)) document.getElementById(key).value = staff[key];
    }
    showModal();
}

function showModal(){ document.getElementById("staffModal").style.display = "flex"; }
function closeModal(){ document.getElementById("staffModal").style.display = "none"; }

// AJAX Save
document.getElementById("staffForm").addEventListener("submit", async (e)=>{
    e.preventDefault();
    const formData = new FormData(e.target);

    const res = await fetch(location.pathname, {
        method:"POST",
        body: formData,
        headers:{ "X-Requested-With":"XMLHttpRequest" }
    });
    const data = await res.json();
    if(data.errors) { alert(data.errors.join("\n")); return; }
    alert(data.message);
    if(data.success){ closeModal(); location.reload(); }
});

// AJAX Delete
async function deleteStaff(id){
    if(!confirm("Delete this staff?")) return;
    const res = await fetch(`?delete=${id}`,{ headers:{ "X-Requested-With":"XMLHttpRequest" }});
    const data = await res.json();
    alert(data.message);
    if(data.success) location.reload();
}
</script>

</body>
</html>
