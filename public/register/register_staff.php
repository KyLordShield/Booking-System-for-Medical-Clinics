<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../../index.php");
    exit;
}

require_once dirname(__DIR__, 2) . '/classes/User.php';
require_once dirname(__DIR__, 2) . '/config/Database.php';

$db = new Database();
$conn = $db->connect();
$userObj = new User();

// Get staff ID from redirect
$staff_id = $_GET['staff_id'] ?? null;

if (!$staff_id || !is_numeric($staff_id)) {
    echo "No valid staff ID provided.";
    exit;
}

$staff_id = (int)$staff_id;

// Fetch staff info
$stmt = $conn->prepare("SELECT * FROM staff WHERE STAFF_ID = ?");
$stmt->execute([$staff_id]);
$staff = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$staff) {
    echo "Staff not found.";
    exit;
}

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $confirm  = trim($_POST['confirm']);

    try {
        // 1. Password confirmation
        if ($password !== $confirm) {
            throw new Exception("Passwords do not match.");
        }

        // 2. Check if staff already has an account
        if ($userObj->existsByEntity('Staff', $staff_id)) {
            throw new Exception("This staff already has a user account.");
        }

        // 3. Check if username already exists
        if ($userObj->isUsernameTaken($username)) {
            throw new Exception("Username is already taken.");
        }

        // 4. Optional: Check if email already used as patient (if you have emailExists in User)
        $staffEmail = $staff['STAFF_EMAIL'];
        if (method_exists($userObj, 'emailExists')) {
            $emailExistsMethod = new ReflectionMethod(User::class, 'emailExists');
            $emailExistsMethod->setAccessible(true);
            if ($emailExistsMethod->invoke($userObj, $staffEmail)) {
                throw new Exception("Email already exists in the system (patient record).");
            }
        }

        // 5. Create user account
        $result = $userObj->createForEntity('Staff', $staff_id, $username, $password, 0);

        if (str_starts_with($result, "User created successfully!")) {
            $success = "User account created successfully!";
        } else {
            throw new Exception($result);
        }

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Register Staff User</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="../../assets/css/style.css">
<style>
    :root {
        --primary: #1d4ed8; /* Match your theme */
    }
</style>
</head>
<body class="p-6">

<div class="max-w-3xl mx-auto bg-white shadow-lg rounded-xl p-8">

    <h2 class="text-3xl font-bold mb-6 text-[var(--primary)]">
        Staff Successfully Added
    </h2>

    <table class="min-w-full bg-white border border-gray-300 rounded-lg overflow-hidden">
        <tbody>
            <tr class="border-b">
                <th class="px-4 py-3 bg-gray-100 text-left font-semibold w-40">ID</th>
                <td class="px-4 py-3"><?= htmlspecialchars($staff['STAFF_ID']) ?></td>
            </tr>
            <tr class="border-b">
                <th class="px-4 py-3 bg-gray-100 text-left font-semibold">Name</th>
                <td class="px-4 py-3">
                    <?= htmlspecialchars($staff['STAFF_FIRST_NAME']) ?>
                    <?= $staff['STAFF_MIDDLE_INIT'] ? htmlspecialchars($staff['STAFF_MIDDLE_INIT']).'. ' : '' ?>
                    <?= htmlspecialchars($staff['STAFF_LAST_NAME']) ?>
                </td>
            </tr>
            <tr class="border-b">
                <th class="px-4 py-3 bg-gray-100 text-left font-semibold">Email</th>
                <td class="px-4 py-3"><?= htmlspecialchars($staff['STAFF_EMAIL']) ?></td>
            </tr>
            <tr>
                <th class="px-4 py-3 bg-gray-100 text-left font-semibold">Contact</th>
                <td class="px-4 py-3"><?= htmlspecialchars($staff['STAFF_CONTACT_NUM']) ?></td>
            </tr>
        </tbody>
    </table>

    <br><br>

    <h3 class="text-2xl font-semibold mb-4 text-gray-700">
        Create Login Account for This Staff
    </h3>

    <?php if ($error): ?>
        <div class="bg-red-100 text-red-700 px-4 py-3 rounded-lg mb-4 border border-red-300">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="bg-green-100 text-green-700 px-4 py-3 rounded-lg mb-4 border border-green-300">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="space-y-4">

        <div>
            <label class="block font-medium mb-1">Username</label>
            <input type="text" name="username"
                class="w-full px-4 py-2 border rounded-lg focus:ring focus:ring-blue-300"
                required minlength="3">
        </div>

        <div>
            <label class="block font-medium mb-1">Password</label>
            <input type="password" name="password"
                class="w-full px-4 py-2 border rounded-lg focus:ring focus:ring-blue-300"
                required minlength="6">
        </div>

        <div>
            <label class="block font-medium mb-1">Confirm Password</label>
            <input type="password" name="confirm"
                class="w-full px-4 py-2 border rounded-lg focus:ring focus:ring-blue-300"
                required minlength="6">
        </div>

        <button type="submit"
            class="w-full bg-[var(--primary)] text-white py-2 rounded-lg text-lg font-semibold hover:opacity-90 transition">
            Create Account
        </button>
        
    </form>

    <!-- Back Button -->
    <a href="../../public/staff_pages/staff_manage.php"
       class="inline-block mt-6 px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition">
       Back
    </a>

</div>

</body>
</html>