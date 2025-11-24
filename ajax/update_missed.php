<?php
require_once __DIR__ . '/../config/Database.php';

// Prevent running via browser
if (php_sapi_name() !== 'cli') {
  exit("This script can only run via command line.");
}

try {
    // Connect to database
    $conn = (new Database())->connect();
    $today = date('Y-m-d');

    // -----------------------------
    // Set STAT_IDs
    // -----------------------------
    // Replace these with actual STAT_ID values from your STATUS table
    $scheduledId = 1; // Scheduled
    $pendingId   = 4; // Pending (optional)
    $missedId    = 5; // Missed

    // -----------------------------
    // Update past appointments
    // -----------------------------
    $updateMissedSQL = "
        UPDATE appointment
        SET STAT_ID = :missedId
        WHERE DATE(APPT_DATE) < :today
          AND STAT_ID IN (:scheduledId, :pendingId)
    ";

    $stmt = $conn->prepare($updateMissedSQL);

    // PDO doesn’t allow binding an array directly for IN(), so we can use separate query
    $stmt = $conn->prepare("
        UPDATE appointment
        SET STAT_ID = :missedId
        WHERE DATE(APPT_DATE) < :today
          AND (STAT_ID = :scheduledId OR STAT_ID = :pendingId)
    ");

    $stmt->execute([
        ':missedId'   => $missedId,
        ':today'      => $today,
        ':scheduledId'=> $scheduledId,
        ':pendingId'  => $pendingId
    ]);

    echo "Missed appointments updated successfully.\n";

} catch (PDOException $e) {
    echo "Error updating missed appointments: " . $e->getMessage() . "\n";
}
