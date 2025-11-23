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

// Escape helper
function esc($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

$loggedStaffId = $_SESSION['STAFF_ID'];

// Fetch logged-in staff
$stmt = $conn->prepare("SELECT * FROM staff WHERE STAFF_ID = ?");
$stmt->execute([$loggedStaffId]);
$myself = $stmt->fetch(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Staff Profile - Medical Clinic System</title>
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
#staffModal .modal-content {
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

#staffForm {
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

<?php include dirname(__DIR__, 2) . "/partials/header.php"; ?>

<main>
    <div class="profile-wrapper">
        <h1>Staff Profile</h1>
        
        <div class="profile-header">
            <div class="profile-header-content">
                <!-- AVATAR SECTION -->
                <div class="profile-avatar">
                    <?= strtoupper(substr($myself['STAFF_FIRST_NAME'],0,1)) ?>
                </div>

                <!-- INFO SECTION -->
                <div class="profile-details">
                    <h2 class="profile-name">
                        <?php
                            $mid = trim($myself['STAFF_MIDDLE_INIT'] ?? '');
                            $midDot = $mid !== '' ? esc($mid).'. ' : '';
                            echo esc($myself['STAFF_FIRST_NAME']).' '.$midDot.esc($myself['STAFF_LAST_NAME']);
                        ?>
                    </h2>
                    <div class="profile-role">Medical Clinic Staff</div>

                    <div class="profile-info-grid">
                        <div class="info-item">
                            <div class="info-label">Staff ID</div>
                            <div class="info-value">#<?= esc($myself['STAFF_ID']) ?></div>
                        </div>

                        <div class="info-item">
                            <div class="info-label">Contact Number</div>
                            <div class="info-value"><?= esc($myself['STAFF_CONTACT_NUM']) ?></div>
                        </div>

                        <div class="info-item" style="grid-column: span 2;">
                            <div class="info-label">Email Address</div>
                            <div class="info-value"><?= esc($myself['STAFF_EMAIL']) ?></div>
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

<?php include dirname(__DIR__, 2) . "/partials/footer.php"; ?>

<!-- FORMAL MODAL -->
<div class="modal" id="staffModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Edit Profile Information</h2>
            <span class="close-btn" onclick="closeModal()">&times;</span>
        </div>

        <div class="modal-body">
            <form id="staffForm">
                <input type="hidden" name="staff_id" id="staff_id">

                <div class="form-row">
                    <div class="form-group">
                        <label>First Name *</label>
                        <input type="text" id="fname" name="STAFF_FIRST_NAME" required>
                    </div>

                    <div class="form-group">
                        <label>Middle Initial</label>
                        <input type="text" id="mname" name="STAFF_MIDDLE_INIT" maxlength="2">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group full-width">
                        <label>Last Name *</label>
                        <input type="text" id="lname" name="STAFF_LAST_NAME" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group full-width">
                        <label>Contact Number *</label>
                        <input type="text" id="phone" name="STAFF_CONTACT_NUM" 
                               pattern="\d{11}" maxlength="11" 
                               placeholder="09XXXXXXXXX" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group full-width">
                        <label>Email Address (Gmail Only) *</label>
                        <input type="email" id="email" name="STAFF_EMAIL"
                               pattern="^[a-zA-Z0-9._%+-]+@gmail\.com$"
                               placeholder="example@gmail.com"
                               required>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn-save" onclick="closeModal()">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// ✅ Get primary color from CSS variable
const primaryColor = getComputedStyle(document.documentElement).getPropertyValue('--primary').trim() || '#002339';

function openEditModal(staff){
    document.getElementById("staff_id").value = staff.STAFF_ID;
    document.getElementById("fname").value = staff.STAFF_FIRST_NAME;
    document.getElementById("mname").value = staff.STAFF_MIDDLE_INIT || '';
    document.getElementById("lname").value = staff.STAFF_LAST_NAME;
    document.getElementById("phone").value = staff.STAFF_CONTACT_NUM;
    document.getElementById("email").value = staff.STAFF_EMAIL;

    document.getElementById("staffModal").style.display = "flex";
}

function closeModal(){
    document.getElementById("staffModal").style.display = "none";
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById("staffModal");
    if (event.target === modal) {
        closeModal();
    }
}

// ✅ AJAX SAVE WITH SWEETALERT
document.getElementById("staffForm").addEventListener("submit", async (e)=>{
    e.preventDefault();

    const formData = new FormData(e.target);
    
    try {
        const res = await fetch("staff_manage.php", {
            method: "POST",
            body: formData,
            headers: { "X-Requested-With": "XMLHttpRequest" } 
        });

        const data = await res.json();
        
        if(data.success){
            await Swal.fire({
                icon: 'success',
                title: 'Profile Updated!',
                text: data.message,
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
                text: data.message || 'Failed to update profile',
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
</script>

</body>
</html>