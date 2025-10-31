<?php
require_once __DIR__ . '/../config/Database.php';

class PaymentMethod extends Database
{
    // Fetch all payment methods
    public function getAllMethods()
    {
        $sql = "SELECT * FROM PAYMENT_METHOD ORDER BY PYMT_METH_ID DESC";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Add new payment method
    public function addMethod($name)
    {
        $sql = "INSERT INTO PAYMENT_METHOD (PYMT_METH_NAME) VALUES (:name)";
        $stmt = $this->connect()->prepare($sql);
        return $stmt->execute([':name' => $name]);
    }

    // Update existing method
    public function updateMethod($id, $name)
    {
        $sql = "UPDATE PAYMENT_METHOD 
                SET PYMT_METH_NAME = :name 
                WHERE PYMT_METH_ID = :id";
        $stmt = $this->connect()->prepare($sql);
        return $stmt->execute([
            ':name' => $name,
            ':id' => $id
        ]);
    }

    // Delete method
    public function deleteMethod($id)
    {
        $sql = "DELETE FROM PAYMENT_METHOD WHERE PYMT_METH_ID = :id";
        $stmt = $this->connect()->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    // Get single method by ID
    public function getMethodById($id)
    {
        $sql = "SELECT * FROM PAYMENT_METHOD WHERE PYMT_METH_ID = :id";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
