<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('Asia/Manila');

class Database {

    private $conn;

    public function connect() {

        // Get environment variables for Heroku
        $host = getenv('DB_HOST');
        $port = getenv('DB_PORT');
        $dbname = getenv('DB_NAME');
        $username = getenv('DB_USER');
        $password = getenv('DB_PASS');

        // Fallback to localhost (XAMPP)
        if (!$host || !$dbname || !$username || !$password) {
            $host = "localhost";
            $dbname = "medical_booking";
            $username = "root";
            $password = "";
            $port = 3306; // ADD THIS
        }

        // Ensure port is ALWAYS set
        if (!$port) {
            $port = 3306;
        }

        if ($this->conn === null) {
            try {
                $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";

                $this->conn = new PDO($dsn, $username, $password);
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            } catch (PDOException $e) {
                die("Database connection failed: " . $e->getMessage());
            }
        }

        return $this->conn;
    }
}
?>
