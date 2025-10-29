<?php
require_once __DIR__ . '/../classes/Schedule.php';
require_once __DIR__ . '/../classes/Appointment.php';

header('Content-Type: application/json');

if (!isset($_GET['doc_id']) || !isset($_GET['date'])) {
    echo json_encode([]);
    exit;
}

$doc_id = $_GET['doc_id'];
$date = $_GET['date'];

$schedObj = new Schedule();
$apptObj = new Appointment();

$schedules = $schedObj->getScheduleByDoctor($doc_id);
$dayName = date('l', strtotime($date)); // Full day name: Monday, Tuesday, etc.

$availableTimes = [];

foreach ($schedules as $sched) {
    // Normalize schedule days
    $schedDays = array_map('trim', explode(',', $sched['SCHED_DAYS']));
    $schedDays = array_map('ucfirst', $schedDays); // ensure first letter is capital

    if (!in_array($dayName, $schedDays)) continue;

    $startTime = strtotime($sched['SCHED_START_TIME']);
    $endTime   = strtotime($sched['SCHED_END_TIME']);

    // Generate 1-hour slots
    for ($t = $startTime; $t + 3600 <= $endTime; $t += 3600) {
        $slotStart = date('H:i', $t);         // format HH:MM
        $slotEnd   = date('H:i', $t + 3600);  // format HH:MM

        if (!$apptObj->isTimeBooked($doc_id, $date, $slotStart)) {
            $availableTimes[] = [
                'time' => $slotStart,
                'endTime' => $slotEnd
            ];
        }
    }
}

echo json_encode($availableTimes);
?>
