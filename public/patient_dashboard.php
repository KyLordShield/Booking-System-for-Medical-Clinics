<?php
session_start();
require_once __DIR__ . '/../classes/Patient.php';
require_once __DIR__ . '/../classes/Appointment.php';

// Verify patient session
if (!isset($_SESSION['PAT_ID']) || $_SESSION['role'] !== 'patient') {
    header("Location: ../index.php");
    exit;
}

$pat_id = $_SESSION['PAT_ID'];

$patientObj = new Patient();
$appointmentObj = new Appointment();

$message = "";

// Handle update form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_info'])) {
    $fname   = trim($_POST['PAT_FIRST_NAME']);
    $mname   = trim($_POST['PAT_MIDDLE_INIT']);
    $lname   = trim($_POST['PAT_LAST_NAME']);
    $dob     = trim($_POST['PAT_DOB']);
    $gender  = trim($_POST['PAT_GENDER']);
    $contact = trim($_POST['PAT_CONTACT_NUM']);
    $email   = trim($_POST['PAT_EMAIL']);
    $address = trim($_POST['PAT_ADDRESS']);

    $message = $patientObj->updatePatient($pat_id, $fname, $mname, $lname, $dob, $gender, $contact, $email, $address);
    $patient = $patientObj->getPatientById($pat_id);
} else {
    $patient = $patientObj->getPatientById($pat_id);
}

// Fetch appointments
$appointments = $appointmentObj->getAppointmentsByPatient($pat_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Patient Dashboard | Medicina</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- ✅ Tailwind CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- ✅ Global Custom CSS -->
    <link rel="stylesheet" href="/Booking-System-For-Medical-Clinics/assets/css/style.css">
</head>
<!-- Applying doctor's dashboard body styles -->
<body class="bg-[var(--secondary)] min-h-screen flex flex-col font-[Georgia]">

    <!-- ✅ NAVIGATION BAR - APPLIED DOCTOR'S STYLES -->
    <div class="navbar flex justify-between items-center px-10 py-5 bg-[var(--primary)] rounded-b-[35px] shadow-lg">
        <div class="navbar-brand flex items-center text-white text-2xl font-bold">
            <img src="https://cdn-icons-png.flaticon.com/512/3304/3304567.png" alt="Medicina Logo" class="w-11 mr-3">
            Medicina
        </div>

        <div class="nav-links flex gap-6">
            <!-- Patient Navigation Links -->
            <a class="active text-white font-semibold hover:text-[#bfe1eb] transition" href="/Booking-System-For-Medical-Clinics/public/patient_dashboard.php">Home</a>
             <a class="text-white font-semibold hover:text-[#bfe1eb] transition" href="/Booking-System-For-Medical-Clinics/public/patient_pages/view_patients.php">Patients</a>
            <a class="text-white font-semibold hover:text-[#bfe1eb] transition" href="/Booking-System-For-Medical-Clinics/public/patient_pages/create_appointment.php">Book Appointment</a>
            <a class="text-white font-semibold hover:text-[#bfe1eb] transition" href="/Booking-System-For-Medical-Clinics/index.php">Log out</a>
        </div>
    </div>

    <!-- ✅ MAIN CONTENT - APPLIED DOCTOR'S STYLES -->
    <main class="flex flex-col flex-1 px-20 py-16 w-full"> 
        
        <?php if ($message): ?>
            <!-- Assuming 'message' class is defined in style.css for alerts -->
            <div class="message p-4 mb-4 rounded-lg bg-green-100 text-green-800 font-medium">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <!-- Patient Info Section - Styled to mimic Doctor's Profile Card/Info Layout -->
        <div class="flex items-start mb-10 w-full">
            
            <!-- Profile Card -->
            <div class="profile-card bg-[var(--light)] w-[250px] h-[250px] rounded-[40px] flex justify-center items-center shadow-md flex-shrink-0">
                <img src="https://cdn-icons-png.flaticon.com/512/3304/3304567.png" alt="Patient Icon" class="w-[130px]">
            </div>

            <!-- Patient Info -->
            <div class="patient-info ml-14 flex-1">
                <h1 class="text-[45px] font-bold text-[var(--primary)]">Welcome,</h1>
                <p class="text-[20px] mt-1 text-gray-800"><?= htmlspecialchars(($patient['PAT_FIRST_NAME'] ?? '') . ' ' . ($patient['PAT_LAST_NAME'] ?? '')) ?></p>
                <p class="text-[18px] mt-1 text-gray-700">Patient ID: <?= htmlspecialchars($pat_id) ?></p>

                <!-- Patient Details -->
                <div id="viewInfo" class="patient-details mt-4 text-lg">
                    <p><strong class="font-semibold">Email:</strong> <?= htmlspecialchars($patient['PAT_EMAIL'] ?? '') ?></p>
                    <p><strong class="font-semibold">Contact:</strong> <?= htmlspecialchars($patient['PAT_CONTACT_NUM'] ?? '') ?></p>
                    <p><strong class="font-semibold">Address:</strong> <?= htmlspecialchars($patient['PAT_ADDRESS'] ?? '') ?></p>
                    <p><strong class="font-semibold">Gender:</strong> <?= htmlspecialchars($patient['PAT_GENDER'] ?? '') ?></p>
                    <p><strong class="font-semibold">Date of Birth:</strong> <?= htmlspecialchars($patient['PAT_DOB'] ?? '') ?></p>
                </div>
                
                <div class="patient-actions mt-5 flex gap-4">
                    <!-- Update Button: Uses doctor's update button styles -->
                    <button id="editInfo" class="btn-update bg-[var(--light)] px-8 py-2 rounded-full font-semibold hover:bg-[#bfe1eb] transition">
                        🖋 UPDATE INFO
                    </button>
                    <!-- Create Appointment Button: Mimics the doctor's general button style -->
                    <a href="patient_pages/create_appointment.php" class="bg-[var(--primary)] text-white px-8 py-2 rounded-full font-semibold hover:bg-sky-600 transition flex items-center">
                        ➕ Create Appointment
                    </a>
                </div>
            </div>
        </div>

        <!-- Update Form - Modal -->
        <form id="updateForm" class="modal fixed z-10 left-0 top-0 w-full h-full overflow-auto bg-black bg-opacity-40 hidden justify-center items-center" method="post" action="">
            <div class="modal-content bg-white p-8 rounded-xl w-4/5 max-w-lg shadow-2xl relative">
                <h2 class="text-2xl font-bold text-[var(--primary)] mb-4">Update Info</h2>
                <span class="close-btn absolute top-4 right-6 text-xl font-bold cursor-pointer" id="cancelEdit">❌</span>

                <input type="text" name="PAT_FIRST_NAME" value="<?= htmlspecialchars($patient['PAT_FIRST_NAME'] ?? '') ?>" placeholder="First Name" required class="w-full p-3 mb-3 border border-gray-300 rounded-lg">
                <input type="text" name="PAT_MIDDLE_INIT" value="<?= htmlspecialchars($patient['PAT_MIDDLE_INIT'] ?? '') ?>" placeholder="Middle Initial" class="w-full p-3 mb-3 border border-gray-300 rounded-lg">
                <input type="text" name="PAT_LAST_NAME" value="<?= htmlspecialchars($patient['PAT_LAST_NAME'] ?? '') ?>" placeholder="Last Name" required class="w-full p-3 mb-3 border border-gray-300 rounded-lg">
                <input type="email" name="PAT_EMAIL" value="<?= htmlspecialchars($patient['PAT_EMAIL'] ?? '') ?>" placeholder="Email" required class="w-full p-3 mb-3 border border-gray-300 rounded-lg">
                <input type="text" name="PAT_CONTACT_NUM" value="<?= htmlspecialchars($patient['PAT_CONTACT_NUM'] ?? '') ?>" placeholder="Contact Number" required class="w-full p-3 mb-3 border border-gray-300 rounded-lg">
                <input type="text" name="PAT_ADDRESS" value="<?= htmlspecialchars($patient['PAT_ADDRESS'] ?? '') ?>" placeholder="Address" required class="w-full p-3 mb-3 border border-gray-300 rounded-lg">
                <select name="PAT_GENDER" required class="w-full p-3 mb-3 border border-gray-300 rounded-lg appearance-none">
                    <option value="Male" <?= ($patient['PAT_GENDER'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                    <option value="Female" <?= ($patient['PAT_GENDER'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                </select>
                <input type="date" name="PAT_DOB" value="<?= htmlspecialchars($patient['PAT_DOB'] ?? '') ?>" required class="w-full p-3 mb-3 border border-gray-300 rounded-lg">
                <button type="submit" name="update_info" class="create-btn w-full p-3 mt-4 bg-[var(--primary)] text-white font-semibold rounded-lg hover:bg-sky-600 transition">
                    💾 Save Changes
                </button>
            </div>
        </form>


        <!-- Appointments Section - STYLED TO MATCH REFERENCE TABLE -->
        <div class="appointments w-full mt-6">
            <h2 class="text-3xl font-bold text-[var(--primary)] mb-4">Your Appointments</h2>
            <?php if (!empty($appointments)): ?>
                <!-- Table Container Styled from Reference -->
                <div class="table-container bg-[var(--light)] p-6 rounded-[25px] shadow-md">
                    <table class="w-full border-collapse text-[var(--primary)]">
                        <thead>
                            <!-- Header Row Styled from Reference -->
                            <tr class="border-b border-gray-300">
                                <!-- Table Headers Styled from Reference -->
                                <th class="py-3 px-4 text-left text-[var(--primary)]">ID</th>
                                <th class="py-3 px-4 text-left text-[var(--primary)]">Date</th>
                                <th class="py-3 px-4 text-left text-[var(--primary)]">Time</th>
                                <th class="py-3 px-4 text-left text-[var(--primary)]">Service</th>
                                <th class="py-3 px-4 text-left text-[var(--primary)]">Doctor</th>
                                <th class="py-3 px-4 text-left text-[var(--primary)]">Status</th>
                                <th class="py-3 px-4 text-left text-[var(--primary)]">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($appointments as $appt): ?>
                            <!-- Table Body Rows Styled from Reference -->
                            <tr data-doc-id="<?= $appt['DOC_ID'] ?>" class="border-b border-gray-300 hover:bg-gray-50 transition">
                                <td class="py-3 px-4"><?= htmlspecialchars($appt['APPT_ID']) ?></td>
                                <td class="py-3 px-4"><?= htmlspecialchars($appt['APPT_DATE']) ?></td>
                                <td class="py-3 px-4"><?= htmlspecialchars($appt['APPT_TIME']) ?></td>
                                <td class="py-3 px-4"><?= htmlspecialchars($appt['SERV_NAME'] ?? 'N/A') ?></td>
                                <td class="py-3 px-4"><?= htmlspecialchars($appt['DOCTOR_NAME'] ?? 'N/A') ?></td>
                                <td class="py-3 px-4"><?= htmlspecialchars($appt['STAT_NAME'] ?? 'Pending') ?></td>
                                <td class="py-3 px-4 flex gap-2">
                                    <!-- Using reference button style, maintaining distinction -->
                                    <button class="appt-action appt-update px-4 py-1 rounded-full text-white bg-yellow-500 hover:bg-yellow-600 transition text-sm font-medium">✏ Update</button>
                                    <button class="appt-action appt-cancel px-4 py-1 rounded-full text-white bg-red-500 hover:bg-red-600 transition text-sm font-medium" 
                                            data-appt-id="<?= $appt['APPT_ID'] ?>" 
                                            <?= in_array($appt['STAT_NAME'], ['Completed', 'Cancelled']) ? 'disabled style="opacity:0.5;cursor:not-allowed;"' : '' ?>>
                                                ❌ Cancel
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                            <!-- Empty row to simulate 'no more records found' look if needed -->
                            <tr class="hover:bg-gray-50">
                                <td colspan="7" class="py-6 text-center text-gray-500">End of appointment list.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-lg text-gray-700">You currently have no appointments.</p>
            <?php endif; ?>
        </div>
    </main>

    <!-- ✅ FOOTER - APPLIED DOCTOR'S STYLES -->
    <footer class="bg-[var(--primary)] text-white text-center py-4 text-sm rounded-t-[35px]">
        &copy; 2025 Medicina Clinic | All Rights Reserved
    </footer>

<!-- Existing JavaScript logic remains here -->
<script>
// --------------------- TOGGLE UPDATE INFO FORM ---------------------
const editBtn = document.getElementById('editInfo');
const cancelBtn = document.getElementById('cancelEdit');
const viewDiv = document.getElementById('viewInfo');
const formDiv = document.getElementById('updateForm');

editBtn.addEventListener('click', () => {
    viewDiv.style.display = 'none';
    formDiv.style.display = 'flex'; // modal flex center
    editBtn.style.display = 'none';
});

cancelBtn.addEventListener('click', () => {
    formDiv.style.display = 'none';
    viewDiv.style.display = 'block';
    editBtn.style.display = 'inline-block';
});

// --------------------- DISABLE CANCEL & UPDATE BUTTONS ON PAGE LOAD ---------------------
document.querySelectorAll('tr').forEach(row => {
    const statusCell = row.querySelector('td:nth-child(6)');
    const cancelBtn = row.querySelector('.appt-cancel');
    const updateBtn = row.querySelector('.appt-update');

    if (statusCell && cancelBtn && updateBtn) {
        const status = statusCell.textContent.trim();
        if (status === 'Cancelled' || status === 'Completed') {
            cancelBtn.disabled = true;
            updateBtn.disabled = true;
            cancelBtn.style.opacity = 0.5;
            cancelBtn.style.cursor = 'not-allowed';
            updateBtn.style.opacity = 0.5;
            updateBtn.style.cursor = 'not-allowed';
        }
    }
});

// --------------------- CANCEL APPOINTMENT ---------------------
document.querySelectorAll('.appt-cancel').forEach(btn => {
    btn.addEventListener('click', function() {
        const row = btn.closest('tr');
        const apptId = btn.getAttribute('data-appt-id');

        const isConfirmed = confirmCustom('Are you sure you want to cancel appointment ID ' + apptId + '?');
        if (!isConfirmed) return;

        fetch('../ajax/cancel_appointment.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'APPT_ID=' + encodeURIComponent(apptId)
        })
        .then(res => res.text())
        .then(msg => {
            alertCustom(msg);
            const statusCell = row.querySelector('td:nth-child(6)');
            if (statusCell) statusCell.textContent = 'Cancelled';

            btn.disabled = true;
            btn.style.opacity = 0.5;
            btn.style.cursor = 'not-allowed';

            const updateBtn = row.querySelector('.appt-update');
            if (updateBtn) {
                updateBtn.disabled = true;
                updateBtn.style.opacity = 0.5;
                updateBtn.style.cursor = 'not-allowed';
            }
        })
        .catch(() => alertCustom('Error cancelling appointment.'));
    });
});

// --------------------- RESCHEDULE APPOINTMENT ---------------------
document.querySelectorAll('.appt-update').forEach(btn => {
    btn.addEventListener('click', function() {
        if (btn.disabled) return; // skip disabled

        const row = btn.closest('tr');
        document.querySelectorAll('.reschedule-row').forEach(r => r.remove());

        const apptId = row.querySelector('td').textContent;
        const docId = row.dataset.docId;

        const rescheduleRow = document.createElement('tr');
        rescheduleRow.classList.add('reschedule-row', 'bg-gray-100', 'border-b', 'border-gray-300');
        rescheduleRow.innerHTML = `
            <td colspan="7" class="py-4 px-4">
                <form class="reschedule-form flex flex-wrap gap-4 items-center justify-start text-gray-800">
                    <label class="font-medium text-sm">New Date:</label>
                    <input type="date" name="APPT_DATE" min="<?= date('Y-m-d') ?>" required class="p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[var(--primary)] outline-none">
                    <label class="font-medium text-sm">New Time:</label>
                    <select name="APPT_TIME" required class="p-2 border border-gray-300 rounded-lg appearance-none focus:ring-2 focus:ring-[var(--primary)] outline-none">
                        <option value="">-- Choose Time --</option>
                    </select>
                    <button type="submit" class="bg-[var(--primary)] text-white py-2 px-4 rounded-full font-medium hover:bg-sky-600 transition">Save Reschedule</button>
                    <button type="button" class="cancel-reschedule bg-gray-400 text-white py-2 px-4 rounded-full font-medium hover:bg-gray-500 transition">Cancel</button>
                </form>
            </td>
        `;
        row.insertAdjacentElement('afterend', rescheduleRow);

        const form = rescheduleRow.querySelector('.reschedule-form');
        const dateInput = form.querySelector('input[name="APPT_DATE"]');
        const timeSelect = form.querySelector('select[name="APPT_TIME"]');

        dateInput.addEventListener('change', () => {
            if (!dateInput.value) return;
            timeSelect.innerHTML = '<option value="">Loading...</option>';
            fetch(`../ajax/get_available_times.php?doc_id=${docId}&date=${dateInput.value}`)
                .then(res => res.json())
                .then(times => {
                    timeSelect.innerHTML = '<option value="">-- Choose Time --</option>';
                    if (times.length === 0) timeSelect.innerHTML = '<option value="" disabled>No times available</option>';
                    times.forEach(slot => {
                        const opt = document.createElement('option');
                        opt.value = slot.time;
                        opt.textContent = `${slot.time} - ${slot.endTime}`;
                        timeSelect.appendChild(opt);
                    });
                })
                .catch(() => timeSelect.innerHTML = '<option value="" disabled>Error loading times</option>');
        });

        rescheduleRow.querySelector('.cancel-reschedule').addEventListener('click', () => rescheduleRow.remove());

        form.addEventListener('submit', e => {
            e.preventDefault();
            const newDate = dateInput.value;
            const newTime = timeSelect.value;
            if (!newDate || !newTime) return alertCustom("Please select a new date and available time slot.");

            if (!confirmCustom('Confirm reschedule to ' + newDate + ' at ' + newTime + '?')) return;

            fetch('../ajax/reschedule_appointment.php', {
                method: 'POST',
                headers: {'Content-Type':'application/x-www-form-urlencoded'},
                body: `APPT_ID=${apptId}&APPT_DATE=${newDate}&APPT_TIME=${newTime}`
            })
            .then(res => res.text())
            .then(msg => {
                alertCustom(msg);
                location.reload();
            })
            .catch(() => alertCustom('Error updating appointment.'));
        });
    });
});

// --------------------- CUSTOM ALERT ---------------------
function alertCustom(message) {
    console.log("ALERT: " + message);
    const container = document.body;
    let alertBox = document.getElementById('custom-alert');
    if (!alertBox) {
        alertBox = document.createElement('div');
        alertBox.id = 'custom-alert';
        alertBox.className = 'fixed top-5 right-5 z-50 p-4 rounded-lg shadow-xl text-white font-semibold transition-opacity duration-300';
        container.appendChild(alertBox);
    }

    let bgColor = 'bg-[var(--primary)]';
    if (message.includes("Error") || message.includes("not found") || message.includes("denied")) bgColor = 'bg-red-600';
    else if (message.includes("successfully") || message.includes("done")) bgColor = 'bg-green-600';

    alertBox.className = `fixed top-5 right-5 z-50 p-4 rounded-lg shadow-xl text-white font-semibold transition-opacity duration-300 ${bgColor}`;
    alertBox.textContent = message;
    alertBox.style.opacity = '1';

    setTimeout(() => {
        alertBox.style.opacity = '0';
    }, 5000);
}

// --------------------- CUSTOM CONFIRM ---------------------
function confirmCustom(message) {
    return window.confirm(message);
}
</script>


</body>
</html>