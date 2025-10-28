<?php
require_once __DIR__ . '/../config/Database.php';

class Service {
    private $conn;
    private $table = "SERVICE";

    public function __construct() {
        $db = new Database();
        $this->conn = $db->connect();
    }

    public function getAllServices() {
        $sql = "SELECT SERV_ID, SERV_NAME FROM {$this->table} ORDER BY SERV_NAME ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
