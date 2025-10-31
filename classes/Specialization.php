<?php
require_once __DIR__ . '/../config/Database.php';

class Specialization {
    private $conn;
    private $table = "SPECIALIZATION";

    public function __construct() {
        $db = new Database();
        $this->conn = $db->connect();
    }

    // 🟩 Get all specializations
    public function getAll() {
        try {
            $sql = "SELECT SPEC_ID, SPEC_NAME FROM {$this->table} ORDER BY SPEC_NAME ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    // 🟦 Get doctors under a specific specialization
    public function getDoctorsBySpecialization($spec_id) {
        try {
            $sql = "SELECT D.DOC_ID, D.DOC_FIRST_NAME, D.DOC_LAST_NAME 
                    FROM DOCTOR D 
                    WHERE D.SPEC_ID = :spec_id
                    ORDER BY D.DOC_LAST_NAME ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':spec_id', $spec_id);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
}
?>
