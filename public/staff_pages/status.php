<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../index.php");
    exit;
}
require_once __DIR__ . '/../../classes/Status.php';
$status = new Status();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $name = trim($_POST['STAT_NAME'] ?? '');
    $id = $_POST['STAT_ID'] ?? null;

    if ($action === 'add' && $name !== '') {
        $status->create($name);
        exit('success');
    }

    if ($action === 'edit' && $id && $name !== '') {
        $status->update($id, $name);
        exit('success');
    }

    if ($action === 'delete' && $id) {
        $status->delete($id);
        exit('success');
    }

    exit('error');
}

$statuses = $status->getAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Status Management | Staff Dashboard</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<!-- ✅ Tailwind CSS -->
<script src="https://cdn.tailwindcss.com"></script>

<!-- ✅ Custom CSS -->
<link rel="stylesheet" href="/Booking-System-For-Medical-Clinics/assets/css/style.css">
</head>

<body class="bg-[var(--secondary)] min-h-screen flex flex-col font-[Georgia]">

<!-- ✅ NAVBAR -->
<div class="navbar flex justify-between items-center px-10 py-5 bg-[var(--primary)] rounded-b-[35px] shadow-lg">
  <div class="navbar-brand flex items-center text-white text-2xl font-bold">
    <img src="https://cdn-icons-png.flaticon.com/512/3209/3209999.png" alt="Medicina Logo" class="w-11 mr-3">
    Medicina
  </div>

  <div class="nav-links flex gap-4">
    <a href="/Booking-System-For-Medical-Clinics/public/staff_dashboard.php">Home</a>
    <a href="/Booking-System-For-Medical-Clinics/public/staff_pages/staff_manage.php">Staff</a>
    <a href="/Booking-System-For-Medical-Clinics/public/staff_pages/services.php">Services</a>
    <a class="active" href="#">Status</a>
    <a href="/Booking-System-For-Medical-Clinics/public/staff_pages/payments.php">Payments</a>
    <a href="/Booking-System-For-Medical-Clinics/public/staff_pages/specialization.php">Specialization</a>
    <a href="/Booking-System-For-Medical-Clinics/public/staff_pages/smedical_records.php">Medical Records</a>
    <a href="/Booking-System-For-Medical-Clinics/index.php">Log out</a>
  </div>
</div>

<!-- ✅ MAIN CONTENT -->
<main class="flex-1 px-10 py-10">
  <h2 class="text-[36px] font-bold text-[var(--primary)] mb-6">Appointment Status Management</h2>

  <!-- Search + Add -->
  <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
    <div class="search-box w-full sm:w-[300px]">
      <input type="text" id="searchInput" placeholder="Search status..."
             class="w-full px-4 py-2 rounded-full border-none focus:ring-2 focus:ring-[var(--primary)] outline-none text-[16px]">
    </div>
    <button id="openAddModal" class="create-btn">+ Add Status</button>
  </div>

  <!-- Table -->
  <div class="table-container bg-[var(--light)] p-6 rounded-[25px] shadow-md">
    <table class="w-full border-collapse text-[var(--primary)]">
      <thead>
        <tr class="border-b border-gray-300">
          <th class="py-3 px-4 text-left">Status ID</th>
          <th class="py-3 px-4 text-left">Status Name</th>
          <th class="py-3 px-4 text-left">Actions</th>
        </tr>
      </thead>
      <tbody id="statusTable">
        <?php if (count($statuses) > 0): ?>
          <?php foreach ($statuses as $row): ?>
          <tr data-id="<?= $row['STAT_ID'] ?>" class="border-b border-gray-300 hover:bg-gray-50">
            <td class="py-3 px-4"><?= htmlspecialchars($row['STAT_ID']) ?></td>
            <td class="py-3 px-4"><?= htmlspecialchars($row['STAT_NAME']) ?></td>
            <td class="py-3 px-4">
              <button class="btn-edit text-blue-600 font-semibold mr-3">Edit</button>
              <button class="btn-delete text-red-600 font-semibold">Delete</button>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="3" class="text-center text-gray-500 py-6">No status data found...</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</main>

<!-- ✅ FOOTER -->
<footer class="bg-[var(--primary)] text-[var(--white)] text-center py-4 rounded-t-[35px] text-sm mt-6">
  &copy; 2025 Medicina Clinic | All Rights Reserved
</footer>

<!-- ✅ MODAL -->
<div id="statusModal" class="modal">
  <div class="modal-content">
    <span class="close-btn" id="closeModal">&times;</span>
    <h2 id="modalTitle">Add New Status</h2>
    <form id="statusForm">
      <input type="hidden" name="action" value="add">
      <input type="hidden" name="STAT_ID" id="STAT_ID">
      <div class="col-span-2">
        <label>Status Name</label>
        <input type="text" name="STAT_NAME" id="STAT_NAME" required>
      </div>
      <button type="submit" id="saveBtn">Save</button>
    </form>
  </div>
</div>

<!-- ✅ JS -->
<script>
const modal = document.getElementById('statusModal');
const openBtn = document.getElementById('openAddModal');
const closeBtn = document.getElementById('closeModal');
const form = document.getElementById('statusForm');
const table = document.getElementById('statusTable');
const title = document.getElementById('modalTitle');
const saveBtn = document.getElementById('saveBtn');
const idField = document.getElementById('STAT_ID');
const nameField = document.getElementById('STAT_NAME');

// 🔹 Open Add Modal
openBtn.addEventListener('click', () => {
  form.reset();
  title.textContent = 'Add New Status';
  form.action.value = 'add';
  modal.style.display = 'flex';
});

// 🔹 Close Modal
closeBtn.addEventListener('click', () => modal.style.display = 'none');
window.onclick = e => { if (e.target === modal) modal.style.display = 'none'; };

// 🔹 Add/Edit Status
form.addEventListener('submit', e => {
  e.preventDefault();
  const formData = new FormData(form);
  fetch('status.php', { method: 'POST', body: formData })
    .then(res => res.text())
    .then(response => {
      if (response.trim() === 'success') location.reload();
      else alert('Error saving status.');
    });
});

// 🔹 Edit Button
table.addEventListener('click', e => {
  if (e.target.classList.contains('btn-edit')) {
    const row = e.target.closest('tr');
    const id = row.dataset.id;
    const name = row.cells[1].textContent.trim();
    idField.value = id;
    nameField.value = name;
    form.action.value = 'edit';
    title.textContent = 'Edit Status';
    modal.style.display = 'flex';
  }
});

// 🔹 Delete Button
table.addEventListener('click', e => {
  if (e.target.classList.contains('btn-delete')) {
    if (!confirm('Delete this status?')) return;
    const row = e.target.closest('tr');
    const id = row.dataset.id;
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('STAT_ID', id);
    fetch('status.php', { method: 'POST', body: formData })
      .then(res => res.text())
      .then(response => {
        if (response.trim() === 'success') row.remove();
        else alert('Failed to delete.');
      });
  }
});

// 🔍 Search Filter
document.getElementById('searchInput').addEventListener('keyup', function() {
  const filter = this.value.toLowerCase();
  document.querySelectorAll('#statusTable tr').forEach(row => {
    const name = row.cells[1]?.textContent.toLowerCase() || '';
    row.style.display = name.includes(filter) ? '' : 'none';
  });
});
</script>

</body>
</html>
