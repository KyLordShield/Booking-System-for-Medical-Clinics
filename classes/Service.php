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
        $sql = "SELECT * FROM {$this->table} ORDER BY SERV_ID ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getServiceById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE SERV_ID = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Add new service
    public function createService($name, $description, $price) {
        $sql = "INSERT INTO {$this->table} (SERV_NAME, SERV_DESCRIPTION, SERV_PRICE) 
                VALUES (:name, :description, :price)";
        
        $stmt = $this->conn->prepare($sql);
        
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':price', $price);
        
        return $stmt->execute();
    }

    // Update existing service
    public function updateService($id, $name, $description, $price) {
        $sql = "UPDATE {$this->table} 
                SET SERV_NAME = :name, 
                    SERV_DESCRIPTION = :description,
                    SERV_PRICE = :price 
                WHERE SERV_ID = :id";
        
        $stmt = $this->conn->prepare($sql);
        
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':price', $price);
        
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
