<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once dirname(__DIR__, 2) . '/classes/Doctor.php';
require_once dirname(__DIR__, 2) . '/classes/User.php';

$doctorObj = new Doctor();
$userObj = new User();

// Get doctor ID from redirect
$docId = $_GET['doc'] ?? null;

if (!$docId) {
    echo "No doctor ID provided.";
    exit;
}

$doctor = $doctorObj->getById($docId);
if (!$doctor) {
    echo "Doctor not found.";
    exit;
}

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $confirm  = trim($_POST['confirm']);

    try {

        /** ---------------------------
         * 1. Password confirmation
         * --------------------------- */
        if ($password !== $confirm) {
            throw new Exception("Passwords do not match.");
        }

        /** ---------------------------
         * 2. Check if doctor already has an account
         * --------------------------- */
        if ($userObj->existsByEntity('Doctor', $docId)) {
            throw new Exception("This doctor already has a user account.");
        }

        /** ---------------------------
         * 3. Check if username already exists
         * --------------------------- */
        if ($userObj->isUsernameTaken($username)) {
            throw new Exception("Username is already taken.");
        }

        /** ---------------------------
         * 4. Check if doctor email already exists as patient
         * (Using your existing `emailExists()` method)
         * --------------------------- */
        $doctorEmail = $doctor['DOC_EMAIL'];

        // emailExists() checks PATIENT table - optional but safe check
        $emailExistsMethod = new ReflectionMethod(User::class, 'emailExists');
        $emailExistsMethod->setAccessible(true);

        if ($emailExistsMethod->invoke($userObj, $doctorEmail)) {
            throw new Exception("Email already exists in the system (patient record).");
        }

        /** ---------------------------
         * 5. Create user account
         * --------------------------- */
        $result = $userObj->createForEntity('Doctor', $docId, $username, $password, 0);

        if (str_starts_with($result, "✅")) {
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
<title>Register Doctor User</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body class="p-6">

<div class="max-w-3xl mx-auto bg-white shadow-lg rounded-xl p-8">
    

    <h2 class="text-3xl font-bold mb-6 text-[var(--primary)]">
        Doctor Successfully Added
    </h2>

    <table class="min-w-full bg-white border border-gray-300 rounded-lg overflow-hidden">
        <tbody>
            <tr class="border-b">
                <th class="px-4 py-3 bg-gray-100 text-left font-semibold w-40">ID</th>
                <td class="px-4 py-3"><?= htmlspecialchars($doctor['DOC_ID']) ?></td>
            </tr>
            <tr class="border-b">
                <th class="px-4 py-3 bg-gray-100 text-left font-semibold">Name</th>
                <td class="px-4 py-3">
                    <?= htmlspecialchars($doctor['DOC_FIRST_NAME']." ".$doctor['DOC_LAST_NAME']) ?>
                </td>
            </tr>
            <tr class="border-b">
                <th class="px-4 py-3 bg-gray-100 text-left font-semibold">Email</th>
                <td class="px-4 py-3"><?= htmlspecialchars($doctor['DOC_EMAIL']) ?></td>
            </tr>
            <tr>
                <th class="px-4 py-3 bg-gray-100 text-left font-semibold">Contact</th>
                <td class="px-4 py-3"><?= htmlspecialchars($doctor['DOC_CONTACT_NUM']) ?></td>
            </tr>
        </tbody>
    </table>

    <br><br>

    <h3 class="text-2xl font-semibold mb-4 text-gray-700">
        Create Login Account for This Doctor
    </h3>

    <?php if ($error): ?>
        <div class="bg-red-100 text-red-700 px-4 py-3 rounded-lg mb-4 border border-red-300">
            <?= $error ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="bg-green-100 text-green-700 px-4 py-3 rounded-lg mb-4 border border-green-300">
            <?= $success ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="space-y-4">

        <div>
            <label class="block font-medium mb-1">Username</label>
            <input type="text" name="username"
                class="w-full px-4 py-2 border rounded-lg focus:ring focus:ring-blue-300"
                required>
        </div>

        <div>
            <label class="block font-medium mb-1">Password</label>
            <input type="password" name="password"
                class="w-full px-4 py-2 border rounded-lg focus:ring focus:ring-blue-300"
                required>
        </div>

        <div>
            <label class="block font-medium mb-1">Confirm Password</label>
            <input type="password" name="confirm"
                class="w-full px-4 py-2 border rounded-lg focus:ring focus:ring-blue-300"
                required>
        </div>

        <button type="submit"
            class="w-full bg-[var(--primary)] text-white py-2 rounded-lg text-lg font-semibold hover:opacity-90 transition">
            Create Account
        </button>
        
    </form>
    <!-- Back Button at the bottom -->
<a href="../../public/doctor_pages/doctor_manage.php"
   class="inline-block mt-6 px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition">
   &larr; Back
</a>


</div>

</body>
</html>
