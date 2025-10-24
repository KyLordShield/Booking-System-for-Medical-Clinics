<?php
require_once __DIR__ . '/../config/Database.php';

class User {
    private $conn;
    private $user_table = "USERS";
    private $patient_table = "PATIENT";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    // Check if username already exists
    public function usernameExists($username) {
        $sql = "SELECT USER_ID FROM {$this->user_table} WHERE USER_NAME = :username LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    // Register a new patient + user
    public function registerPatient($fname, $mname, $lname, $dob, $gender, $contact, $email, $address, $username, $password) {
        try {
            $this->conn->beginTransaction();

            // Insert into PATIENT
            $sql1 = "INSERT INTO {$this->patient_table}
                (PAT_FIRST_NAME, PAT_MIDDLE_INIT, PAT_LAST_NAME, PAT_DOB, PAT_GENDER, PAT_CONTACT_NUM, PAT_EMAIL, PAT_ADDRESS, PAT_CREATED_AT)
                VALUES (:fname, :mname, :lname, :dob, :gender, :contact, :email, :address, NOW())";
            $stmt1 = $this->conn->prepare($sql1);
            $stmt1->execute([
                ':fname' => $fname,
                ':mname' => $mname,
                ':lname' => $lname,
                ':dob' => $dob,
                ':gender' => $gender,
                ':contact' => $contact,
                ':email' => $email,
                ':address' => $address
            ]);

            $pat_id = $this->conn->lastInsertId();

            // Insert into USER
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $sql2 = "INSERT INTO {$this->user_table}
                (USER_NAME, USER_PASSWORD, PAT_ID, USER_IS_SUPERADMIN, USER_CREATED_AT)
                VALUES (:username, :password, :pat_id, 0, NOW())";
            $stmt2 = $this->conn->prepare($sql2);
            $stmt2->execute([
                ':username' => $username,
                ':password' => $hashed,
                ':pat_id' => $pat_id
            ]);

            $this->conn->commit();
            return "✅ Registration successful!";
        } catch (PDOException $e) {
            $this->conn->rollBack();
            return "❌ Registration failed: " . $e->getMessage();
        }
    }
}
?>
