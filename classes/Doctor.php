<?php
require_once __DIR__ . '/../config/Database.php';

class Doctor {
    private $conn;
    private $table = "DOCTOR";

    public function __construct() {
        $db = new Database();
        $this->conn = $db->connect();
    }

    // Returns doctors with specialization name (if any)
    public function getAllDoctorsWithSpecialization() {
        $sql = "SELECT d.DOC_ID, d.DOC_FIRST_NAME, d.DOC_LAST_NAME, d.SPEC_ID, s.SPEC_NAME
                FROM DOCTOR d
                LEFT JOIN SPECIALIZATION s ON d.SPEC_ID = s.SPEC_ID
                ORDER BY d.DOC_LAST_NAME ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Backwards-compatible simple method (if used elsewhere)
    public function getAllDoctors() {
        return $this->getAllDoctorsWithSpecialization();
    }

    // 🟦 Get doctors filtered by service (based on specialization)
public function getDoctorsByService($serv_id) {
    $sql = "SELECT d.DOC_ID, d.DOC_FIRST_NAME, d.DOC_LAST_NAME, s.SPEC_NAME
            FROM doctor d
            JOIN specialization s ON d.SPEC_ID = s.SPEC_ID
            JOIN service sv ON s.SPEC_ID = sv.SPEC_ID
            WHERE sv.SERV_ID = :serv_id
            ORDER BY d.DOC_LAST_NAME ASC";
    $stmt = $this->conn->prepare($sql);
    $stmt->bindParam(':serv_id', $serv_id);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

}
?>
