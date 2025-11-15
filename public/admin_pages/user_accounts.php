<?php
session_start();

// ✅ Restrict access to Admin only
if (
    empty($_SESSION['USER_IS_SUPERADMIN']) ||
    $_SESSION['USER_IS_SUPERADMIN'] != 1 ||
    $_SESSION['role'] !== 'admin'
) {
    header("Location: ../../index.php");
    exit;
}

// ✅ Include database + classes
require_once '../../classes/User.php';
require_once '../../classes/Patient.php';
require_once '../../classes/Doctor.php';
require_once '../../classes/Staff.php';

// ✅ Fetch all data with users
$userObj = new User();
$patientObj = new Patient();
$doctorObj = new Doctor();
$staffObj = new Staff();

$patients = $patientObj->getAllWithUsers(); 
$doctors = $doctorObj->getAllWithUsers(); 
$staffs = $staffObj->getAllWithUsers();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Users | Medicina Admin</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="/Booking-System-For-Medical-Clinics/assets/css/style.css">
</head>

<body class="font-serif bg-[var(--secondary)] flex flex-col min-h-screen">

<!-- NAVBAR -->
<!-- ✅ HEADER LINK -->
  <?php include dirname(__DIR__, 2) . "/partials/header.php"; ?>
<!-- ✅ HEADER LINK -->

<!-- ✅ MAIN -->
<main class="flex-1 p-16">
  <h1 class="text-center text-[var(--primary)] text-4xl font-bold mb-8">Manage Users</h1>

  <!-- ✅ Toggle Filter -->
  <<div class="text-right mb-5 flex items-center justify-end gap-4">
  <span class="text-sm font-medium">Show only entities with User accounts</span>

  <label class="switch">
    <input type="checkbox" id="filterUsersOnly" checked>
    <span class="slider round"></span>
  </label>

  <button class="create-btn" onclick="openModal()">+ Create User</button>
</div>


  <div class="tabs">
    <button class="tab-btn active" data-tab="patients" onclick="showTab('patients')">Patients</button>
    <button class="tab-btn" data-tab="doctors" onclick="showTab('doctors')">Doctors</button>
    <button class="tab-btn" data-tab="staff" onclick="showTab('staff')">Staff</button>
  </div>

  <!-- ✅ PATIENTS TABLE -->
  <div id="patients" class="table-container">
    <h2 style="color:#002339; margin-bottom:15px;">Patients List</h2>
    <table>
      <tr>
        <th>ID</th><th>Name</th><th>Email</th><th>Username</th><th>Password</th><th>Action</th>
      </tr>
      <?php foreach ($patients as $p): ?>
      <tr class="<?= empty($p['USER_ID']) ? 'no-user' : 'has-user' ?>">
        <td><?= htmlspecialchars($p['PAT_ID']) ?></td>
        <td><?= htmlspecialchars($p['PAT_FIRST_NAME'] . ' ' . $p['PAT_LAST_NAME']) ?></td>
        <td><?= htmlspecialchars($p['PAT_EMAIL'] ?? '—') ?></td>
        <td><?= htmlspecialchars($p['USER_NAME'] ?? '—') ?></td>
        <td>
          <span class="password-field" data-password="<?= htmlspecialchars($p['USER_PASSWORD'] ?? '') ?>">••••••</span>
          <?php if(!empty($p['USER_PASSWORD'])): ?>
          <button type="button" class="toggle-pwd">Show</button>
          <?php endif; ?>
        </td>
        <td>
          <button type="button" class="btn edit-btn" 
                  data-userid="<?= $p['USER_ID'] ?? '' ?>" 
                  data-username="<?= htmlspecialchars($p['USER_NAME'] ?? '') ?>" 
                  data-password="<?= htmlspecialchars($p['USER_PASSWORD'] ?? '') ?>" 
                  onclick="openEditModal(this)">Edit</button>

          <form method="POST" action="create_user.php" style="display:inline;" 
                onsubmit="return confirm('Are you sure you want to delete this user?');">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="user_id" value="<?= $p['USER_ID'] ?? '' ?>">
            <button type="submit" class="btn">Delete</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </table>
  </div>

  <!-- ✅ DOCTORS TABLE -->
  <div id="doctors" class="table-container hidden">
    <h2 style="color:#002339; margin-bottom:15px;">Doctors List</h2>
    <table>
      <tr>
        <th>ID</th><th>Name</th><th>Specialization</th><th>Email</th><th>Username</th><th>Password</th><th>Action</th>
      </tr>
      <?php foreach ($doctors as $d): ?>
      <tr class="<?= empty($d['USER_ID']) ? 'no-user' : 'has-user' ?>">
        <td><?= htmlspecialchars($d['DOC_ID']) ?></td>
        <td><?= htmlspecialchars($d['DOC_FIRST_NAME'] . ' ' . $d['DOC_LAST_NAME']) ?></td>
        <td><?= htmlspecialchars($d['SPEC_NAME'] ?? '—') ?></td>
        <td><?= htmlspecialchars($d['DOC_EMAIL'] ?? '—') ?></td>
        <td><?= htmlspecialchars($d['USER_NAME'] ?? '—') ?></td>
        <td>
          <span class="password-field" data-password="<?= htmlspecialchars($d['USER_PASSWORD'] ?? '') ?>">••••••</span>
          <?php if(!empty($d['USER_PASSWORD'])): ?>
          <button type="button" class="toggle-pwd">Show</button>
          <?php endif; ?>
        </td>
        <td>
          <button type="button" class="btn edit-btn" 
                  data-userid="<?= $d['USER_ID'] ?? '' ?>" 
                  data-username="<?= htmlspecialchars($d['USER_NAME'] ?? '') ?>" 
                  data-password="<?= htmlspecialchars($d['USER_PASSWORD'] ?? '') ?>" 
                  onclick="openEditModal(this)">Edit</button>

          <form method="POST" action="create_user.php" style="display:inline;" 
                onsubmit="return confirm('Are you sure you want to delete this user?');">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="user_id" value="<?= $d['USER_ID'] ?? '' ?>">
            <button type="submit" class="btn">Delete</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </table>
  </div>

  <!-- ✅ STAFF TABLE -->
  <div id="staff" class="table-container hidden">
    <h2 style="color:#002339; margin-bottom:15px;">Staff List</h2>
    <table>
      <tr>
        <th>ID</th><th>Name</th><th>Email</th><th>Username</th><th>Password</th><th>Action</th>
      </tr>
      <?php foreach ($staffs as $s): ?>
      <tr class="<?= empty($s['USER_ID']) ? 'no-user' : 'has-user' ?>">
        <td><?= htmlspecialchars($s['STAFF_ID']) ?></td>
        <td><?= htmlspecialchars($s['STAFF_FIRST_NAME'] . ' ' . $s['STAFF_LAST_NAME']) ?></td>
        <td><?= htmlspecialchars($s['STAFF_EMAIL'] ?? '—') ?></td>
        <td><?= htmlspecialchars($s['USER_NAME'] ?? '—') ?></td>
        <td>
          <span class="password-field" data-password="<?= htmlspecialchars($s['USER_PASSWORD'] ?? '') ?>">••••••</span>
          <?php if(!empty($s['USER_PASSWORD'])): ?>
          <button type="button" class="toggle-pwd">Show</button>
          <?php endif; ?>
        </td>
        <td>
          <button type="button" class="btn edit-btn" 
                  data-userid="<?= $s['USER_ID'] ?? '' ?>" 
                  data-username="<?= htmlspecialchars($s['USER_NAME'] ?? '') ?>" 
                  data-password="<?= htmlspecialchars($s['USER_PASSWORD'] ?? '') ?>" 
                  onclick="openEditModal(this)">Edit</button>

          <form method="POST" action="create_user.php" style="display:inline;" 
                onsubmit="return confirm('Are you sure you want to delete this user?');">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="user_id" value="<?= $s['USER_ID'] ?? '' ?>">
            <button type="submit" class="btn">Delete</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </table>
  </div>

  <!-- ✅ CREATE USER MODAL -->
  <div id="createModal" class="modal">
    <div class="modal-content">
      <span class="close-btn" onclick="closeModal()">&times;</span>
      <h2>Create User</h2>
      <form method="POST" action="create_user.php">
        <input type="hidden" name="action" value="create">
        <label>Role</label>
        <select name="role" id="role" onchange="loadEntities(this.value)" required>
          <option value="">--Select Role--</option>
          <option value="Patient">Patient</option>
          <option value="Doctor">Doctor</option>
          <option value="Staff">Staff</option>
        </select>

        <label>Select Entity</label>
        <select name="entity_id" id="entityDropdown" required>
          <option value="">--Select--</option>
        </select>

        <label>Username</label>
        <input type="text" name="username" required>
        <label>Password</label>
        <input type="password" name="password" required>
        <button type="submit">Create User</button>
      </form>
    </div>
  </div>

 <!-- EDIT USER MODAL -->
<div id="editModal" class="modal">
  <div class="modal-content">
    <span class="close-btn" onclick="closeEditModal()">&times;</span>
    <h2>Edit User</h2>
    <form method="POST" action="create_user.php" id="editUserForm">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="user_id" id="edit_user_id">
      
      <label>Username</label>
      <input type="text" name="username" id="edit_username" required>
      
      <label>Password</label>
      <input type="text" name="password" id="edit_password" required>
      
      <button type="submit">Update User</button>
    </form>
  </div>
</div>

</main>

<!-- ✅ JS -->
<script>
function showTab(tabName) {
  document.querySelectorAll('.table-container').forEach(c => c.style.display = 'none');
  document.querySelector('#' + tabName).style.display = 'block';
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  document.querySelector(`[data-tab="${tabName}"]`).classList.add('active');
}
window.onload = () => showTab('patients');

function openModal() { document.getElementById('createModal').style.display = 'flex'; }
function closeModal() { document.getElementById('createModal').style.display = 'none'; }

function openEditModal(btn) {
  const userId = btn.dataset.userid;
  const username = btn.dataset.username;
  const password = btn.dataset.password;

  if (!userId) {
    alert("Error: User ID is missing! Cannot edit.");
    return;
  }

  document.getElementById('edit_user_id').value = userId;
  document.getElementById('edit_username').value = username;
  document.getElementById('edit_password').value = password;

  document.getElementById('edit_password').type = 'text';
  document.getElementById('editModal').style.display = 'flex';
}

function closeEditModal() { document.getElementById('editModal').style.display = 'none'; }

function loadEntities(role) {
  const dropdown = document.getElementById('entityDropdown');
  dropdown.innerHTML = '<option>Loading...</option>';
  fetch(`/Booking-System-For-Medical-Clinics/ajax/fetch_entities.php?role=${role}`)
  .then(res => res.json())
  .then(data => {
    dropdown.innerHTML = '<option value="">--Select--</option>';
    data.forEach(item => dropdown.innerHTML += `<option value="${item.id}">${item.name}</option>`);
  })
  .catch(console.error);
}

document.querySelectorAll('.toggle-pwd').forEach(btn => {
  btn.addEventListener('click', () => {
    const span = btn.previousElementSibling;
    if (span.textContent === '••••••') {
      span.textContent = span.dataset.password;
      btn.textContent = 'Hide';
    } else {
      span.textContent = '••••••';
      btn.textContent = 'Show';
    }
  });
});

// ✅ Filter toggle
document.addEventListener("DOMContentLoaded", () => {
  const filterCheckbox = document.getElementById('filterUsersOnly');

  function applyFilter() {
    const showOnly = filterCheckbox.checked;

    document.querySelectorAll('.table-container table tr').forEach(row => {
      if (!row.querySelector('td')) return; // skip header row
      if (showOnly && row.classList.contains('no-user')) {
        row.style.display = 'none';
      } else {
        row.style.display = '';
      }
    });
  }

  // Listen to the checkbox change
  filterCheckbox.addEventListener('change', applyFilter);

  // Apply filter on page load
  applyFilter();
});

</script>


<!-- ✅ FOOTER LINK -->
  <?php include dirname(__DIR__, 2) . "/partials/footer.php"; ?>

</body>
</html>
