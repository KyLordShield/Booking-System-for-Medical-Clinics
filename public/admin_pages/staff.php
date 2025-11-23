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

require_once dirname(__DIR__, 2) . '/classes/User.php';          // NEW
require_once dirname(__DIR__, 2) . '/config/Database.php';

$db = new Database();
$conn = $db->connect();
$userObj = new User();                                           // NEW

/* ---------- AJAX INSERT / UPDATE ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json');

    $action = $_POST['action'] ?? '';

    /* ---------- CREATE STAFF USER (NEW ENDPOINT) ---------- */
    if ($action === 'createStaffUser') {
        $staff_id = (int)($_POST['staff_id'] ?? 0);
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        $errors = [];
        if ($username === '') $errors[] = 'Username is required.';
        if ($password === '') $errors[] = 'Password is required.';
        if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';

        if (!empty($errors)) {
            echo json_encode(['success' => false, 'errors' => $errors]);
            exit;
        }

        try {
            // Check if staff exists
            $stmt = $conn->prepare("SELECT STAFF_ID FROM staff WHERE STAFF_ID = ?");
            $stmt->execute([$staff_id]);
            if (!$stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Staff not found.']);
                exit;
            }

            // Check if staff already has user
            if ($userObj->existsByEntity('Staff', $staff_id)) {
                echo json_encode(['success' => false, 'message' => 'This staff already has a user account.']);
                exit;
            }

            // Check username
            if ($userObj->isUsernameTaken($username)) {
                echo json_encode(['success' => false, 'message' => 'Username already exists.']);
                exit;
            }

            // Create user
            $result = $userObj->createForEntity('Staff', $staff_id, $username, $password, 0);
            $ok = str_starts_with($result, 'User created successfully!');

            echo json_encode([
                'success' => $ok,
                'message' => $ok ? 'User account created!' : $result
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    /* ---------- ORIGINAL INSERT / UPDATE ---------- */
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
            // Check duplicate email
$stmt = $conn->prepare("SELECT STAFF_ID FROM staff WHERE STAFF_EMAIL = ?");
$stmt->execute([$data['email']]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Email is already in use!']);
    exit;
}

// Check duplicate contact number
$stmt = $conn->prepare("SELECT STAFF_ID FROM staff WHERE STAFF_CONTACT_NUM = ?");
$stmt->execute([$data['contact']]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Contact number is already in use!']);
    exit;
}

            $stmt = $conn->prepare("INSERT INTO staff 
                (STAFF_FIRST_NAME, STAFF_MIDDLE_INIT, STAFF_LAST_NAME, STAFF_CONTACT_NUM, STAFF_EMAIL, STAFF_CREATED_AT, STAFF_UPDATED_AT)
                VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
            $stmt->execute([$data['first'], $data['middle'], $data['last'], $data['contact'], $data['email']]);
            $newStaffId = $conn->lastInsertId();

            $fullName = trim("{$data['first']} " . ($data['middle'] ? $data['middle'].'. ' : '') . $data['last']);

            echo json_encode([
                'success' => true,
                'message' => 'Staff added successfully!',
                'newStaffId' => $newStaffId,
                'staffName' => $fullName
            ]);
        } else {
            // Duplicate email (excluding current staff)
$stmt = $conn->prepare("SELECT STAFF_ID FROM staff WHERE STAFF_EMAIL = ? AND STAFF_ID != ?");
$stmt->execute([$data['email'], $id]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Email is already in use by another staff!']);
    exit;
}

// Duplicate contact (excluding current staff)
$stmt = $conn->prepare("SELECT STAFF_ID FROM staff WHERE STAFF_CONTACT_NUM = ? AND STAFF_ID != ?");
$stmt->execute([$data['contact'], $id]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Contact number is already in use by another staff!']);
    exit;
}

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
<link rel="stylesheet" href="../../assets/css/style.css">
<style>
    .modal { display: none; justify-content: center; align-items: center; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,.5); z-index: 1000; }
    .modal-content { background: #fff; padding: 20px; border-radius: 8px; max-width: 420px; width: 90%; position: relative; }
    .close-btn { position: absolute; top: 8px; right: 12px; font-size: 24px; cursor: pointer; }
    .error { color: #d00; margin-top: 5px; font-size: 0.9rem; }
    #userModalErrors { margin-top: 10px; }
</style>
</head>
<body>

<?php include dirname(__DIR__, 2) . "/partials/header.php"; ?>

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

<!-- ==================== STAFF MODAL ==================== -->
<div class="modal" id="staffModal">
<div class="modal-content">
<span class="close-btn" onclick="closeModal('staffModal')">&times;</span>
<h2 id="modalTitle">Add Staff</h2>

<form id="staffForm">
<input type="hidden" name="STAFF_ID" id="STAFF_ID">

<label>First Name</label>
<input type="text" id="STAFF_FIRST_NAME" name="STAFF_FIRST_NAME" required>

<label>Middle Initial</label>
<input type="text" id="STAFF_MIDDLE_INIT" name="STAFF_MIDDLE_INIT" maxlength="1">

<label>Last Name</label>
<input type="text" id="STAFF_LAST_NAME" name="STAFF_LAST_NAME" required>

<label>Contact</label>
<input type="text" id="STAFF_CONTACT_NUM" name="STAFF_CONTACT_NUM" pattern="\d{11}" maxlength="11" required>

<label>Email</label>
<input type="email" id="STAFF_EMAIL" name="STAFF_EMAIL" pattern="^[a-zA-Z0-9._%+-]+@gmail\.com$" placeholder="example@gmail.com" required>

<button class="btn" type="submit">Save</button>
</form>
</div>
</div>

<!-- ==================== USER CREATION MODAL ==================== -->
<div class="modal" id="userModal">
<div class="modal-content">
<span class="close-btn" onclick="closeModal('userModal')">&times;</span>
<h2>Create Account for <span id="staffFullName"></span></h2>

<form id="userForm">
<input type="hidden" name="staff_id" id="userStaffId">
<input type="hidden" name="action" value="createStaffUser">

<label>Username</label>
<input type="text" name="username" required minlength="3">

<label>Password</label>
<input type="password" name="password" required minlength="6">

<div id="userModalErrors"></div>

<button class="btn" type="submit">Create Account</button>
</form>
</div>
</div>

<?php include dirname(__DIR__, 2) . "/partials/footer.php"; ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
/* ---------- MODAL HELPERS ---------- */
function showModal(id) { document.getElementById(id).style.display = 'flex'; }
function closeModal(id) { document.getElementById(id).style.display = 'none'; }

/* ---------- STAFF MODAL ---------- */
function openAddModal() {
    document.getElementById("modalTitle").innerText = "Add Staff";
    document.getElementById("staffForm").reset();
    document.getElementById("STAFF_ID").value = "";
    showModal('staffModal');
}

function openEditModal(staff) {
    document.getElementById("modalTitle").innerText = "Edit Staff";
    for (const key in staff) {
        const el = document.getElementById(key);
        if (el) el.value = staff[key] ?? '';
    }
    showModal('staffModal');
}

/* ---------- STAFF FORM SUBMIT ---------- */
document.getElementById("staffForm").addEventListener("submit", async e => {
    e.preventDefault();
    const formData = new FormData(e.target);

    const res = await fetch(location.pathname, {
        method: "POST",
        body: formData,
        headers: { "X-Requested-With": "XMLHttpRequest" }
    });
    const data = await res.json();

    if (data.errors) {
        Swal.fire({
            icon: "error",
            title: "Validation Error",
            html: data.errors.join("<br>")
        });
        return;
    }

    if (!data.success) {
        Swal.fire({
            icon: "error",
            title: "Error",
            text: data.message
        });
        return;
    }

    // Only show success if success === true
    Swal.fire({
        icon: "success",
        title: "Success!",
        text: data.message,
        timer: 1500,
        showConfirmButton: false
    });

    closeModal('staffModal');

    if (data.newStaffId) {
        document.getElementById('staffFullName').innerText = data.staffName;
        document.getElementById('userStaffId').value = data.newStaffId;
        document.getElementById('userForm').reset();
        document.getElementById('userModalErrors').innerHTML = '';
        showModal('userModal');
    } else {
        setTimeout(() => location.reload(), 1500);
    }
});


/* ---------- USER FORM SUBMIT ---------- */
document.getElementById("userForm").addEventListener("submit", async e => {
    e.preventDefault();
    const formData = new FormData(e.target);

    const res = await fetch(location.pathname, {
        method: "POST",
        body: formData,
        headers: { "X-Requested-With": "XMLHttpRequest" }
    });
    const data = await res.json();

    const errDiv = document.getElementById('userModalErrors');
    errDiv.innerHTML = '';

    if (data.errors) {
        data.errors.forEach(msg => {
            const p = document.createElement('p');
            p.className = 'error';
            p.textContent = msg;
            errDiv.appendChild(p);
        });

        Swal.fire({
            icon: "error",
            title: "Validation Error",
            html: data.errors.join("<br>")
        });
        return;
    }

    if (!data.success && !data.message.includes("created")) {
    Swal.fire({
        icon: "error",
        title: "Error",
        text: data.message
    });
    return;
}


    Swal.fire({
        icon: "success",
        title: "Account Created!",
        text: data.message,
        timer: 1500,
        showConfirmButton: false
    });

    closeModal('userModal');
    setTimeout(() => location.reload(), 1500);
});

/* ---------- DELETE ---------- */
async function deleteStaff(id) {
    const confirmDelete = await Swal.fire({
        icon: "warning",
        title: "Delete Staff?",
        text: "This action cannot be undone.",
        showCancelButton: true,
        confirmButtonText: "Delete",
        cancelButtonText: "Cancel"
    });

    if (!confirmDelete.isConfirmed) return;

    const res = await fetch(`?delete=${id}`, {
        headers: { "X-Requested-With": "XMLHttpRequest" }
    });
    const data = await res.json();

    if (!data.success) {
        Swal.fire({
            icon: "error",
            title: "Error",
            text: data.message
        });
        return;
    }

    Swal.fire({
        icon: "success",
        title: "Deleted!",
        text: data.message,
        timer: 1500,
        showConfirmButton: false
    });

    setTimeout(() => location.reload(), 1500);
}
</script>

</body>
</html>