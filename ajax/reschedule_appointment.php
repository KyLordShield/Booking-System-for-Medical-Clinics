<?php
session_start();
require_once __DIR__ . '/../classes/Appointment.php';
require_once __DIR__ . '/../classes/Schedule.php';

if (!isset($_SESSION['PAT_ID']) || $_SESSION['role'] !== 'patient') {
    http_response_code(403);
    echo "❌ Unauthorized";
    exit;
}

if (empty($_POST['APPT_ID']) || empty($_POST['APPT_DATE']) || empty($_POST['APPT_TIME'])) {
    echo "❌ Missing required data.";
    exit;
}

$apptId  = $_POST['APPT_ID'];
$newDate = $_POST['APPT_DATE'];
$newTime = $_POST['APPT_TIME'];

$apptObj  = new Appointment();
$schedObj = new Schedule();

// ✅ 1. Get the appointment details (via a method, not direct $conn)
$appointment = $apptObj->getAppointmentByIdAndPatient($apptId, $_SESSION['PAT_ID']);
if (!$appointment) {
    echo "❌ Appointment not found.";
    exit;
}

$docId = $appointment['DOC_ID'];

// ✅ 2. Check if the doctor is available
if (!$schedObj->isDoctorAvailable($docId, $newDate, $newTime)) {
    echo "❌ Doctor is not available at this date/time.";
    exit;
}

// ✅ 3. Reschedule the appointment (through Appointment class)
if ($apptObj->rescheduleAppointment($apptId, $newDate, $newTime, $_SESSION['PAT_ID'])) {
    echo "✅ Appointment rescheduled successfully!";
} else {
    echo "❌ Failed to update appointment. Please try again.";
}
?>
