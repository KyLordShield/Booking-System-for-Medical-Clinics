<?php
session_start();
require_once __DIR__ . '/../../classes/Appointment.php';
require_once __DIR__ . '/../../classes/Doctor.php';
require_once __DIR__ . '/../../classes/Service.php';
require_once __DIR__ . '/../../classes/Schedule.php';
require_once dirname(__DIR__, 2) . '/config/Database.php';

    // Verify login
    if (!isset($_SESSION['PAT_ID']) || $_SESSION['role'] !== 'patient') {
        header("Location: /index.php");
        exit;
    }

$pat_id = $_SESSION['PAT_ID'];

$appointmentObj = new Appointment();
$doctorObj = new Doctor();
$serviceObj = new Service();
$schedObj = new Schedule();

$db = new Database();
$conn = $db->connect();

// Load payment methods
$paymentMethods = [];
try {
    $stmtPm = $conn->query("SELECT PYMT_METH_ID, PYMT_METH_NAME FROM payment_method ORDER BY PYMT_METH_NAME ASC");
    $paymentMethods = $stmtPm->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// SweetAlert message
$alert = ['type'=>'', 'message'=>''];

// Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_appt'])) {
    $appt_date = trim($_POST['APPT_DATE'] ?? '');
    $appt_time = trim($_POST['APPT_TIME'] ?? '');
    $doc_id = $_POST['DOC_ID'] ?? '';
    $serv_id = $_POST['SERV_ID'] ?? '';
    $payment_method_id = $_POST['payment_method'] ?? null;

    if (empty($appt_date) || empty($appt_time) || empty($doc_id) || empty($serv_id)) {
        $alert = ['type'=>'error', 'message'=>'Please fill out all fields.'];
    } else {
        $available = $schedObj->isDoctorAvailable($doc_id, $appt_date, $appt_time);

        if (!$available) {
            $alert = ['type'=>'error', 'message'=>'Doctor is not available at the selected date/time.'];
        } else {
            $result = $appointmentObj->createAppointment($pat_id, $doc_id, $serv_id, $appt_date, $appt_time);

            // Check for success (handles both ✅ and "Success" text)
            if (strpos($result, '✅') !== false || strpos($result, 'Success') !== false) {
                try {
                    $sql = "SELECT APPT_ID FROM appointment 
                            WHERE PAT_ID = :pat AND APPT_DATE = :adate AND APPT_TIME = :atime
                            ORDER BY APPT_CREATED_AT DESC LIMIT 1";
                    $pst = $conn->prepare($sql);
                    $pst->execute([':pat' => $pat_id, ':adate' => $appt_date, ':atime' => $appt_time]);
                    $apptRow = $pst->fetch(PDO::FETCH_ASSOC);

                    if (!$apptRow || empty($apptRow['APPT_ID'])) {
                        $alert = ['type'=>'error', 'message'=>'Appointment created but could not find ID. Contact admin.'];
                    } else {
                        $apptId = $apptRow['APPT_ID'];

                        $amount = 0.00;
                        try {
                            $pst2 = $conn->prepare("SELECT SERV_PRICE FROM service WHERE SERV_ID = :sid LIMIT 1");
                            $pst2->execute([':sid' => $serv_id]);
                            $sRow = $pst2->fetch(PDO::FETCH_ASSOC);
                            if ($sRow) $amount = (float)$sRow['SERV_PRICE'];
                        } catch (Exception $e) {}

                        $pymtMethId = is_numeric($payment_method_id) ? (int)$payment_method_id : null;
                        $pendingStatusId = 4;

                        try {
                            $ins = $conn->prepare("INSERT INTO payment (PYMT_AMOUNT_PAID, PYMT_DATE, PYMT_METH_ID, PYMT_STAT_ID, APPT_ID)
                                                   VALUES (:amount, CURDATE(), :meth, :stat, :appt)");
                            $ins->execute([
                                ':amount' => $amount,
                                ':meth' => $pymtMethId,
                                ':stat' => $pendingStatusId,
                                ':appt' => $apptId
                            ]);
                            $alert = ['type'=>'success', 'message'=>'Appointment booked successfully!'];
                        } catch (Exception $e) {
                            $alert = ['type'=>'warning', 'message'=>'Appointment created but payment failed. Contact admin.'];
                        }
                    }
                } catch (Exception $e) {
                    $alert = ['type'=>'warning', 'message'=>'Error processing payment.'];
                }
            } else {
                $alert = ['type'=>'error', 'message'=>$result];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Create Appointment | Medicina</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link rel="stylesheet" href="../../assets/css/style.css">
<link rel="stylesheet" href="../../assets/css/appointment.css">
<link rel="stylesheet" href="../../assets/css/payment.css">

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<style>

</style>
</head>

<body>

<?php include dirname(__DIR__, 2) . "/partials/header.php"; ?>

<main>
    <form method="POST" id="appointmentForm" class="appt-wrapper">
        <input type="hidden" name="payment_method" id="payment_method" value="">
        <div class="appt-grid">

            <!-- Left Section -->
            <div class="left-section">
                <h1>Appointment</h1>
                <p>Choose Time, Date,<br>Service, and Doctor</p>
                <button type="button" id="openPaymentBtn" class="confirm-btn">Confirm Booking</button>
                <a href="../patient_dashboard.php" class="back-link">Back to Dashboard</a>
            </div>

            <!-- Calendar -->
            <div class="calendar-box">
                <div class="calendar-header">Select Date</div>
                <div class="calendar-wrapper">
                    <div id="inlineCalendar"></div>
                </div>
                <input type="hidden" name="APPT_DATE" id="APPT_DATE" required>
                <div class="calendar-hint">Click a date to choose</div>
            </div>

            <!-- Right Inputs -->
            <div class="right-section">

                <!-- SERVICE CARD SELECTOR -->
                <div class="pill-select-wrapper">
                    <div class="service-display" id="serviceDisplay">
                        <span class="placeholder">SERVICE</span>
                        <svg class="dropdown-icon" width="12" height="8" viewBox="0 0 12 8" fill="none">
                            <path d="M1 1L6 6L11 1" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <input type="hidden" name="SERV_ID" id="SERV_ID" required>
                </div>

                <!-- SERVICE MODAL -->
                <div id="serviceModal" class="service-modal">
                    <div class="service-modal-content">
                        <div class="service-modal-header">
                            <h2>Select a Service</h2>
                            <button type="button" class="service-close" id="serviceClose">×</button>
                        </div>
                        <div class="service-grid">
                            <?php foreach ($serviceObj->getAllServices() as $s): ?>
                                <?php
                                    $price = number_format((float)($s['SERV_PRICE'] ?? 0), 2);
                                    $desc = $s['SERV_DESCRIPTION'] ?? 'No description.';
                                    $short = strlen($desc) > 80 ? substr($desc,0,80).'...' : $desc;
                                ?>
                                <div class="service-card"
                                     data-id="<?= $s['SERV_ID'] ?>"
                                     data-name="<?= htmlspecialchars($s['SERV_NAME']) ?>">
                                    <h3><?= htmlspecialchars($s['SERV_NAME']) ?></h3>
                                    <p class="service-desc"><?= htmlspecialchars($short) ?></p>
                                    <p class="service-price">₱<?= $price ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if (empty($serviceObj->getAllServices())): ?>
                            <p class="service-empty">No services available at this time.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Doctor & Time -->
                <div class="pill-select-wrapper">
                    <select name="DOC_ID" id="DOC_ID" required>
                        <option value="">DOCTOR</option>
                    </select>
                </div>

                <div class="pill-select-wrapper">
                    <select name="APPT_TIME" id="APPT_TIME" required>
                        <option value="">TIME</option>
                    </select>
                </div>

                <div class="pill-select-wrapper small-note">
                    <small>Choose payment method on confirmation</small>
                </div>

            </div>
        </div>

        <button type="submit" name="create_appt" id="finalSubmit" style="display:none;">Submit</button>
    </form>
</main>

<?php include dirname(__DIR__, 2) . "/partials/footer.php"; ?>

<!-- PAYMENT MODAL -->
<div id="paymentModal" class="payment-modal" aria-hidden="true">
    <div class="payment-modal-content">
        <button class="payment-close" id="paymentClose">×</button>
        <h2>Select Payment Method</h2>
        <div class="payment-grid">
            <?php if (!empty($paymentMethods)): ?>
                <?php foreach ($paymentMethods as $pm): ?>
                    <div class="payment-card" data-pmid="<?= (int)$pm['PYMT_METH_ID'] ?>">
                        <div class="pm-name"><?= htmlspecialchars($pm['PYMT_METH_NAME']) ?></div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="pm-fallback">No payment methods found.</p>
            <?php endif; ?>
        </div>
        <div class="payment-actions">
            <button id="payConfirm" class="confirm-btn" disabled>Pay & Confirm</button>
            <button id="payCancel" class="btn">Cancel</button>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    // Elements
    const serviceDisplay   = document.getElementById("serviceDisplay");
    const serviceModal     = document.getElementById("serviceModal");
    const serviceClose     = document.getElementById("serviceClose");
    const hiddenServId     = document.getElementById("SERV_ID");
    const doctorSelect     = document.getElementById("DOC_ID");
    const timeSelect       = document.getElementById("APPT_TIME");
    const dateInput        = document.getElementById("APPT_DATE");

    const openPaymentBtn   = document.getElementById("openPaymentBtn");
    const paymentModal     = document.getElementById("paymentModal");
    const paymentClose     = document.getElementById("paymentClose");
    const payCancel        = document.getElementById("payCancel");
    const payConfirm       = document.getElementById("payConfirm");
    const paymentMethodInput = document.getElementById("payment_method");

    let selectedPaymentId = null;

    // === SERVICE CARD MODAL ===
    serviceDisplay.addEventListener("click", () => {
        serviceModal.style.display = "flex";
    });

    serviceClose.addEventListener("click", () => {
        serviceModal.style.display = "none";
    });

    serviceModal.addEventListener("click", (e) => {
        if (e.target === serviceModal) serviceModal.style.display = "none";
    });

    document.querySelectorAll(".service-card").forEach(card => {
        card.addEventListener("click", function () {
            const name = this.dataset.name;
            const id = this.dataset.id;

            // Update display - just show service name
            const displaySpan = serviceDisplay.querySelector('span');
            displaySpan.textContent = name;
            displaySpan.classList.remove('placeholder');
            
            serviceDisplay.classList.add("selected");
            hiddenServId.value = id;
            serviceModal.style.display = "none";

            // Reset doctor & time, then reload doctors
            doctorSelect.innerHTML = '<option value="">DOCTOR</option>';
            timeSelect.innerHTML = '<option value="">TIME</option>';
            
            // Load doctors for this service
            doctorSelect.innerHTML = '<option>Loading...</option>';
            fetch('../../ajax/get_doctors_by_service.php?serv_id=' + encodeURIComponent(id))
                .then(res => res.json())
                .then(data => {
                    doctorSelect.innerHTML = '<option value="">DOCTOR</option>';
                    if (Array.isArray(data) && data.length > 0) {
                        data.forEach(doc => {
                            const opt = document.createElement('option');
                            opt.value = doc.DOC_ID;
                            opt.textContent = `${doc.DOC_FIRST_NAME} ${doc.DOC_LAST_NAME} — ${doc.SPEC_NAME}`;
                            doctorSelect.appendChild(opt);
                        });
                    } else {
                        doctorSelect.innerHTML = '<option value="">No doctors available</option>';
                    }
                })
                .catch(() => {
                    doctorSelect.innerHTML = '<option value="">Error loading doctors</option>';
                });
        });
    });

    // === TIME LOADING ===
    function loadAvailableTimes() {
        const docID = doctorSelect.value;
        const date = dateInput.value;
        if (!docID || !date) return;

        timeSelect.innerHTML = '<option>Loading...</option>';
        fetch(`../../ajax/get_available_times.php?doc_id=${encodeURIComponent(docID)}&date=${encodeURIComponent(date)}`)
            .then(res => res.json())
            .then(data => {
                timeSelect.innerHTML = '<option value="">TIME</option>';
                if (Array.isArray(data) && data.length > 0) {
                    data.forEach(slot => {
                        const opt = document.createElement('option');
                        opt.value = slot.time;
                        opt.textContent = `${slot.time} - ${slot.endTime}`;
                        timeSelect.appendChild(opt);
                    });
                } else {
                    timeSelect.innerHTML = '<option value="">No available times</option>';
                }
            })
            .catch(() => {
                timeSelect.innerHTML = '<option value="">Error loading times</option>';
            });
    }

    doctorSelect.addEventListener('change', loadAvailableTimes);
    dateInput.addEventListener('change', loadAvailableTimes);

    // === PAYMENT MODAL ===
    openPaymentBtn.addEventListener('click', () => {
        if (!dateInput.value || !hiddenServId.value || !doctorSelect.value || !timeSelect.value) {
            Swal.fire({
                icon: 'warning',
                title: 'Missing fields',
                text: 'Please complete all fields first.',
                confirmButtonColor: '#002339'
            });
            return;
        }
        paymentModal.style.display = 'flex';
        paymentModal.setAttribute('aria-hidden', 'false');
    });

    function closePaymentModal() {
        paymentModal.style.display = 'none';
        paymentModal.setAttribute('aria-hidden', 'true');
        document.querySelectorAll('.payment-card.selected').forEach(el => el.classList.remove('selected'));
        selectedPaymentId = null;
        payConfirm.disabled = true;
        paymentMethodInput.value = '';
    }

    paymentClose.addEventListener('click', closePaymentModal);
    payCancel.addEventListener('click', closePaymentModal);
    paymentModal.addEventListener('click', e => { if (e.target === paymentModal) closePaymentModal(); });

    document.querySelectorAll('.payment-card').forEach(card => {
        card.setAttribute('role', 'button');
        card.setAttribute('tabindex', '0');

        card.addEventListener('click', function() {
            document.querySelectorAll('.payment-card.selected').forEach(el => el.classList.remove('selected'));
            this.classList.add('selected');
            selectedPaymentId = this.getAttribute('data-pmid');
            paymentMethodInput.value = selectedPaymentId;
            payConfirm.disabled = false;
        });

        card.addEventListener('keydown', function(e) {
            if (e.key === "Enter" || e.key === " ") {
                e.preventDefault();
                this.click();
            }
        });
    });

    payConfirm.addEventListener('click', () => {
        if (!selectedPaymentId) {
            Swal.fire({icon:'warning', title:'Select payment', text:'Please choose a payment method.'});
            return;
        }
        document.getElementById('finalSubmit').click();
    });

    // === CALENDAR ===
    const calendarContainer = document.getElementById('inlineCalendar');
    
    flatpickr(calendarContainer, {
        inline: true,
        minDate: "today",
        dateFormat: "Y-m-d",
        appendTo: calendarContainer.parentElement,
        onChange: function(selectedDates, dateStr) {
            dateInput.value = dateStr;
            loadAvailableTimes();
            document.querySelector('.calendar-box').classList.add('date-selected');
        }
    });

    // Force calendar inside wrapper
    setTimeout(() => {
        const realCalendar = document.querySelector('.flatpickr-calendar.inline');
        if (realCalendar && !realCalendar.parentElement.classList.contains('calendar-wrapper')) {
            calendarContainer.parentElement.appendChild(realCalendar);
        }
    }, 100);
});
</script>

<?php if(!empty($alert['message'])): ?>
<script>
Swal.fire({
    icon: '<?= $alert['type'] ?>',
    title: '<?= $alert['type']==='success' ? 'Success!' : ($alert['type']==='warning' ? 'Notice' : 'Oops!') ?>',
    text: '<?= addslashes($alert['message']) ?>',
    confirmButtonColor: '#002339'
});
</script>
<?php endif; ?>

</body>
</html>