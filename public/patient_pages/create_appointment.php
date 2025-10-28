<?php
session_start();
require_once __DIR__ . '/../../classes/Appointment.php';
require_once __DIR__ . '/../../classes/Doctor.php';
require_once __DIR__ . '/../../classes/Service.php';

// ✅ Verify login session
if (!isset($_SESSION['PAT_ID']) || $_SESSION['role'] !== 'patient') {
    header("Location: ../index.php");
    exit;
}

$pat_id = $_SESSION['PAT_ID'];

$appointmentObj = new Appointment();
$doctorObj = new Doctor();
$serviceObj = new Service();

// Fetch doctors & services for dropdowns
$doctors = $doctorObj->getAllDoctors();
$services = $serviceObj->getAllServices();

$message = "";

// ✅ Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_appt'])) {
    $appt_date = trim($_POST['APPT_DATE']);
    $appt_time = trim($_POST['APPT_TIME']);
    $doc_id = $_POST['DOC_ID'];
    $serv_id = $_POST['SERV_ID'];

    $result = $appointmentObj->createAppointment($pat_id, $doc_id, $serv_id, $appt_date, $appt_time);

    if (strpos($result, '✅') !== false) {
        header("Location: ../patient_dashboard.php?success=1");
        exit;
    } else {
        $message = $result;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Appointment</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; margin: 0; padding: 20px; }
        .container { max-width: 600px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        h1 { text-align: center; color: #333; }
        form { margin-top: 20px; }
        label { display: block; margin: 10px 0 5px; font-weight: bold; }
        input, select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; }
        button { width: 100%; padding: 12px; background-color: #28a745; color: #fff; border: none; border-radius: 5px; margin-top: 20px; cursor: pointer; font-size: 16px; }
        button:hover { background-color: #218838; }
        a { display: block; text-align: center; margin-top: 15px; color: #555; text-decoration: none; }
        a:hover { text-decoration: underline; }
        .message { text-align: center; margin-top: 15px; font-weight: bold; color: #d9534f; }
    </style>
</head>
<body>

<div class="container">
    <h1>Create Appointment</h1>

    <?php if ($message): ?>
        <p class="message"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <form method="POST">
        <label for="APPT_DATE">Date:</label>
        <input type="date" name="APPT_DATE" required>

        <label for="APPT_TIME">Time:</label>
        <input type="time" name="APPT_TIME" required>

        <label for="SERV_ID">Select Service:</label>
        <select name="SERV_ID" required>
            <option value="">-- Choose Service --</option>
            <?php foreach ($services as $s): ?>
                <option value="<?= $s['SERV_ID'] ?>"><?= htmlspecialchars($s['SERV_NAME']) ?></option>
            <?php endforeach; ?>
        </select>

        <label for="DOC_ID">Select Doctor:</label>
        <select name="DOC_ID" required>
            <option value="">-- Choose Doctor --</option>
            <?php foreach ($doctors as $d): ?>
                <option value="<?= $d['DOC_ID'] ?>">
                    <?= htmlspecialchars($d['DOC_FIRST_NAME'] . ' ' . $d['DOC_LAST_NAME']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit" name="create_appt">Create Appointment</button>
    </form>

    <a href="../patient_dashboard.php">⬅ Back to Dashboard</a>
</div>

</body>
</html>
