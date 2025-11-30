<?php
session_start();
require_once __DIR__ . '/../../classes/Payment.php';
require_once __DIR__ . '/../../classes/PaymentMethod.php';
require_once __DIR__ . '/../../classes/PaymentStatus.php';
require_once dirname(__DIR__, 2) . '/classes/Medical_Records.php';
require_once dirname(__DIR__, 2) . '/config/Database.php';
$db = new Database();
$conn = $db->connect();

/* ---------- 1. AUTH CHECK ---------- */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../../index.php");
    exit;
}

$payment = new Payment();
$method = new PaymentMethod();
$status = new PaymentStatus();
$mr = new MedicalRecord();

$payments = $payment->getAllPayments();
$methods = $method->getAllMethods();
$statuses = $status->getAllStatuses();
$medicalRecords = $mr->getAll();

// Fetch appointments for dropdown
$apptOptions = [];
try {
    $stmt = $conn->prepare("
        SELECT a.APPT_ID, CONCAT(p.PAT_FIRST_NAME, ' ', p.PAT_LAST_NAME) AS patient_name
        FROM appointment a
        LEFT JOIN patient p ON p.PAT_ID = a.PAT_ID
        ORDER BY a.APPT_ID DESC
    ");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        $apptOptions[] = [
            'APPT_ID' => $r['APPT_ID'],
            'label'   => "Appt#".$r['APPT_ID']." — ".$r['patient_name']
        ];
    }
} catch (Exception $e) {
    // ignore or log error
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Payments | Staff Dashboard</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="../../assets/css/style.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-[var(--secondary)] min-h-screen flex flex-col font-[Georgia]">

<?php include dirname(__DIR__, 2) . "/partials/header.php"; ?>

<main class="flex-1 px-10 py-10">
  <h2 class="text-[36px] font-bold text-[var(--primary)] mb-6">Payments Management</h2>

  <div class="flex flex-wrap justify-between items-center mb-6 gap-3">
    <input type="text" id="searchPayment" placeholder="Search payment..." class="px-4 py-2 rounded-full border-none w-72 focus:ring-2 focus:ring-[var(--primary)] outline-none text-[16px]">

    <div class="flex flex-wrap gap-2">
      <button id="openAddPayment" class="create-btn">+ Add Payment</button>
      <button id="openAddMethod" class="create-btn">+ Add Method</button>
      <button id="openEditMethod" class="create-btn">Edit Methods</button>
      <button id="openAddStatus" class="create-btn">+ Add Status</button>
      <button id="openEditStatus" class="create-btn">Edit Status</button>
    </div>
  </div>

  <!-- Table -->
  <div class="table-container bg-[var(--light)] p-6 rounded-[25px] shadow-md overflow-x-auto">
    <table class="w-full border-collapse text-[var(--primary)] min-w-[800px]">
      <thead>
        <tr class="border-b border-gray-300 bg-[var(--light)]">
          <th class="py-3 px-4 text-left">Payment ID</th>
          <th class="py-3 px-4 text-left">Appointment ID</th>
          <th class="py-3 px-4 text-left">Amount</th>
          <th class="py-3 px-4 text-left">Date</th>
          <th class="py-3 px-4 text-left">Method</th>
          <th class="py-3 px-4 text-left">Status</th>
          <th class="py-3 px-4 text-left">Actions</th>
        </tr>
      </thead>
      <tbody id="paymentTableBody">
        <?php if (!empty($payments)): ?>
          <?php foreach ($payments as $p): ?>
            <tr class="border-b border-gray-300 hover:bg-gray-50" data-payment='<?= json_encode($p, JSON_HEX_APOS|JSON_HEX_QUOT) ?>'>
              <td class="py-3 px-4"><?= htmlspecialchars($p['PYMT_ID']) ?></td>
              <td class="py-3 px-4">
              <div class="font-medium">Appt#<?= htmlspecialchars($p['APPT_ID']) ?></div>
              <div class="text-sm text-gray-600">
              <?= $p['patient_name'] ? htmlspecialchars($p['patient_name']) : '<em class="text-gray-400">No patient</em>' ?>
              </div>
              </td>
              <td class="py-3 px-4">₱<?= number_format($p['PYMT_AMOUNT_PAID'], 2) ?></td>
              <td class="py-3 px-4"><?= htmlspecialchars($p['PYMT_DATE']) ?></td>
              <td class="py-3 px-4"><?= htmlspecialchars($p['PYMT_METH_NAME']) ?></td>
              <td class="py-3 px-4">
                <span class="px-3 py-1 rounded-full text-sm font-semibold <?= $p['PYMT_STAT_NAME'] === 'Paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' ?>">
                  <?= htmlspecialchars($p['PYMT_STAT_NAME']) ?>
                </span>
              </td>
              <td class="py-3 px-4">
                <button class="btn updatePaymentBtn" data-id="<?= $p['PYMT_ID'] ?>">Update</button>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="7" class="text-center py-6 text-gray-500">No payments found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</main>

<?php include dirname(__DIR__, 2) . "/partials/footer.php"; ?>

<!-- Add Payment Modal -->
<div id="modalAddPayment" class="hidden fixed inset-0 bg-black bg-opacity-40 flex justify-center items-center z-50">
  <div class="bg-white p-8 rounded-2xl w-[400px] relative">
    <h3 class="text-xl font-bold mb-4 text-[var(--primary)]">Add Payment</h3>
    <form id="formAddPayment" class="space-y-3">
      <select name="appt_id" class="input-box w-full" required>
        <option value="">Select Appointment</option>
        <?php foreach ($apptOptions as $opt): ?>
          <option value="<?= htmlspecialchars($opt['APPT_ID']) ?>">
            <?= htmlspecialchars($opt['label']) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <input name="amount" placeholder="Amount Paid" class="input-box w-full" required>
      <select name="method_id" class="input-box w-full" required>
        <option value="">Select Method</option>
        <?php foreach ($methods as $m): ?>
          <option value="<?= $m['PYMT_METH_ID'] ?>"><?= htmlspecialchars($m['PYMT_METH_NAME']) ?></option>
        <?php endforeach; ?>
      </select>
      <select name="status_id" class="input-box w-full" required>
        <option value="">Select Status</option>
        <?php foreach ($statuses as $s): ?>
          <option value="<?= $s['PYMT_STAT_ID'] ?>"><?= htmlspecialchars($s['PYMT_STAT_NAME']) ?></option>
        <?php endforeach; ?>
      </select>
      <div class="flex justify-end gap-2 pt-4">
        <button type="button" class="cancel-btn closeModal">Cancel</button>
        <button type="submit" class="create-btn">Save</button>
      </div>
    </form>
  </div>
</div>

<!-- Update Payment Modal -->
<div id="modalUpdatePayment" class="hidden fixed inset-0 bg-black bg-opacity-40 flex justify-center items-center z-50">
  <div class="bg-white p-8 rounded-2xl w-[400px] relative">
    <h3 class="text-xl font-bold mb-4 text-[var(--primary)]">Update Payment</h3>
    <form id="formUpdatePayment" class="space-y-3">
      <input type="hidden" name="payment_id" id="updatePaymentId">
      
      <label class="block text-sm font-semibold text-gray-700">Appointment ID</label>
      <input id="updateApptId" class="input-box w-full bg-gray-100" readonly>

      <label class="block text-sm font-semibold text-gray-700">Amount Paid</label>
      <input name="amount" id="updateAmount" placeholder="Amount Paid" class="input-box w-full" required>
      
      <label class="block text-sm font-semibold text-gray-700">Payment Method</label>
      <select name="method_id" id="updateMethodId" class="input-box w-full" required>
        <option value="">Select Method</option>
        <?php foreach ($methods as $m): ?>
          <option value="<?= $m['PYMT_METH_ID'] ?>"><?= htmlspecialchars($m['PYMT_METH_NAME']) ?></option>
        <?php endforeach; ?>
      </select>
      
      <label class="block text-sm font-semibold text-gray-700">Payment Status</label>
      <select name="status_id" id="updateStatusId" class="input-box w-full" required>
        <option value="">Select Status</option>
        <?php foreach ($statuses as $s): ?>
          <option value="<?= $s['PYMT_STAT_ID'] ?>"><?= htmlspecialchars($s['PYMT_STAT_NAME']) ?></option>
        <?php endforeach; ?>
      </select>
      
      <div class="flex justify-end gap-2 pt-4">
        <button type="button" class="cancel-btn closeModal">Cancel</button>
        <button type="submit" class="create-btn">Update</button>
      </div>
    </form>
  </div>
</div>

<!-- Add Method Modal -->
<div id="modalAddMethod" class="hidden fixed inset-0 bg-black bg-opacity-40 flex justify-center items-center z-50">
  <div class="bg-white p-8 rounded-2xl w-[350px] relative">
    <h3 class="text-xl font-bold mb-4 text-[var(--primary)]">Add Payment Method</h3>
    <form id="formAddMethod" class="space-y-3">
      <input name="name" placeholder="Method Name" class="input-box w-full" required>
      <div class="flex justify-end gap-2 pt-4">
        <button type="button" class="cancel-btn closeModal">Cancel</button>
        <button type="submit" class="create-btn">Save</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Methods Modal -->
<div id="modalEditMethod" class="hidden fixed inset-0 bg-black bg-opacity-40 flex justify-center items-center z-50">
  <div class="bg-white p-8 rounded-2xl w-[400px] max-h-[90vh] flex flex-col">
    <h3 class="text-xl font-bold mb-4 text-[var(--primary)] flex-shrink-0">Edit Payment Methods</h3>
    <div class="overflow-y-auto flex-1 pr-2 -mr-2">
      <?php foreach ($methods as $m): ?>
        <div class="flex items-center gap-2 mb-2">
          <input type="text" class="border rounded px-2 py-1 flex-1" data-id="<?= $m['PYMT_METH_ID'] ?>" value="<?= htmlspecialchars($m['PYMT_METH_NAME']) ?>">
          <button class="updateMethod text-blue-600 font-semibold">Save</button>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="flex justify-end mt-4 flex-shrink-0">
      <button class="cancel-btn closeModal">Close</button>
    </div>
  </div>
</div>

<!-- Add Status Modal -->
<div id="modalAddStatus" class="hidden fixed inset-0 bg-black bg-opacity-40 flex justify-center items-center z-50">
  <div class="bg-white p-8 rounded-2xl w-[350px]">
    <h3 class="text-xl font-bold mb-4 text-[var(--primary)]">Add Payment Status</h3>
    <form id="formAddStatus" class="space-y-3">
      <input name="name" placeholder="Status Name" class="input-box w-full" required>
      <div class="flex justify-end gap-2 pt-4">
        <button type="button" class="cancel-btn closeModal">Cancel</button>
        <button type="submit" class="create-btn">Save</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Status Modal -->
<div id="modalEditStatus" class="hidden fixed inset-0 bg-black bg-opacity-40 flex justify-center items-center z-50">
  <div class="bg-white p-8 rounded-2xl w-[400px] max-h-[90vh] flex flex-col">
    <h3 class="text-xl font-bold mb-4 text-[var(--primary)] flex-shrink-0">Edit Payment Status</h3>
    <div class="overflow-y-auto flex-1 pr-2 -mr-2">
      <?php foreach ($statuses as $s): ?>
        <div class="flex items-center gap-2 mb-2">
          <input type="text" class="border rounded px-2 py-1 flex-1" data-id="<?= $s['PYMT_STAT_ID'] ?>" value="<?= htmlspecialchars($s['PYMT_STAT_NAME']) ?>">
          <button class="updateStatus text-blue-600 font-semibold">Save</button>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="flex justify-end mt-4 flex-shrink-0">
      <button class="cancel-btn closeModal">Close</button>
    </div>
  </div>
</div>

<script>
$(function(){
  // OPEN MODAL
  $("[id^='open']").click(function(){
    const target = $(this).attr('id').replace('open', 'modal');
    $("#" + target).removeClass("hidden").fadeIn(200);
  });

  // CLOSE MODAL
  $(".closeModal").click(function(){
    $(this).closest("[id^='modal']").stop(true, true).fadeOut(200, function(){
      $(this).addClass("hidden");
    });
  });

  // SEARCH PAYMENTS
  $("#searchPayment").on("keyup", function() {
    const value = $(this).val().toLowerCase();
    $("#paymentTableBody tr").filter(function() {
      $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
    });
  });

  // ADD PAYMENT
  $("#formAddPayment").submit(function(e){
    e.preventDefault();

    $.ajax({
      url: "../../ajax/ajax_payment_actions.php",
      type: "POST",
      data: $(this).serialize() + "&action=addPayment",
      dataType: "json",
      success: function(res){
        Swal.fire({
          icon: res.success ? "success" : "error",
          title: res.success ? "Success!" : "Error",
          text: res.message,
          confirmButtonColor: res.success ? "#3085d6" : "#d33",
        }).then(() => {
          if (res.success) location.reload();
        });
      },
      error: function(xhr, status, error){
        Swal.fire({
          icon: "error",
          title: "Server Error",
          text: "Something went wrong: " + error,
        });
      }
    });
  });

  // OPEN UPDATE MODAL
  $(".updatePaymentBtn").click(function(){
    const paymentData = $(this).closest("tr").data("payment");
    
    $("#updatePaymentId").val(paymentData.PYMT_ID);
    $("#updateApptId").val("Appt#" + paymentData.APPT_ID);
    $("#updateAmount").val(paymentData.PYMT_AMOUNT_PAID);
    $("#updateMethodId").val(paymentData.PYMT_METH_ID);
    $("#updateStatusId").val(paymentData.PYMT_STAT_ID);
    
    $("#modalUpdatePayment").removeClass("hidden").fadeIn(200);
  });

  // UPDATE PAYMENT
  $("#formUpdatePayment").submit(function(e){
    e.preventDefault();

    const formData = $(this).serialize() + "&action=updatePayment";
    console.log("Sending data:", formData); // DEBUG

    $.ajax({
      url: "../../ajax/ajax_payment_actions.php",
      type: "POST",
      data: formData,
      dataType: "json",
      success: function(res){
        console.log("Response:", res); // DEBUG
        Swal.fire({
          icon: res.success ? "success" : "error",
          title: res.success ? "Success!" : "Error",
          text: res.message,
          confirmButtonColor: res.success ? "#3085d6" : "#d33",
        }).then(() => {
          if (res.success) location.reload();
        });
      },
      error: function(xhr, status, error){
        console.error("Error details:", xhr.responseText); // DEBUG
        Swal.fire({
          icon: "error",
          title: "Server Error",
          text: "Something went wrong. Check console for details.",
        });
      }
    });
  });

  // ADD METHOD
  $("#formAddMethod").submit(function(e){
    e.preventDefault();
    $.ajax({
      url: "../../ajax/ajax_payment_actions.php",
      type: "POST",
      data: $(this).serialize() + "&action=addMethod",
      dataType: "json",
      success: function(res){
        Swal.fire({
          icon: res.success ? "success" : "error",
          title: res.success ? "Success!" : "Error",
          text: res.message,
        }).then(() => {
          if (res.success) location.reload();
        });
      }
    });
  });

  // UPDATE METHOD
  $(".updateMethod").click(function(){
    const id = $(this).prev().data("id");
    const name = $(this).prev().val();
    $.ajax({
      url: "../../ajax/ajax_payment_actions.php",
      type: "POST",
      data: {action: "updateMethod", id, name},
      dataType: "json",
      success: function(res){
        Swal.fire({
          icon: res.success ? "success" : "info",
          title: res.success ? "Updated!" : "Info",
          text: res.message,
          timer: 2000,
          showConfirmButton: false
        });
      }
    });
  });

  // ADD STATUS
  $("#formAddStatus").submit(function(e){
    e.preventDefault();
    $.ajax({
      url: "../../ajax/ajax_payment_actions.php",
      type: "POST",
      data: $(this).serialize() + "&action=addStatus",
      dataType: "json",
      success: function(res){
        Swal.fire({
          icon: res.success ? "success" : "error",
          title: res.success ? "Success!" : "Error",
          text: res.message,
        }).then(() => {
          if (res.success) location.reload();
        });
      }
    });
  });

  // UPDATE STATUS
  $(".updateStatus").click(function(){
    const id = $(this).prev().data("id");
    const name = $(this).prev().val();
    $.ajax({
      url: "../../ajax/ajax_payment_actions.php",
      type: "POST",
      data: {action: "updateStatus", id, name},
      dataType: "json",
      success: function(res){
        Swal.fire({
          icon: res.success ? "success" : "info",
          title: res.success ? "Updated!" : "Info",
          text: res.message,
          timer: 2000,
          showConfirmButton: false
        });
      }
    });
  });
});
</script>
</body>
</html>