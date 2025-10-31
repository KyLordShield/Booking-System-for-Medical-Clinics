<?php
require_once __DIR__ . '/../config/Database.php';

class Specialization {
    private $conn;
    private $table = "specialization";

    public function __construct() {
        $db = new Database();
        $this->conn = $db->connect();
    }

    public function getAll() {
        $sql = "SELECT * FROM {$this->table} ORDER BY SPEC_ID ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function insert($name) {
        $sql = "INSERT INTO {$this->table} (SPEC_NAME, SPEC_CREATED_AT, SPEC_UPDATED_AT)
                VALUES (?, NOW(), NOW())";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$name]);
    }

    public function update($id, $name) {
        $sql = "UPDATE {$this->table} 
                SET SPEC_NAME=?, SPEC_UPDATED_AT=NOW()
                WHERE SPEC_ID=?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$name, $id]);
    }

    public function delete($id) {
        $stmt = $this->conn->prepare("DELETE FROM {$this->table} WHERE SPEC_ID=?");
        return $stmt->execute([$id]);
    }

    public function search($keyword) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE SPEC_NAME LIKE ? 
                ORDER BY SPEC_ID ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(["%{$keyword}%"]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDoctorsBySpecId($specId) {
        $sql = "SELECT d.DOC_ID, d.DOC_FIRST_NAME, d.DOC_LAST_NAME, d.DOC_EMAIL 
                FROM doctor d
                WHERE d.SPEC_ID = ?
                ORDER BY d.DOC_LAST_NAME ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$specId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
