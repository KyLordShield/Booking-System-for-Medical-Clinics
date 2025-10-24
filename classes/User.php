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

    // Check if username exists
    private function usernameExists($username) {
        $sql = "SELECT USER_ID FROM {$this->user_table} WHERE USER_NAME = :username LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    // Check if email exists
    private function emailExists($email) {
        $sql = "SELECT PAT_ID FROM {$this->patient_table} WHERE PAT_EMAIL = :email LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    // Check if contact exists
    private function contactExists($contact) {
        $sql = "SELECT PAT_ID FROM {$this->patient_table} WHERE PAT_CONTACT_NUM = :contact LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':contact', $contact);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    // Register a new patient + user
    public function registerPatient($fname, $mname, $lname, $dob, $gender, $contact, $email, $address, $username, $password) {
        try {
            // ✅ Validate empty required fields
            if (empty($fname) || empty($lname) || empty($dob) || empty($gender) ||
                empty($contact) || empty($email) || empty($address) || empty($username) || empty($password)) {
                return "⚠️ Please fill in all required fields.";
            }

            // ✅ Check duplicates before inserting
            if ($this->usernameExists($username)) {
                return "⚠️ Username already exists. Please choose another.";
            }
            if ($this->emailExists($email)) {
                return "⚠️ Email is already registered.";
            }
            if ($this->contactExists($contact)) {
                return "⚠️ Contact number is already in use.";
            }

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

            // Plain text password insert (temporary)
            $sql2 = "INSERT INTO {$this->user_table}
                (USER_NAME, USER_PASSWORD, PAT_ID, USER_IS_SUPERADMIN, USER_CREATED_AT)
                VALUES (:username, :password, :pat_id, 0, NOW())";
            $stmt2 = $this->conn->prepare($sql2);
            $stmt2->execute([
                ':username' => $username,
                ':password' => $password,
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
