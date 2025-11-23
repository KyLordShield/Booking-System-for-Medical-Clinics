<?php
// Note: This file assumes necessary session/authentication checks are handled in the file that includes or links to it.
// Adding necessary imports and basic structure for a standalone view.
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
    <title>View All Patients | Staff Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- ✅ Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- ✅ Custom CSS -->
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>

<body class="bg-[var(--secondary)] min-h-screen flex flex-col font-[Georgia]">

    <!-- ✅ HEADER LINK -->
    <?php include dirname(__DIR__, 2) . "/partials/header.php"; ?>

    <!-- ✅ MAIN CONTENT -->
    <main class="flex-1 px-10 py-10">
        <h2 class="text-[36px] font-bold text-[var(--primary)] mb-6 text-center">All Registered Patients</h2>

        <div class="table-container bg-[var(--light)] p-6 rounded-[25px] shadow-xl max-w-7xl mx-auto">
            
            <!-- 🔍 Search Bar -->
            <form method="get" action="" class="flex flex-col sm:flex-row gap-4 mb-6 items-center justify-center">
                <input type="text" name="search" placeholder="Search by name" 
                    value="<?= htmlspecialchars($search ?? '') ?>"
                    class="w-full sm:w-[350px] px-4 py-2 rounded-full border-none focus:ring-2 focus:ring-[var(--primary)] outline-none text-[16px]">
                
                <button type="submit" 
                    class="px-6 py-2 rounded-full text-white bg-[var(--primary)] font-medium hover:bg-sky-600 transition shadow-md w-full sm:w-auto">
                    Search
                </button>

                <a href="view_patients.php" 
                    class="px-6 py-2 rounded-full text-white bg-gray-500 font-medium hover:bg-gray-600 transition shadow-md w-full sm:w-auto text-center">
                    Reset
                </a>
            </form>

            <!-- 🧾 Patient Table -->
            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-[var(--primary)] min-w-[1000px]">
                    <thead>
                        <tr class="border-b border-gray-300 bg-gray-50">
                            <th class="py-3 px-4 text-left text-[var(--primary)]">ID</th>
                            <th class="py-3 px-4 text-left text-[var(--primary)]">Full Name</th>
                            <th class="py-3 px-4 text-left text-[var(--primary)]">Email</th>
                            <th class="py-3 px-4 text-left text-[var(--primary)]">Contact</th>
                            <th class="py-3 px-4 text-left text-[var(--primary)]">Gender</th>
                            <th class="py-3 px-4 text-left text-[var(--primary)]">Date of Birth</th>
                            <th class="py-3 px-4 text-left text-[var(--primary)]">Address</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (!empty($patients)): ?>
                            <?php foreach ($patients as $pat): ?>
                                <tr class="border-b border-gray-300 hover:bg-gray-50">

                                    <!-- PATIENT ID -->
                                    <td class="py-3 px-4"><?= htmlspecialchars($pat['PAT_ID'] ?? '') ?></td>

                                    <!-- FULL NAME (NULL-SAFE) -->
                                    <td class="py-3 px-4">
                                        <?= htmlspecialchars(
                                            ($pat['PAT_FIRST_NAME'] ?? '') . ' ' .
                                            ($pat['PAT_MIDDLE_INIT'] ?? '') . ' ' .
                                            ($pat['PAT_LAST_NAME'] ?? '')
                                        ) ?>
                                    </td>

                                    <!-- EMAIL -->
                                    <td class="py-3 px-4"><?= htmlspecialchars($pat['PAT_EMAIL'] ?? '') ?></td>

                                    <!-- CONTACT NUMBER -->
                                    <td class="py-3 px-4"><?= htmlspecialchars($pat['PAT_CONTACT_NUM'] ?? '') ?></td>

                                    <!-- GENDER -->
                                    <td class="py-3 px-4"><?= htmlspecialchars($pat['PAT_GENDER'] ?? '') ?></td>

                                    <!-- DATE OF BIRTH -->
                                    <td class="py-3 px-4"><?= htmlspecialchars($pat['PAT_DOB'] ?? '') ?></td>

                                    <!-- ADDRESS -->
                                    <td class="py-3 px-4"><?= htmlspecialchars($pat['PAT_ADDRESS'] ?? '') ?></td>

                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr class="hover:bg-gray-50">
                                <td colspan="7" class="py-6 text-center text-gray-500 font-medium">No patients found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="text-center mt-8">
                <a href="../patient_dashboard.php" 
                    class="inline-block px-6 py-2 rounded-full text-gray-700 bg-[var(--secondary)] font-medium hover:bg-gray-200 transition">
                    ⬅ Back to Dashboard
                </a>
            </div>
        </div>
    </main>

    <!-- ✅ FOOTER -->
    <?php include dirname(__DIR__, 2) . "/partials/footer.php"; ?>

</body>
</html>
