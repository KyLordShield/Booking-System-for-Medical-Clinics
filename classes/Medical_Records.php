<?php
require_once __DIR__ . '/../config/Database.php';

class MedicalRecord {
    private $conn;
    private $table = "medical_record";

    public function __construct() {
        $db = new Database();
        $this->conn = $db->connect();
    }

    // ✅ Get all medical records belonging to a doctor
    public function getRecordsByDoctor($doc_id, $search = null) {
        try {
            $sql = "SELECT mr.*,
                    COALESCE(CONCAT(p.PAT_FIRST_NAME, ' ', p.PAT_LAST_NAME), CONCAT('Appt#', a.APPT_ID)) AS patient_name
                    FROM {$this->table} mr
                    JOIN appointment a ON mr.APPT_ID = a.APPT_ID
                    LEFT JOIN patient p ON a.PAT_ID = p.PAT_ID
                    WHERE a.DOC_ID = :doc";

            $params = [':doc' => $doc_id];

            if ($search) {
                $sql .= " AND (mr.MED_REC_DIAGNOSIS LIKE :s OR mr.MED_REC_PRESCRIPTION LIKE :s 
                          OR CONCAT(p.PAT_FIRST_NAME, ' ', p.PAT_LAST_NAME) LIKE :s)";
                $params[':s'] = "%$search%";
            }

            $sql .= " ORDER BY mr.MED_REC_VISIT_DATE DESC, mr.MED_REC_CREATED_AT DESC";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    // ✅ Insert new record
    public function add($data) {
        $sql = "INSERT INTO {$this->table}
                (MED_REC_DIAGNOSIS, MED_REC_PRESCRIPTION, MED_REC_VISIT_DATE,
                 MED_REC_CREATED_AT, MED_REC_UPDATED_AT, APPT_ID)
                VALUES (:diag, :presc, :visit, NOW(), NOW(), :appt)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':diag' => $data['diagnosis'],
            ':presc' => $data['prescription'],
            ':visit' => $data['visit_date'],
            ':appt' => $data['appt_id']
        ]);
    }

    // ✅ Update record
    public function update($id, $data) {
        $sql = "UPDATE {$this->table} SET
                MED_REC_DIAGNOSIS = :diag,
                MED_REC_PRESCRIPTION = :presc,
                MED_REC_VISIT_DATE = :visit,
                MED_REC_UPDATED_AT = NOW()
                WHERE MED_REC_ID = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':diag' => $data['diagnosis'],
            ':presc' => $data['prescription'],
            ':visit' => $data['visit_date'],
            ':id' => $id
        ]);
    }

    // ✅ Delete record
    public function delete($id) {
        $stmt = $this->conn->prepare("DELETE FROM {$this->table} WHERE MED_REC_ID = ?");
        return $stmt->execute([$id]);
    }

    // ✅ Check if record belongs to doctor (prevents editing other doctors’ records)
    public function verifyOwnership($record_id, $doc_id) {
        $sql = "SELECT mr.MED_REC_ID FROM {$this->table} mr
                JOIN appointment a ON mr.APPT_ID = a.APPT_ID
                WHERE mr.MED_REC_ID = ? AND a.DOC_ID = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$record_id, $doc_id]);
        return $stmt->fetchColumn() ? true : false;
    }
}
?>
