<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../index.php");
    exit;
}

require_once dirname(__DIR__, 2) . "/config/Database.php";
$db = new Database();
$conn = $db->connect();

// Add or update specialization
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $specName = trim($_POST['SPEC_NAME']);
    $specId = $_POST['SPEC_ID'] ?? '';

    if ($specId == "") {
        $stmt = $conn->prepare("INSERT INTO specialization (SPEC_NAME, SPEC_CREATED_AT, SPEC_UPDATED_AT)
                                VALUES (?, NOW(), NOW())");
        $stmt->execute([$specName]);
    } else {
        $stmt = $conn->prepare("UPDATE specialization
                                SET SPEC_NAME=?, SPEC_UPDATED_AT=NOW()
                                WHERE SPEC_ID=?");
        $stmt->execute([$specName, $specId]);
    }
    header("Location: specialization.php");
    exit;
}

// Delete specialization
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM specialization WHERE SPEC_ID=?");
    $stmt->execute([$id]);
    header("Location: specialization.php");
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
  <title>Specialization | Staff Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />

  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="/Booking-System-For-Medical-Clinics/assets/css/style.css">
</head>

<body class="bg-[var(--secondary)] min-h-screen flex flex-col font-[Georgia]">

  <!-- NAVBAR -->
<!-- ✅ HEADER LINK -->
  <?php include dirname(__DIR__, 2) . "/partials/header.php"; ?>

  <!-- ✅ MAIN -->
  <main class="px-10 py-10 flex-1">
    <h2 class="text-[36px] font-bold text-[var(--primary)] mb-6">Specialization Management</h2>

    <!-- ✅ Search + Add button -->
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

    <!-- ✅ Table -->
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

              <a href="?delete=<?= $s['SPEC_ID'] ?>"
                onclick="return confirm('Delete specialization?')"
                class="btn bg-[var(--primary)] text-white px-6 py-2 rounded-full ml-2">Delete</a>

            </td>
          </tr>
          <?php endforeach ?>
        </tbody>
      </table>
    </div>
  </main>
 
<!-- ✅ FOOTER LINK -->
  <?php include dirname(__DIR__, 2) . "/partials/footer.php"; ?>

  <!-- ✅ Modal: Add/Edit Specialization -->
  <div id="specModal" class="modal hidden">
    <div class="modal-content p-6">
      <span class="close-btn" onclick="closeModal()">&times;</span>
      <h2 id="modalTitle" class="text-xl font-bold mb-4">Add Specialization</h2>

      <form method="POST">
        <input type="hidden" id="SPEC_ID" name="SPEC_ID">
        <label class="block mb-1 font-semibold">Specialization Name</label>
        <input id="SPEC_NAME" name="SPEC_NAME" required
          class="border rounded-lg px-4 py-2 w-full mb-4">

        <button class="bg-[var(--primary)] text-white px-6 py-2 rounded-full">Save</button>
      </form>
    </div>
  </div>

  <!-- ✅ Modal: Doctors -->
  <div id="doctorModal" class="modal hidden">
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

  <!-- ✅ JS -->
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
          html += `<tr class='border-b hover:bg-gray-50'>
                    <td class='py-2'>${d.DOC_FIRST_NAME} ${d.DOC_LAST_NAME}</td>
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
  </script>

</body>

</html>
