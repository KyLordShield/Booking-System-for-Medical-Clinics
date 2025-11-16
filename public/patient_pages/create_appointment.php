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

// ✅ SweetAlert message
$alert = ['type'=>'', 'message'=>''];

// ✅ Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_appt'])) {
    $appt_date = trim($_POST['APPT_DATE']);
    $appt_time = trim($_POST['APPT_TIME']);
    $doc_id = $_POST['DOC_ID'] ?? '';
    $serv_id = $_POST['SERV_ID'] ?? '';

    if (empty($appt_date) || empty($appt_time) || empty($doc_id) || empty($serv_id)) {
        $alert = ['type'=>'error', 'message'=>'⚠ Please fill out all fields.'];
    } else {
        // ✅ Check if doctor is available (schedule + booked)
        $available = $schedObj->isDoctorAvailable($doc_id, $appt_date, $appt_time);

        if (!$available) {
            $alert = ['type'=>'error', 'message'=>'❌ Doctor is not available at the selected date/time.'];
        } else {
            $result = $appointmentObj->createAppointment($pat_id, $doc_id, $serv_id, $appt_date, $appt_time);

            if (strpos($result, '✅') !== false) {
                $alert = ['type'=>'success', 'message'=>'✅ Appointment created successfully!'];
            } else {
                $alert = ['type'=>'error', 'message'=>$result];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Create Appointment | Medicina</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<!-- ✅ Tailwind CDN -->
<script src="https://cdn.tailwindcss.com"></script>

<!-- ✅ Global Custom CSS -->
<link rel="stylesheet" href="/Booking-System-For-Medical-Clinics/assets/css/style.css">

<!-- ✅ SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-[var(--secondary)] min-h-screen flex flex-col font-[Georgia]">

<!-- NAVBAR -->
<?php include dirname(__DIR__, 2) . "/partials/header.php"; ?>

<!-- MAIN CONTENT -->
<main class="flex flex-col flex-1 px-20 py-16 w-full items-center justify-start">
    <div class="appointment-form-container bg-[var(--light)] p-8 rounded-[25px] shadow-xl w-full max-w-lg">
        <h1 class="text-3xl font-bold text-[var(--primary)] text-center mb-6">Book New Appointment</h1>

        <form method="POST" id="appointmentForm" class="space-y-4">
            <div>
                <label for="SERV_ID" class="block mb-1 font-semibold text-gray-700">Select Service:</label>
                <select name="SERV_ID" id="SERV_ID" required 
                    class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[var(--primary)] outline-none appearance-none">
                    <option value="">-- Choose Service --</option>
                    <?php foreach ($services as $s): ?>
                        <option value="<?= $s['SERV_ID'] ?>"><?= htmlspecialchars($s['SERV_NAME']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="DOC_ID" class="block mb-1 font-semibold text-gray-700">Select Doctor:</label>
                <select name="DOC_ID" id="DOC_ID" required
                    class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[var(--primary)] outline-none appearance-none">
                    <option value="">-- Choose Doctor --</option>
                </select>
            </div>

            <div>
                <label for="APPT_DATE" class="block mb-1 font-semibold text-gray-700">Date:</label>
                <input type="date" name="APPT_DATE" id="APPT_DATE" min="<?= date('Y-m-d') ?>" required
                    class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[var(--primary)] outline-none">
            </div>

            <div>
                <label for="APPT_TIME" class="block mb-1 font-semibold text-gray-700">Time:</label>
                <select name="APPT_TIME" id="APPT_TIME" required
                    class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[var(--primary)] outline-none appearance-none">
                    <option value="">-- Choose Time --</option>
                </select>
            </div>

            <button type="submit" name="create_appt" 
                class="w-full p-3 mt-6 bg-[var(--primary)] text-white font-semibold rounded-lg hover:bg-sky-600 transition">
                📝 Create Appointment
            </button>
        </form>

        <a href="../patient_dashboard.php" class="block text-center mt-6 text-gray-600 font-medium hover:text-[var(--primary)] transition">
            ⬅ Back to Dashboard
        </a>
    </div>
</main>

<!-- FOOTER -->
<?php include dirname(__DIR__, 2) . "/partials/footer.php"; ?>

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

// ✅ SweetAlert notification
<?php if(!empty($alert['message'])): ?>
Swal.fire({
    icon: '<?= $alert['type'] ?>',
    title: '<?= $alert['type']==='success' ? 'Success!' : 'Oops!' ?>',
    text: '<?= $alert['message'] ?>',
    confirmButtonColor: '<?= $alert['type']==='success' ? '#3085d6' : '#d33' ?>'
});
<?php endif; ?>
</script>

</body>
</html>
