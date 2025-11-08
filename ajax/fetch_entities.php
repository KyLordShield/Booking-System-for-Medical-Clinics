<?php
require_once '../classes/Patient.php';
require_once '../classes/Doctor.php';
require_once '../classes/Staff.php';
require_once '../classes/User.php';

$role = $_GET['role'];
$entities = [];

switch($role) {
    case 'Patient':
        $patientObj = new Patient();
        $patients = $patientObj->getPatientsWithoutUser();
        foreach($patients as $p) {
            $entities[] = ['id' => $p['PAT_ID'], 'name' => $p['PAT_FIRST_NAME'].' '.$p['PAT_LAST_NAME']];
        }
        break;

    case 'Doctor':
        $doctorObj = new Doctor();
        $doctors = $doctorObj->getDoctorsWithoutUser();
        foreach($doctors as $d) {
            $entities[] = ['id' => $d['DOC_ID'], 'name' => 'Dr. '.$d['DOC_FIRST_NAME'].' '.$d['DOC_LAST_NAME']];
        }
        break;

    case 'Staff':
        $staffObj = new Staff();
        $staffs = $staffObj->getStaffWithoutUser();
        foreach($staffs as $s) {
            $entities[] = ['id' => $s['STAFF_ID'], 'name' => $s['STAFF_FIRST_NAME'].' '.$s['STAFF_LAST_NAME']];
        }
        break;
}

header('Content-Type: application/json');
echo json_encode($entities);
