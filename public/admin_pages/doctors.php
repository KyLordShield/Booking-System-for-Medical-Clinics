<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

/* ---------- 1. AUTH CHECK ---------- */
if (
    empty($_SESSION['USER_IS_SUPERADMIN']) ||
    $_SESSION['USER_IS_SUPERADMIN'] != 1 ||
    $_SESSION['role'] !== 'admin'
) {
    header("Location: ../../index.php");
    exit;
}

require_once dirname(__DIR__, 2) . '/classes/Doctor.php';
require_once dirname(__DIR__, 2) . '/classes/Specialization.php';
require_once dirname(__DIR__, 2) . '/classes/User.php';

$doctorObj = new Doctor();
$specObj   = new Specialization();
$userObj   = new User();

/* ---------- AJAX INSERT / UPDATE ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json');

    $action = $_POST['action'] ?? '';

    /* ---------- CREATE DOCTOR USER ---------- */
    if ($action === 'createDoctorUser') {
        $doc_id   = (int)($_POST['doc_id'] ?? 0);
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
            // 1. Doctor must exist
            $doc = $doctorObj->getById($doc_id);
            if (!$doc) {
                echo json_encode(['success' => false, 'message' => 'Doctor not found.']);
                exit;
            }

            // 2. Doctor must NOT already have a user account
            if ($userObj->existsByEntity('Doctor', $doc_id)) {
                echo json_encode(['success' => false, 'message' => 'This doctor already has a user account.']);
                exit;
            }

            // 3. Username must be unique
            if ($userObj->isUsernameTaken($username)) {
                echo json_encode(['success' => false, 'message' => 'Username already exists.']);
                exit;
            }

            // 4. Create the user
            $result = $userObj->createForEntity('Doctor', $doc_id, $username, $password, 0);
            // Check if creation was successful (handle emoji prefix)
            $ok = (strpos($result, 'User created successfully!') !== false);

            echo json_encode([
                'success' => $ok,
                'message' => $ok ? 'User account created successfully!' : $result
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    /* ---------- DOCTOR INSERT / UPDATE ---------- */
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
    if ($data['email'] === '' || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email required';
    }
    if ($data['spec'] === '') $errors[] = 'Specialization required';

    if (!empty($errors)) {
        echo json_encode(['success' => false, 'errors' => $errors]);
        exit;
    }

    try {
        if ($id === "") {
            $newDocId = $doctorObj->insert($data);
            $ok = $newDocId !== false;
            $message = $ok ? 'Doctor added' : 'Insert failed';
            echo json_encode([
                'success'   => $ok,
                'message'   => $message,
                'newDocId'  => $ok ? $newDocId : null,
                'docName'   => $ok ? $data['first'].' '.($data['middle'] ? $data['middle'].'. ' : '').$data['last'] : null
            ]);
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
<link rel="stylesheet" href="../../assets/css/style.css">
<style>
    .modal{display:none;justify-content:center;align-items:center;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.5);z-index:1000;}
    .modal-content{background:#fff;padding:20px;border-radius:8px;max-width:420px;width:90%;position:relative;}
    .close-btn{position:absolute;top:8px;right:12px;font-size:24px;cursor:pointer;}
    .error{color:#d00;margin-top:5px;font-size:0.9rem;}
    #userModalErrors{margin-top:10px;}
</style>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<?php include dirname(__DIR__, 2) . "/partials/header.php"; ?>

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

<?php include dirname(__DIR__, 2) . "/partials/footer.php"; ?>

<!-- ==================== DOCTOR MODAL ==================== -->
<div class="modal" id="doctorModal">
<div class="modal-content">
<span class="close-btn" onclick="closeModal('doctorModal')">&times;</span>
<h2 id="modalTitle"></h2>

<form id="doctorForm">
<input type="hidden" name="DOC_ID" id="DOC_ID">

<label>First Name</label>
<input type="text" id="DOC_FIRST_NAME" name="DOC_FIRST_NAME" required>

<label>Middle Initial</label>
<input type="text" id="DOC_MIDDLE_INIT" name="DOC_MIDDLE_INIT" maxlength="1">

<label>Last Name</label>
<input type="text" id="DOC_LAST_NAME" name="DOC_LAST_NAME" required>

<label>Email</label>
<input type="email" id="DOC_EMAIL" name="DOC_EMAIL" pattern="^[a-zA-Z0-9._%+-]+@gmail\.com$" placeholder="example@gmail.com" required>

<label>Contact</label>
<input type="text" id="DOC_CONTACT_NUM" name="DOC_CONTACT_NUM" pattern="\d{11}" maxlength="11" required>

<label>Specialization</label>
<select id="SPEC_ID" name="SPEC_ID" required>
<?php foreach($specializations as $sp): ?>
<option value="<?= esc($sp['SPEC_ID']) ?>"><?= esc($sp['SPEC_NAME']) ?></option>
<?php endforeach; ?>
</select>

<button class="btn" type="submit">Save</button>
</form>
</div>
</div>

<!-- ==================== USER-CREATION MODAL ==================== -->
<div class="modal" id="userModal">
<div class="modal-content">
<span class="close-btn" onclick="closeModal('userModal')">&times;</span>
<h2>Create Account for <span id="docFullName"></span></h2>

<form id="userForm">
<input type="hidden" name="doc_id" id="userDocId">
<input type="hidden" name="action" value="createDoctorUser">

<label>Username</label>
<input type="text" name="username" required minlength="3">

<label>Password</label>
<input type="password" name="password" required minlength="6">

<div id="userModalErrors"></div>

<button class="btn" type="submit">Create Account</button>
</form>
</div>
</div>

<script>
/* ---------- MODAL HELPERS ---------- */
function showModal(id){ document.getElementById(id).style.display = 'flex'; }
function closeModal(id){ document.getElementById(id).style.display = 'none'; }

/* ---------- DOCTOR MODAL ---------- */
function openAddModal(){
    document.getElementById('modalTitle').innerText = "Add Doctor";
    document.getElementById('doctorForm').reset();
    document.getElementById('DOC_ID').value = "";
    showModal('doctorModal');
}

function openEditModal(d){
    document.getElementById('modalTitle').innerText = "Edit Doctor";
    for (const key in d) {
        const el = document.getElementById(key);
        if (el) el.value = d[key] ?? '';
    }
    showModal('doctorModal');
}

/* ---------- DOCTOR FORM SUBMIT ---------- */
document.getElementById('doctorForm').addEventListener('submit', async e => {
    e.preventDefault();
    const form = new FormData(e.target);
    
    try {
        const res = await fetch(location.pathname, {
            method: "POST",
            headers: { "X-Requested-With": "XMLHttpRequest" },
            body: form
        });
        const json = await res.json();

        if (json.errors) {
            Swal.fire({
                icon: 'error',
                title: 'Validation Error',
                html: json.errors.join("<br>")
            });
            return;
        }

        Swal.fire({
            icon: json.success ? 'success' : 'error',
            title: json.success ? 'Success' : 'Error',
            text: json.message
        }).then(() => {
            if (json.success) {
                closeModal('doctorModal');
                if (json.newDocId) {
                    // NEW DOCTOR → OPEN USER MODAL
                    document.getElementById('docFullName').innerText = json.docName;
                    document.getElementById('userDocId').value = json.newDocId;
                    document.getElementById('userForm').reset();
                    document.getElementById('userModalErrors').innerHTML = '';
                    showModal('userModal');
                } else {
                    location.reload();
                }
            }
        });
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'An error occurred: ' + error.message
        });
    }
});

/* ---------- USER FORM SUBMIT ---------- */
document.getElementById('userForm').addEventListener('submit', async e => {
    e.preventDefault();
    const form = new FormData(e.target);
    
    try {
        const res = await fetch(location.pathname, {
            method: "POST",
            headers: { "X-Requested-With": "XMLHttpRequest" },
            body: form
        });
        const json = await res.json();

        const errDiv = document.getElementById('userModalErrors');
        errDiv.innerHTML = '';

        if (json.errors) {
            Swal.fire({
                icon: 'error',
                title: 'Validation Error',
                html: json.errors.join("<br>")
            });
            return;
        }

        Swal.fire({
            icon: json.success ? 'success' : 'error',
            title: json.success ? 'Success' : 'Error',
            text: json.message
        }).then(() => {
            if (json.success) {
                closeModal('userModal');
                location.reload();
            }
        });
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'An error occurred: ' + error.message
        });
    }
});

/* ---------- DELETE DOCTOR ---------- */
async function deleteDoc(id){
    const result = await Swal.fire({
        title: 'Are you sure?',
        text: "Delete this doctor?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    });

    if(!result.isConfirmed) return;

    try {
        const res = await fetch(`?delete=${id}`, {
            headers: { "X-Requested-With": "XMLHttpRequest" }
        });
        const json = await res.json();

        Swal.fire({
            icon: json.success ? 'success' : 'error',
            title: json.success ? 'Deleted!' : 'Error',
            text: json.message
        }).then(() => {
            if(json.success) location.reload();
        });
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'An error occurred: ' + error.message
        });
    }
}
</script>

</body>
</html>