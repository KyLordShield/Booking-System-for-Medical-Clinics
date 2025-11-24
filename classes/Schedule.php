<?php
require_once __DIR__ . '/../config/Database.php';

class Schedule {
    private $conn;
    private $table = "schedule";

    public function __construct() {
        $db = new Database();
        $this->conn = $db->connect();
    }

    // Get all schedules (for admin)
    public function getAllSchedules() {
        $sql = "SELECT SCHED_ID, SCHED_DAYS, SCHED_START_TIME, SCHED_END_TIME, DOC_ID FROM {$this->table}";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get schedule for a specific doctor
    public function getScheduleByDoctor($doc_id) {
        $sql = "SELECT SCHED_ID, SCHED_DAYS, SCHED_START_TIME, SCHED_END_TIME
                FROM {$this->table} WHERE DOC_ID = :doc_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':doc_id', $doc_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Check if doctor is available at a specific date/time
    public function isDoctorAvailable($doc_id, $date, $time) {
    try {
        $schedules = $this->getScheduleByDoctor($doc_id);
        if (!$schedules) return false;

        $dayName = date('l', strtotime($date));

        // Only use hour (ignore minutes/seconds)
        $apptHour = (int)date('H', strtotime($time));

        $withinSchedule = false;

        foreach ($schedules as $sched) {

            // Allow multiple days: Monday, Tuesday, Friday
            $schedDays = array_map('trim', explode(',', $sched['SCHED_DAYS']));
            if (!in_array($dayName, $schedDays)) continue;

            $startHour = (int)date('H', strtotime($sched['SCHED_START_TIME']));
            $endHour   = (int)date('H', strtotime($sched['SCHED_END_TIME']));

            // ✅ Hour-only range check
            if ($apptHour >= $startHour && $apptHour < $endHour) {
                $withinSchedule = true;
                break;
            }
        }

        if (!$withinSchedule) return false;

        // ✅ Check if doctor already has appointment in same hour
        $sql = "SELECT * FROM appointment
                WHERE DOC_ID = :doc_id
                AND APPT_DATE = :date
                AND HOUR(APPT_TIME) = :hour
                AND STAT_ID != 3";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':doc_id' => $doc_id,
            ':date'   => $date,
            ':hour'   => $apptHour
        ]);

        return $stmt->fetch() ? false : true;
    }
    catch (PDOException $e) {
        return false;
    }
}

}
?>
