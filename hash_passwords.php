<?php
require_once __DIR__ . '/config/Database.php';

$database = new Database();
$conn = $database->connect();

try {
    $stmt = $conn->query("SELECT USER_ID, USER_PASSWORD FROM USERS");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($users as $user) {
        $userId = $user['USER_ID'];
        $password = $user['USER_PASSWORD'];

        // Skip if password already looks like a hash (starts with $2y$)
        if (substr($password, 0, 4) === '$2y$') {
            continue;
        }

        // Hash the plain-text password
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        $update = $conn->prepare("UPDATE USERS SET USER_PASSWORD = :pass WHERE USER_ID = :id");
        $update->execute([
            ':pass' => $hashed,
            ':id'   => $userId
        ]);

        echo "Updated USER_ID $userId\n";
    }

    echo "All old passwords hashed successfully!";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
