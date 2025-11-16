<?php
require_once __DIR__ . '/../config/Database.php';

class Staff {
    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->connect();
    }

   public function getAll() {
    $sql = "SELECT 
                s.STAFF_ID,
                s.STAFF_FIRST_NAME,
                s.STAFF_MIDDLE_INIT,
                s.STAFF_LAST_NAME,
                s.STAFF_EMAIL,
                s.STAFF_CONTACT_NUM,
                u.USER_NAME
            FROM STAFF s
            LEFT JOIN USERS u ON s.STAFF_ID = u.STAFF_ID
            ORDER BY s.STAFF_ID DESC";
    
    $stmt = $this->conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}



    // ✅ Get single staff by ID
    public function getById($id) {
        $sql = "SELECT * FROM STAFF WHERE STAFF_ID = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // ✅ Add new staff
    public function create($firstName, $lastName, $role) {
        $sql = "INSERT INTO STAFF (STAFF_FIRST_NAME, STAFF_LAST_NAME, STAFF_ROLE) 
                VALUES (?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$firstName, $lastName, $role]);
    }

    // ✅ Update staff info
    public function update($id, $firstName, $lastName, $role) {
        $sql = "UPDATE STAFF 
                SET STAFF_FIRST_NAME = ?, STAFF_LAST_NAME = ?, STAFF_ROLE = ?
                WHERE STAFF_ID = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$firstName, $lastName, $role, $id]);
    }

    // ✅ Delete staff
    public function delete($id) {
        $sql = "DELETE FROM STAFF WHERE STAFF_ID = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$id]);
    }




    //GET ALL STAFF WHO ALREADY HAVE A USER ACCOUNTS FOR THE ADMIN USER MANAGE PAGE
   public function getAllWithUsers() {
    $sql = "SELECT st.*, u.USER_ID, u.USER_NAME, u.USER_PASSWORD, u.USER_LAST_LOGIN, u.USER_CREATED_AT
            FROM STAFF st
            LEFT JOIN USERS u ON st.STAFF_ID = u.STAFF_ID
            ORDER BY st.STAFF_ID DESC";
    $stmt = $this->conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


// Get all staff who don't have a user account yet
public function getStaffWithoutUser() {
    $sql = "SELECT st.*
            FROM STAFF st
            LEFT JOIN USERS u ON st.STAFF_ID = u.STAFF_ID
            WHERE u.USER_ID IS NULL
            ORDER BY st.STAFF_ID ASC";
    $stmt = $this->conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


}
?>
