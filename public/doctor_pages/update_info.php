<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// AUTH CHECK ONLY DOCTORS ALLOWED
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'doctor') {
    header("Location: ../../index.php");
    exit;
}

require_once dirname(__DIR__, 2) . '/classes/Doctor.php';
require_once dirname(__DIR__, 2) . '/classes/Specialization.php';

$doctorObj = new Doctor();
$specObj = new Specialization();

$loggedRole = $_SESSION['role'] ?? "";
$loggedDocId = $_SESSION['DOC_ID'] ?? null;

if ($loggedRole !== "doctor") {
    header("Location: ../index.php");
    exit;
}

/* ---------- AJAX INSERT / UPDATE (uses classes) ---------- */
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

    // Validation (server-side)
    $errors = [];
    if ($data['first'] === '') $errors[] = 'First name required';
    if ($data['last'] === '')  $errors[] = 'Last name required';
    if ($data['contact'] === '') $errors[] = 'Contact required';
    if ($data['email'] === '' || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email required';
    if ($data['spec'] === '') $errors[] = 'Specialization required';

    if (!empty($errors)) {
        echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
        exit;
    }

    // Block editing others (doctors can only update themselves)
    if ($loggedRole === "doctor" && $id !== "" && $id != $loggedDocId) {
        echo json_encode(['success' => false, 'message' => 'Not allowed to update other doctors']);
        exit;
    }

    try {
        if ($id === "") {
            $ok = $doctorObj->insert($data);
            echo json_encode(['success' => (bool)$ok, 'message' => $ok ? 'Doctor added successfully!' : 'Insert failed']);
        } else {
            $ok = $doctorObj->update($id, $data);
            echo json_encode(['success' => (bool)$ok, 'message' => $ok ? 'Profile updated successfully!' : 'Update failed']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

/* ---------- AJAX DELETE BLOCKED FOR DOCTORS ---------- */
if (isset($_GET['delete']) && isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    // doctors cannot delete
    echo json_encode(['success' => false, 'message' => 'Doctors cannot delete accounts']);
    exit;
}

/* ---------- Data for page (use classes) ---------- */
$specializations = $specObj->getAll();

// Logged-in doctor's full record (includes SPEC_NAME if class returns it)
$myself = $doctorObj->getById($loggedDocId);

// Search param for other doctors
$search = trim($_GET['q'] ?? '');
// Get other doctors excluding self
$others = $doctorObj->getAll($loggedDocId, $search);

// helper
function esc($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Doctor Profile - Medical Clinic System</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="../../assets/css/style.css">

<!-- ✅ SweetAlert2 CDN -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

<style>
/* FORMAL PROFILE STYLES */
.profile-wrapper {
    max-width: 900px;
    margin: 0 auto;
}

.profile-header {
    background: var(--white);
    border-radius: 15px;
    padding: 40px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}

.profile-header-content {
    display: flex;
    gap: 40px;
    align-items: flex-start;
}

.profile-avatar {
    width: 140px;
    height: 140px;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    border-radius: 12px;
    display: flex;
    justify-content: center;
    align-items: center;
    color: var(--white);
    font-size: 56px;
    font-weight: 700;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(0,35,57,0.2);
}

.profile-details {
    flex: 1;
}

.profile-name {
    font-size: 32px;
    color: var(--primary);
    font-weight: 700;
    margin: 0 0 8px 0;
    letter-spacing: -0.5px;
}

.profile-role {
    font-size: 16px;
    color: var(--secondary);
    font-weight: 600;
    margin-bottom: 25px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.profile-info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
    margin-bottom: 25px;
}

.info-item {
    display: flex;
    flex-direction: column;
}

.info-label {
    font-size: 13px;
    color: #666;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 6px;
}

.info-value {
    font-size: 16px;
    color: var(--primary);
    font-weight: 500;
}

.profile-actions {
    display: flex;
    gap: 12px;
    margin-top: 30px;
    padding-top: 25px;
    border-top: 2px solid #f0f0f0;
}

.btn-edit-profile {
    background: var(--primary);
    color: var(--white);
    border: none;
    padding: 12px 30px;
    border-radius: 8px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    letter-spacing: 0.3px;
}

.btn-edit-profile:hover {
    background: #014769;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,35,57,0.3);
}

/* FORMAL MODAL STYLES */
#doctorModal .modal-content {
    max-width: 650px;
    padding: 0;
    border-radius: 12px;
}

.modal-header {
    background: var(--primary);
    color: var(--white);
    padding: 25px 35px;
    border-radius: 12px 12px 0 0;
    position: relative;
}

.modal-header h2 {
    color: var(--white);
    font-size: 24px;
    margin: 0;
    font-weight: 600;
    letter-spacing: -0.3px;
}

.modal-header .close-btn {
    position: absolute;
    right: 20px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 28px;
    color: var(--white);
    cursor: pointer;
    opacity: 0.8;
    transition: opacity 0.2s;
}

.modal-header .close-btn:hover {
    opacity: 1;
}

.modal-body {
    padding: 35px;
}

#doctorForm {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group.full-width {
    grid-column: span 2;
}

.form-group label {
    font-size: 13px;
    color: var(--primary);
    font-weight: 600;
    margin-bottom: 8px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.form-group input,
.form-group select {
    padding: 12px 16px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 15px;
    transition: all 0.3s ease;
    font-family: Georgia, serif;
}

.form-group input:focus,
.form-group select:focus {
    outline: none;
    border-color: var(--secondary);
    box-shadow: 0 0 0 3px rgba(109, 169, 198, 0.1);
}

.form-actions {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
    margin-top: 10px;
    padding-top: 25px;
    border-top: 2px solid #f0f0f0;
}

.btn-cancel {
    background: #f5f5f5;
    color: var(--primary);
    border: none;
    padding: 12px 28px;
    border-radius: 8px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-cancel:hover {
    background: #e0e0e0;
}

.btn-save {
    background: var(--primary);
    color: var(--white);
    border: none;
    padding: 12px 32px;
    border-radius: 8px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-save:hover {
    background: #014769;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,35,57,0.3);
}

/* RESPONSIVE */
@media(max-width: 768px) {
    .profile-header-content {
        flex-direction: column;
        align-items: center;
        text-align: center;
    }
    
    .profile-info-grid {
        grid-template-columns: 1fr;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .form-group.full-width {
        grid-column: span 1;
    }
}
</style>
</head>
<body>

<!-- NAVBAR -->
<?php include dirname(__DIR__, 2) . "/partials/header.php"; ?>

<main>
    <div class="profile-wrapper">
        <h1>Doctor Profile</h1>
        
        <div class="profile-header">
            <div class="profile-header-content">
                <!-- AVATAR SECTION -->
                <div class="profile-avatar">
                    <?= strtoupper(substr($myself['DOC_FIRST_NAME'] ?? 'D',0,1)) ?>
                </div>

                <!-- INFO SECTION -->
                <div class="profile-details">
                    <h2 class="profile-name">
                        <?php
                            $mid = trim($myself['DOC_MIDDLE_INIT'] ?? '');
                            $midDot = $mid !== '' ? esc($mid) . '. ' : '';
                            echo esc($myself['DOC_FIRST_NAME'] ?? '') . ' ' . $midDot . esc($myself['DOC_LAST_NAME'] ?? '');
                        ?>
                    </h2>
                    <div class="profile-role">
                        <?= esc($myself['SPEC_NAME'] ?? 'Medical Doctor') ?>
                    </div>

                    <div class="profile-info-grid">
                        <div class="info-item">
                            <div class="info-label">Doctor ID</div>
                            <div class="info-value">#<?= esc($myself['DOC_ID']) ?></div>
                        </div>

                        <div class="info-item">
                            <div class="info-label">Specialization</div>
                            <div class="info-value"><?= esc($myself['SPEC_NAME'] ?? $myself['SPEC_ID'] ?? 'N/A') ?></div>
                        </div>

                        <div class="info-item">
                            <div class="info-label">Contact Number</div>
                            <div class="info-value"><?= esc($myself['DOC_CONTACT_NUM']) ?></div>
                        </div>

                        <div class="info-item">
                            <div class="info-label">Email Address</div>
                            <div class="info-value"><?= esc($myself['DOC_EMAIL']) ?></div>
                        </div>
                    </div>

                    <div class="profile-actions">
                        <button class="btn-edit-profile" 
                            onclick='openEditModal(<?= json_encode($myself, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>
                            Edit Profile Information
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- FORMAL MODAL -->
<div class="modal" id="doctorModal" aria-hidden="true">
    <div class="modal-content" role="dialog" aria-labelledby="modalTitle">
        <div class="modal-header">
            <h2 id="modalTitle">Edit Profile Information</h2>
            <span class="close-btn" onclick="closeModal()">&times;</span>
        </div>

        <div class="modal-body">
            <form id="doctorForm">
                <input type="hidden" id="DOC_ID" name="DOC_ID">

                <div class="form-row">
                    <div class="form-group">
                        <label>First Name *</label>
                        <input type="text" id="DOC_FIRST_NAME" name="DOC_FIRST_NAME" required>
                    </div>

                    <div class="form-group">
                        <label>Middle Initial</label>
                        <input type="text" id="DOC_MIDDLE_INIT" name="DOC_MIDDLE_INIT" maxlength="2">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group full-width">
                        <label>Last Name *</label>
                        <input type="text" id="DOC_LAST_NAME" name="DOC_LAST_NAME" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group full-width">
                        <label>Email Address *</label>
                        <input type="email" id="DOC_EMAIL" name="DOC_EMAIL" 
                               placeholder="doctor@example.com" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group full-width">
                        <label>Contact Number *</label>
                        <input type="text" id="DOC_CONTACT_NUM" name="DOC_CONTACT_NUM" 
                               placeholder="09XXXXXXXXX" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group full-width">
                        <label>Specialization *</label>
                        <select id="SPEC_ID" name="SPEC_ID" required>
                            <option value="">Select Specialization</option>
                            <?php foreach($specializations as $sp): ?>
                                <option value="<?= esc($sp['SPEC_ID']) ?>">
                                    <?= esc($sp['SPEC_NAME']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn-save" id="saveButton" onclick="closeModal()" >Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- FOOTER -->
<?php include dirname(__DIR__, 2) . "/partials/footer.php"; ?>

<script>
// ✅ Get primary color from CSS variable
const primaryColor = getComputedStyle(document.documentElement).getPropertyValue('--primary').trim() || '#002339';

function showModal(){ 
    document.getElementById('doctorModal').style.display = 'flex'; 
}

function closeModal(){ 
    document.getElementById('doctorModal').style.display = 'none'; 
}

// Helper to enable/disable form
function setFormDisabled(disabled) {
    document.querySelectorAll('#doctorForm input, #doctorForm select').forEach(el => {
        el.disabled = disabled;
    });
    document.getElementById('saveButton').style.display = disabled ? 'none' : 'block';
}

function openAddModal(){
    document.getElementById('modalTitle').innerText = 'Add Doctor';
    document.getElementById('doctorForm').reset();
    document.getElementById('DOC_ID').value = "";

    setFormDisabled(false);
    showModal();
}

function openEditModal(data){
    document.getElementById('modalTitle').innerText = 'Edit Profile Information';
    fillForm(data);

    setFormDisabled(false);
    showModal();
}

function openViewModal(data){
    document.getElementById('modalTitle').innerText = 'View Doctor Details';
    fillForm(data);

    setFormDisabled(true);
    showModal();
}

function fillForm(d){
    document.getElementById('DOC_ID').value = d.DOC_ID || '';
    document.getElementById('DOC_FIRST_NAME').value = d.DOC_FIRST_NAME || '';
    document.getElementById('DOC_MIDDLE_INIT').value = d.DOC_MIDDLE_INIT || '';
    document.getElementById('DOC_LAST_NAME').value = d.DOC_LAST_NAME || '';
    document.getElementById('DOC_EMAIL').value = d.DOC_EMAIL || '';
    document.getElementById('DOC_CONTACT_NUM').value = d.DOC_CONTACT_NUM || '';
    document.getElementById('SPEC_ID').value = d.SPEC_ID || '';
}

// ✅ Submit Add / Update with SweetAlert
document.getElementById('doctorForm').addEventListener('submit', async function(e){
    e.preventDefault();
    const form = new FormData(this);

    try {
        const res = await fetch(location.pathname, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: form
        });

        const json = await res.json();

        if(json.success){
            await Swal.fire({
                icon: 'success',
                title: 'Profile Updated!',
                text: json.message,
                confirmButtonColor: primaryColor,
                timer: 2000,
                showConfirmButton: true
            });
            closeModal();
            location.reload();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Update Failed',
                text: json.message || 'Failed to update profile',
                confirmButtonColor: primaryColor
            });
        }
    } catch (err) {
        console.error('Update error:', err);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Something went wrong while updating your profile',
            confirmButtonColor: primaryColor
        });
    }
});

// Click backdrop to close
document.getElementById('doctorModal').addEventListener('click', function(e){
    if(e.target === this) closeModal();
});

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('doctorModal');
    if (event.target === modal) {
        closeModal();
    }
}
</script>

</body>
</html>