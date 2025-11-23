<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../index.php");
    exit;
}

require_once '../../classes/Medical_Records.php';
$recordObj = new MedicalRecord();
$records = $recordObj->getAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Medical Records | Staff Dashboard</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="../../assets/css/style.css">
</head>

<body class="bg-[var(--secondary)] min-h-screen flex flex-col font-[Georgia]">

<?php include dirname(__DIR__, 2) . "/partials/header.php"; ?>

<main class="flex-1 px-10 py-10">

    <div class="flex flex-col md:flex-row justify-between items-center mb-10 gap-6">
        <div>
            <h1 class="text-[36px] font-bold text-[var(--primary)]">Medical Records</h1>
            <p class="text-gray-600 mt-2">View patient records — Read Only</p>
        </div>
        <img src="https://cdn-icons-png.flaticon.com/512/2965/2965567.png" class="w-32 h-32">
    </div>

    <div class="mb-6 w-full sm:w-[300px]">
        <input id="searchInput" type="text" placeholder="Search diagnosis..." 
        class="w-full px-4 py-2 rounded-full border-none focus:ring-2 focus:ring-[var(--primary)] text-[16px]">
    </div>

    <div class="overflow-x-auto rounded-xl shadow-lg">
        <table class="w-full bg-[var(--light)] text-left">
            <thead>
                <tr class="border-b text-left font-bold bg-[var(--light)]">
                    <th class="p-3">Record ID</th>
                    <th class="p-3">Diagnosis</th>
                    <th class="p-3">Prescription</th>
                    <th class="p-3">Visit Date</th>
                    <th class="p-3">Appointment ID</th>
                </tr>
            </thead>
            <tbody id="tableBody">
                <?php if(empty($records)): ?>
                    <tr><td colspan="5" class="text-center py-4 text-gray-500">No records found...</td></tr>
                <?php else: ?>
                    <?php foreach ($records as $rec): ?>
                        <tr class="border-b hover:bg-gray-50">
                            <td class="p-3"><?= htmlspecialchars($rec['MED_REC_ID']) ?></td>
                            <td class="p-3"><?= htmlspecialchars($rec['MED_REC_DIAGNOSIS']) ?></td>
                            <td class="p-3"><?= htmlspecialchars($rec['MED_REC_PRESCRIPTION']) ?></td>
                            <td class="p-3"><?= htmlspecialchars($rec['MED_REC_VISIT_DATE']) ?></td>
                            <td class="p-3"><?= htmlspecialchars($rec['APPT_ID']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</main>


<!-- ✅ FOOTER LINK -->
  <?php include dirname(__DIR__, 2) . "/partials/footer.php"; ?>

<script>
document.getElementById('searchInput').addEventListener('keyup', function() {
    const val = this.value.toLowerCase();
    document.querySelectorAll('tbody tr').forEach(row => {
        const diagnosis = row.children[1].textContent.toLowerCase();
        row.style.display = diagnosis.includes(val) ? '' : 'none';
    });
});
</script>

</body>
</html>
