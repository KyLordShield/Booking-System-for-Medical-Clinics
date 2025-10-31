<?php
require_once dirname(__DIR__, 1) . '/config/Database.php';

class Doctor {
    private $conn;
    public function __construct() {
        $db = new Database();
        $this->conn = $db->connect();
    }

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

    public function insert($data) {
        $stmt = $this->conn->prepare("
            INSERT INTO doctor 
            (DOC_FIRST_NAME, DOC_MIDDLE_INIT, DOC_LAST_NAME, DOC_CONTACT_NUM, DOC_EMAIL, SPEC_ID, DOC_CREATED_AT, DOC_UPDATED_AT)
            VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        return $stmt->execute([
            $data['first'], $data['middle'], $data['last'],
            $data['contact'], $data['email'], $data['spec']
        ]);
    }

    public function update($id, $data) {
        $stmt = $this->conn->prepare("
            UPDATE doctor SET
            DOC_FIRST_NAME=?, DOC_MIDDLE_INIT=?, DOC_LAST_NAME=?, 
            DOC_CONTACT_NUM=?, DOC_EMAIL=?, SPEC_ID=?, DOC_UPDATED_AT=NOW()
            WHERE DOC_ID=?
        ");
        return $stmt->execute([
            $data['first'], $data['middle'], $data['last'],
            $data['contact'], $data['email'], $data['spec'], $id
        ]);
    }

    public function delete($id) {
        $stmt = $this->conn->prepare("DELETE FROM doctor WHERE DOC_ID=?");
        return $stmt->execute([$id]);
    }
}
