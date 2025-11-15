<?php
session_start();
require_once __DIR__ . '/../../classes/Appointment.php';
require_once __DIR__ . '/../../config/Database.php';

// ✅ Restrict to Doctor Role Only
if (!isset($_SESSION['DOC_ID']) || $_SESSION['role'] !== 'doctor') {
    header("Location: /Booking-System-For-Medical-Clinics/index.php");
    exit;
}

$doctorId = $_SESSION['DOC_ID'];
$conn = (new Database())->connect();
$today = date('Y-m-d');

// ---------------------------------------------------------
// ✅ AUTO-UPDATE MISSED APPOINTMENTS (Past date & still Scheduled)
// ---------------------------------------------------------
$updateMissedSQL = "
    UPDATE APPOINTMENT 
    SET STAT_ID = (SELECT STAT_ID FROM STATUS WHERE STAT_NAME = 'Missed')
    WHERE DOC_ID = :doc_id
      AND APPT_DATE < :today
      AND STAT_ID = (SELECT STAT_ID FROM STATUS WHERE STAT_NAME = 'Scheduled')
";
$stmtMissed = $conn->prepare($updateMissedSQL);
$stmtMissed->execute([':doc_id' => $doctorId, ':today' => $today]);

// ---------------------------------------------------------
// ✅ FETCH ALL APPOINTMENTS
// ---------------------------------------------------------
$sql = "SELECT 
            A.APPT_ID, 
            A.APPT_DATE, 
            A.APPT_TIME,
            CONCAT(P.PAT_FIRST_NAME, ' ', P.PAT_LAST_NAME) AS PATIENT_NAME,
            S.SERV_NAME,
            ST.STAT_NAME
        FROM APPOINTMENT A
        JOIN PATIENT P ON A.PAT_ID = P.PAT_ID
        JOIN SERVICE S ON A.SERV_ID = S.SERV_ID
        JOIN STATUS ST ON A.STAT_ID = ST.STAT_ID
        WHERE A.DOC_ID = :doc_id
        ORDER BY A.APPT_DATE ASC, A.APPT_TIME ASC";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':doc_id', $doctorId);
$stmt->execute();
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ✅ Separate appointments
$todayAppointments = [];
$upcomingAppointments = [];
$completedAppointments = [];
$missedAppointments = [];
$cancelledAppointments = [];

foreach ($appointments as $appt) {
    $date = $appt['APPT_DATE'];
    $status = $appt['STAT_NAME'];

    if ($status === 'Completed') {
        $completedAppointments[] = $appt;
    } elseif ($status === 'Cancelled') {
        $cancelledAppointments[] = $appt;
    } elseif ($status === 'Missed') {
        $missedAppointments[] = $appt;
    } elseif ($date === $today) {
        $todayAppointments[] = $appt;
    } elseif ($date > $today) {
        $upcomingAppointments[] = $appt;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Appointments | Medicina</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="/Booking-System-For-Medical-Clinics/assets/css/style.css">
</head>
<body class="bg-[var(--secondary)] flex flex-col min-h-screen font-[Georgia]">

    <!-- ✅ NAVBAR -->
    <?php include dirname(__DIR__, 2) . '/partials/header.php' ?>

    <!-- ✅ MAIN CONTENT -->
    <main class="flex-1 p-10">
        <h1 class="text-[38px] font-bold text-[var(--primary)] mb-8">Appointments</h1>

        <!-- ✅ Tabs -->
        <div class="tabs flex flex-wrap gap-4 mb-6">
            <button class="tab-btn active" data-tab="today" onclick="showTab('today')">Today</button>
            <button class="tab-btn" data-tab="upcoming" onclick="showTab('upcoming')">Upcoming</button>
            <button class="tab-btn" data-tab="completed" onclick="showTab('completed')">Completed</button>
            <button class="tab-btn" data-tab="missed" onclick="showTab('missed')">Missed</button>
            <button class="tab-btn" data-tab="cancelled" onclick="showTab('cancelled')">Cancelled</button>
        </div>

        <!-- ================= TODAY ================= -->
        <div id="today" class="table-container bg-[var(--light)] p-6 rounded-[25px] shadow-md">
            <h2 class="text-[var(--primary)] mb-4 text-2xl font-semibold">Today’s Appointments</h2>
            <table class="w-full border-collapse text-[var(--primary)]">
                <thead>
                    <tr class="border-b border-gray-300">
                        <th class="py-3 px-4 text-left">Time</th>
                        <th class="py-3 px-4 text-left">Patient</th>
                        <th class="py-3 px-4 text-left">Service</th>
                        <th class="py-3 px-4 text-left">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($todayAppointments)): ?>
                        <?php foreach ($todayAppointments as $appt): ?>
                            <tr class="border-b border-gray-300 hover:bg-gray-50">
                                <td class="py-3 px-4"><?= htmlspecialchars($appt['APPT_TIME']) ?></td>
                                <td class="py-3 px-4"><?= htmlspecialchars($appt['PATIENT_NAME']) ?></td>
                                <td class="py-3 px-4"><?= htmlspecialchars($appt['SERV_NAME']) ?></td>
                                <td class="py-3 px-4">
                                    <?= htmlspecialchars($appt['STAT_NAME']) ?>
                                    <?php if ($appt['STAT_NAME'] === 'Scheduled'): ?>
                                        <button class="check-btn ml-2 px-2 py-1 bg-green-500 text-white rounded"
                                                data-appt-id="<?= $appt['APPT_ID'] ?>" 
                                                data-status="Completed">
                                            Check
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="py-3 px-4 text-center text-gray-500">No appointments for today.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- ================= UPCOMING ================= -->
        <div id="upcoming" class="table-container bg-[var(--light)] p-6 rounded-[25px] shadow-md mt-6 hidden">
            <h2 class="text-[var(--primary)] mb-4 text-2xl font-semibold">Upcoming Appointments</h2>
            <table class="w-full border-collapse text-[var(--primary)]">
                <thead>
                    <tr class="border-b border-gray-300">
                        <th class="py-3 px-4 text-left">Date</th>
                        <th class="py-3 px-4 text-left">Time</th>
                        <th class="py-3 px-4 text-left">Patient</th>
                        <th class="py-3 px-4 text-left">Service</th>
                        <th class="py-3 px-4 text-left">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($upcomingAppointments)): ?>
                        <?php foreach ($upcomingAppointments as $appt): ?>
                            <tr class="border-b border-gray-300 hover:bg-gray-50">
                                <td class="py-3 px-4"><?= htmlspecialchars($appt['APPT_DATE']) ?></td>
                                <td class="py-3 px-4"><?= htmlspecialchars($appt['APPT_TIME']) ?></td>
                                <td class="py-3 px-4"><?= htmlspecialchars($appt['PATIENT_NAME']) ?></td>
                                <td class="py-3 px-4"><?= htmlspecialchars($appt['SERV_NAME']) ?></td>
                                <td class="py-3 px-4"><?= htmlspecialchars($appt['STAT_NAME']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="py-3 px-4 text-center text-gray-500">No upcoming appointments.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- ================= COMPLETED ================= -->
        <div id="completed" class="table-container bg-[var(--light)] p-6 rounded-[25px] shadow-md mt-6 hidden">
            <h2 class="text-[var(--primary)] mb-4 text-2xl font-semibold">Completed Appointments</h2>
            <table class="w-full border-collapse text-[var(--primary)]">
                <thead>
                    <tr class="border-b border-gray-300">
                        <th class="py-3 px-4 text-left">Date</th>
                        <th class="py-3 px-4 text-left">Time</th>
                        <th class="py-3 px-4 text-left">Patient</th>
                        <th class="py-3 px-4 text-left">Service</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($completedAppointments)): ?>
                        <?php foreach ($completedAppointments as $appt): ?>
                            <tr class="border-b border-gray-300 hover:bg-gray-50">
                                <td class="py-3 px-4"><?= htmlspecialchars($appt['APPT_DATE']) ?></td>
                                <td class="py-3 px-4"><?= htmlspecialchars($appt['APPT_TIME']) ?></td>
                                <td class="py-3 px-4"><?= htmlspecialchars($appt['PATIENT_NAME']) ?></td>
                                <td class="py-3 px-4"><?= htmlspecialchars($appt['SERV_NAME']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="py-3 px-4 text-center text-gray-500">No completed appointments.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- ================= MISSED ================= -->
        <div id="missed" class="table-container bg-[var(--light)] p-6 rounded-[25px] shadow-md mt-6 hidden">
            <h2 class="text-[var(--primary)] mb-4 text-2xl font-semibold">Missed Appointments</h2>
            <table class="w-full border-collapse text-[var(--primary)]">
                <thead>
                    <tr class="border-b border-gray-300">
                        <th class="py-3 px-4 text-left">Date</th>
                        <th class="py-3 px-4 text-left">Time</th>
                        <th class="py-3 px-4 text-left">Patient</th>
                        <th class="py-3 px-4 text-left">Service</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($missedAppointments)): ?>
                        <?php foreach ($missedAppointments as $appt): ?>
                            <tr class="border-b border-gray-300 hover:bg-gray-50">
                                <td class="py-3 px-4"><?= htmlspecialchars($appt['APPT_DATE']) ?></td>
                                <td class="py-3 px-4"><?= htmlspecialchars($appt['APPT_TIME']) ?></td>
                                <td class="py-3 px-4"><?= htmlspecialchars($appt['PATIENT_NAME']) ?></td>
                                <td class="py-3 px-4"><?= htmlspecialchars($appt['SERV_NAME']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="py-3 px-4 text-center text-gray-500">No missed appointments.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- ================= CANCELLED ================= -->
        <div id="cancelled" class="table-container bg-[var(--light)] p-6 rounded-[25px] shadow-md mt-6 hidden">
            <h2 class="text-[var(--primary)] mb-4 text-2xl font-semibold">Cancelled Appointments</h2>
            <table class="w-full border-collapse text-[var(--primary)]">
                <thead>
                    <tr class="border-b border-gray-300">
                        <th class="py-3 px-4 text-left">Date</th>
                        <th class="py-3 px-4 text-left">Time</th>
                        <th class="py-3 px-4 text-left">Patient</th>
                        <th class="py-3 px-4 text-left">Service</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($cancelledAppointments)): ?>
                        <?php foreach ($cancelledAppointments as $appt): ?>
                            <tr class="border-b border-gray-300 hover:bg-gray-50">
                                <td class="py-3 px-4"><?= htmlspecialchars($appt['APPT_DATE']) ?></td>
                                <td class="py-3 px-4"><?= htmlspecialchars($appt['APPT_TIME']) ?></td>
                                <td class="py-3 px-4"><?= htmlspecialchars($appt['PATIENT_NAME']) ?></td>
                                <td class="py-3 px-4"><?= htmlspecialchars($appt['SERV_NAME']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="py-3 px-4 text-center text-gray-500">No cancelled appointments.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </main>

    <!-- ✅ FOOTER -->
    <?php include dirname(__DIR__, 2) . '/partials/footer.php' ?>

    <!-- ================= JS for Tabs & Check Button ================= -->
    <script>
        function showTab(tabName) {
            document.querySelectorAll('.table-container').forEach(c => c.classList.add('hidden'));
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.getElementById(tabName).classList.remove('hidden');
            document.querySelector(`[data-tab="${tabName}"]`).classList.add('active');
        }

        window.onload = () => showTab('today');

        $(document).ready(function() {
            $('.check-btn').click(function() {
                var btn = $(this);
                var apptId = btn.data('appt-id');
                var status = btn.data('status');

                $.post('/Booking-System-For-Medical-Clinics/ajax/update_appointment_status.php',
                    { appt_id: apptId, status: status },
                    function(response) {
                        if (response.includes('✅')) {
                            btn.closest('td').text(status);
                        } else {
                            alert(response);
                        }
                    }
                );
            });
        });
    </script>
</body>
</html>
