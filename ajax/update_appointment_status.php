<?php
require_once __DIR__ . '/../config/Database.php';
$conn = (new Database())->connect();

if (isset($_POST['appt_id'], $_POST['status'])) {
    $appt_id = $_POST['appt_id'];
    $status  = $_POST['status'];

    // Get the status ID
    $stmt = $conn->prepare("SELECT STAT_ID FROM STATUS WHERE STAT_NAME = :status LIMIT 1");
    $stmt->bindParam(':status', $status);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $statusId = $row['STAT_ID'] ?? null;

    if (!$statusId) {
        echo "Invalid status.";  
        exit;
    }

    // Update the appointment
    $update = $conn->prepare("UPDATE APPOINTMENT SET STAT_ID = :statusId WHERE APPT_ID = :appt_id");
    $update->execute([':statusId' => $statusId, ':appt_id' => $appt_id]);

    // Check if the update actually affected any row
    if ($update->rowCount() > 0) {
        // SUCCESS: Return your friendly message + a hidden success marker
        echo "Appointment updated to {$status}|SUCCESS|";
    } else {
        // No row was updated (maybe already completed or wrong ID)
        echo "No changes made.";
    }
} else {
    echo "Invalid request.";
}
?>