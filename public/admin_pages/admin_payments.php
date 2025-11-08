<?php
session_start();
/* ---------- AUTH: only admin/superadmin ---------- */
if (
    empty($_SESSION['USER_IS_SUPERADMIN']) ||
    $_SESSION['USER_IS_SUPERADMIN'] != 1 ||
    $_SESSION['role'] !== 'admin'
) {
   header("Location: ../../index.php");
    exit;
}

require_once __DIR__ . '/../../classes/Payment.php';
require_once __DIR__ . '/../../classes/PaymentMethod.php';
require_once __DIR__ . '/../../classes/PaymentStatus.php';

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
<title>Payments | Admin Dashboard</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<!-- ✅ Tailwind CSS -->
<script src="https://cdn.tailwindcss.com"></script>

<!-- ✅ Custom CSS -->
<link rel="stylesheet" href="/Booking-System-For-Medical-Clinics/assets/css/style.css">

<!-- ✅ jQuery + SweetAlert -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-[var(--secondary)] min-h-screen flex flex-col font-[Georgia]">

<!-- ✅ NAVBAR -->
<div class="navbar flex justify-between items-center px-10 py-5 bg-[var(--primary)] rounded-b-[35px] shadow-lg">
  <div class="navbar-brand flex items-center text-white text-2xl font-bold">
    <img src="https://cdn-icons-png.flaticon.com/512/3209/3209999.png" class="w-11 mr-3">Medicina
  </div>

  <div class="nav-links flex gap-4">
    <a href="/Booking-System-For-Medical-Clinics/public/admin_dashboard.php">Dashboard</a>
    <a href="/Booking-System-For-Medical-Clinics/public/admin_pages/admin_specialization.php">Specialization</a>
    <a href="/Booking-System-For-Medical-Clinics/public/admin_pages/admin_services.php">Services</a>
    <a href="/Booking-System-For-Medical-Clinics/public/admin_pages/admin_status.php">Status</a>
    <a  href="/Booking-System-For-Medical-Clinics/public/admin_pages/admin_schedules.php">Schedules</a>
    <a href="/Booking-System-For-Medical-Clinics/public/admin_pages/admin_medical_records.php">Medical Records</a>
    <a class="active" href="/Booking-System-For-Medical-Clinics/public/admin_pages/admin_payments.php">Payments</a>
    <a href="/Booking-System-For-Medical-Clinics/index.php">Log out</a>
  </div>
</div>

<!-- ✅ MAIN CONTENT -->
<main class="flex-1 px-10 py-10">
  <h2 class="text-[36px] font-bold text-[var(--primary)] mb-6">Payments Management (Admin)</h2>

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

  <!-- ✅ Table -->
  <div class="table-container bg-[var(--light)] p-6 rounded-[25px] shadow-md overflow-x-auto">
    <table class="w-full border-collapse text-[var(--primary)] min-w-[800px]" id="paymentsTable">
      <thead>
         <tr class="border-b text-left font-bold bg-[var(--light)]">
          <th class="py-3 px-4 text-left">Payment ID</th>
          <th class="py-3 px-4 text-left">Appointment ID</th>
          <th class="py-3 px-4 text-left">Amount</th>
          <th class="py-3 px-4 text-left">Date</th>
          <th class="py-3 px-4 text-left">Method</th>
          <th class="py-3 px-4 text-left">Status</th>
          <th class="py-3 px-4 text-center">Actions</th>
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
              <td class="py-3 px-4 text-center">
                <button class="editPayment text-blue-600 mr-3" 
                  data-id="<?= $p['PYMT_ID'] ?>" 
                  data-appt="<?= $p['APPT_ID'] ?>" 
                  data-amount="<?= $p['PYMT_AMOUNT_PAID'] ?>" 
                  data-method="<?= $p['PYMT_METH_ID'] ?>" 
                  data-status="<?= $p['PYMT_STAT_ID'] ?>">Edit</button>
                <button class="deletePayment text-red-600" data-id="<?= $p['PYMT_ID'] ?>">Delete</button>
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

<!-- ✅ FOOTER -->
<footer class="bg-[var(--primary)] text-white text-center py-4 rounded-t-[35px] text-sm mt-6">
  &copy; 2025 Medicina Clinic | All Rights Reserved
</footer>

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

<!-- Edit Payment Modal -->
<div id="modalEditPayment" class="hidden fixed inset-0 bg-black bg-opacity-40 flex justify-center items-center z-50">
  <div class="bg-white p-8 rounded-2xl w-[400px] relative">
    <h3 class="text-xl font-bold mb-4 text-[var(--primary)]">Edit Payment</h3>
    <form id="formEditPayment" class="space-y-3">
      <input type="hidden" name="id">
      <input type="text" value="<?= htmlspecialchars($p['APPT_ID']) ?>" class="input-box w-full" readonly>

      <input name="amount" placeholder="Amount Paid" class="input-box w-full" required>
      <select name="method_id" class="input-box w-full" required>
        <?php foreach ($methods as $m): ?>
          <option value="<?= $m['PYMT_METH_ID'] ?>"><?= htmlspecialchars($m['PYMT_METH_NAME']) ?></option>
        <?php endforeach; ?>
      </select>
      <select name="status_id" class="input-box w-full" required>
        <?php foreach ($statuses as $s): ?>
          <option value="<?= $s['PYMT_STAT_ID'] ?>"><?= htmlspecialchars($s['PYMT_STAT_NAME']) ?></option>
        <?php endforeach; ?>
      </select>
      <div class="flex justify-between pt-4">
        <button type="button" class="cancel-btn closeModal">Cancel</button>
        <button type="submit" class="create-btn">Update</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Methods Modal -->
<div id="modalEditMethod" class="hidden fixed inset-0 bg-black bg-opacity-40 flex justify-center items-center z-50">
  <div class="bg-white p-8 rounded-2xl w-[400px] max-h-[90vh] flex flex-col">
    <h3 class="text-xl font-bold mb-4 text-[var(--primary)]">Edit Payment Methods</h3>
    <div class="overflow-y-auto flex-1 pr-2 -mr-2">
    <?php foreach ($methods as $m): ?>
      <div class="flex items-center gap-2 mb-2">
        <input type="text" class="border rounded px-2 py-1 flex-1" data-id="<?= $m['PYMT_METH_ID'] ?>" value="<?= htmlspecialchars($m['PYMT_METH_NAME']) ?>">
        <button class="updateMethod text-blue-600 font-semibold">Save</button>
        <button class="deleteMethod text-red-600 font-semibold" data-id="<?= $m['PYMT_METH_ID'] ?>">Delete</button>
      </div>
    <?php endforeach; ?>
    </div>
    <div class="flex justify-end mt-4">
      <button class="cancel-btn closeModal">Close</button>
    </div>
  </div>
</div>

<!-- Edit Status Modal -->
<div id="modalEditStatus" class="hidden fixed inset-0 bg-black bg-opacity-40 flex justify-center items-center z-50">
  <div class="bg-white p-8 rounded-2xl w-[400px] max-h-[90vh] flex flex-col">
    <h3 class="text-xl font-bold mb-4 text-[var(--primary)]">Edit Payment Status</h3>
    <div class="overflow-y-auto flex-1 pr-2 -mr-2">
    <?php foreach ($statuses as $s): ?>
      <div class="flex items-center gap-2 mb-2">
        <input type="text" class="border rounded px-2 py-1 flex-1" data-id="<?= $s['PYMT_STAT_ID'] ?>" value="<?= htmlspecialchars($s['PYMT_STAT_NAME']) ?>">
        <button class="updateStatus text-blue-600 font-semibold">Save</button>
        <button class="deleteStatus text-red-600 font-semibold" data-id="<?= $s['PYMT_STAT_ID'] ?>">Delete</button>
      </div>
    <?php endforeach; ?>
    </div>
    <div class="flex justify-end mt-4">
      <button class="cancel-btn closeModal">Close</button>
    </div>
  </div>
</div>
<!-- Add Method Modal -->
<div id="modalAddMethod" class="hidden fixed inset-0 bg-black bg-opacity-40 flex justify-center items-center z-50">
  <div class="bg-white p-6 rounded-2xl w-[400px] relative">
    <h3 class="text-xl font-bold mb-4 text-[var(--primary)]">Add Payment Method</h3>
    <form id="formAddMethod" class="space-y-3">
      <input type="text" name="name" placeholder="Method Name" class="input-box w-full" required>
      <div class="flex justify-end gap-2 pt-4">
        <button type="button" class="cancel-btn closeModal">Cancel</button>
        <button type="submit" class="create-btn">Save</button>
      </div>
    </form>
  </div>
</div>

<!-- Add Status Modal -->
<div id="modalAddStatus" class="hidden fixed inset-0 bg-black bg-opacity-40 flex justify-center items-center z-50">
  <div class="bg-white p-6 rounded-2xl w-[400px] relative">
    <h3 class="text-xl font-bold mb-4 text-[var(--primary)]">Add Payment Status</h3>
    <form id="formAddStatus" class="space-y-3">
      <input type="text" name="name" placeholder="Status Name" class="input-box w-full" required>
      <div class="flex justify-end gap-2 pt-4">
        <button type="button" class="cancel-btn closeModal">Cancel</button>
        <button type="submit" class="create-btn">Save</button>
      </div>
    </form>
  </div>
</div>


<!-- ✅ JS -->
<script>
$(function(){

  // ============ OPEN/CLOSE MODALS ============
  $("[id^='open']").click(function(){
    const target = $(this).attr('id').replace('open', 'modal');
    $("#" + target).removeClass("hidden").fadeIn(200);
  });

  $(".closeModal").click(function(){
    $(this).closest("[id^='modal']").fadeOut(200, function(){
      $(this).addClass("hidden");
    });
  });

  // ============ ADD PAYMENT ============
  $("#formAddPayment").submit(function(e){
    e.preventDefault();
    $.ajax({
      url: "../../ajax/ajax_payment_actions.php",
      type: "POST",
      data: $(this).serialize() + "&action=addPayment",
      dataType: "json",
      success: function(res){
        Swal.fire({icon: res.success ? "success" : "error", title: res.message}).then(()=>location.reload());
      }
    });
  });

  // ============ EDIT PAYMENT ============
  $(".editPayment").click(function(){
    const modal = $("#modalEditPayment");
    modal.find("[name='id']").val($(this).data("id"));
    modal.find("[name='appt_id']").val($(this).data("appt"));
    modal.find("[name='amount']").val($(this).data("amount"));
    modal.find("[name='method_id']").val($(this).data("method"));
    modal.find("[name='status_id']").val($(this).data("status"));
    modal.removeClass("hidden").fadeIn(200);
  });

  $("#formEditPayment").submit(function(e){
    e.preventDefault();
    $.ajax({
      url: "../../ajax/ajax_payment_actions.php",
      type: "POST",
      data: $(this).serialize() + "&action=updatePayment",
      dataType: "json",
      success: function(res){
        Swal.fire({icon: res.success ? "success" : "error", title: res.message}).then(()=>location.reload());
      }
    });
  });

  // ============ DELETE PAYMENT ============
  $(".deletePayment").click(function(){
    const id = $(this).data("id");
    Swal.fire({
      title: "Delete this payment?",
      icon: "warning",
      showCancelButton: true,
      confirmButtonText: "Yes, delete",
      confirmButtonColor: "#d33"
    }).then(res=>{
      if(res.isConfirmed){
        $.post("../../ajax/ajax_payment_actions.php", {action:"deletePayment", id}, function(res){
          Swal.fire({icon: res.success ? "success" : "error", title: res.message}).then(()=>location.reload());
        },"json");
      }
    });
  });

  // Add Method
$("#formAddMethod").submit(function(e){
    e.preventDefault();
    $.post("../../ajax/ajax_payment_actions.php", $(this).serialize()+"&action=addMethod", function(res){
        Swal.fire({icon: res.success ? "success" : "error", title: res.message}).then(()=> location.reload());
    }, "json");
});

// Add Status
$("#formAddStatus").submit(function(e){
    e.preventDefault();
    $.post("../../ajax/ajax_payment_actions.php", $(this).serialize()+"&action=addStatus", function(res){
        Swal.fire({icon: res.success ? "success" : "error", title: res.message}).then(()=> location.reload());
    }, "json");
});


  // ============ UPDATE / DELETE METHOD ============
  $(".updateMethod").click(function(){
    const id = $(this).prev().data("id");
    const name = $(this).prev().val();
    $.post("../../ajax/ajax_payment_actions.php",{action:"updateMethod",id,name},function(res){
      alert(res.message);
    },"json");
  });

  $(".deleteMethod").click(function(){
    const id = $(this).data("id");
    $.post("../../ajax/ajax_payment_actions.php",{action:"deleteMethod",id},function(res){
      alert(res.message);
      location.reload();
    },"json");
  });

  // ============ UPDATE / DELETE STATUS ============
  $(".updateStatus").click(function(){
    const id = $(this).prev().data("id");
    const name = $(this).prev().val();
    $.post("../../ajax/ajax_payment_actions.php",{action:"updateStatus",id,name},function(res){
      alert(res.message);
    },"json");
  });

  $(".deleteStatus").click(function(){
    const id = $(this).data("id");
    $.post("../../ajax/ajax_payment_actions.php",{action:"deleteStatus",id},function(res){
      alert(res.message);
      location.reload();
    },"json");
  });

});
</script>
</body>
</html>
