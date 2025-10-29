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
<title>Patient Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
<style>
body {
    font-family: 'Roboto', sans-serif;
    background-color: #f4f7f9;
    margin: 0;
}
header {
    background-color: #007bff;
    color: #fff;
    padding: 20px;
    text-align: center;
    font-size: 24px;
    font-weight: bold;
}
.container {
    max-width: 1100px;
    margin: 30px auto;
    padding: 0 20px;
}
.patient-info, .appointments {
    background: #fff;
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 25px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
}
.patient-info h2, .appointments h2 {
    margin-top: 0;
    color: #333;
    font-size: 22px;
}
.patient-details {
    line-height: 1.8;
}
.patient-actions {
    margin-top: 15px;
}
.patient-actions button, .bottom-actions a {
    display: inline-block;
    background-color: #28a745;
    color: white;
    text-decoration: none;
    padding: 10px 16px;
    border-radius: 6px;
    font-weight: 500;
    border: none;
    cursor: pointer;
    transition: background 0.2s;
}
.patient-actions button:hover, .bottom-actions a:hover {
    background-color: #218838;
}
#updateForm {
    display: none;
    margin-top: 15px;
}
#updateForm input, #updateForm select {
    width: 100%;
    padding: 8px;
    margin: 6px 0;
    border-radius: 5px;
    border: 1px solid #ccc;
}
#updateForm button {
    margin-top: 10px;
}
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
}
th, td {
    padding: 12px;
    border-bottom: 1px solid #ddd;
    text-align: center;
}
th {
    background-color: #007bff;
    color: white;
}
.appt-action {
    background-color: #17a2b8;
    color: white;
    border: none;
    padding: 6px 10px;
    border-radius: 4px;
    cursor: pointer;
}
.appt-action:hover { background-color: #138496; }
.appt-cancel { background-color: #dc3545; }
.appt-cancel:hover { background-color: #c82333; }
.bottom-actions {
    text-align: center;
    margin-top: 30px;
}
.bottom-actions a {
    background-color: #6c757d;
}
.bottom-actions a:hover {
    background-color: #5a6268;
}
.message {
    text-align: center;
    margin-bottom: 15px;
    font-weight: bold;
    color: #007bff;
}
.reschedule-row td {
    background-color: #f1f9ff;
}
</style>
</head>
<body>
<header>Patient Dashboard</header>

<div class="container">

    <?php if ($message): ?>
        <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="patient-info">
        <h2>Welcome, <?= htmlspecialchars($patient['PAT_FIRST_NAME'] ?? 'Patient') ?>!</h2>
        
        <div id="viewInfo" class="patient-details">
            <strong>Full Name:</strong> <?= htmlspecialchars(($patient['PAT_FIRST_NAME'] ?? '') . ' ' . ($patient['PAT_MIDDLE_INIT'] ?? '') . ' ' . ($patient['PAT_LAST_NAME'] ?? '')) ?><br>
            <strong>Email:</strong> <?= htmlspecialchars($patient['PAT_EMAIL'] ?? '') ?><br>
            <strong>Contact:</strong> <?= htmlspecialchars($patient['PAT_CONTACT_NUM'] ?? '') ?><br>
            <strong>Address:</strong> <?= htmlspecialchars($patient['PAT_ADDRESS'] ?? '') ?><br>
            <strong>Gender:</strong> <?= htmlspecialchars($patient['PAT_GENDER'] ?? '') ?><br>
            <strong>Date of Birth:</strong> <?= htmlspecialchars($patient['PAT_DOB'] ?? '') ?><br>
        </div>

        <form id="updateForm" method="post" action="">
            <input type="text" name="PAT_FIRST_NAME" value="<?= htmlspecialchars($patient['PAT_FIRST_NAME'] ?? '') ?>" placeholder="First Name" required>
            <input type="text" name="PAT_MIDDLE_INIT" value="<?= htmlspecialchars($patient['PAT_MIDDLE_INIT'] ?? '') ?>" placeholder="Middle Initial">
            <input type="text" name="PAT_LAST_NAME" value="<?= htmlspecialchars($patient['PAT_LAST_NAME'] ?? '') ?>" placeholder="Last Name" required>
            <input type="email" name="PAT_EMAIL" value="<?= htmlspecialchars($patient['PAT_EMAIL'] ?? '') ?>" placeholder="Email" required>
            <input type="text" name="PAT_CONTACT_NUM" value="<?= htmlspecialchars($patient['PAT_CONTACT_NUM'] ?? '') ?>" placeholder="Contact Number" required>
            <input type="text" name="PAT_ADDRESS" value="<?= htmlspecialchars($patient['PAT_ADDRESS'] ?? '') ?>" placeholder="Address" required>
            <select name="PAT_GENDER" required>
                <option value="Male" <?= ($patient['PAT_GENDER'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                <option value="Female" <?= ($patient['PAT_GENDER'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
            </select>
            <input type="date" name="PAT_DOB" value="<?= htmlspecialchars($patient['PAT_DOB'] ?? '') ?>" required>
            <button type="submit" name="update_info">💾 Save Changes</button>
            <button type="button" id="cancelEdit">❌ Cancel</button>
        </form>

        <div class="patient-actions">
            <button id="editInfo">🖋 Update Info</button>
            <a href="patient_pages/create_appointment.php">➕ Create Appointment</a>
        </div>
    </div>

    <div class="appointments">
        <h2>Your Appointments</h2>
        <?php if (!empty($appointments)): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Service</th>
                        <th>Doctor</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($appointments as $appt): ?>
                    <tr data-doc-id="<?= $appt['DOC_ID'] ?>">
                        <td><?= htmlspecialchars($appt['APPT_ID']) ?></td>
                        <td><?= htmlspecialchars($appt['APPT_DATE']) ?></td>
                        <td><?= htmlspecialchars($appt['APPT_TIME']) ?></td>
                        <td><?= htmlspecialchars($appt['SERV_NAME'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($appt['DOCTOR_NAME'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($appt['STAT_NAME'] ?? 'Pending') ?></td>
                        <td>
                            <button class="appt-action appt-update">✏ Update</button>
                            <button 
                                class="appt-action appt-cancel" 
                                data-appt-id="<?= $appt['APPT_ID'] ?>" 
                                <?= in_array($appt['STAT_NAME'], ['Completed', 'Cancelled']) ? 'disabled style="opacity:0.5;cursor:not-allowed;"' : '' ?>>
                                ❌ Cancel
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No appointments found.</p>
        <?php endif; ?>
    </div>

    <div class="bottom-actions">
        <a href="patient_pages/view_patients.php">👥 View All Patients</a>
    </div>
</div>

<script>
// Edit profile toggle
const editBtn = document.getElementById('editInfo');
const cancelBtn = document.getElementById('cancelEdit');
const viewDiv = document.getElementById('viewInfo');
const formDiv = document.getElementById('updateForm');
editBtn.addEventListener('click', () => {
    viewDiv.style.display = 'none';
    formDiv.style.display = 'block';
    editBtn.style.display = 'none';
});
cancelBtn.addEventListener('click', () => {
    formDiv.style.display = 'none';
    viewDiv.style.display = 'block';
    editBtn.style.display = 'inline-block';
});

// Cancel Appointment
document.querySelectorAll('.appt-cancel').forEach(btn => {
    btn.addEventListener('click', function() {
        const row = btn.closest('tr');
        const apptId = row.querySelector('td').textContent;
        if (!confirm('Are you sure you want to cancel this appointment?')) return;
        fetch('../ajax/cancel_appointment.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'APPT_ID=' + encodeURIComponent(apptId)
        })
        .then(res => res.text())
        .then(msg => {
            alert(msg);
            row.querySelector('td:nth-child(6)').textContent = 'Cancelled';
            btn.disabled = true;
            btn.style.opacity = 0.5;
            btn.style.cursor = 'not-allowed';
        })
        .catch(err => alert('Error cancelling appointment.'));
    });
});

// Reschedule Appointment
document.querySelectorAll('.appt-update').forEach(btn => {
    btn.addEventListener('click', function() {
        const row = btn.closest('tr');
        const apptId = row.querySelector('td').textContent;
        const docId = row.dataset.docId;
        if (row.nextElementSibling && row.nextElementSibling.classList.contains('reschedule-row')) return;

        const rescheduleRow = document.createElement('tr');
        rescheduleRow.classList.add('reschedule-row');
        rescheduleRow.innerHTML = `
            <td colspan="7">
                <form class="reschedule-form" data-appt-id="${apptId}">
                    <input type="date" name="APPT_DATE" min="<?= date('Y-m-d') ?>" required>
                    <select name="APPT_TIME" required>
                        <option value="">-- Choose Time --</option>
                    </select>
                    <button type="submit">Save</button>
                    <button type="button" class="cancel-reschedule">Cancel</button>
                </form>
            </td>
        `;
        row.insertAdjacentElement('afterend', rescheduleRow);

        const form = rescheduleRow.querySelector('.reschedule-form');
        const dateInput = form.querySelector('input[name="APPT_DATE"]');
        const timeSelect = form.querySelector('select[name="APPT_TIME"]');

        dateInput.addEventListener('change', () => {
            if (!dateInput.value) return;
            timeSelect.innerHTML = '<option>Loading...</option>';
            fetch(`../ajax/get_available_times.php?doc_id=${docId}&date=${dateInput.value}`)
                .then(res => res.json())
                .then(times => {
                    timeSelect.innerHTML = '<option value="">-- Choose Time --</option>';
                    times.forEach(slot => {
                        const opt = document.createElement('option');
                        opt.value = slot.time;
                        opt.textContent = `${slot.time} - ${slot.endTime}`;
                        timeSelect.appendChild(opt);
                    });
                })
                .catch(() => timeSelect.innerHTML = '<option>Error loading times</option>');
        });

        rescheduleRow.querySelector('.cancel-reschedule').addEventListener('click', () => rescheduleRow.remove());

        form.addEventListener('submit', e => {
            e.preventDefault();
            const newDate = dateInput.value;
            const newTime = timeSelect.value;
            if (!newDate || !newTime) return alert("Select date and time.");
            fetch('../ajax/reschedule_appointment.php', {
                method: 'POST',
                headers: {'Content-Type':'application/x-www-form-urlencoded'},
                body: `APPT_ID=${apptId}&APPT_DATE=${newDate}&APPT_TIME=${newTime}`
            })
            .then(res => res.text())
            .then(msg => {
                alert(msg);
                location.reload();
            })
            .catch(() => alert('Error updating appointment.'));
        });
    });
});
</script>

</body>
</html>
