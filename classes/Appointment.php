<?php
require_once __DIR__ . '/../config/Database.php';

class Appointment {
    private $conn;
    private $table = "APPOINTMENT";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    // 🟩 Get all appointments for a specific patient
    public function getAppointmentsByPatient($pat_id) {
        try {
            $sql = "SELECT 
                        A.APPT_ID,
                        A.APPT_DATE,
                        A.APPT_TIME,
                        S.SERV_NAME,
                        ST.STAT_NAME,
                        CONCAT(D.DOC_FIRST_NAME, ' ', D.DOC_LAST_NAME) AS DOCTOR_NAME
                    FROM {$this->table} A
                    LEFT JOIN SERVICE S ON A.SERV_ID = S.SERV_ID
                    LEFT JOIN STATUS ST ON A.STAT_ID = ST.STAT_ID
                    LEFT JOIN DOCTOR D ON A.DOC_ID = D.DOC_ID
                    WHERE A.PAT_ID = :pat_id
                    ORDER BY A.APPT_DATE DESC, A.APPT_TIME DESC";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':pat_id', $pat_id);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return ['error' => $e->getMessage()];
        }
    }

// 🟩 Patients Creats new Appointments
    public function createAppointment($pat_id, $doc_id, $serv_id, $appt_date, $appt_time) {
    try {
        $appt_id = 'APPT-' . time();
        $stat_id = 1; // Default to "Pending"

        $sql = "INSERT INTO APPOINTMENT (APPT_ID, APPT_DATE, APPT_TIME, PAT_ID, DOC_ID, SERV_ID, STAT_ID)
                VALUES (:id, :date, :time, :pat, :doc, :serv, :stat)";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':id' => $appt_id,
            ':date' => $appt_date,
            ':time' => $appt_time,
            ':pat' => $pat_id,
            ':doc' => $doc_id,
            ':serv' => $serv_id,
            ':stat' => $stat_id
        ]);
        return "✅ Appointment created successfully!";
    } catch (PDOException $e) {
        return "❌ Failed to create appointment: " . $e->getMessage();
    }
}

}
?>
