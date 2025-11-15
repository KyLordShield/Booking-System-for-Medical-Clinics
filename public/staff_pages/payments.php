<?php
session_start();
require_once __DIR__ . '/../../classes/Payment.php';
require_once __DIR__ . '/../../classes/PaymentMethod.php';
require_once __DIR__ . '/../../classes/PaymentStatus.php';

/* ---------- 1. AUTH CHECK ---------- */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../../index.php");
    exit;
}

$payment = new Payment();
$method = new PaymentMethod();
$status = new PaymentStatus();

$payments = $payment->getAllPayments();
$methods = $method->getAllMethods();
$statuses = $status->getAllStatuses();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Payments | Staff Dashboard</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<!-- ✅ Tailwind CSS -->
<script src="https://cdn.tailwindcss.com"></script>

<!-- ✅ Custom CSS -->
<link rel="stylesheet" href="/Booking-System-For-Medical-Clinics/assets/css/style.css">

<!-- ✅ jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- ✅ SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-[var(--secondary)] min-h-screen flex flex-col font-[Georgia]">

<!-- NAVBAR -->
<!-- ✅ HEADER LINK -->
  <?php include dirname(__DIR__, 2) . "/partials/header.php"; ?>
<!-- ✅ HEADER LINK -->

<!-- ✅ MAIN CONTENT -->
<main class="flex-1 px-10 py-10">
  <h2 class="text-[36px] font-bold text-[var(--primary)] mb-6">Payments Management</h2>

  <div class="flex flex-wrap justify-between items-center mb-6 gap-3">
    <input type="text" placeholder="Search payment..." class="px-4 py-2 rounded-full border-none w-72 focus:ring-2 focus:ring-[var(--primary)] outline-none text-[16px]">

    <div class="flex flex-wrap gap-2">
      <button id="openAddPayment" class="create-btn">+ Add Payment</button>
      <button id="openAddMethod" class="create-btn">+ Add Method</button>
      <button id="openEditMethod" class="create-btn">Edit Methods</button>
      <button id="openAddStatus" class="create-btn">+ Add Status</button>
      <button id="openEditStatus" class="create-btn">Edit Status</button>
    </div>
  </div>

  <!-- ✅ Table -->
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
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($payments)): ?>
          <?php foreach ($payments as $p): ?>
            <tr class="border-b border-gray-300 hover:bg-gray-50">
              <td class="py-3 px-4"><?= htmlspecialchars($p['PYMT_ID']) ?></td>
              <td class="py-3 px-4"><?= htmlspecialchars($p['APPT_ID']) ?></td>
              <td class="py-3 px-4"><?= htmlspecialchars($p['PYMT_AMOUNT_PAID']) ?></td>
              <td class="py-3 px-4"><?= htmlspecialchars($p['PYMT_DATE']) ?></td>
              <td class="py-3 px-4"><?= htmlspecialchars($p['PYMT_METH_NAME']) ?></td>
              <td class="py-3 px-4"><?= htmlspecialchars($p['PYMT_STAT_NAME']) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="6" class="text-center py-6 text-gray-500">No payments found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</main>

<!-- ✅ FOOTER -->


  <?php include dirname(__DIR__, 2) . "/partials/footer.php"; ?>


<!-- ==============================
     🔹 MODALS
============================== -->

<!-- Add Payment Modal -->
<div id="modalAddPayment" class="hidden fixed inset-0 bg-black bg-opacity-40 flex justify-center items-center z-50">
  <div class="bg-white p-8 rounded-2xl w-[400px] relative">
    <h3 class="text-xl font-bold mb-4 text-[var(--primary)]">Add Payment</h3>
    <form id="formAddPayment" class="space-y-3">
      <input name="appt_id" placeholder="Appointment ID" class="input-box w-full" required>
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

<!-- Edit Methods Modal (Fixed for overflow) -->
<div id="modalEditMethod" class="hidden fixed inset-0 bg-black bg-opacity-40 flex justify-center items-center z-50">
  <div class="bg-white p-8 rounded-2xl w-[400px] max-h-[90vh] flex flex-col">
    <h3 class="text-xl font-bold mb-4 text-[var(--primary)] flex-shrink-0">Edit Payment Methods</h3>
    <!-- Scrollable content wrapper -->
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

<!-- Edit Status Modal (Fixed for overflow) -->
<div id="modalEditStatus" class="hidden fixed inset-0 bg-black bg-opacity-40 flex justify-center items-center z-50">
  <div class="bg-white p-8 rounded-2xl w-[400px] max-h-[90vh] flex flex-col">
    <h3 class="text-xl font-bold mb-4 text-[var(--primary)] flex-shrink-0">Edit Payment Status</h3>
    <!-- Scrollable content wrapper -->
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

<!-- ✅ JS -->
<script>
$(function(){
  // OPEN MODAL
  $("[id^='open']").click(function(){
    const target = $(this).attr('id').replace('open', 'modal');
    $("#" + target).removeClass("hidden").fadeIn(200);
  });

  // CLOSE MODAL
  $(".closeModal").click(function(){
    // Use .stop() before fadeOut to prevent rapid clicking from causing issues
    $(this).closest("[id^='modal']").stop(true, true).fadeOut(200, function(){
      $(this).addClass("hidden");
    });
  });

  // ADD PAYMENT
  $("#formAddPayment").submit(function(e){
  e.preventDefault();

  $.ajax({
    url: "../../ajax/ajax_payment_actions.php",
    type: "POST",
    data: $(this).serialize() + "&action=addPayment",
    dataType: "json", // ✅ ensures proper parsing
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


  // ADD METHOD
  $("#formAddMethod").submit(function(e){
    e.preventDefault();
    $.post("../../ajax/ajax_payment_actions.php", $(this).serialize() + "&action=addMethod", function(res){
      alert(res.message);
      location.reload();
    }, "json");
  });

  // UPDATE METHOD
  $(".updateMethod").click(function(){
    const id = $(this).prev().data("id");
    const name = $(this).prev().val();
    $.post("../../ajax/ajax_payment_actions.php", {action: "updateMethod", id, name}, function(res){
      alert(res.message);
    }, "json");
  });

  // ADD STATUS
  $("#formAddStatus").submit(function(e){
    e.preventDefault();
    $.post("../../ajax/ajax_payment_actions.php", $(this).serialize() + "&action=addStatus", function(res){
      alert(res.message);
      location.reload();
    }, "json");
  });

  // UPDATE STATUS
  $(".updateStatus").click(function(){
    const id = $(this).prev().data("id");
    const name = $(this).prev().val();
    $.post("../../ajax/ajax_payment_actions.php", {action: "updateStatus", id, name}, function(res){
      alert(res.message);
    }, "json");
  });
});
</script>
</body>
</html>