<?php
require_once __DIR__ . '/../config/Database.php';

class Login {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    public function authenticate($username, $password) {
        try {
            $sql = "SELECT * FROM USERS WHERE USER_NAME = :username LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':username', $username);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                // ✅ For now, use plain text comparison (testing mode)
                if ($password === $user['USER_PASSWORD']) {
                    // Update last login
                    $update = $this->conn->prepare("UPDATE USERS SET USER_LAST_LOGIN = NOW() WHERE USER_ID = :id");
                    $update->bindParam(':id', $user['USER_ID']);
                    $update->execute();

                    // Determine user type
                    if ($user['USER_IS_SUPERADMIN']) {
                        return ['role' => 'admin', 'id' => $user['USER_ID']];
                    } elseif (!empty($user['PAT_ID'])) {
                        return ['role' => 'patient', 'id' => $user['PAT_ID']];
                    } elseif (!empty($user['STAFF_ID'])) {
                        return ['role' => 'staff', 'id' => $user['STAFF_ID']];
                    } elseif (!empty($user['DOC_ID'])) {
                        return ['role' => 'doctor', 'id' => $user['DOC_ID']];
                    } else {
                        return ['role' => 'unknown'];
                    }
                } else {
                    return false; // Invalid password
                }
            } else {
                return false; // No user found
            }
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
            return false;
        }
    }
}
?>
