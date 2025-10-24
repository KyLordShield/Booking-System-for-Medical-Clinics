<?php
require_once __DIR__ . '/../config/Database.php'; // ✅ fixed path


class User {
    private $conn;
    private $user_table = "user";
    private $patient_table = "patient";

    public function __construct($db) {
        $this->conn = $db;
    }

    // 🟢 Register new patient + user
    public function registerPatient($fname, $lname, $email, $contact, $username, $password) {
        try {
            $this->conn->beginTransaction();

            // Step 1: Insert into PATIENT
            $query1 = "INSERT INTO {$this->patient_table} 
                       (PAT_FIRST_NAME, PAT_LAST_NAME, PAT_EMAIL, PAT_CONTACT_NUM, PAT_CREATED_AT)
                       VALUES (:fname, :lname, :email, :contact, NOW())";
            $stmt1 = $this->conn->prepare($query1);
            $stmt1->execute([
                ':fname' => $fname,
                ':lname' => $lname,
                ':email' => $email,
                ':contact' => $contact
            ]);

            $pat_id = $this->conn->lastInsertId(); // get the new PAT_ID

            // Step 2: Insert into USER
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $query2 = "INSERT INTO {$this->user_table}
                       (USER_NAME, USER_PASSWORD, PAT_ID, USER_CREATED_AT)
                       VALUES (:username, :password, :pat_id, NOW())";
            $stmt2 = $this->conn->prepare($query2);
            $stmt2->execute([
                ':username' => $username,
                ':password' => $hashed,
                ':pat_id' => $pat_id
            ]);

            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            $this->conn->rollBack();
            echo "Registration failed: " . $e->getMessage();
            return false;
        }
    }
}
?>
