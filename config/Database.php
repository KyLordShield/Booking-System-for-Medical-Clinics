<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('Asia/Manila');

class Database {
    private $conn;

    public function connect() {

        $host = getenv('DB_HOST') ?: 'localhost';
        $port = getenv('DB_PORT') ?: 3306;
        $dbname = getenv('DB_NAME') ?: 'medicina';
        $username = getenv('DB_USER') ?: 'root';
        $password = getenv('DB_PASS') ?: '';
        $useSSL = getenv('DB_SSL') == "1";  // SSL enabled only when DB_SSL=1

        if ($this->conn === null) {
            try {
                $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";

                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                ];

                // Use SSL only when connecting to Aiven
                if ($useSSL) {
                    $options[PDO::MYSQL_ATTR_SSL_CA] = __DIR__ . "/aiven-ca.pem";
                    $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
                }

                $this->conn = new PDO($dsn, $username, $password, $options);

            } catch (PDOException $e) {
                die("Database connection failed: " . $e->getMessage());
            }
        }

        return $this->conn;
    }
}
