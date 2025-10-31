<?php
require_once __DIR__ . '/../config/Database.php';

class Payment extends Database
{
    // Fetch all payments (joins with appointment, patient, doctor, method, status)
    public function getAllPayments()
{
    $sql = "SELECT 
                p.PYMT_ID,
                p.APPT_ID,
                p.PYMT_AMOUNT_PAID,
                p.PYMT_DATE,
                m.PYMT_METH_NAME,
                s.PYMT_STAT_NAME
            FROM PAYMENT p
            LEFT JOIN PAYMENT_METHOD m ON p.PYMT_METH_ID = m.PYMT_METH_ID
            LEFT JOIN PAYMENT_STATUS s ON p.PYMT_STAT_ID = s.PYMT_STAT_ID
            ORDER BY p.PYMT_ID DESC";
    
    $stmt = $this->connect()->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


    // Add payment
    public function addPayment($amount, $date, $method_id, $status_id, $appt_id) {
    try {
        if (empty($amount) || empty($method_id) || empty($status_id) || empty($appt_id)) {
            return ['success' => false, 'message' => 'All fields are required.'];
        }

        $conn = $this->connect(); // ✅ get PDO connection

        // ✅ Check appointment existence (Varchar ID like 2025-11-0000002)
        $check = $conn->prepare("SELECT APPT_ID FROM APPOINTMENT WHERE APPT_ID = ?");
        $check->execute([$appt_id]);
        if ($check->rowCount() === 0) {
            return ['success' => false, 'message' => 'Invalid Appointment ID: ' . $appt_id];
        }

        // ✅ Check foreign keys (optional)
        $methCheck = $conn->prepare("SELECT PYMT_METH_ID FROM PAYMENT_METHOD WHERE PYMT_METH_ID = ?");
        $methCheck->execute([$method_id]);
        if ($methCheck->rowCount() === 0) {
            return ['success' => false, 'message' => 'Invalid Payment Method ID.'];
        }

        $statCheck = $conn->prepare("SELECT PYMT_STAT_ID FROM PAYMENT_STATUS WHERE PYMT_STAT_ID = ?");
        $statCheck->execute([$status_id]);
        if ($statCheck->rowCount() === 0) {
            return ['success' => false, 'message' => 'Invalid Payment Status ID.'];
        }

        // ✅ Insert payment
        $stmt = $conn->prepare("
            INSERT INTO PAYMENT (PYMT_AMOUNT_PAID, PYMT_DATE, PYMT_METH_ID, PYMT_STAT_ID, APPT_ID)
            VALUES (?, ?, ?, ?, ?)
        ");
        $success = $stmt->execute([$amount, $date, $method_id, $status_id, $appt_id]);

        if (!$success) {
            $errorInfo = $stmt->errorInfo();
            return ['success' => false, 'message' => 'SQL Error: ' . implode(' | ', $errorInfo)];
        }

        return ['success' => true, 'message' => '✅ Payment added successfully!'];

    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Database Error: ' . $e->getMessage()];
    }
}





    // Update payment
    public function updatePayment($id, $amount, $date, $method_id, $status_id)
    {
        $sql = "UPDATE PAYMENT 
                SET PYMT_AMOUNT_PAID = :amount, 
                    PYMT_DATE = :date, 
                    PYMT_METH_ID = :method_id, 
                    PYMT_STAT_ID = :status_id
                WHERE PYMT_ID = :id";
        $stmt = $this->connect()->prepare($sql);
        return $stmt->execute([
            ':amount' => $amount,
            ':date' => $date,
            ':method_id' => $method_id,
            ':status_id' => $status_id,
            ':id' => $id
        ]);
    }

    // Delete payment
    public function deletePayment($id)
    {
        $sql = "DELETE FROM PAYMENT WHERE PYMT_ID = :id";
        $stmt = $this->connect()->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    // Get single payment
    public function getPaymentById($id)
    {
        $sql = "SELECT * FROM PAYMENT WHERE PYMT_ID = :id";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
