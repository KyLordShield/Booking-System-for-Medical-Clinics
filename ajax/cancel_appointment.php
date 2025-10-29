<?php
session_start();
require_once __DIR__ . '/../classes/Appointment.php';

// Only allow logged-in patients
if (!isset($_SESSION['PAT_ID']) || $_SESSION['role'] !== 'patient') {
    http_response_code(403);
    echo "❌ Unauthorized";
    exit;
}

if (!isset($_POST['APPT_ID'])) {
    echo "❌ No appointment selected.";
    exit;
}

$apptObj = new Appointment();
$apptId = $_POST['APPT_ID'];

// Use the class method cancelAppointment
$result = $apptObj->cancelAppointment($apptId, $_SESSION['PAT_ID']);

if ($result === true) {
    echo "✅ Appointment cancelled successfully!";
} else {
    echo "❌ " . $result;
}
