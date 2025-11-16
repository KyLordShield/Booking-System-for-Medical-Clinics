<?php
require_once dirname(__DIR__, 1) . '/config/Database.php';

class Doctor {
    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->connect();
    }

    /* ==================== PRIVATE HELPERS ==================== */
    private function contactExists($contact, $excludeId = null) {
        $sql = "SELECT DOC_ID FROM DOCTOR WHERE DOC_CONTACT_NUM = :contact";
        if ($excludeId) $sql .= " AND DOC_ID != :id";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':contact', $contact);
        if ($excludeId) $stmt->bindParam(':id', $excludeId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    private function emailExists($email, $excludeId = null) {
        $sql = "SELECT DOC_ID FROM DOCTOR WHERE DOC_EMAIL = :email";
        if ($excludeId) $sql .= " AND DOC_ID != :id";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':email', $email);
        if ($excludeId) $stmt->bindParam(':id', $excludeId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    /* ==================== PUBLIC METHODS ==================== */

    public function getAll($excludeDocId = null, $search = null) {
        $sql = "SELECT d.*, s.SPEC_NAME
                FROM doctor d
                LEFT JOIN specialization s ON d.SPEC_ID = s.SPEC_ID
                WHERE 1";

        $params = [];

        if ($excludeDocId) {
            $sql .= " AND d.DOC_ID != ?";
            $params[] = $excludeDocId;
        }

        if ($search) {
            $sql .= " AND (d.DOC_FIRST_NAME LIKE ? OR d.DOC_LAST_NAME LIKE ? 
                     OR d.DOC_EMAIL LIKE ? OR d.DOC_CONTACT_NUM LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // DONT DELETE THIS FUNCTION — FOR FILTERING DOCTOR BY SERVICE
    public function getDoctorsByService($serv_id) {
        try {
            $sql = "SELECT SPEC_ID FROM SERVICE WHERE SERV_ID = :serv_id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':serv_id', $serv_id, PDO::PARAM_INT);
            $stmt->execute();
            $service = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$service) return [];

            $spec_id = $service['SPEC_ID'];

            $sql = "SELECT D.DOC_ID, D.DOC_FIRST_NAME, D.DOC_LAST_NAME, S.SPEC_NAME
                    FROM DOCTOR D
                    JOIN SPECIALIZATION S ON D.SPEC_ID = S.SPEC_ID
                    WHERE D.SPEC_ID = :spec_id
                    ORDER BY D.DOC_LAST_NAME ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':spec_id', $spec_id, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    public function getById($id) {
        $stmt = $this->conn->prepare("
            SELECT d.*, s.SPEC_NAME
            FROM doctor d
            LEFT JOIN specialization s ON d.SPEC_ID = s.SPEC_ID
            WHERE d.DOC_ID = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /* ==================== INSERT (WITH DUPLICATE CHECK) ==================== */
    public function insert($data) {
        try {
            // Pre-check duplicates
            if ($this->contactExists($data['contact'])) {
                throw new Exception('Contact number is already in use by another doctor.');
            }
            if ($this->emailExists($data['email'])) {
                throw new Exception('Email is already registered to another doctor.');
            }

            $sql = "INSERT INTO doctor 
                    (DOC_FIRST_NAME, DOC_MIDDLE_INIT, DOC_LAST_NAME, DOC_CONTACT_NUM, DOC_EMAIL, SPEC_ID, DOC_CREATED_AT, DOC_UPDATED_AT)
                    VALUES (:first, :middle, :last, :contact, :email, :spec, NOW(), NOW())";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':first'   => $data['first'],
                ':middle'  => $data['middle'],
                ':last'    => $data['last'],
                ':contact' => $data['contact'],
                ':email'   => $data['email'],
                ':spec'    => $data['spec']
            ]);

            return $this->conn->lastInsertId(); // Return new ID
        } catch (PDOException $e) {
            // Fallback: catch DB-level duplicate error (1062)
            if ($e->getCode() == '23000') {
                if (strpos($e->getMessage(), 'DOC_CONTACT_NUM') !== false) {
                    throw new Exception('Contact number is already in use by another doctor.');
                }
                if (strpos($e->getMessage(), 'DOC_EMAIL') !== false) {
                    throw new Exception('Email is already registered to another doctor.');
                }
            }
            throw new Exception('Failed to add doctor: ' . $e->getMessage());
        }
    }

    /* ==================== UPDATE (WITH DUPLICATE CHECK) ==================== */
    public function update($id, $data) {
        try {
            // Pre-check duplicates (excluding current doctor)
            if ($this->contactExists($data['contact'], $id)) {
                throw new Exception('Contact number is already in use by another doctor.');
            }
            if ($this->emailExists($data['email'], $id)) {
                throw new Exception('Email is already registered to another doctor.');
            }

            $sql = "UPDATE doctor SET
                    DOC_FIRST_NAME=?, DOC_MIDDLE_INIT=?, DOC_LAST_NAME=?, 
                    DOC_CONTACT_NUM=?, DOC_EMAIL=?, SPEC_ID=?, DOC_UPDATED_AT=NOW()
                    WHERE DOC_ID=?";

            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([
                $data['first'], $data['middle'], $data['last'],
                $data['contact'], $data['email'], $data['spec'], $id
            ]);
        } catch (PDOException $e) {
            if ($e->getCode() == '23000') {
                if (strpos($e->getMessage(), 'DOC_CONTACT_NUM') !== false) {
                    throw new Exception('Contact number is already in use by another doctor.');
                }
                if (strpos($e->getMessage(), 'DOC_EMAIL') !== false) {
                    throw new Exception('Email is already registered to another doctor.');
                }
            }
            throw new Exception('Failed to update doctor: ' . $e->getMessage());
        }
    }

    public function delete($id) {
        $stmt = $this->conn->prepare("DELETE FROM doctor WHERE DOC_ID=?");
        return $stmt->execute([$id]);
    }

    // TAKES ALL DOCTORS WHO ALREADY HAVE A USER ACCOUNT FOR ADMIN USER MANAGE
    public function getAllWithUsers() {
        $sql = "SELECT d.*, s.SPEC_NAME, u.USER_ID, u.USER_NAME, u.USER_PASSWORD
                FROM DOCTOR d
                LEFT JOIN SPECIALIZATION s ON d.SPEC_ID = s.SPEC_ID
                LEFT JOIN USERS u ON d.DOC_ID = u.DOC_ID
                ORDER BY d.DOC_ID DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get all doctors who don't have a user account yet
    public function getDoctorsWithoutUser() {
        $sql = "SELECT d.*, s.SPEC_NAME
                FROM DOCTOR d
                LEFT JOIN USERS u ON d.DOC_ID = u.DOC_ID
                LEFT JOIN SPECIALIZATION s ON d.SPEC_ID = s.SPEC_ID
                WHERE u.USER_ID IS NULL
                ORDER BY d.DOC_ID ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>