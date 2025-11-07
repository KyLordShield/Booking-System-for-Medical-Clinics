<?php
require_once __DIR__ . '/../config/Database.php';

class Service {
    private $conn;
    private $table = "SERVICE";

    public function __construct() {
        $db = new Database();
        $this->conn = $db->connect();
    }

    // Fetch all services with their specialization names
    public function getAllServices() {
        $sql = "SELECT s.*, sp.SPEC_NAME 
                FROM {$this->table} s
                LEFT JOIN specialization sp ON s.SPEC_ID = sp.SPEC_ID
                ORDER BY s.SERV_ID ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get a single service by ID
    public function getServiceById($id) {
        $sql = "SELECT s.*, sp.SPEC_NAME 
                FROM {$this->table} s
                LEFT JOIN specialization sp ON s.SPEC_ID = sp.SPEC_ID
                WHERE s.SERV_ID = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Add new service (includes specialization)
    public function createService($name, $description, $price, $spec_id) {
        $sql = "INSERT INTO {$this->table} 
                (SERV_NAME, SERV_DESCRIPTION, SERV_PRICE, SPEC_ID) 
                VALUES (:name, :description, :price, :spec_id)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':price', $price);
        $stmt->bindParam(':spec_id', $spec_id);
        
        return $stmt->execute();
    }

    // Update existing service (includes specialization)
    public function updateService($id, $name, $description, $price, $spec_id) {
        $sql = "UPDATE {$this->table} 
                SET SERV_NAME = :name, 
                    SERV_DESCRIPTION = :description,
                    SERV_PRICE = :price,
                    SPEC_ID = :spec_id
                WHERE SERV_ID = :id";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':price', $price);
        $stmt->bindParam(':spec_id', $spec_id);
        
        return $stmt->execute();
    }

    // Delete service
    public function deleteService($id) {
        $sql = "DELETE FROM {$this->table} WHERE SERV_ID = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    // Check if service exists
    public function serviceExists($id) {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE SERV_ID = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    }
}
?>
