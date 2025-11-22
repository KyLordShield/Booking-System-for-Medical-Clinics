<?php
session_start();
require_once __DIR__ . '/../config/Database.php';  // ← ONLY ONE ../
require_once __DIR__ . '/../classes/Appointment.php'; // if needed later

if (!isset($_SESSION['DOC_ID']) || $_SESSION['role'] !== 'doctor') {
    echo "Unauthorized";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['appt_id'])) {
    echo "Invalid request";
    exit;
}

$conn = (new Database())->connect();

$appt_id      = $_POST['appt_id'];
$diagnosis    = $_POST['diagnosis'] ?? '';
$prescription = $_POST['prescription'] ?? '';
$visit_date   = date('Y-m-d');

try {
    // Prevent duplicate medical records
    $check = $conn->prepare("SELECT MED_REC_ID FROM MEDICAL_RECORD WHERE APPT_ID = ?");
    $check->execute([$appt_id]);
    if ($check->rowCount() > 0) {
        echo "Record already exists";
        exit;
    }

    $sql = "INSERT INTO MEDICAL_RECORD 
            (APPT_ID, MED_REC_DIAGNOSIS, MED_REC_PRESCRIPTION, MED_REC_VISIT_DATE) 
            VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$appt_id, $diagnosis, $prescription, $visit_date]);

    echo "success";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>