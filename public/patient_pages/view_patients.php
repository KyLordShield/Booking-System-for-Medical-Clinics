<?php
require_once __DIR__ . '/../../classes/Patient.php';

$patientObj = new Patient();
$search = isset($_GET['search']) ? trim($_GET['search']) : "";

// Fetch all or filtered patients
if ($search) {
    $patients = $patientObj->searchPatients($search);
} else {
    $patients = $patientObj->getAllPatients();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View All Patients</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f5f6fa; margin: 0; padding: 0; }
        header { background: #007bff; color: white; text-align: center; padding: 20px; font-size: 24px; font-weight: bold; }
        .container { max-width: 1000px; margin: 30px auto; background: #fff; padding: 20px 30px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        h2 { text-align: center; color: #333; }
        form { text-align: center; margin-bottom: 20px; }
        input[type="text"] {
            padding: 8px 10px;
            width: 250px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        button, a.back-btn {
            padding: 8px 14px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            margin-left: 5px;
        }
        button:hover, a.back-btn:hover { background: #0056b3; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: center; }
        th { background: #007bff; color: white; }
        tr:nth-child(even) { background: #f9f9f9; }
        .no-result { text-align: center; color: #777; padding: 15px; font-style: italic; }
    </style>
</head>
<body>

<header>Patient Management - View All Patients</header>

<div class="container">
    <h2>All Registered Patients</h2>

    <!-- 🔍 Search Bar -->
    <form method="get" action="">
        <input type="text" name="search" placeholder="Search by first or last name" value="<?= htmlspecialchars($search) ?>">
        <button type="submit">Search</button>
        <a href="view_patient.php" class="back-btn">Reset</a>
    </form>

    <!-- 🧾 Patient Table -->
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Full Name</th>
                <th>Email</th>
                <th>Contact</th>
                <th>Gender</th>
                <th>Date of Birth</th>
                <th>Address</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($patients)): ?>
                <?php foreach ($patients as $pat): ?>
                    <tr>
                        <td><?= htmlspecialchars($pat['PAT_ID']) ?></td>
                        <td><?= htmlspecialchars($pat['PAT_FIRST_NAME'] . ' ' . $pat['PAT_MIDDLE_INIT'] . ' ' . $pat['PAT_LAST_NAME']) ?></td>
                        <td><?= htmlspecialchars($pat['PAT_EMAIL']) ?></td>
                        <td><?= htmlspecialchars($pat['PAT_CONTACT_NUM']) ?></td>
                        <td><?= htmlspecialchars($pat['PAT_GENDER']) ?></td>
                        <td><?= htmlspecialchars($pat['PAT_DOB']) ?></td>
                        <td><?= htmlspecialchars($pat['PAT_ADDRESS']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="7" class="no-result">No patients found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div style="text-align:center; margin-top:20px;">
        <a href="../patient_dashboard.php" class="back-btn">⬅ Back to Dashboard</a>
    </div>
</div>

</body>
</html>
