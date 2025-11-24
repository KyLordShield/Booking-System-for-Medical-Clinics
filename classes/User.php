<?php
require_once __DIR__ . '/../config/Database.php';

class User {
    private $conn;
    private $user_table = "users";
    private $patient_table = "patient";

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

            // INSERT USER TABLE
            $sql2 = "INSERT INTO {$this->user_table}
                (USER_NAME, USER_PASSWORD, PAT_ID, USER_IS_SUPERADMIN, USER_CREATED_AT)
                VALUES (:username, :password, :pat_id, 0, NOW())";
            $stmt2 = $this->conn->prepare($sql2);
            $stmt2->execute([
                ':username' => $username,
                ':password' => password_hash($password, PASSWORD_DEFAULT),
                ':pat_id' => $pat_id
            ]);

            $this->conn->commit();
            return "✅ Registration successful!";
        } catch (PDOException $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            return "❌ Registration failed: " . $e->getMessage();
        }

    }


//============ Check if entity already has a user account==================
public function existsByEntity($role, $entity_id) {
    switch($role) {
        case 'Patient':
            $sql = "SELECT USER_ID FROM {$this->user_table} WHERE PAT_ID = :id LIMIT 1";
            break;
        case 'Doctor':
            $sql = "SELECT USER_ID FROM {$this->user_table} WHERE DOC_ID = :id LIMIT 1";
            break;
        case 'Staff':
            $sql = "SELECT USER_ID FROM {$this->user_table} WHERE STAFF_ID = :id LIMIT 1";
            break;
        default: return false;
    }

    $stmt = $this->conn->prepare($sql);
    $stmt->bindParam(':id', $entity_id);
    $stmt->execute();
    return $stmt->rowCount() > 0;
}

// Create user for existing entity
public function createForEntity($role, $entity_id, $username, $password, $is_superadmin = 0) {
    try {
        if ($this->usernameExists($username)) {
            return "⚠️ Username already exists.";
        }

        $data = [
            'PAT_ID' => null,
            'DOC_ID' => null,
            'STAFF_ID' => null,
            'username' => $username,
            'password' =>  password_hash($password, PASSWORD_DEFAULT), // store as plain text (not recommended for production)

            'is_superadmin' => $is_superadmin
        ];

        switch($role){
            case 'Patient': $data['PAT_ID'] = $entity_id; break;
            case 'Doctor': $data['DOC_ID'] = $entity_id; break;
            case 'Staff': $data['STAFF_ID'] = $entity_id; break;
        }

        $sql = "INSERT INTO {$this->user_table} (USER_NAME, USER_PASSWORD, USER_IS_SUPERADMIN, PAT_ID, DOC_ID, STAFF_ID, USER_CREATED_AT)
                VALUES (:username, :password, :is_superadmin, :PAT_ID, :DOC_ID, :STAFF_ID, NOW())";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($data);

        return "✅ User created successfully!";
    } catch (PDOException $e) {
        return "❌ Failed to create user: " . $e->getMessage();
    }
}

// Delete user by USER_ID
public function delete($user_id) {
    $stmt = $this->conn->prepare("DELETE FROM users WHERE USER_ID = ?");
    return $stmt->execute([$user_id]);
}

// Update user by USER_ID
public function updateUser($user_id, $username, $password) {
    $stmt = $this->conn->prepare("
        UPDATE users SET USER_NAME = ?, USER_PASSWORD = ? WHERE USER_ID = ?
    ");
    return $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT), $user_id]);

}



// CONNECTS FROM THE PRIVATE USER CLASS AND USE THIS TO ACCeSS
public function isUsernameTaken($username, $exclude_user_id = null) {
    $sql = "SELECT USER_ID FROM {$this->user_table} WHERE USER_NAME = :username";
    if ($exclude_user_id) {
        $sql .= " AND USER_ID != :exclude_id";
    }

    $stmt = $this->conn->prepare($sql);
    $stmt->bindParam(':username', $username);
    if ($exclude_user_id) {
        $stmt->bindParam(':exclude_id', $exclude_user_id, PDO::PARAM_INT);
    }
    $stmt->execute();
    return $stmt->rowCount() > 0;
}

}
?>
