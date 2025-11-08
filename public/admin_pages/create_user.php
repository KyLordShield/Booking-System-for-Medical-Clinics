<?php
require_once __DIR__ . '/../../classes/User.php';
require_once __DIR__ . '/../../classes/Patient.php';
require_once __DIR__ . '/../../classes/Doctor.php';
require_once __DIR__ . '/../../classes/Staff.php';

$userObj = new User();

/* =====================================================
   CREATE USER
===================================================== */
if (isset($_POST['action']) && $_POST['action'] === 'create') {
    $role = $_POST['role'] ?? '';
    $entity_id = $_POST['entity_id'] ?? '';
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($role) || empty($entity_id) || empty($username) || empty($password)) {
        die("⚠️ Please fill in all required fields.");
    }

    if ($userObj->isUsernameTaken($username, $user_id)) {
    die("⚠️ Username already exists.");
}

    if ($userObj->existsByEntity($role, $entity_id)) {
        die("⚠️ This entity already has a user account.");
    }

    $result = $userObj->createForEntity($role, $entity_id, $username, $password);
    if ($result) {
        header("Location: user_accounts.php?success=1");
        exit;
    } else {
        die("⚠️ Failed to create user.");
    }
}

/* =====================================================
   EDIT USER
===================================================== */
if (isset($_POST['action']) && $_POST['action'] === 'edit') {
    $user_id = $_POST['user_id'] ?? '';
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // 🧩 Debugging helper: uncomment if needed
    // var_dump($_POST); exit;

    if (empty($user_id)) {
        die("⚠️ Error: User ID is missing! Cannot edit.");
    }

    if (empty($username) || empty($password)) {
        die("⚠️ Please fill in all required fields.");
    }

    // Prevent duplicate usernames (except for current user)
    if ($userObj->isUsernameTaken($username, $user_id)) {
        die("⚠️ Username already exists.");
    }

    $result = $userObj->updateUser($user_id, $username, $password);

    if ($result) {
        header("Location: user_accounts.php?updated=1");
        exit;
    } else {
        die("⚠️ Failed to update user.");
    }
}




/* =====================================================
   DELETE USER
===================================================== */
if (isset($_POST['action']) && $_POST['action'] === 'delete') {
    $user_id = $_POST['user_id'] ?? '';
    if (empty($user_id)) {
        die("⚠️ User ID is missing.");
    }

    $result = $userObj->delete($user_id);
    if ($result) {
        header("Location: user_accounts.php?deleted=1");
        exit;
    } else {
        die("⚠️ Failed to delete user.");
    }
}

?>
