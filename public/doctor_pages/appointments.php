<?php
session_start();
require_once __DIR__ . '/../../classes/Appointment.php';
require_once __DIR__ . '/../../config/Database.php';

if (!isset($_SESSION['DOC_ID']) || $_SESSION['role'] !== 'doctor') {
    header("Location: ../index.php");
    exit;
}

$doctorId = $_SESSION['DOC_ID'];
$conn = (new Database())->connect();
$today = date('Y-m-d');

// GET STATUS IDs
$statusRows = $conn->query("SELECT STAT_NAME, STAT_ID FROM status")->fetchAll(PDO::FETCH_KEY_PAIR);
$scheduledId = $statusRows['Scheduled'] ?? null;
$pendingId   = $statusRows['Pending'] ?? null;
$completedId = $statusRows['Completed'] ?? null;
$missedId    = $statusRows['Missed'] ?? null;

$idsToCheck = [];
if ($scheduledId) $idsToCheck[] = $scheduledId;
if ($pendingId)   $idsToCheck[] = $pendingId;

// AUTO-UPDATE MISSED
if ($missedId && !empty($idsToCheck)) {
    $placeholders = implode(',', array_fill(0, count($idsToCheck), '?'));
    $updateMissedSQL = "UPDATE appointment SET STAT_ID = ? WHERE APPT_DATE < ? AND STAT_ID IN ($placeholders)";
    $stmt = $conn->prepare($updateMissedSQL);
    $params = array_merge([$missedId, $today], $idsToCheck);
    $stmt->execute($params);
}

// FETCH APPOINTMENTS + CHECK IF MEDICAL RECORD EXISTS
$sql = "
    SELECT 
        A.APPT_ID,
        A.APPT_DATE,
        A.APPT_TIME,
        CONCAT(P.PAT_FIRST_NAME, ' ', COALESCE(P.PAT_MIDDLE_INIT,''), ' ', P.PAT_LAST_NAME) AS PATIENT_NAME,
        S.SERV_NAME,
        ST.STAT_NAME,
        MR.MED_REC_ID IS NOT NULL AS HAS_MED_RECORD
    FROM appointment A
    LEFT JOIN patient P ON A.PAT_ID = P.PAT_ID
    LEFT JOIN service S ON A.SERV_ID = S.SERV_ID
    LEFT JOIN status ST ON A.STAT_ID = ST.STAT_ID
    LEFT JOIN medical_record MR ON A.APPT_ID = MR.APPT_ID
    WHERE A.DOC_ID = :docId
    ORDER BY A.APPT_DATE ASC, A.APPT_TIME ASC
";

$stmt = $conn->prepare($sql);
$stmt->bindParam(':docId', $doctorId);
$stmt->execute();
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// SORT APPOINTMENTS
$todayAppointments = $upcomingAppointments = $completedAppointments = $missedAppointments = $cancelledAppointments = [];

foreach ($appointments as $appt) {
    $dateObj  = new DateTime($appt['APPT_DATE']);
    $todayObj = new DateTime($today);
    $status   = $appt['STAT_NAME'];

    if ($status === 'Completed') {
        $completedAppointments[] = $appt;
    } elseif ($status === 'Cancelled') {
        $cancelledAppointments[] = $appt;
    } elseif ($status === 'Missed') {
        $missedAppointments[] = $appt;
    } elseif ($dateObj->format('Y-m-d') === $today) {
        $todayAppointments[] = $appt;
    } elseif ($dateObj > $todayObj) {
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); }
        .modal-content { background-color: white; margin: 5% auto; padding: 30px; width: 90%; max-width: 700px; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
        .close { color: #aaa; float: right; font-size: 32px; font-weight: bold; cursor: pointer; }
        .close:hover { color: #000; }
        textarea { min-height: 120px; resize: vertical; }
    </style>
</head>
<body class="bg-[var(--secondary)] flex flex-col min-h-screen font-[Georgia]">

    <?php include dirname(__DIR__, 2) . '/partials/header.php' ?>

    <main class="flex-1 p-10">
        <h1 class="text-[38px] font-bold text-[var(--primary)] mb-8">Appointments</h1>

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
                <th class="py-3 px-4 text-left">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($todayAppointments)): ?>
                <?php foreach ($todayAppointments as $appt): ?>
                    <?php 
                        $hasRecord = !empty($appt['HAS_MED_RECORD']);
                        $canComplete = $hasRecord && in_array($appt['STAT_NAME'], ['Scheduled', 'Pending']);
                    ?>
                    <tr class="border-b border-gray-300 hover:bg-gray-50">
                        <td class="py-3 px-4"><?= htmlspecialchars($appt['APPT_TIME']) ?></td>
                        <td class="py-3 px-4"><?= htmlspecialchars($appt['PATIENT_NAME']) ?></td>
                        <td class="py-3 px-4"><?= htmlspecialchars($appt['SERV_NAME']) ?></td>
                        <td class="py-3 px-4">
                            <span class="status-text"><?= htmlspecialchars($appt['STAT_NAME']) ?></span>
                        </td>
                        <td class="py-3 px-4 space-x-2">
                            <?php if (!$hasRecord): ?>
                                <button class="btn add-record-btn"
                                        data-appt-id="<?= $appt['APPT_ID'] ?>"
                                        data-patient="<?= htmlspecialchars($appt['PATIENT_NAME']) ?>"
                                        data-service="<?= htmlspecialchars($appt['SERV_NAME']) ?>"
                                        data-date="<?= $today ?>"
                                        data-time="<?= $appt['APPT_TIME'] ?>">
                                    Add Record
                                </button>
                            <?php else: ?>
                                <span class="text-green-600 font-semibold text-sm">Record Added</span>
                            <?php endif; ?>

                            <button class="check-btn px-3 py-2 rounded-lg text-white text-sm font-medium transition <?= $canComplete ? 'bg-green-500 hover:bg-green-600' : 'bg-gray-400 cursor-not-allowed' ?>"
                                    data-appt-id="<?= $appt['APPT_ID'] ?>"
                                    <?= $canComplete ? '' : 'disabled' ?>>
                                Complete
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="5" class="py-8 text-center text-gray-500">No appointments for today.</td></tr>
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
                <tr><td colspan="5" class="py-3 px-4 text-center text-gray-500">No upcoming  No upcoming appointments.</td></tr>
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

    <?php include dirname(__DIR__, 2) . '/partials/footer.php' ?>

    <!-- ================= MEDICAL RECORD MODAL ================= -->
    <div id="recordModal" class="modal">
        <div class="modal-content">
            <span class="close">×</span>
            <h2 class="text-2xl font-bold text-[var(--primary)] mb-6">Add Medical Record</h2>
            <div class="grid grid-cols-2 gap-4 mb-4 text-sm">
                <div><strong>Patient:</strong> <span id="modal-patient"></span></div>
                <div><strong>Service:</strong> <span id="modal-service"></span></div>
                <div><strong>Date:</strong> <span id="modal-date"></span></div>
                <div><strong>Time:</strong> <span id="modal-time"></span></div>
            </div>
            <form id="medicalRecordForm">
                <input type="hidden" id="modal-appt-id" name="appt_id">
                
                <div class="mb-5">
                    <label class="block text-gray-700 font-semibold mb-2">Diagnosis</label>
                    <textarea name="diagnosis" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-[var(--primary)]" placeholder="Enter diagnosis..." required></textarea>
                </div>

                <div class="mb-6">
                    <label class="block text-gray-700 font-semibold mb-2">Prescription / Treatment Plan</label>
                    <textarea name="prescription" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-[var(--primary)]" placeholder="Enter prescription, dosage, instructions..."></textarea>
                </div>

                <div class="flex justify-end space-x-3">
                    <button type="button" class="px-6 py-3 bg-gray-500 text-white rounded-lg hover:bg-gray-600 close">Cancel</button>
                    <button type="submit" class="px-6 py-3 bg-[var(--primary)] text-white rounded-lg hover:bg-opacity-90 font-semibold">
                        Save Medical Record
                    </button>
                </div>
            </form>
        </div>
    </div>

   <script>
    function showTab(tabName) {
        document.querySelectorAll('.table-container').forEach(c => c.classList.add('hidden'));
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.getElementById(tabName).classList.remove('hidden');
        document.querySelector(`[data-tab="${tabName}"]`).classList.add('active');
    }
    window.onload = () => showTab('today');

    const modal = document.getElementById('recordModal');
    const closeBtns = document.querySelectorAll('.close');

    // Open Modal
    $(document).on('click', '.add-record-btn', function() {
        const btn = $(this);
        $('#modal-appt-id').val(btn.data('appt-id'));
        $('#modal-patient').text(btn.data('patient'));
        $('#modal-service').text(btn.data('service'));
        $('#modal-date').text(btn.data('date'));
        $('#modal-time').text(btn.data('time'));
        modal.style.display = 'block';
    });

    closeBtns.forEach(btn => btn.onclick = () => modal.style.display = 'none');
    window.onclick = e => { if (e.target === modal) modal.style.display = 'none'; };

    // === SAVE MEDICAL RECORD ===
    $('#medicalRecordForm').on('submit', function(e) {
        e.preventDefault();
        $.post('../../ajax/add_medical_record.php', $(this).serialize())
        .done(function(res) {
            if (res.trim() === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: 'Medical record saved successfully!',
                    confirmButtonColor: '#002339',
                    timer: 2000,
                    timerProgressBar: true
                });

                const apptId = $('#modal-appt-id').val();
                const row = $(`.add-record-btn[data-appt-id="${apptId}"]`).closest('tr');

                row.find('.add-record-btn').replaceWith('<span class="text-green-600 font-semibold text-sm">Record Added</span>');
                row.find('.check-btn')
                    .removeClass('bg-gray-400 cursor-not-allowed')
                    .addClass('bg-green-500 hover:bg-green-600')
                    .prop('disabled', false);

                modal.style.display = 'none';
                $('#medicalRecordForm')[0].reset();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Oops!',
                    text: 'Failed to save record.',
                    confirmButtonColor: '#002339'
                });
            }
        });
    });

    // === COMPLETE APPOINTMENT ===
    $(document).on('click', '.check-btn:not(:disabled)', function() {
        const btn = $(this);
        const apptId = btn.data('appt-id');
        const row = btn.closest('tr');

        btn.prop('disabled', true).text('Completing...');

        $.post('../../ajax/update_appointment_status.php', {
            appt_id: apptId,
            status: 'Completed'
        })
        .done(function(response) {
            if (response.includes('|SUCCESS|')) {
                row.find('.status-text').text('Completed');
                row.find('td:last-child').html('<span class="text-green-600 font-bold">Completed</span>');

                // Reload only the Completed tab
                $('#completed').load(location.href + ' #completed > *', function() {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'Appointment completed!',
                        confirmButtonColor: '#002339',
                        timer: 2000,
                        timerProgressBar: true
                    });
                    showTab('completed');
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Could not complete appointment.',
                    confirmButtonColor: '#002339'
                });
                btn.prop('disabled', false).text('Complete');
            }
        });
    });
</script>
</body>
</html>