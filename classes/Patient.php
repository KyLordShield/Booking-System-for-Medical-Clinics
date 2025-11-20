<?php
require_once __DIR__ . '/../config/Database.php';

class Patient {
    private $conn;
    private $table = "PATIENT";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

       // 🟢 FIXED - SAFE VERSION (keeps backward compatibility)
    public function getPatientById($pat_id) {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE PAT_ID = :pat_id LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':pat_id', $pat_id, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: [];  // ← This fixes the fatal error forever
        } catch (PDOException $e) {
            error_log("Patient::getPatientById Error: " . $e->getMessage());
            return []; // Never return string or error array
        }
    }

    // 🟢 FIXED - Accepts BOTH old array style AND new individual params
    public function updatePatient($pat_id, $fname, $mname = null, $lname = null, $dob = null, $gender = null, $contact = null, $email = null, $address = null) {
        // If first param after $pat_id is array → old style (backward compatible)
        if (is_array($fname)) {
            extract($fname); // creates $first, $middle, etc.
            $fname = $first ?? $fname['first'] ?? '';
            $mname = $middle ?? $fname['middle'] ?? '';
            $lname = $last ?? $fname['last'] ?? '';
            $dob = $dob ?? $fname['dob'] ?? '';
            $gender = $gender ?? $fname['gender'] ?? '';
            $contact = $contact ?? $fname['contact'] ?? '';
            $email = $email ?? $fname['email'] ?? '';
            $address = $address ?? $fname['address'] ?? '';
        }

        try {
            $sql = "UPDATE {$this->table}
                    SET PAT_FIRST_NAME = :fname,
                        PAT_MIDDLE_INIT = :mname,
                        PAT_LAST_NAME = :lname,
                        PAT_DOB = :dob,
                        PAT_GENDER = :gender,
                        PAT_CONTACT_NUM = :contact,
                        PAT_EMAIL = :email,
                        PAT_ADDRESS = :address,
                        PAT_UPDATED_AT = NOW()
                    WHERE PAT_ID = :pat_id";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':fname'    => $fname,
                ':mname'    => $mname,
                ':lname'    => $lname,
                ':dob'      => $dob,
                ':gender'   => $gender,
                ':contact'  => $contact,
                ':email'    => $email,
                ':address'  => $address,
                ':pat_id'   => $pat_id
            ]);

            return $stmt->rowCount() > 0 
                ? "Profile updated successfully!" 
                : "No changes were made.";
        } catch (PDOException $e) {
            error_log("Patient::updatePatient Error: " . $e->getMessage());
            return "Database error occurred.";
        }
    }

    // 🟩 Insert new patient
    public function insertPatient($data) {
        try {
            $sql = "INSERT INTO {$this->table} 
                    (PAT_FIRST_NAME, PAT_MIDDLE_INIT, PAT_LAST_NAME, PAT_DOB, PAT_GENDER, PAT_CONTACT_NUM, PAT_EMAIL, PAT_ADDRESS, PAT_CREATED_AT, PAT_UPDATED_AT)
                    VALUES (:first, :middle, :last, :dob, :gender, :contact, :email, :address, NOW(), NOW())";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':first' => $data['first'],
                ':middle' => $data['middle'],
                ':last' => $data['last'],
                ':dob' => $data['dob'],
                ':gender' => $data['gender'],
                ':contact' => $data['contact'],
                ':email' => $data['email'],
                ':address' => $data['address']
            ]);
            return $this->conn->lastInsertId();
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }

    // 🟩 Delete patient
    public function deletePatient($pat_id) {
        try {
            $sql = "DELETE FROM {$this->table} WHERE PAT_ID = :pat_id";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([':pat_id' => $pat_id]);
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }

    // 🟦 Fetch all patients
    public function getAllPatients() {
        try {
            $sql = "SELECT * FROM {$this->table} ORDER BY PAT_ID ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    // 🟩 Search patients by first or last name
    public function searchPatients($keyword) {
        try {
            $sql = "SELECT * FROM {$this->table} 
                    WHERE PAT_FIRST_NAME LIKE :kw OR PAT_LAST_NAME LIKE :kw
                    ORDER BY PAT_LAST_NAME ASC";
            $stmt = $this->conn->prepare($sql);
            $kw = "%{$keyword}%";
            $stmt->bindParam(':kw', $kw);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }



   // 🟩 Takes all Patients with user accounts for admin page manage user
   public function getAllWithUsers() {
    $sql = "SELECT p.*, u.USER_ID, u.USER_NAME, u.USER_PASSWORD, u.USER_LAST_LOGIN, u.USER_CREATED_AT
            FROM PATIENT p
            LEFT JOIN USERS u ON p.PAT_ID = u.PAT_ID
            ORDER BY p.PAT_ID DESC";
    $stmt = $this->conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get all patients who don't have a user account yet
public function getPatientsWithoutUser() {
    $sql = "SELECT p.*
            FROM PATIENT p
            LEFT JOIN USERS u ON p.PAT_ID = u.PAT_ID
            WHERE u.USER_ID IS NULL
            ORDER BY p.PAT_ID ASC";
    $stmt = $this->conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}   


}
?>
