<?php
require_once __DIR__ . '/../config/Database.php';

class Patient {
    private $conn;
    private $table = "PATIENT";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    // 🟩 Fetch patient info by PAT_ID
    public function getPatientById($pat_id) {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE PAT_ID = :pat_id LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':pat_id', $pat_id);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return ['error' => $e->getMessage()];
        }
    }

    // 🟩 Update patient info (used by Manage Info)
    public function updatePatient($pat_id, $fname, $mname, $lname, $dob, $gender, $contact, $email, $address) {
        try {
            $sql = "UPDATE {$this->table}
                    SET PAT_FIRST_NAME = :fname,
                        PAT_MIDDLE_INIT = :mname,
                        PAT_LAST_NAME = :lname,
                        PAT_DOB = :dob,
                        PAT_GENDER = :gender,
                        PAT_CONTACT_NUM = :contact,
                        PAT_EMAIL = :email,
                        PAT_ADDRESS = :address,
                        PAT_UPDATED_AT = NOW()
                    WHERE PAT_ID = :pat_id";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':fname' => $fname,
                ':mname' => $mname,
                ':lname' => $lname,
                ':dob' => $dob,
                ':gender' => $gender,
                ':contact' => $contact,
                ':email' => $email,
                ':address' => $address,
                ':pat_id' => $pat_id
            ]);
            return $stmt->rowCount() > 0 ? "✅ Patient information updated successfully!" : "⚠️ No changes made.";
        } catch (PDOException $e) {
            return "❌ Update failed: " . $e->getMessage();
        }
    }

    
// 🟦 Fetch all patients
public function getAllPatients() {
    try {
        $sql = "SELECT * FROM {$this->table} ORDER BY PAT_LAST_NAME ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

// 🟩 Search patients by first or last name
public function searchPatients($keyword) {
    try {
        $sql = "SELECT * FROM {$this->table} 
                WHERE PAT_FIRST_NAME LIKE :kw OR PAT_LAST_NAME LIKE :kw
                ORDER BY PAT_LAST_NAME ASC";
        $stmt = $this->conn->prepare($sql);
        $kw = "%{$keyword}%";
        $stmt->bindParam(':kw', $kw);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}


}
?>
