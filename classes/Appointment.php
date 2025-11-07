<?php
require_once __DIR__ . '/../config/Database.php';

class Appointment {
    private $conn;
    private $table = "APPOINTMENT";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }
public function getAll() {
    $sql = "SELECT a.*, 
                   CONCAT(p.PAT_FIRST_NAME, ' ', p.PAT_LAST_NAME) AS PATIENT_NAME,
                   CONCAT(d.DOC_FIRST_NAME, ' ', d.DOC_LAST_NAME) AS DOCTOR_NAME,
                   s.STAT_NAME AS APPT_STATUS
            FROM appointment a
            LEFT JOIN patient p ON a.PAT_ID = p.PAT_ID
            LEFT JOIN doctor d ON a.DOC_ID = d.DOC_ID
            LEFT JOIN status s ON a.STAT_ID = s.STAT_ID
            ORDER BY a.APPT_DATE DESC, a.APPT_TIME DESC";

    $stmt = $this->conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


    // Get all appointments for a specific patient
    public function getAppointmentsByPatient($pat_id) {
        try {
            $sql = "SELECT 
                        A.APPT_ID,
                        A.APPT_DATE,
                        A.APPT_TIME,
                        A.DOC_ID,
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

    // Create new appointment with validations
// Create new appointment with validations
public function createAppointment($pat_id, $doc_id, $serv_id, $date, $time) {
    try {
        // Normalize input
        $date = substr($date, 0, 10); // YYYY-MM-DD
        $time = substr($time, 0, 5);  // HH:MM

        // 1) Check duplicates
        $sqlCheck1 = "SELECT APPT_ID FROM {$this->table} WHERE PAT_ID = :pat_id AND APPT_DATE = :date AND APPT_TIME = :time LIMIT 1";
        $stmt = $this->conn->prepare($sqlCheck1);
        $stmt->execute([':pat_id' => $pat_id, ':date' => $date, ':time' => $time]);
        if ($stmt->fetch(PDO::FETCH_ASSOC)) {
            return "⚠ You already have an appointment at that date and time.";
        }

        $sqlCheck2 = "SELECT APPT_ID FROM {$this->table} WHERE DOC_ID = :doc_id AND APPT_DATE = :date AND APPT_TIME = :time LIMIT 1";
        $stmt = $this->conn->prepare($sqlCheck2);
        $stmt->execute([':doc_id' => $doc_id, ':date' => $date, ':time' => $time]);
        if ($stmt->fetch(PDO::FETCH_ASSOC)) {
            return "⚠ The selected doctor already has an appointment at that date and time.";
        }

        // 2) Generate APPT_ID in format YYYY-MM-0000001
        $yearMonth = date('Y-m', strtotime($date)); // e.g., 2025-01

        // Get last appointment number for this month
        $sqlSeq = "SELECT APPT_ID FROM {$this->table} WHERE APPT_ID LIKE :ym ORDER BY APPT_ID DESC LIMIT 1";
        $stmt = $this->conn->prepare($sqlSeq);
        $stmt->execute([':ym' => $yearMonth . '-%']);
        $last = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($last) {
            // Extract the last sequence number
            $parts = explode('-', $last['APPT_ID']);
            $seq = intval($parts[2]) + 1;
        } else {
            $seq = 1;
        }

        $seqPadded = str_pad($seq, 7, '0', STR_PAD_LEFT); // e.g., 0000001
        $appt_id = $yearMonth . '-' . $seqPadded;

        // 3) Get STAT_ID for "Scheduled"
        $sqlStatus = "SELECT STAT_ID FROM STATUS WHERE STAT_NAME = 'Scheduled' LIMIT 1";
        $stmtStatus = $this->conn->prepare($sqlStatus);
        $stmtStatus->execute();
        $status = $stmtStatus->fetch(PDO::FETCH_ASSOC);
        $scheduledId = $status['STAT_ID'] ?? null; // fallback if not found

        if (!$scheduledId) {
            return "❌ Error: 'Scheduled' status not found in STATUS table.";
        }

        // 4) Insert into database with correct STAT_ID
        $sql = "INSERT INTO {$this->table} (APPT_ID, APPT_DATE, APPT_TIME, PAT_ID, DOC_ID, SERV_ID, STAT_ID)
                VALUES (:id, :date, :time, :pat_id, :doc_id, :serv_id, :stat_id)";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':id' => $appt_id,
            ':date' => $date,
            ':time' => $time,
            ':pat_id' => $pat_id,
            ':doc_id' => $doc_id,
            ':serv_id' => $serv_id,
            ':stat_id' => $scheduledId
        ]);

        return "✅ Appointment created successfully! (ID: {$appt_id})";

    } catch (PDOException $e) {
        return "❌ Error creating appointment: " . $e->getMessage();
    }
}




 // Checks if the doctor in that time is booked or not
   public function isTimeBooked($doc_id, $date, $time) {
    $time = date('H:i', strtotime($time));
    $sql = "SELECT 1 FROM APPOINTMENT WHERE DOC_ID = :doc_id AND APPT_DATE = :date AND APPT_TIME = :time AND STAT_ID != 3";
    $stmt = $this->conn->prepare($sql);
    $stmt->execute([
        ':doc_id' => $doc_id,
        ':date' => $date,
        ':time' => $time
    ]);
    return $stmt->fetch() ? true : false;
}




// Handles Cancel of the appointment
public function cancelAppointment($appt_id, $pat_id) {
    try {
        $sql = "SELECT STAT_ID FROM {$this->table} WHERE APPT_ID = :appt_id AND PAT_ID = :pat_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':appt_id' => $appt_id, ':pat_id' => $pat_id]);
        $appt = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$appt) return "Appointment not found or does not belong to you.";

        $statusName = $this->getStatusName($appt['STAT_ID']);
        if (in_array($statusName, ['Completed', 'Cancelled'])) {
            return "Cannot cancel an appointment that is {$statusName}.";
        }

        $sqlStatus = "SELECT STAT_ID FROM STATUS WHERE STAT_NAME = 'Cancelled' LIMIT 1";
        $stmtStatus = $this->conn->query($sqlStatus);
        $cancelStat = $stmtStatus->fetch(PDO::FETCH_ASSOC);

        if (!$cancelStat) return "Cancelled status not found in database.";

        $sqlUpdate = "UPDATE {$this->table} SET STAT_ID = :stat_id WHERE APPT_ID = :appt_id";
        $stmtUpdate = $this->conn->prepare($sqlUpdate);
        $stmtUpdate->execute([':stat_id' => $cancelStat['STAT_ID'], ':appt_id' => $appt_id]);

        return true;
    } catch (PDOException $e) {
        return "Error cancelling appointment: " . $e->getMessage();
    }
}

private function getStatusName($stat_id) {
    $stmt = $this->conn->prepare("SELECT STAT_NAME FROM STATUS WHERE STAT_ID = :id LIMIT 1");
    $stmt->execute([':id' => $stat_id]);
    $res = $stmt->fetch(PDO::FETCH_ASSOC);
    return $res['STAT_NAME'] ?? null;
}


// Handles Reschedule of the appointment
public function rescheduleAppointment($appt_id, $new_date, $new_time, $pat_id) {
    try {
        $sql = "UPDATE {$this->table}
                SET APPT_DATE = :new_date,
                    APPT_TIME = :new_time,
                    STAT_ID = (SELECT STAT_ID FROM STATUS WHERE STAT_NAME = 'Scheduled')
                WHERE APPT_ID = :appt_id AND PAT_ID = :pat_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':new_date', $new_date);
        $stmt->bindParam(':new_time', $new_time);
        $stmt->bindParam(':appt_id', $appt_id);
        $stmt->bindParam(':pat_id', $pat_id);
        return $stmt->execute();
    } catch (PDOException $e) {
        return false;
    }
}

//Helper for the Reschedule Method
public function getAppointmentByIdAndPatient($appt_id, $pat_id) {
    try {
        $sql = "SELECT * FROM {$this->table} WHERE APPT_ID = :appt_id AND PAT_ID = :pat_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':appt_id', $appt_id);
        $stmt->bindParam(':pat_id', $pat_id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return false;
    }
}



}
?>
