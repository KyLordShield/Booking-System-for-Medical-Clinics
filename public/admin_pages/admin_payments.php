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
require_once dirname(__DIR__, 2) . '/config/Database.php';

$payment = new Payment();
$method = new PaymentMethod();
$status = new PaymentStatus();
$db = new Database();
$conn = $db->connect();

$payments = $payment->getAllPayments();
$methods = $method->getAllMethods();
$statuses = $status->getAllStatuses();

// Fetch appointments for dropdown
$apptOptions = [];
try {
    $stmt = $conn->prepare("
        SELECT a.APPT_ID, 
               CONCAT(p.PAT_FIRST_NAME, ' ', p.PAT_LAST_NAME) AS patient_name,
               a.APPT_DATE
        FROM appointment a
        LEFT JOIN patient p ON p.PAT_ID = a.PAT_ID
        LEFT JOIN payment py ON py.APPT_ID = a.APPT_ID
        WHERE py.PYMT_ID IS NULL
        ORDER BY a.APPT_ID DESC
    ");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        $apptOptions[] = [
            'APPT_ID' => $r['APPT_ID'],
            'label'   => "Appt#".$r['APPT_ID']." — ".$r['patient_name']." [".$r['APPT_DATE']."]"
        ];
    }
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Payments | Admin Dashboard</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="../../assets/css/style.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
    .modal { display: none; justify-content: center; align-items: center; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,.5); z-index: 1000; }
    .modal-content { background: #fff; padding: 20px; border-radius: 12px; max-width: 450px; width: 90%; position: relative; max-height: 90vh; overflow-y: auto; }
    .close-btn { position: absolute; top: 8px; right: 12px; font-size: 24px; cursor: pointer; color: #666; }
    .close-btn:hover { color: #000; }
</style>
</head>

<body class="bg-[var(--secondary)] min-h-screen flex flex-col font-[Georgia]">

<?php include dirname(__DIR__, 2) . "/partials/header.php"; ?>

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

  <!-- Table -->
  <div class="table-container bg-[var(--light)] p-6 rounded-[25px] shadow-md overflow-x-auto">
    <table class="w-full border-collapse text-[var(--primary)] min-w-[800px]">
      <thead>
        <tr class="border-b text-left font-bold bg-[var(--light)]">
          <th class="py-3 px-4">Payment ID</th>
          <th class="py-3 px-4">Appointment ID</th>
          <th class="py-3 px-4">Amount</th>
          <th class="py-3 px-4">Date</th>
          <th class="py-3 px-4">Method</th>
          <th class="py-3 px-4">Status</th>
          <th class="py-3 px-4 text-center">Actions</th>
        </tr>
      </thead>
      <tbody id="paymentTableBody">
        <?php if (!empty($payments)): ?>
          <?php foreach ($payments as $p): ?>
            <tr class="border-b border-gray-300 hover:bg-gray-50">
              <td class="py-3 px-4"><?= htmlspecialchars($p['PYMT_ID']) ?></td>
              <td class="py-3 px-4">Appt#<?= htmlspecialchars($p['APPT_ID']) ?></td>
              <td class="py-3 px-4">₱<?= number_format($p['PYMT_AMOUNT_PAID'], 2) ?></td>
              <td class="py-3 px-4"><?= htmlspecialchars($p['PYMT_DATE']) ?></td>
              <td class="py-3 px-4"><?= htmlspecialchars($p['PYMT_METH_NAME']) ?></td>
              <td class="py-3 px-4">
                <span class="px-3 py-1 rounded-full text-sm font-semibold <?= $p['PYMT_STAT_NAME'] === 'Paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' ?>">
                  <?= htmlspecialchars($p['PYMT_STAT_NAME']) ?>
                </span>
              </td>
              <td class="py-3 px-4 text-center">
                <button class="editPayment text-blue-600 mr-3 hover:underline" 
                  data-id="<?= $p['PYMT_ID'] ?>" 
                  data-appt="<?= $p['APPT_ID'] ?>" 
                  data-amount="<?= $p['PYMT_AMOUNT_PAID'] ?>" 
                  data-method="<?= $p['PYMT_METH_ID'] ?>" 
                  data-status="<?= $p['PYMT_STAT_ID'] ?>">Edit</button>
                <button class="deletePayment text-red-600 hover:underline" data-id="<?= $p['PYMT_ID'] ?>">Delete</button>
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
<div id="modalAddPayment" class="modal">
  <div class="modal-content">
    <span class="close-btn closeModal">&times;</span>
    <h3 class="text-xl font-bold mb-4 text-[var(--primary)]">Add Payment</h3>
    <form id="formAddPayment" class="space-y-3">
      <label class="block font-semibold">Appointment</label>
      <select name="appt_id" class="input-box w-full" required>
        <option value="">Select Appointment</option>
        <?php foreach ($apptOptions as $opt): ?>
          <option value="<?= htmlspecialchars($opt['APPT_ID']) ?>">
            <?= htmlspecialchars($opt['label']) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <label class="block font-semibold">Amount Paid</label>
      <input name="amount" type="number" step="0.01" placeholder="Amount Paid" class="input-box w-full" required>
      
      <label class="block font-semibold">Payment Method</label>
      <select name="method_id" class="input-box w-full" required>
        <option value="">Select Method</option>
        <?php foreach ($methods as $m): ?>
          <option value="<?= $m['PYMT_METH_ID'] ?>"><?= htmlspecialchars($m['PYMT_METH_NAME']) ?></option>
        <?php endforeach; ?>
      </select>
      
      <label class="block font-semibold">Payment Status</label>
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
<div id="modalEditPayment" class="modal">
  <div class="modal-content">
    <span class="close-btn closeModal">&times;</span>
    <h3 class="text-xl font-bold mb-4 text-[var(--primary)]">Edit Payment</h3>
    <form id="formEditPayment" class="space-y-3">
      <input type="hidden" name="id">
      
      <label class="block font-semibold">Appointment ID</label>
      <input type="text" name="appt_display" class="input-box w-full bg-gray-100" readonly>

      <label class="block font-semibold">Amount Paid</label>
      <input name="amount" type="number" step="0.01" placeholder="Amount Paid" class="input-box w-full" required>
      
      <label class="block font-semibold">Payment Method</label>
      <select name="method_id" class="input-box w-full" required>
        <?php foreach ($methods as $m): ?>
          <option value="<?= $m['PYMT_METH_ID'] ?>"><?= htmlspecialchars($m['PYMT_METH_NAME']) ?></option>
        <?php endforeach; ?>
      </select>
      
      <label class="block font-semibold">Payment Status</label>
      <select name="status_id" class="input-box w-full" required>
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
<div id="modalAddMethod" class="modal">
  <div class="modal-content">
    <span class="close-btn closeModal">&times;</span>
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

<!-- Edit Methods Modal -->
<div id="modalEditMethod" class="modal">
  <div class="modal-content">
    <span class="close-btn closeModal">&times;</span>
    <h3 class="text-xl font-bold mb-4 text-[var(--primary)]">Edit Payment Methods</h3>
    <div class="overflow-y-auto max-h-96">
      <?php foreach ($methods as $m): ?>
        <div class="flex items-center gap-2 mb-2">
          <input type="text" class="border rounded px-2 py-1 flex-1" data-id="<?= $m['PYMT_METH_ID'] ?>" value="<?= htmlspecialchars($m['PYMT_METH_NAME']) ?>">
          <button class="updateMethod text-blue-600 font-semibold hover:underline">Save</button>
          <button class="deleteMethod text-red-600 font-semibold hover:underline" data-id="<?= $m['PYMT_METH_ID'] ?>">Delete</button>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="flex justify-end mt-4">
      <button class="cancel-btn closeModal">Close</button>
    </div>
  </div>
</div>

<!-- Add Status Modal -->
<div id="modalAddStatus" class="modal">
  <div class="modal-content">
    <span class="close-btn closeModal">&times;</span>
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

<!-- Edit Status Modal -->
<div id="modalEditStatus" class="modal">
  <div class="modal-content">
    <span class="close-btn closeModal">&times;</span>
    <h3 class="text-xl font-bold mb-4 text-[var(--primary)]">Edit Payment Status</h3>
    <div class="overflow-y-auto max-h-96">
      <?php foreach ($statuses as $s): ?>
        <div class="flex items-center gap-2 mb-2">
          <input type="text" class="border rounded px-2 py-1 flex-1" data-id="<?= $s['PYMT_STAT_ID'] ?>" value="<?= htmlspecialchars($s['PYMT_STAT_NAME']) ?>">
          <button class="updateStatus text-blue-600 font-semibold hover:underline">Save</button>
          <button class="deleteStatus text-red-600 font-semibold hover:underline" data-id="<?= $s['PYMT_STAT_ID'] ?>">Delete</button>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="flex justify-end mt-4">
      <button class="cancel-btn closeModal">Close</button>
    </div>
  </div>
</div>

<script>
$(function(){
  // Open/Close Modals
  $("[id^='open']").click(function(){
    const target = $(this).attr('id').replace('open', 'modal');
    $("#" + target).css('display', 'flex');
  });

  $(".closeModal").click(function(){
    $(this).closest(".modal").css('display', 'none');
  });

  // Search functionality
  $("#searchPayment").on("keyup", function() {
    const value = $(this).val().toLowerCase();
    $("#paymentTableBody tr").filter(function() {
      $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
    });
  });

  // Add Payment
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
          text: res.message
        }).then(() => { if(res.success) location.reload(); });
      }
    });
  });

  // Edit Payment
  $(".editPayment").click(function(){
    const modal = $("#modalEditPayment");
    modal.find("[name='id']").val($(this).data("id"));
    modal.find("[name='appt_display']").val("Appt#" + $(this).data("appt"));
    modal.find("[name='amount']").val($(this).data("amount"));
    modal.find("[name='method_id']").val($(this).data("method"));
    modal.find("[name='status_id']").val($(this).data("status"));
    modal.css('display', 'flex');
  });

  $("#formEditPayment").submit(function(e){
    e.preventDefault();
    $.ajax({
      url: "../../ajax/ajax_payment_actions.php",
      type: "POST",
      data: $(this).serialize() + "&action=updatePayment&payment_id=" + $("[name='id']").val(),
      dataType: "json",
      success: function(res){
        Swal.fire({
          icon: res.success ? "success" : "error",
          title: res.success ? "Success!" : "Error",
          text: res.message
        }).then(() => { if(res.success) location.reload(); });
      }
    });
  });

  // Delete Payment
  $(".deletePayment").click(function(){
    const id = $(this).data("id");
    Swal.fire({
      title: "Are you sure?",
      text: "Delete this payment?",
      icon: "warning",
      showCancelButton: true,
      confirmButtonText: "Yes, delete it!",
      cancelButtonText: "Cancel",
      confirmButtonColor: "#d33"
    }).then(res => {
      if(res.isConfirmed){
        $.post("../../ajax/ajax_payment_actions.php", {action:"deletePayment", id}, function(res){
          Swal.fire({
            icon: res.success ? "success" : "error",
            title: res.success ? "Deleted!" : "Error",
            text: res.message
          }).then(() => { if(res.success) location.reload(); });
        }, "json");
      }
    });
  });

  // Add Method
  $("#formAddMethod").submit(function(e){
    e.preventDefault();
    $.post("../../ajax/ajax_payment_actions.php", $(this).serialize()+"&action=addMethod", function(res){
      Swal.fire({
        icon: res.success ? "success" : "error",
        title: res.success ? "Success!" : "Error",
        text: res.message
      }).then(() => { if(res.success) location.reload(); });
    }, "json");
  });

  // Add Status
  $("#formAddStatus").submit(function(e){
    e.preventDefault();
    $.post("../../ajax/ajax_payment_actions.php", $(this).serialize()+"&action=addStatus", function(res){
      Swal.fire({
        icon: res.success ? "success" : "error",
        title: res.success ? "Success!" : "Error",
        text: res.message
      }).then(() => { if(res.success) location.reload(); });
    }, "json");
  });

  // Update/Delete Method
  $(".updateMethod").click(function(){
    const id = $(this).prev().data("id");
    const name = $(this).prev().val();
    $.post("../../ajax/ajax_payment_actions.php",{action:"updateMethod",id,name},function(res){
      Swal.fire({
        icon: res.success ? "success" : "info",
        title: res.message,
        timer: 2000,
        showConfirmButton: false
      });
    },"json");
  });

  $(".deleteMethod").click(function(){
    const id = $(this).data("id");
    Swal.fire({
      title: "Delete this method?",
      icon: "warning",
      showCancelButton: true,
      confirmButtonText: "Yes, delete",
      confirmButtonColor: "#d33"
    }).then(res => {
      if(res.isConfirmed){
        $.post("../../ajax/ajax_payment_actions.php",{action:"deleteMethod",id},function(res){
          Swal.fire({icon: res.success ? "success" : "error", title: res.message}).then(() => location.reload());
        },"json");
      }
    });
  });

  // Update/Delete Status
  $(".updateStatus").click(function(){
    const id = $(this).prev().data("id");
    const name = $(this).prev().val();
    $.post("../../ajax/ajax_payment_actions.php",{action:"updateStatus",id,name},function(res){
      Swal.fire({
        icon: res.success ? "success" : "info",
        title: res.message,
        timer: 2000,
        showConfirmButton: false
      });
    },"json");
  });

  $(".deleteStatus").click(function(){
    const id = $(this).data("id");
    Swal.fire({
      title: "Delete this status?",
      icon: "warning",
      showCancelButton: true,
      confirmButtonText: "Yes, delete",
      confirmButtonColor: "#d33"
    }).then(res => {
      if(res.isConfirmed){
        $.post("../../ajax/ajax_payment_actions.php",{action:"deleteStatus",id},function(res){
          Swal.fire({icon: res.success ? "success" : "error", title: res.message}).then(() => location.reload());
        },"json");
      }
    });
  });
});
</script>
</body>
</html>