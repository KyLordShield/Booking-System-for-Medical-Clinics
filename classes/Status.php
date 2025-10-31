<?php
require_once __DIR__ . '/../config/Database.php';

class Status {
    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->connect();
    }

    // ✅ READ: Fetch all statuses
    public function getAll() {
        $sql = "SELECT * FROM STATUS ORDER BY STAT_ID DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ✅ CREATE: Add new status
    public function create($name) {
        $sql = "INSERT INTO STATUS (STAT_NAME) VALUES (:name)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':name', $name);
        return $stmt->execute();
    }

    // ✅ UPDATE: Edit existing status
    public function update($id, $name) {
        $sql = "UPDATE STATUS SET STAT_NAME = :name WHERE STAT_ID = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':name', $name);
        return $stmt->execute();
    }

    // ✅ DELETE: Remove status
    public function delete($id) {
        $sql = "DELETE FROM STATUS WHERE STAT_ID = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    // ✅ FIND: Get one status by ID
    public function getById($id) {
        $sql = "SELECT * FROM STATUS WHERE STAT_ID = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
