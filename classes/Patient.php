<?php
require_once __DIR__ . '/../config/Database.php';

class Patient {
    private $conn;
    private $table = "PATIENT";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    /* ==================== PRIVATE HELPERS ==================== */
    private function contactExists($contact, $excludeId = null) {
        $sql = "SELECT PAT_ID FROM {$this->table} WHERE PAT_CONTACT_NUM = :contact";
        if ($excludeId) $sql .= " AND PAT_ID != :id";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':contact', $contact);
        if ($excludeId) $stmt->bindParam(':id', $excludeId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    private function emailExists($email, $excludeId = null) {
        $sql = "SELECT PAT_ID FROM {$this->table} WHERE PAT_EMAIL = :email";
        if ($excludeId) $sql .= " AND PAT_ID != :id";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':email', $email);
        if ($excludeId) $stmt->bindParam(':id', $excludeId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    /* ==================== PUBLIC METHODS ==================== */

    // Fetch patient info by PAT_ID
    public function getPatientById($pat_id) {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE PAT_ID = :pat_id LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':pat_id', $pat_id);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return ['error' => $e->getMessage()];
        }
    }

    // Update patient info
    public function updatePatient($pat_id, $data) {
        try {
            // Pre-check duplicates (excluding current patient)
            if ($this->contactExists($data['contact'], $pat_id)) {
                throw new Exception('Contact number is already in use by another patient.');
            }
            if ($this->emailExists($data['email'], $pat_id)) {
                throw new Exception('Email is already registered to another patient.');
            }

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
                ':fname' => $data['first'],
                ':mname' => $data['middle'],
                ':lname' => $data['last'],
                ':dob' => $data['dob'],
                ':gender' => $data['gender'],
                ':contact' => $data['contact'],
                ':email' => $data['email'],
                ':address' => $data['address'],
                ':pat_id' => $pat_id
            ]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            // Catch duplicate entry (1062)
            if ($e->getCode() == '23000') {
                if (strpos($e->getMessage(), 'PAT_CONTACT_NUM') !== false) {
                    throw new Exception('Contact number is already in use by another patient.');
                }
                if (strpos($e->getMessage(), 'PAT_EMAIL') !== false) {
                    throw new Exception('Email is already registered to another patient.');
                }
            }
            throw new Exception('Failed to update patient: ' . $e->getMessage());
        }
    }

    // Insert new patient
    public function insertPatient($data) {
        try {
            // Pre-check duplicates
            if ($this->contactExists($data['contact'])) {
                throw new Exception('Contact number is already in use by another patient.');
            }
            if ($this->emailExists($data['email'])) {
                throw new Exception('Email is already registered to another patient.');
            }

            $sql = "INSERT INTO {$this->table} 
                    (PAT_FIRST_NAME, PAT_MIDDLE_INIT, PAT_LAST_NAME, PAT_DOB, PAT_GENDER, 
                     PAT_CONTACT_NUM, PAT_EMAIL, PAT_ADDRESS, PAT_CREATED_AT, PAT_UPDATED_AT)
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
            return $this->conn->lastInsertId(); // Return new PAT_ID
        } catch (PDOException $e) {
            // Fallback: catch DB-level duplicate error (1062)
            if ($e->getCode() == '23000') {
                if (strpos($e->getMessage(), 'PAT_CONTACT_NUM') !== false) {
                    throw new Exception('Contact number is already in use by another patient.');
                }
                if (strpos($e->getMessage(), 'PAT_EMAIL') !== false) {
                    throw new Exception('Email is already registered to another patient.');
                }
            }
            throw new Exception('Failed to add patient: ' . $e->getMessage());
        }
    }

    // Delete patient
    public function deletePatient($pat_id) {
        try {
            $sql = "DELETE FROM {$this->table} WHERE PAT_ID = :pat_id";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([':pat_id' => $pat_id]);
        } catch (PDOException $e) {
            throw new Exception('Failed to delete patient: ' . $e->getMessage());
        }
    }

    // Fetch all patients
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

    // Search patients by first or last name
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

    // Takes all Patients with user accounts for admin page manage user
    public function getAllWithUsers() {
        $sql = "SELECT p.*, u.USER_ID, u.USER_NAME, u.USER_PASSWORD
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