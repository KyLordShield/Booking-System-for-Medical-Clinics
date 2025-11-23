<?php 
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ✅ Only admin can access
if (
    empty($_SESSION['USER_IS_SUPERADMIN']) ||
    $_SESSION['USER_IS_SUPERADMIN'] != 1 ||
    $_SESSION['role'] !== 'admin'
) {
    header("Location: ../../index.php");
    exit;
}

require_once dirname(__DIR__, 2) . '/config/Database.php';
require_once dirname(__DIR__, 2) . '/classes/Schedule.php';

$db = new Database();
$conn = $db->connect();
$schedule = new Schedule(); // Note: This is instantiated but not used; consider removing if unnecessary

// ✅ AJAX HANDLERS
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json');

    // CSRF validation
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit;
    }

    $action = $_POST['action'] ?? '';

    // ADD/UPDATE SCHEDULE
    if ($action === 'save') {
        $days = isset($_POST['day']) ? implode(', ', $_POST['day']) : '';
        $start = $_POST['start_time'] . ":00";
        $end = $_POST['end_time'] . ":00";
        $sched_id = $_POST['sched_id'] ?? '';
        $doc_id = intval($_POST['doc_id'] ?? 0);

        // Validation
        if (empty($days)) {
            echo json_encode(['success' => false, 'message' => 'Please select at least one day']);
            exit;
        }
        if ($doc_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Please select a doctor']);
            exit;
        }
        if ($start >= $end) {
            echo json_encode(['success' => false, 'message' => 'End time must be after start time']);
            exit;
        }

        // Validate doc_id exists
        $docCheck = $conn->prepare("SELECT DOC_ID FROM doctor WHERE DOC_ID = ?");
        $docCheck->execute([$doc_id]);
        if (!$docCheck->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Invalid doctor selected']);
            exit;
        }

        try {
            // Determine exclude ID for overlap check
            $excludeId = ($sched_id == '') ? 0 : intval($sched_id);

            // Fetch existing schedules for this doctor (excluding current if updating)
            $checkSql = "SELECT SCHED_ID, SCHED_DAYS, SCHED_START_TIME, SCHED_END_TIME 
                         FROM schedule 
                         WHERE DOC_ID = ? AND SCHED_ID != ?";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->execute([$doc_id, $excludeId]);
            $existingSchedules = $checkStmt->fetchAll(PDO::FETCH_ASSOC);

            // Parse new days into array
            $newDaysArray = array_map('trim', explode(',', $days));

            // Check each existing schedule for overlap
            $hasOverlap = false;
            foreach ($existingSchedules as $existing) {
                $existingDaysArray = array_map('trim', explode(',', $existing['SCHED_DAYS']));
                
                // Check if any common days
                $commonDays = array_intersect($newDaysArray, $existingDaysArray);
                if (!empty($commonDays)) {
                    // Check time overlap on common days
                    if ($existing['SCHED_START_TIME'] < $end && $existing['SCHED_END_TIME'] > $start) {
                        $hasOverlap = true;
                        break;
                    }
                }
            }

            if ($hasOverlap) {
                echo json_encode(['success' => false, 'message' => 'Schedule overlaps with existing schedule for this doctor on shared days']);
                exit;
            }

            if ($sched_id == '') {
                $sql = "INSERT INTO schedule 
                        (SCHED_DAYS, SCHED_START_TIME, SCHED_END_TIME, DOC_ID, SCHED_CREATED_AT, SCHED_UPDATED_AT)
                        VALUES (?, ?, ?, ?, NOW(), NOW())";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$days, $start, $end, $doc_id]);
                echo json_encode(['success' => true, 'message' => 'Schedule added successfully!']);
            } else {
                $sql = "UPDATE schedule 
                        SET SCHED_DAYS=?, SCHED_START_TIME=?, SCHED_END_TIME=?, DOC_ID=?, SCHED_UPDATED_AT=NOW()
                        WHERE SCHED_ID=?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$days, $start, $end, $doc_id, $sched_id]);
                echo json_encode(['success' => true, 'message' => 'Schedule updated successfully!']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        exit;
    }

    // DELETE SCHEDULE
    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid schedule ID']);
            exit;
        }

        try {
            $stmt = $conn->prepare("DELETE FROM schedule WHERE SCHED_ID=?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Schedule deleted successfully!']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error deleting schedule']);
        }
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
}

// ✅ Fetch all doctors for dropdown
$doctors = $conn->query("SELECT DOC_ID, CONCAT(DOC_FIRST_NAME, ' ', DOC_LAST_NAME) AS name FROM doctor ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// ✅ Fetch all schedules + doctor names
$sql = "SELECT s.*, CONCAT(d.DOC_FIRST_NAME, ' ', d.DOC_LAST_NAME) AS doctor_name 
        FROM schedule s 
        JOIN doctor d ON s.DOC_ID = d.DOC_ID
        ORDER BY d.DOC_LAST_NAME, s.SCHED_START_TIME";
$schedules = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);

function esc($v) { return htmlspecialchars($v ?? '', ENT_QUOTES); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin | Doctor Schedules</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="../../assets/css/style.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-[var(--secondary)] min-h-screen flex flex-col">

<?php include dirname(__DIR__, 2) . "/partials/header.php"; ?>

<main class="flex-1 p-10">
  <h2 class="text-3xl font-bold text-[var(--primary)] mb-6">Doctor Schedules</h2>

  <!-- ✅ FORM -->
  <div class="bg-[var(--light)] p-6 rounded-[25px] shadow-md mb-6">
    <h3 class="text-xl font-bold text-[var(--primary)] mb-4" id="formTitle">Add New Schedule</h3>
    <form id="scheduleForm" class="grid grid-cols-1 md:grid-cols-5 gap-4">
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="sched_id" id="sched_id">
      <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

      <!-- Doctor select -->
      <div>
        <label class="font-semibold text-[var(--primary)]">Doctor *</label>
        <select name="doc_id" id="doc_id" required class="border rounded-lg w-full px-2 py-2">
          <option value="">Select Doctor</option>
          <?php foreach ($doctors as $doc): ?>
            <option value="<?= $doc['DOC_ID'] ?>"><?= esc($doc['name']) ?></option>
          <?php endforeach ?>
        </select>
      </div>

      <!-- Days -->
      <div>
        <label class="font-semibold text-[var(--primary)]">Days * (Hold Ctrl/Cmd)</label>
        <select name="day[]" id="day" multiple required class="border rounded-lg w-full min-h-[100px] px-2 py-2">
          <?php foreach(['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'] as $d): ?>
            <option value="<?= $d ?>"><?= $d ?></option>
          <?php endforeach ?>
        </select>
      </div>

      <!-- Start Hour -->
      <div>
        <label class="font-semibold text-[var(--primary)]">Start Time *</label>
        <select name="start_time" id="start_time" required class="border rounded-lg w-full px-2 py-2">
          <?php for($h=7;$h<=18;$h++): ?>
            <option value="<?= sprintf('%02d', $h) ?>"><?= sprintf('%02d:00', $h) ?></option>
          <?php endfor ?>
        </select>
      </div>

      <!-- End Hour -->
      <div>
        <label class="font-semibold text-[var(--primary)]">End Time *</label>
        <select name="end_time" id="end_time" required class="border rounded-lg w-full px-2 py-2">
          <?php for($h=8;$h<=21;$h++): ?>
            <option value="<?= sprintf('%02d', $h) ?>"><?= sprintf('%02d:00', $h) ?></option>
          <?php endfor ?>
        </select>
      </div>

      <div class="flex gap-2">
        <button type="submit" class="bg-[var(--primary)] text-white px-6 py-3 rounded-xl hover:opacity-90 transition h-fit">
          Save
        </button>
        <button type="button" onclick="resetForm()" class="bg-gray-400 text-white px-6 py-3 rounded-xl hover:opacity-90 transition h-fit">
          Reset
        </button>
      </div>
    </form>
  </div>

  <!-- ✅ TABLE -->
  <div class="bg-[var(--light)] p-6 rounded-[25px] shadow-md">
    <h3 class="text-xl font-bold text-[var(--primary)] mb-4">Existing Schedules</h3>
    <div class="overflow-x-auto">
      <table class="w-full text-[var(--primary)]">
        <thead>
          <tr class="border-b text-left font-bold">
            <th class="py-3 px-4">Doctor</th>
            <th class="py-3 px-4">Days</th>
            <th class="py-3 px-4">Start</th>
            <th class="py-3 px-4">End</th>
            <th class="py-3 px-4">Action</th>
          </tr>
        </thead>

        <tbody id="scheduleTableBody">
        <?php if(!$schedules): ?>
            <tr><td colspan="5" class="text-center py-3">No schedules yet</td></tr>
        <?php endif; ?>

        <?php foreach ($schedules as $s): ?>
          <tr class="border-b hover:bg-gray-100">
            <td class="py-3 px-4"><?= esc($s['doctor_name']) ?></td>
            <td class="py-3 px-4"><?= esc($s['SCHED_DAYS']) ?></td>
            <td class="py-3 px-4"><?= date('h:i A', strtotime($s['SCHED_START_TIME'])) ?></td>
            <td class="py-3 px-4"><?= date('h:i A', strtotime($s['SCHED_END_TIME'])) ?></td>
            <td class="py-3 px-4 flex gap-2">
              <button onclick='editSchedule(<?= json_encode($s) ?>)' class="btn bg-blue-600 text-white px-4 py-1 rounded-lg hover:bg-blue-700">Edit</button>
              <button onclick="deleteSchedule(<?= $s['SCHED_ID'] ?>)" class="btn bg-red-600 text-white px-4 py-1 rounded-lg hover:bg-red-700">Delete</button>
            </td>
          </tr>
        <?php endforeach ?>
        </tbody>
      </table>
    </div>
  </div>
</main>

<?php include dirname(__DIR__, 2) . "/partials/footer.php"; ?>

<script>
// Prevent double submission
let isSubmitting = false;

// Form submission
document.getElementById('scheduleForm').addEventListener('submit', async function(e) {
  e.preventDefault();
  if (isSubmitting) return;
  isSubmitting = true;
  
  const formData = new FormData(this);
  const submitBtn = this.querySelector('button[type=submit]');
  submitBtn.disabled = true;
  submitBtn.textContent = 'Saving...';

  try {
    const res = await fetch(location.pathname, {
      method: 'POST',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      body: formData
    });
    const json = await res.json();

    Swal.fire({
      icon: json.success ? 'success' : 'error',
      title: json.success ? 'Success!' : 'Error',
      text: json.message
    }).then(() => {
      if (json.success) {
        location.reload();
      } else {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Save';
      }
    });
  } catch (error) {
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'An error occurred: ' + error.message
    });
    submitBtn.disabled = false;
    submitBtn.textContent = 'Save';
  } finally {
    isSubmitting = false;
  }
});

// Edit schedule
function editSchedule(s) {
  document.getElementById('formTitle').textContent = 'Edit Schedule';
  document.getElementById("sched_id").value = s.SCHED_ID;
  document.getElementById("doc_id").value = s.DOC_ID;

  // Multi-select restore
  let savedDays = s.SCHED_DAYS.split(',').map(d => d.trim());
  [...document.getElementById("day").options].forEach(opt => {
    opt.selected = savedDays.includes(opt.value);
  });

  document.getElementById("start_time").value = s.SCHED_START_TIME.substring(0,2);
  document.getElementById("end_time").value = s.SCHED_END_TIME.substring(0,2);
  
  window.scrollTo({ top: 0, behavior: "smooth" });
}

// Delete schedule
async function deleteSchedule(id) {
  const result = await Swal.fire({
    title: 'Are you sure?',
    text: "Delete this schedule?",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Yes, delete it!',
    cancelButtonText: 'Cancel',
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6'
  });

  if (!result.isConfirmed) return;

  try {
    const res = await fetch(location.pathname, {
      method: 'POST',
      headers: { 
        'X-Requested-With': 'XMLHttpRequest',
        'Content-Type': 'application/x-www-form-urlencoded'
      },
      body: new URLSearchParams({
        action: 'delete',
        id: id,
        csrf_token: '<?= $_SESSION['csrf_token'] ?>' // Add CSRF to delete as well
      })
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

// Reset form
function resetForm() {
  document.getElementById('formTitle').textContent = 'Add New Schedule';
  document.getElementById('scheduleForm').reset();
  document.getElementById('sched_id').value = '';
}
</script>

</body>
</html>