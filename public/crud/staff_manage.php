<?php
// staff_manage.php
// Single-file CRUD with center modals + AJAX (uses classes/Database.php)

// TODO: enable session-role protection in production
session_start();
 if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
     header('Location: ../../index.php');
     exit;
 }

require_once __DIR__ . '/../../classes/Database.php';
$db = new Database();
$conn = $db->connect();

// helper to escape for HTML
function esc($s) {
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

// ---------- AJAX endpoints ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    header('Content-Type: application/json; charset=utf-8');

    // ADD
    if ($action === 'add') {
        $first = trim($_POST['first'] ?? '');
        $middle = trim($_POST['middle'] ?? '');
        $last = trim($_POST['last'] ?? '');
        $contact = trim($_POST['contact'] ?? '');
        $email = trim($_POST['email'] ?? '');

        // server-side validation
        $errors = [];
        if ($first === '') $errors[] = 'First name is required.';
        if ($last === '') $errors[] = 'Last name is required.';
        if ($contact === '') $errors[] = 'Contact number is required.';
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';

        if (!empty($errors)) {
            echo json_encode(['success' => false, 'errors' => $errors]);
            exit;
        }

        try {
            $sql = "INSERT INTO staff (STAFF_FIRST_NAME, STAFF_MIDDLE_INIT, STAFF_LAST_NAME, STAFF_CONTACT_NUM, STAFF_EMAIL, STAFF_CREATED_AT, STAFF_UPDATED_AT)
                    VALUES (:first, :middle, :last, :contact, :email, NOW(), NOW())";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':first' => $first,
                ':middle' => $middle,
                ':last' => $last,
                ':contact' => $contact,
                ':email' => $email
            ]);
            echo json_encode(['success' => true, 'message' => 'Staff added successfully.']);
        } catch (PDOException $e) {
            // duplicate email or other DB error
            echo json_encode(['success' => false, 'errors' => ['Database error: '.$e->getMessage()]]);
        }
        exit;
    }

    // FETCH (return HTML rows for tbody)
    if ($action === 'fetch') {
        $search = trim($_POST['q'] ?? '');
        if ($search !== '') {
            $q = '%' . $search . '%';
            $sql = "SELECT * FROM staff
                    WHERE (CONCAT(STAFF_FIRST_NAME, ' ', COALESCE(STAFF_MIDDLE_INIT,''), ' ', STAFF_LAST_NAME) LIKE :q
                           OR STAFF_CONTACT_NUM LIKE :q
                           OR STAFF_EMAIL LIKE :q)
                    ORDER BY STAFF_LAST_NAME ASC, STAFF_FIRST_NAME ASC";
            $stmt = $conn->prepare($sql);
            $stmt->execute([':q' => $q]);
        } else {
            $sql = "SELECT * FROM staff ORDER BY STAFF_LAST_NAME ASC, STAFF_FIRST_NAME ASC";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
        }

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // build HTML
        ob_start();
        if (count($rows) === 0) {
            ?>
            <tr>
              <td colspan="7" style="text-align:center;">No records found...</td>
            </tr>
            <?php
        } else {
            foreach ($rows as $row) {
                $mid = trim($row['STAFF_MIDDLE_INIT']);
                $middle = $mid !== '' ? esc($mid) . '.' : '';
                $fullname = esc($row['STAFF_FIRST_NAME']) . ' ' . $middle . ' ' . esc($row['STAFF_LAST_NAME']);
                ?>
                <tr data-id="<?php echo esc($row['STAFF_ID']); ?>">
                  <td><?php echo esc($row['STAFF_ID']); ?></td>
                  <td><?php echo $fullname; ?></td>
                  <td><?php echo esc($row['STAFF_CONTACT_NUM']); ?></td>
                  <td><?php echo esc($row['STAFF_EMAIL']); ?></td>
                  <td><?php echo esc($row['STAFF_CREATED_AT']); ?></td>
                  <td><?php echo esc($row['STAFF_UPDATED_AT']); ?></td>
                  <td>
                    <button class="action-btn edit-btn" data-id="<?php echo esc($row['STAFF_ID']); ?>">Edit</button>
                    <button class="action-btn delete-btn" data-id="<?php echo esc($row['STAFF_ID']); ?>">Delete</button>
                  </td>
                </tr>
                <?php
            }
        }

        $html = ob_get_clean();
        echo json_encode(['success' => true, 'html' => $html]);
        exit;
    }

    // GET single row (for edit) - returns JSON with row data
    if ($action === 'get') {
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid ID']);
            exit;
        }
        $sql = "SELECT * FROM staff WHERE STAFF_ID = :id LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            echo json_encode(['success' => false, 'error' => 'Not found']);
            exit;
        }
        echo json_encode(['success' => true, 'data' => $row]);
        exit;
    }

    // UPDATE
    if ($action === 'update') {
        $id = intval($_POST['id'] ?? 0);
        $first = trim($_POST['first'] ?? '');
        $middle = trim($_POST['middle'] ?? '');
        $last = trim($_POST['last'] ?? '');
        $contact = trim($_POST['contact'] ?? '');
        $email = trim($_POST['email'] ?? '');

        $errors = [];
        if ($id <= 0) $errors[] = 'Invalid ID';
        if ($first === '') $errors[] = 'First name is required.';
        if ($last === '') $errors[] = 'Last name is required.';
        if ($contact === '') $errors[] = 'Contact number is required.';
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';

        if (!empty($errors)) {
            echo json_encode(['success' => false, 'errors' => $errors]);
            exit;
        }

        try {
            $sql = "UPDATE staff SET
                        STAFF_FIRST_NAME = :first,
                        STAFF_MIDDLE_INIT = :middle,
                        STAFF_LAST_NAME = :last,
                        STAFF_CONTACT_NUM = :contact,
                        STAFF_EMAIL = :email,
                        STAFF_UPDATED_AT = NOW()
                    WHERE STAFF_ID = :id";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':first' => $first,
                ':middle' => $middle,
                ':last' => $last,
                ':contact' => $contact,
                ':email' => $email,
                ':id' => $id
            ]);
            echo json_encode(['success' => true, 'message' => 'Staff updated successfully.']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'errors' => ['Database error: '.$e->getMessage()]]);
        }
        exit;
    }

    // DELETE
    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid ID']);
            exit;
        }
        try {
            $sql = "DELETE FROM staff WHERE STAFF_ID = :id";
            $stmt = $conn->prepare($sql);
            $stmt->execute([':id' => $id]);
            echo json_encode(['success' => true, 'message' => 'Staff deleted.']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => 'Database error: '.$e->getMessage()]);
        }
        exit;
    }

    // unknown action
    echo json_encode(['success' => false, 'error' => 'Unknown action']);
    exit;
}
// ---------- end AJAX endpoints ----------

// If not AJAX POST, render page normally (GET)
$initialSearch = trim($_GET['q'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Staff Management</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<style>
/* THEME CSS (same as your other pages) */
:root {
  --primary: #002339;
  --secondary: #6da9c6;
  --light: #d0edf5;
  --white: #fff;
}
* { margin: 0; padding: 0; box-sizing: border-box; }
body {
  font-family: Georgia, serif;
  background: var(--secondary);
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}
.navbar {
  background: var(--primary);
  padding: 20px 50px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-radius: 0 0 35px 35px;
}
.navbar-brand {
  color: var(--white);
  font-size: 28px;
  font-weight: bold;
  display: flex;
  align-items: center;
}
.navbar-brand img {
  width: 45px;
  margin-right: 10px;
}
.nav-links {
  display: flex;
  margin-left: auto;
  gap: 18px;
}
.nav-links a {
  color: var(--white);
  text-decoration: none;
  font-size: 15px;
  font-weight: bold;
  padding: 6px 14px;
  border-radius: 30px;
  transition: .3s ease;
}
.nav-links a:hover, .nav-links a.active {
  background: var(--light);
  color: var(--primary);
}
main {
  flex: 1;
  padding: 40px 60px;
}
.page-title {
  font-size: 32px;
  font-weight: bold;
  margin-bottom: 20px;
}
.top-controls {
  display: flex;
  justify-content: space-between;
  margin-bottom: 20px;
  gap: 10px;
  align-items: center;
}
.search-box input {
  padding: 8px 15px;
  border-radius: 20px;
  border: none;
  width: 320px;
  max-width: 60vw;
}
.btn-add {
  background: var(--primary);
  color: var(--white);
  padding: 10px 22px;
  border: none;
  border-radius: 25px;
  font-weight: bold;
  cursor: pointer;
  transition: .3s;
  text-decoration: none;
  display: inline-block;
}
.btn-add:hover {
  background: var(--light);
  color: var(--primary);
}
.table-container {
  background: var(--white);
  padding: 20px;
  border-radius: 20px;
  overflow-x: auto;
}
table {
  width: 100%;
  border-collapse: collapse;
  min-width: 800px;
}
th, td {
  padding: 12px;
  text-align: left;
  border-bottom: 2px solid var(--secondary);
}
th {
  background: var(--primary);
  color: var(--white);
}
.action-btn {
  padding: 6px 12px;
  background: var(--primary);
  color: var(--white);
  border-radius: 20px;
  text-decoration: none;
  font-size: 12px;
  margin-right: 6px;
  display: inline-block;
}
.action-btn:hover {
  background: var(--secondary);
}
.delete-btn {
  background: #b30000;
}
.delete-btn:hover { background: #8b0000; }

.footer {
  background: var(--primary);
  color: var(--white);
  text-align: center;
  padding: 20px 0;
  font-size: 14px;
}

/* ---------- MODAL styles (center) ---------- */
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.45);
  display: none;
  align-items: center;
  justify-content: center;
  z-index: 999;
}
.modal {
  background: #fff;
  width: 520px;
  max-width: calc(100% - 40px);
  border-radius: 12px;
  padding: 20px;
  box-shadow: 0 20px 50px rgba(0,0,0,0.2);
}
.modal h3 { margin-bottom: 10px; color: var(--primary); }
.form-row { margin-bottom: 12px; }
.form-row label { display:block; font-weight:700; margin-bottom:6px; }
.form-row input { width:100%; padding:10px 12px; border-radius:8px; border:1px solid #ccc; }

/* modal footer buttons */
.modal-footer { display:flex; justify-content:flex-end; gap:10px; margin-top:12px; }
.btn-outline { background: transparent; border: 1px solid #ccc; padding:8px 14px; border-radius:8px; cursor:pointer; }
.btn-primary { background: var(--primary); color:#fff; border:none; padding:8px 14px; border-radius:8px; cursor:pointer; }

/* small error box */
.form-errors { background:#ffe6e6; color:#700; padding:8px 10px; border-radius:6px; margin-bottom:10px; display:none; }
.success-msg { background:#e6ffed; color:#0a6a2f; padding:8px 10px; border-radius:6px; margin-bottom:10px; display:none; }

@media(max-width:480px) {
  .modal { width: 92%; padding:14px; }
}
</style>
</head>
<body>

<!-- NAV -->
<div class="navbar">
    <div class="navbar-brand">
      <img src="https://cdn-icons-png.flaticon.com/512/3209/3209999.png" alt="">
      Medicina
    </div>
    <div class="nav-links">
      <a href="/Booking-System-For-Medical-Clinics/public/staff_dashboard.php">Home</a>
      <a class="active" href="#">Staff</a>
      <a href="services.php">Services</a>
      <a href="status.php">Status</a>
      <a href="payments.php">Payments</a>
      <a href="specialization.php">Specialization</a>
      <a href="medical_records.php">Medical Records</a>
      <a href="/Booking-System-For-Medical-Clinics/index.php">Log out</a>
    </div>
</div>

<main>
  <div class="page-title">Staff Management</div>

  <div class="top-controls">
    <div style="display:flex; gap:8px; align-items:center;">
      <input id="searchInput" class="search-box" type="text" placeholder="Search name, contact or email..." value="<?php echo esc($initialSearch); ?>">
      <button id="btnSearch" class="btn-add">Search</button>
      <button id="btnReset" class="btn-add" style="background:#888">Reset</button>
    </div>

    <div>
      <button id="btnAdd" class="btn-add">+ Add Staff</button>
    </div>
  </div>

  <div class="table-container">
    <table>
      <thead>
        <tr>
          <th>Staff ID</th>
          <th>Full Name</th>
          <th>Contact</th>
          <th>Email</th>
          <th>Created At</th>
          <th>Updated At</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody id="tableBody">
        <!-- rows loaded by AJAX -->
        <tr><td colspan="7" style="text-align:center;">Loading...</td></tr>
      </tbody>
    </table>
  </div>

</main>

<div class="footer">
  &copy; 2025 Medicina Clinic | All Rights Reserved
</div>

<!-- ========== MODAL ========== -->
<div id="modalBackdrop" class="modal-backdrop" role="dialog" aria-hidden="true">
  <div class="modal" role="document" aria-labelledby="modalTitle">
    <h3 id="modalTitle">Add Staff</h3>

    <div id="formErrors" class="form-errors"></div>
    <div id="formSuccess" class="success-msg"></div>

    <div class="form-row">
      <label for="first">First Name</label>
      <input id="first" name="first" type="text" placeholder="First name">
    </div>
    <div class="form-row">
      <label for="middle">Middle Init</label>
      <input id="middle" name="middle" type="text" placeholder="Middle initial (optional)">
    </div>
    <div class="form-row">
      <label for="last">Last Name</label>
      <input id="last" name="last" type="text" placeholder="Last name">
    </div>
    <div class="form-row">
      <label for="contact">Contact Number</label>
      <input id="contact" name="contact" type="text" placeholder="09XXXXXXXXX">
    </div>
    <div class="form-row">
      <label for="email">Email</label>
      <input id="email" name="email" type="email" placeholder="email@example.com">
    </div>

    <div class="modal-footer">
      <button id="modalCancel" class="btn-outline">Cancel</button>
      <button id="modalSave" class="btn-primary">Save</button>
    </div>
  </div>
</div>

<script>
// Helper: show notification inside modal
function showErrors(arr) {
  const box = document.getElementById('formErrors');
  if (!Array.isArray(arr)) arr = [arr];
  box.innerHTML = arr.map(x => '<div>'+x+'</div>').join('');
  box.style.display = 'block';
  document.getElementById('formSuccess').style.display = 'none';
}
function showSuccess(msg) {
  const s = document.getElementById('formSuccess');
  s.innerHTML = msg;
  s.style.display = 'block';
  document.getElementById('formErrors').style.display = 'none';
}

// modal logic
const modalBackdrop = document.getElementById('modalBackdrop');
const modalTitle = document.getElementById('modalTitle');
const btnAdd = document.getElementById('btnAdd');
const modalSave = document.getElementById('modalSave');
const modalCancel = document.getElementById('modalCancel');
let editingId = 0; // 0 = add mode

function openModal(mode='add', data=null) {
  editingId = 0;
  document.getElementById('formErrors').style.display = 'none';
  document.getElementById('formSuccess').style.display = 'none';

  if (mode === 'add') {
    modalTitle.textContent = 'Add Staff';
    document.getElementById('first').value = '';
    document.getElementById('middle').value = '';
    document.getElementById('last').value = '';
    document.getElementById('contact').value = '';
    document.getElementById('email').value = '';
    editingId = 0;
  } else if (mode === 'edit' && data) {
    modalTitle.textContent = 'Edit Staff';
    document.getElementById('first').value = data.STAFF_FIRST_NAME || '';
    document.getElementById('middle').value = data.STAFF_MIDDLE_INIT || '';
    document.getElementById('last').value = data.STAFF_LAST_NAME || '';
    document.getElementById('contact').value = data.STAFF_CONTACT_NUM || '';
    document.getElementById('email').value = data.STAFF_EMAIL || '';
    editingId = parseInt(data.STAFF_ID, 10) || 0;
  }

  modalBackdrop.style.display = 'flex';
  modalBackdrop.setAttribute('aria-hidden', 'false');
  document.getElementById('first').focus();
}

function closeModal() {
  modalBackdrop.style.display = 'none';
  modalBackdrop.setAttribute('aria-hidden', 'true');
  editingId = 0;
}

// open add modal
btnAdd.addEventListener('click', function(){
  openModal('add');
});

// cancel modal
modalCancel.addEventListener('click', function(){ closeModal(); });

// clicking outside modal closes
modalBackdrop.addEventListener('click', function(e){
  if (e.target === modalBackdrop) closeModal();
});

// Save (Add or Update)
modalSave.addEventListener('click', function(){
  const first = document.getElementById('first').value.trim();
  const middle = document.getElementById('middle').value.trim();
  const last = document.getElementById('last').value.trim();
  const contact = document.getElementById('contact').value.trim();
  const email = document.getElementById('email').value.trim();

  // client-side validation
  const errs = [];
  if (!first) errs.push('First name is required.');
  if (!last) errs.push('Last name is required.');
  if (!contact) errs.push('Contact number is required.');
  if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) errs.push('Valid email is required.');

  if (errs.length) {
    showErrors(errs);
    return;
  }

  // choose action
  let action = editingId === 0 ? 'add' : 'update';
  const payload = new URLSearchParams();
  payload.append('action', action);
  if (editingId !== 0) payload.append('id', editingId);
  payload.append('first', first);
  payload.append('middle', middle);
  payload.append('last', last);
  payload.append('contact', contact);
  payload.append('email', email);

  modalSave.disabled = true;
  modalSave.textContent = 'Saving...';

  fetch(location.href, {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: payload.toString()
  })
  .then(r => r.json())
  .then(data => {
    modalSave.disabled = false;
    modalSave.textContent = 'Save';
    if (!data.success) {
      showErrors(data.errors || data.error || 'An error occurred');
    } else {
      // success: close modal, refresh table
      showSuccess(data.message || 'Saved');
      setTimeout(()=> {
        closeModal();
        loadTable(); // refresh
      }, 600); // small delay so user sees success briefly
    }
  })
  .catch(err => {
    modalSave.disabled = false;
    modalSave.textContent = 'Save';
    showErrors('Network error');
    console.error(err);
  });
});

// load table via AJAX (fetch action=fetch)
function loadTable(q='') {
  const payload = new URLSearchParams();
  payload.append('action','fetch');
  if (q) payload.append('q', q);

  const tbody = document.getElementById('tableBody');
  tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;">Loading...</td></tr>';

  fetch(location.href, {
    method:'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: payload.toString()
  })
  .then(r => r.json())
  .then(resp => {
    if (resp.success) {
      tbody.innerHTML = resp.html;
      attachRowHandlers(); // add event listeners for newly added buttons
    } else {
      tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;">Failed to load</td></tr>';
    }
  })
  .catch(e => {
    tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;">Network error</td></tr>';
    console.error(e);
  });
}

// attach event listeners for edit / delete buttons in table
function attachRowHandlers() {
  document.querySelectorAll('.edit-btn').forEach(btn => {
    btn.onclick = function(){
      const id = this.getAttribute('data-id');
      // fetch row data
      const payload = new URLSearchParams();
      payload.append('action','get');
      payload.append('id', id);
      fetch(location.href, {
        method:'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: payload.toString()
      })
      .then(r => r.json())
      .then(resp => {
        if (resp.success) {
          openModal('edit', resp.data);
        } else {
          alert(resp.error || 'Failed to fetch row');
        }
      }).catch(e => { alert('Network error'); console.error(e); });
    };
  });

  document.querySelectorAll('.delete-btn').forEach(btn => {
    btn.onclick = function(){
      const id = this.getAttribute('data-id');
      if (!confirm('Delete this staff record? This cannot be undone.')) return;
      const payload = new URLSearchParams();
      payload.append('action','delete');
      payload.append('id', id);
      fetch(location.href, {
        method:'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: payload.toString()
      })
      .then(r => r.json())
      .then(resp => {
        if (resp.success) {
          loadTable(); // refresh
        } else {
          alert(resp.error || 'Failed to delete');
        }
      }).catch(e => { alert('Network error'); console.error(e); });
    };
  });
}

// initial load (use initialSearch provided by PHP)
document.addEventListener('DOMContentLoaded', function(){
  loadTable('<?php echo esc($initialSearch); ?>');

  // search handlers
  document.getElementById('btnSearch').addEventListener('click', function(){
    const q = document.getElementById('searchInput').value.trim();
    loadTable(q);
  });
  document.getElementById('btnReset').addEventListener('click', function(){
    document.getElementById('searchInput').value = '';
    loadTable('');
  });

  // press enter in search input
  document.getElementById('searchInput').addEventListener('keydown', function(e){
    if (e.key === 'Enter') {
      e.preventDefault();
      document.getElementById('btnSearch').click();
    }
  });
});
</script>

</body>
</html>
