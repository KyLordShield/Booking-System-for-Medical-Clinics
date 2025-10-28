<?php
require_once __DIR__ . '/../config/Database.php';

class Doctor {
    private $conn;
    private $table = "DOCTOR";

    public function __construct() {
        $db = new Database();
        $this->conn = $db->connect();
    }

    public function getAllDoctors() {
        $sql = "SELECT DOC_ID, DOC_FIRST_NAME, DOC_LAST_NAME FROM {$this->table} ORDER BY DOC_LAST_NAME ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
