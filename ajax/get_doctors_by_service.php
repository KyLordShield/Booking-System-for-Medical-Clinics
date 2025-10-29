<?php
require_once __DIR__ . '/../classes/Doctor.php';

header('Content-Type: application/json');

if (!isset($_GET['serv_id'])) {
    echo json_encode([]);
    exit;
}

$serv_id = $_GET['serv_id'];
$doctorObj = new Doctor();
$doctors = $doctorObj->getDoctorsByService($serv_id);

echo json_encode($doctors);
?>
