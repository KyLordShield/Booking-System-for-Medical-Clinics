<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// ---------- AUTH CHECK ----------
if (
    empty($_SESSION['USER_IS_SUPERADMIN']) ||
    $_SESSION['USER_IS_SUPERADMIN'] != 1 ||
    $_SESSION['role'] !== 'admin'
) {
    header("Location: ../../index.php");
    exit;
}

require_once dirname(__DIR__, 2) . '/classes/Patient.php';
require_once dirname(__DIR__, 2) . '/classes/User.php';

$patientObj = new Patient();
$userObj    = new User();

/* ---------- AJAX INSERT / UPDATE ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json');

    $action = $_POST['action'] ?? '';

    /* ---------- CREATE PATIENT USER ---------- */
    if ($action === 'createPatientUser') {
        $pat_id   = (int)($_POST['pat_id'] ?? 0);
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
            // 1. Patient must exist
            $patient = $patientObj->getPatientById($pat_id);
            if (!$patient) {
                echo json_encode(['success' => false, 'message' => 'Patient not found.']);
                exit;
            }

            // 2. Patient must NOT already have a user account
            if ($userObj->existsByEntity('Patient', $pat_id)) {
                echo json_encode(['success' => false, 'message' => 'This patient already has a user account.']);
                exit;
            }

            // 3. Username must be unique
            if ($userObj->isUsernameTaken($username)) {
                echo json_encode(['success' => false, 'message' => 'Username already exists.']);
                exit;
            }

            // 4. Create the user
            $result = $userObj->createForEntity('Patient', $pat_id, $username, $password, 0);
            
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

    /* ---------- PATIENT INSERT / UPDATE ---------- */
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
    if ($data['email'] === '' || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email required';
    }
    if ($data['address'] === '') $errors[] = 'Address required';

    if (!empty($errors)) {
        echo json_encode(['success' => false, 'errors' => $errors]);
        exit;
    }

    try {
        if ($id === "") {
            // INSERT - Check for duplicates using Patient methods
            if (method_exists($patientObj, 'checkDuplicateEmail')) {
                if ($patientObj->checkDuplicateEmail($data['email'])) {
                    echo json_encode(['success' => false, 'message' => 'Email is already in use!']);
                    exit;
                }
            }
            
            if (method_exists($patientObj, 'checkDuplicateContact')) {
                if ($patientObj->checkDuplicateContact($data['contact'])) {
                    echo json_encode(['success' => false, 'message' => 'Contact number is already in use!']);
                    exit;
                }
            }

            $newId = $patientObj->insertPatient($data);
            if ($newId) {
                $fullName = trim("{$data['first']} " . ($data['middle'] ? $data['middle'].'. ' : '') . $data['last']);
                echo json_encode([
                    'success' => true,
                    'message' => 'Patient added',
                    'newPatId' => $newId,
                    'patName'  => $fullName
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Insert failed']);
            }
        } else {
            // UPDATE - Check for duplicates (excluding current patient)
            if (method_exists($patientObj, 'checkDuplicateEmail')) {
                if ($patientObj->checkDuplicateEmail($data['email'], $id)) {
                    echo json_encode(['success' => false, 'message' => 'Email is already in use by another patient!']);
                    exit;
                }
            }
            
            if (method_exists($patientObj, 'checkDuplicateContact')) {
                if ($patientObj->checkDuplicateContact($data['contact'], $id)) {
                    echo json_encode(['success' => false, 'message' => 'Contact number is already in use by another patient!']);
                    exit;
                }
            }

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
<title>Admin – Patient Management</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="/Booking-System-For-Medical-Clinics/assets/css/style.css">
<style>
    .modal { display: none; justify-content: center; align-items: center; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,.5); z-index: 1000; }
    .modal-content { background: #fff; padding: 20px; border-radius: 8px; max-width: 420px; width: 90%; position: relative; }
    .close-btn { position: absolute; top: 8px; right: 12px; font-size: 24px; cursor: pointer; }
    .error { color: #d00; margin-top: 5px; font-size: 0.9rem; }
    #userModalErrors { margin-top: 10px; }
</style>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<?php include dirname(__DIR__, 2) . "/partials/header.php"; ?>

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

<?php include dirname(__DIR__, 2) . "/partials/footer.php"; ?>

<!-- ==================== PATIENT MODAL ==================== -->
<div class="modal" id="patientModal">
<div class="modal-content">
<span class="close-btn" onclick="closeModal('patientModal')">&times;</span>
<h2 id="modalTitle"></h2>

<form id="patientForm">
<input type="hidden" name="PAT_ID" id="PAT_ID">

<label>First Name</label>
<input type="text" id="PAT_FIRST_NAME" name="PAT_FIRST_NAME" required>

<label>Middle Initial</label>
<input type="text" id="PAT_MIDDLE_INIT" name="PAT_MIDDLE_INIT" maxlength="1">

<label>Last Name</label>
<input type="text" id="PAT_LAST_NAME" name="PAT_LAST_NAME" required>

<label>Date of Birth</label>
<input type="date" id="PAT_DOB" name="PAT_DOB" required>

<label>Gender</label>
<select id="PAT_GENDER" name="PAT_GENDER" required>
    <option value="">Select</option>
    <option value="Male">Male</option>
    <option value="Female">Female</option>
</select>

<label>Contact</label>
<input type="text" id="PAT_CONTACT_NUM" name="PAT_CONTACT_NUM" pattern="\d{11}" maxlength="11" required>

<label>Email</label>
<input type="email" id="PAT_EMAIL" name="PAT_EMAIL" pattern="^[a-zA-Z0-9._%+-]+@gmail\.com$" placeholder="example@gmail.com" required>

<label>Address</label>
<input type="text" id="PAT_ADDRESS" name="PAT_ADDRESS" required>

<button class="btn" type="submit">Save</button>
</form>
</div>
</div>

<!-- ==================== USER CREATION MODAL ==================== -->
<div class="modal" id="userModal">
<div class="modal-content">
<span class="close-btn" onclick="closeModal('userModal')">&times;</span>
<h2>Create Account for <span id="patFullName"></span></h2>

<form id="userForm">
<input type="hidden" name="pat_id" id="userPatId">
<input type="hidden" name="action" value="createPatientUser">

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
function showModal(id) { document.getElementById(id).style.display = 'flex'; }
function closeModal(id) { document.getElementById(id).style.display = 'none'; }

/* ---------- PATIENT MODAL ---------- */
function openAddModal(){
    document.getElementById('modalTitle').innerText = "Add Patient";
    document.getElementById('patientForm').reset();
    document.getElementById('PAT_ID').value = "";
    showModal('patientModal');
}

function openEditModal(p){
    document.getElementById('modalTitle').innerText = "Edit Patient";
    for(const key in p){
        const el = document.getElementById(key);
        if(el) el.value = p[key] ?? '';
    }
    showModal('patientModal');
}

/* ---------- PATIENT FORM SUBMIT ---------- */
document.getElementById('patientForm').addEventListener('submit', async e => {
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
                closeModal('patientModal');
                if (json.newPatId) {
                    document.getElementById('patFullName').innerText = json.patName;
                    document.getElementById('userPatId').value = json.newPatId;
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

/* ---------- DELETE PATIENT ---------- */
async function deletePatient(id){
    const result = await Swal.fire({
        title: 'Are you sure?',
        text: "Delete this patient?",
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