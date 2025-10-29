<?php
require_once __DIR__ . '/../config/Database.php';

class Schedule {
    private $conn;
    private $table = "SCHEDULE";

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

            $dayName = date('l', strtotime($date)); // Monday, Tuesday, etc.

            $apptTime = date('H:i:s', strtotime($time));  // normalize to HH:MM:SS

            $withinSchedule = false;
            foreach ($schedules as $sched) {
                $schedDays = array_map('trim', explode(',', $sched['SCHED_DAYS']));
                if (in_array($dayName, $schedDays)) {
                    $startTime = date('H:i:s', strtotime($sched['SCHED_START_TIME']));
                    $endTime   = date('H:i:s', strtotime($sched['SCHED_END_TIME']));
                    if ($apptTime >= $startTime && $apptTime < $endTime) {
                        $withinSchedule = true;
                        break;
                    }
                }
            }

            if (!$withinSchedule) return false;

            // Check if the doctor already has an appointment
            $sql = "SELECT * FROM APPOINTMENT
                    WHERE DOC_ID = :doc_id
                    AND APPT_DATE = :date
                    AND APPT_TIME = :time
                    AND STAT_ID != 3"; // exclude canceled
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':doc_id' => $doc_id,
                ':date' => $date,
                ':time' => $apptTime
            ]);

            return $stmt->fetch(PDO::FETCH_ASSOC) ? false : true;

        } catch (PDOException $e) {
            return false;
        }
    }
}
?>
