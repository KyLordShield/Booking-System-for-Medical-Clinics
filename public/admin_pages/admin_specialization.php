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

require_once dirname(__DIR__, 2) . "/config/Database.php";
$db = new Database();
$conn = $db->connect();

// AJAX: Add or update specialization
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json');
    
    $specName = trim($_POST['SPEC_NAME'] ?? '');
    $specId = $_POST['SPEC_ID'] ?? '';

    if (empty($specName)) {
        echo json_encode(['success' => false, 'message' => 'Specialization name is required']);
        exit;
    }

    try {
        if ($specId == "") {
            // Check for duplicate
            $check = $conn->prepare("SELECT SPEC_ID FROM specialization WHERE SPEC_NAME = ?");
            $check->execute([$specName]);
            if ($check->fetch()) {
                echo json_encode(['success' => false, 'message' => 'This specialization already exists!']);
                exit;
            }

            $stmt = $conn->prepare("INSERT INTO specialization (SPEC_NAME, SPEC_CREATED_AT, SPEC_UPDATED_AT)
                                    VALUES (?, NOW(), NOW())");
            $stmt->execute([$specName]);
            echo json_encode(['success' => true, 'message' => 'Specialization added successfully!']);
        } else {
            // Check for duplicate (excluding current)
            $check = $conn->prepare("SELECT SPEC_ID FROM specialization WHERE SPEC_NAME = ? AND SPEC_ID != ?");
            $check->execute([$specName, $specId]);
            if ($check->fetch()) {
                echo json_encode(['success' => false, 'message' => 'This specialization already exists!']);
                exit;
            }

            $stmt = $conn->prepare("UPDATE specialization
                                    SET SPEC_NAME=?, SPEC_UPDATED_AT=NOW()
                                    WHERE SPEC_ID=?");
            $stmt->execute([$specName, $specId]);
            echo json_encode(['success' => true, 'message' => 'Specialization updated successfully!']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// AJAX: Delete specialization
if (isset($_GET['delete']) && isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json');
    $id = intval($_GET['delete']);
    
    try {
        // Check if there are doctors with this specialization
        $check = $conn->prepare("SELECT COUNT(*) FROM doctor WHERE SPEC_ID = ?");
        $check->execute([$id]);
        $count = $check->fetchColumn();
        
        if ($count > 0) {
            echo json_encode([
                'success' => false, 
                'message' => "Cannot delete! There are {$count} doctor(s) with this specialization."
            ]);
            exit;
        }

        $stmt = $conn->prepare("DELETE FROM specialization WHERE SPEC_ID=?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'Specialization deleted successfully!']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error deleting specialization']);
    }
    exit;
}

// Fetch all specialization
$search = trim($_GET['q'] ?? '');
$sql = "SELECT * FROM specialization WHERE SPEC_NAME LIKE ? ORDER BY SPEC_ID ASC";
$stmt = $conn->prepare($sql);
$stmt->execute(["%$search%"]);
$specializations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch doctors by spec for modal
if (isset($_GET['browse'])) {
    $specId = intval($_GET['browse']);
    $stmt = $conn->prepare("SELECT DOC_FIRST_NAME, DOC_MIDDLE_INIT, DOC_LAST_NAME, DOC_EMAIL 
                            FROM doctor WHERE SPEC_ID = ?");
    $stmt->execute([$specId]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

function esc($v) {
    return htmlspecialchars($v ?? '', ENT_QUOTES);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <title>Specialization | Admin Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />

  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="../../assets/css/style.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    .modal { display: none; justify-content: center; align-items: center; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,.5); z-index: 1000; }
    .modal-content { background: #fff; padding: 20px; border-radius: 8px; max-width: 500px; width: 90%; position: relative; max-height: 90vh; overflow-y: auto; }
    .close-btn { position: absolute; top: 8px; right: 12px; font-size: 24px; cursor: pointer; }
  </style>
</head>

<body class="bg-[var(--secondary)] min-h-screen flex flex-col font-[Georgia]">

  <?php include dirname(__DIR__, 2) . "/partials/header.php"; ?>

  <main class="px-10 py-10 flex-1">
    <h2 class="text-[36px] font-bold text-[var(--primary)] mb-6">Specialization Management</h2>

    <!-- Search + Add button -->
    <div class="flex justify-between mb-5">
      <form method="get" class="flex items-center gap-2">
        <input type="text" name="q" placeholder="Search specialization..."
          value="<?= esc($search) ?>"
          class="px-4 py-2 rounded-full w-60 border focus:outline-none" />

        <button class="bg-[var(--primary)] text-white px-5 py-2 rounded-full hover:opacity-90 transition">
          Search
        </button>

        <?php if ($search !== ""): ?>
        <a href="specialization.php"
          class="bg-gray-400 text-white px-5 py-2 rounded-full hover:opacity-80 transition">
          Reset
        </a>
        <?php endif; ?>
      </form>

      <button onclick="openAddModal()"
        class="bg-[var(--primary)] text-white px-6 py-2 rounded-full hover:opacity-90">
        + Add Specialization
      </button>
    </div>

    <!-- Table -->
    <div class="bg-[var(--light)] p-6 rounded-[25px] shadow-md">
      <table class="w-full">
        <thead>
          <tr class="border-b">
            <th class="py-3 px-4">ID</th>
            <th class="py-3 px-4">Specialization</th>
            <th class="py-3 px-4">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$specializations): ?>
          <tr>
            <td colspan="3" class="text-center py-4 opacity-60">No data found</td>
          </tr>
          <?php endif; ?>

          <?php foreach ($specializations as $s): ?>
          <tr class="border-b hover:bg-gray-50">
            <td class="py-3 px-4"><?= esc($s['SPEC_ID']) ?></td>
            <td class="py-3 px-4"><?= esc($s['SPEC_NAME']) ?></td>
            <td class="py-3 px-4">

              <button class="btn bg-[var(--primary)] text-white px-6 py-2 rounded-full ml-2"
                onclick='openEditModal(<?= json_encode($s) ?>)'>Edit</button>

              <button class="btn bg-[var(--primary)] text-white px-6 py-2 rounded-full ml-2"
                onclick="browseDoctors(<?= $s['SPEC_ID'] ?>, '<?= esc($s['SPEC_NAME']) ?>')">
                Browse Doctors
              </button>

              <button class="btn bg-red-600 text-white px-6 py-2 rounded-full ml-2"
                onclick="deleteSpec(<?= $s['SPEC_ID'] ?>)">Delete</button>

            </td>
          </tr>
          <?php endforeach ?>
        </tbody>
      </table>
    </div>
  </main>

  <?php include dirname(__DIR__, 2) . "/partials/footer.php"; ?>

  <!-- Modal: Add/Edit Specialization -->
  <div id="specModal" class="modal">
    <div class="modal-content p-6">
      <span class="close-btn" onclick="closeModal()">&times;</span>
      <h2 id="modalTitle" class="text-xl font-bold mb-4">Add Specialization</h2>

      <form id="specForm">
        <input type="hidden" id="SPEC_ID" name="SPEC_ID">
        <label class="block mb-1 font-semibold">Specialization Name</label>
        <input id="SPEC_NAME" name="SPEC_NAME" required
          class="border rounded-lg px-4 py-2 w-full mb-4">

        <button type="submit" class="bg-[var(--primary)] text-white px-6 py-2 rounded-full">Save</button>
      </form>
    </div>
  </div>

  <!-- Modal: Doctors -->
  <div id="doctorModal" class="modal">
    <div class="modal-content p-6 max-w-3xl w-full">
      <span class="close-btn" onclick="closeDoctorsModal()">&times;</span>
      <h2 id="doctorModalTitle" class="text-xl font-bold mb-4"></h2>

      <table class="w-full border-collapse text-[var(--primary)]">
        <thead>
          <tr class="border-b">
            <th class="py-2 text-left">Name</th>
            <th class="py-2 text-left">Email</th>
          </tr>
        </thead>
        <tbody id="doctorList"></tbody>
      </table>
    </div>
  </div>

  <script>
    function openAddModal() {
      document.getElementById("SPEC_ID").value = "";
      document.getElementById("SPEC_NAME").value = "";
      document.getElementById("modalTitle").innerText = "Add Specialization";
      document.getElementById("specModal").style.display = "flex";
    }

    function openEditModal(data) {
      document.getElementById("SPEC_ID").value = data.SPEC_ID;
      document.getElementById("SPEC_NAME").value = data.SPEC_NAME;
      document.getElementById("modalTitle").innerText = "Edit Specialization";
      document.getElementById("specModal").style.display = "flex";
    }

    function closeModal() {
      document.getElementById("specModal").style.display = "none";
    }

    // Form submit with AJAX
    document.getElementById('specForm').addEventListener('submit', async function(e) {
      e.preventDefault();
      
      try {
        const formData = new FormData(this);
        const res = await fetch(location.pathname, {
          method: 'POST',
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
          body: formData
        });
        const json = await res.json();

        Swal.fire({
          icon: json.success ? 'success' : 'error',
          title: json.success ? 'Success' : 'Error',
          text: json.message
        }).then(() => {
          if (json.success) {
            closeModal();
            location.reload();
          }
        });
      } catch (error) {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'An error occurred: ' + error.message
        });
      }
    });

    // Delete with confirmation
    async function deleteSpec(id) {
      const result = await Swal.fire({
        title: 'Are you sure?',
        text: "Delete this specialization?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
      });

      if (!result.isConfirmed) return;

      try {
        const res = await fetch(`?delete=${id}`, {
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const json = await res.json();

        Swal.fire({
          icon: json.success ? 'success' : 'error',
          title: json.success ? 'Deleted!' : 'Error',
          text: json.message
        }).then(() => {
          if (json.success) location.reload();
        });
      } catch (error) {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'Network error occurred'
        });
      }
    }

    // Browse doctors modal
    async function browseDoctors(specId, specName) {
      const res = await fetch(`?browse=${specId}`);
      const doctors = await res.json();

      document.getElementById("doctorModalTitle").innerText =
        "Doctors: " + specName;

      let html = "";
      if (doctors.length === 0) {
        html = `<tr><td colspan="2" class='text-center py-3 opacity-70'>No doctors found</td></tr>`;
      } else {
        doctors.forEach(d => {
          const middleInit = d.DOC_MIDDLE_INIT ? d.DOC_MIDDLE_INIT + '. ' : '';
          html += `<tr class='border-b hover:bg-gray-50'>
                    <td class='py-2'>${d.DOC_FIRST_NAME} ${middleInit}${d.DOC_LAST_NAME}</td>
                    <td class='py-2'>${d.DOC_EMAIL}</td>
                   </tr>`;
        });
      }

      document.getElementById("doctorList").innerHTML = html;
      document.getElementById("doctorModal").style.display = "flex";
    }

    function closeDoctorsModal() {
      document.getElementById("doctorModal").style.display = "none";
    }

    // Close modal on backdrop click
    document.getElementById('specModal').addEventListener('click', function(e) {
      if (e.target === this) closeModal();
    });
    
    document.getElementById('doctorModal').addEventListener('click', function(e) {
      if (e.target === this) closeDoctorsModal();
    });
  </script>

</body>

</html>