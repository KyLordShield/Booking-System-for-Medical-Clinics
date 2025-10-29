<?php
session_start();
require_once __DIR__ . '/../../classes/Appointment.php';
require_once __DIR__ . '/../../classes/Doctor.php';
require_once __DIR__ . '/../../classes/Service.php';
require_once __DIR__ . '/../../classes/Schedule.php';

// ✅ Verify login session
if (!isset($_SESSION['PAT_ID']) || $_SESSION['role'] !== 'patient') {
    header("Location: ../index.php");
    exit;
}

$pat_id = $_SESSION['PAT_ID'];

$appointmentObj = new Appointment();
$doctorObj = new Doctor();
$serviceObj = new Service();
$schedObj = new Schedule();

$services = $serviceObj->getAllServices();
$message = "";

// ✅ Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_appt'])) {
    $appt_date = trim($_POST['APPT_DATE']);
    $appt_time = trim($_POST['APPT_TIME']);
    $doc_id = $_POST['DOC_ID'] ?? '';
    $serv_id = $_POST['SERV_ID'] ?? '';

    if (empty($appt_date) || empty($appt_time) || empty($doc_id) || empty($serv_id)) {
        $message = "⚠ Please fill out all fields.";
    } else {
        // ✅ Check if doctor is available (schedule + booked)
        $available = $schedObj->isDoctorAvailable($doc_id, $appt_date, $appt_time);

        if (!$available) {
            $message = "❌ Doctor is not available at the selected date/time.";
        } else {
            $result = $appointmentObj->createAppointment($pat_id, $doc_id, $serv_id, $appt_date, $appt_time);

            if (strpos($result, '✅') !== false) {
                header("Location: ../patient_dashboard.php?success=1");
                exit;
            } else {
                $message = $result;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Create Appointment</title>
<style>
body { font-family: Arial, sans-serif; background: #f4f6f9; margin: 0; padding: 20px; }
.container { max-width: 600px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
h1 { text-align: center; color: #333; }
form { margin-top: 20px; }
label { display: block; margin: 10px 0 5px; font-weight: bold; }
input, select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; }
button { width: 100%; padding: 12px; background-color: #28a745; color: #fff; border: none; border-radius: 5px; margin-top: 20px; cursor: pointer; font-size: 16px; }
button:hover { background-color: #218838; }
a { display: block; text-align: center; margin-top: 15px; color: #555; text-decoration: none; }
a:hover { text-decoration: underline; }
.message { text-align: center; margin-top: 15px; font-weight: bold; color: #d9534f; }
</style>
</head>
<body>
<div class="container">
<h1>Create Appointment</h1>

<?php if ($message): ?>
    <p class="message"><?= htmlspecialchars($message) ?></p>
<?php endif; ?>

<form method="POST" id="appointmentForm">
    <label for="SERV_ID">Select Service:</label>
    <select name="SERV_ID" id="SERV_ID" required>
        <option value="">-- Choose Service --</option>
        <?php foreach ($services as $s): ?>
            <option value="<?= $s['SERV_ID'] ?>"><?= htmlspecialchars($s['SERV_NAME']) ?></option>
        <?php endforeach; ?>
    </select>

    <label for="DOC_ID">Select Doctor:</label>
    <select name="DOC_ID" id="DOC_ID" required>
        <option value="">-- Choose Doctor --</option>
    </select>

    <label for="APPT_DATE">Date:</label>
    <input type="date" name="APPT_DATE" id="APPT_DATE" min="<?= date('Y-m-d') ?>" required>

    <label for="APPT_TIME">Time:</label>
    <select name="APPT_TIME" id="APPT_TIME" required>
        <option value="">-- Choose Time --</option>
    </select>

    <button type="submit" name="create_appt">Create Appointment</button>
</form>

<a href="../patient_dashboard.php">⬅ Back to Dashboard</a>
</div>

<script>
const serviceSelect = document.getElementById('SERV_ID');
const doctorSelect = document.getElementById('DOC_ID');
const dateInput = document.getElementById('APPT_DATE');
const timeSelect = document.getElementById('APPT_TIME');

// Load doctors based on selected service
serviceSelect.addEventListener('change', function() {
    const servID = this.value;
    doctorSelect.innerHTML = '<option>Loading...</option>';
    fetch('../../ajax/get_doctors_by_service.php?serv_id=' + servID)
        .then(res => res.json())
        .then(data => {
            doctorSelect.innerHTML = '<option value="">-- Choose Doctor --</option>';
            if (data.length > 0) {
                data.forEach(doc => {
                    const opt = document.createElement('option');
                    opt.value = doc.DOC_ID;
                    opt.textContent = `${doc.DOC_FIRST_NAME} ${doc.DOC_LAST_NAME} – ${doc.SPEC_NAME}`;
                    doctorSelect.appendChild(opt);
                });
            } else {
                doctorSelect.innerHTML = '<option>No available doctors for this service</option>';
            }
        })
        .catch(() => {
            doctorSelect.innerHTML = '<option>Error loading doctors</option>';
        });
});

// Load available times based on selected doctor & date
function loadAvailableTimes() {
    const docID = doctorSelect.value;
    const date = dateInput.value;
    if (!docID || !date) {
        timeSelect.innerHTML = '<option value="">-- Choose Time --</option>';
        return;
    }

    timeSelect.innerHTML = '<option>Loading...</option>';
    fetch(`../../ajax/get_available_times.php?doc_id=${docID}&date=${date}`)
        .then(res => res.json())
        .then(data => {
            timeSelect.innerHTML = '<option value="">-- Choose Time --</option>';
            if (data.length > 0) {
                data.forEach(slot => {
                    const opt = document.createElement('option');
                    opt.value = slot.time;
                    opt.textContent = `${slot.time} - ${slot.endTime}`;
                    timeSelect.appendChild(opt);
                });
            } else {
                timeSelect.innerHTML = '<option value="">No available times</option>';
            }
        })
        .catch(() => {
            timeSelect.innerHTML = '<option value="">Error loading times</option>';
        });
}

doctorSelect.addEventListener('change', loadAvailableTimes);
dateInput.addEventListener('change', loadAvailableTimes);
</script>
</body>
</html>
