<?php
require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../classes/User.php';


$database = new Database();
$db = $database->connect();
$user = new User($db);

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fname = $_POST['fname'];
    $lname = $_POST['lname'];
    $email = $_POST['email'];
    $contact = $_POST['contact'];
    $username = $_POST['username'];
    $password = $_POST['password'];

    if ($user->registerPatient($fname, $lname, $email, $contact, $username, $password)) {
        $message = "<div class='alert alert-success'>Registration successful! You can now <a href='login.php'>login</a>.</div>";
    } else {
        $message = "<div class='alert alert-danger'>Registration failed.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Patient Registration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="col-md-6 mx-auto">
        <div class="card p-4 shadow">
            <h3 class="text-center mb-3">Register as a Patient</h3>
            <?= $message ?>

            <form method="POST" action="">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label>First Name</label>
                        <input type="text" name="fname" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Last Name</label>
                        <input type="text" name="lname" class="form-control" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label>Contact Number</label>
                    <input type="text" name="contact" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label>Username</label>
                    <input type="text" name="username" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>

                <button type="submit" class="btn btn-primary w-100">Register</button>
            </form>
        </div>
    </div>
</div>

</body>
</html>
