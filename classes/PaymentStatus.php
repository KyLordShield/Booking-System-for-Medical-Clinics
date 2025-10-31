<?php
require_once __DIR__ . '/../config/Database.php';

class PaymentStatus {
    private $conn;

    public function __construct() {
        $this->conn = (new Database())->connect();
    }

    // ✅ Fetch all statuses
    public function getAllStatuses() {
        $sql = "SELECT * FROM PAYMENT_STATUS ORDER BY PYMT_STAT_ID ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ✅ Add new status
    public function addStatus($name) {
        $sql = "INSERT INTO PAYMENT_STATUS (PYMT_STAT_NAME) VALUES (:name)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([':name' => $name]);
    }

    // ✅ Update existing status
    public function updateStatus($id, $name) {
        $sql = "UPDATE PAYMENT_STATUS 
                SET PYMT_STAT_NAME = :name 
                WHERE PYMT_STAT_ID = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':name' => $name,
            ':id' => $id
        ]);
    }

    // ✅ Delete status
    public function deleteStatus($id) {
        $sql = "DELETE FROM PAYMENT_STATUS WHERE PYMT_STAT_ID = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    // ✅ Get single status by ID (optional helper)
    public function getStatusById($id) {
        $sql = "SELECT * FROM PAYMENT_STATUS WHERE PYMT_STAT_ID = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
