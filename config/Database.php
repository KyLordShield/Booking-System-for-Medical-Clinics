<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('Asia/Manila');
class Database {
    // Database credentials
    private $host = "localhost";
    private $dbname = "medical_booking";
    private $username = "root";
    private $password = "";
    private $conn;

    // Connect to the database
    public function connect() {
        if ($this->conn === null) {
            try {
                // Create a PDO connection
                $this->conn = new PDO(
                    "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4",
                    $this->username,
                    $this->password
                );

                // Set PDO error mode to exception
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            } catch (PDOException $e) {
                echo "Connection failed: " . $e->getMessage();
            }
        }

        return $this->conn;
    }
}
?>
