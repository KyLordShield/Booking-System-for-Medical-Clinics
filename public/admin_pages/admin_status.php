<?php
session_start();
// ---------- 1. AUTH CHECK ----------
if (
    empty($_SESSION['USER_IS_SUPERADMIN']) ||
    $_SESSION['USER_IS_SUPERADMIN'] != 1 ||
    $_SESSION['role'] !== 'admin'
) {
   header("Location: ../../index.php");
   exit;
}

require_once __DIR__ . '/../../classes/Status.php';
$status = new Status();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $name   = trim($_POST['STAT_NAME'] ?? '');
    $id     = $_POST['STAT_ID'] ?? null;

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

<!-- Tailwind CSS -->
<script src="https://cdn.tailwindcss.com"></script>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Custom CSS -->
<link rel="stylesheet" href="/Booking-System-For-Medical-Clinics/assets/css/style.css">
</head>

<body class="bg-[var(--secondary)] min-h-screen flex flex-col font-[Georgia]">

<!-- NAVBAR -->
<?php include dirname(__DIR__, 2) . "/partials/header.php"; ?>

<!-- MAIN CONTENT -->
<main class="flex-1 px-10 py-10">
  <h2 class="text-[36px] font-bold text-[var(--primary)] mb-6">Appointment Status Management</h2>

  <!-- Search + Add Button -->
  <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
    <div class="w-full sm:w-[300px]">
      <input type="text" id="searchInput" placeholder="Search status..."
             class="w-full px-4 py-2 rounded-full border-none focus:ring-2 focus:ring-[var(--primary)] outline-none text-[16px]">
    </div>
    <button id="openAddModal" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-full shadow-lg transition duration-200 transform hover:scale-105">
      + Add Status
    </button>
  </div>

  <!-- Table -->
  <div class="bg-[var(--light)] p-6 rounded-[25px] shadow-md">
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
          <tr data-id="<?= $row['STAT_ID'] ?>" class="border-b border-gray-300 hover:bg-gray-50 transition">
            <td class="py-3 px-4"><?= htmlspecialchars($row['STAT_ID']) ?></td>
            <td class="py-3 px-4"><?= htmlspecialchars($row['STAT_NAME']) ?></td>
            <td class="py-3 px-4">
              <button class="text-blue-600 font-semibold mr-4 hover:underline btn-edit">Edit</button>
              <button class="text-red-600 font-semibold hover:underline btn-delete">Delete</button>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="3" class="text-center text-gray-500 py-8">No status data found...</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</main>

<!-- FOOTER -->
<?php include dirname(__DIR__, 2) . "/partials/footer.php"; ?>

<!-- MODAL -->
<div id="statusModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
  <div class="bg-white rounded-2xl p-8 w-full max-w-md shadow-2xl relative">
    <span class="absolute top-4 right-6 text-3xl cursor-pointer text-gray-500 hover:text-gray-800" id="closeModal">&times;</span>
    <h2 id="modalTitle" class="text-2xl font-bold text-[var(--primary)] mb-6">Add New Status</h2>
    <form id="statusForm">
      <input type="hidden" name="action" value="add">
      <input type="hidden" name="STAT_ID" id="STAT_ID">

      <div class="mb-6">
        <label class="block text-gray-700 font-medium mb-2">Status Name</label>
        <input type="text" name="STAT_NAME" id="STAT_NAME" required
               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[var(--primary)] outline-none transition">
      </div>

      <div class="flex justify-end gap-3">
        <button type="button" id="cancelBtn" class="px-6 py-3 bg-gray-300 hover:bg-gray-400 text-gray-800 rounded-lg font-medium transition">
          Cancel
        </button>
        <button type="submit" class="px-8 py-3 bg-[var(--primary)] hover:bg-[var(--primary-dark)] text-white rounded-lg font-medium transition">
          Save Status
        </button>
      </div>
    </form>
  </div>
</div>

<!-- JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function () {
  const modal     = document.getElementById('statusModal');
  const openBtn   = document.getElementById('openAddModal');
  const closeBtn  = document.getElementById('closeModal');
  const cancelBtn = document.getElementById('cancelBtn');
  const form      = document.getElementById('statusForm');
  const title     = document.getElementById('modalTitle');
  const table     = document.getElementById('statusTable');

  // Open Modal (Add New)
  openBtn.addEventListener('click', () => {
    form.reset();
    title.textContent = 'Add New Status';
    form.action.value = 'add';
    document.getElementById('STAT_ID').value = '';
    modal.classList.remove('hidden');
  });

  // Close Modal
  const closeModal = () => modal.classList.add('hidden');
  closeBtn.addEventListener('click', closeModal);
  cancelBtn.addEventListener('click', closeModal);
  modal.addEventListener('click', e => {
    if (e.target === modal) closeModal();
  });

  // Save (Add or Edit)
  form.addEventListener('submit', async e => {
    e.preventDefault();

    Swal.fire({
      title: 'Saving...',
      allowOutsideClick: false,
      didOpen: () => Swal.showLoading()
    });

    const formData = new FormData(form);

    fetch('', { method: 'POST', body: formData })
      .then(r => r.text())
      .then(res => {
        if (res.trim() === 'success') {
          Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: 'Status saved successfully!',
            toast: true,
            position: 'top-end',
            timer: 3000,
            showConfirmButton: false
          }).then(() => location.reload());
        } else {
          Swal.fire('Error', 'Failed to save status.', 'error');
        }
      })
      .catch(() => Swal.fire('Error', 'Network error.', 'error'));
  });

  // Edit Button
  table.addEventListener('click', e => {
    if (!e.target.classList.contains('btn-edit')) return;
    const row = e.target.closest('tr');
    const id   = row.dataset.id;
    const name = row.cells[1].textContent.trim();

    document.getElementById('STAT_ID').value = id;
    document.getElementById('STAT_NAME').value = name;
    form.action.value = 'edit';
    title.textContent = 'Edit Status';
    modal.classList.remove('hidden');
  });

  // Delete Button
  table.addEventListener('click', async e => {
    if (!e.target.classList.contains('btn-delete')) return;

    const row  = e.target.closest('tr');
    const name = row.cells[1].textContent.trim();

    const result = await Swal.fire({
      title: 'Delete Status?',
      text: `"${name}" will be permanently deleted!`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',
      confirmButtonText: 'Yes, delete it!'
    });

    if (!result.isConfirmed) return;

    const fd = new FormData();
    fd.append('action', 'delete');
    fd.append('STAT_ID', row.dataset.id);

    Swal.fire({ title: 'Deleting...', didOpen: () => Swal.showLoading() });

    fetch('', { method: 'POST', body: fd })
      .then(r => r.text())
      .then(res => {
        if (res.trim() === 'success') {
          Swal.fire({ icon: 'success', title: 'Deleted!', toast: true, position: 'top-end', timer: 2000, showConfirmButton: false });
          row.remove();
        } else {
          Swal.fire('Error', 'Delete failed.', 'error');
        }
      });
  });

  // Search
  document.getElementById('searchInput').addEventListener('keyup', function () {
    const term = this.value.toLowerCase();
    table.querySelectorAll('tr').forEach(row => {
      const name = row.cells[1]?.textContent.toLowerCase() || '';
      row.style.display = name.includes(term) ? '' : 'none';
    });
  });
});
</script>

</body>
</html>