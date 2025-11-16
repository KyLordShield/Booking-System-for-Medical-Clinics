<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'doctor') {
    header("Location: ../../index.php");
    exit;
}

require_once dirname(__DIR__, 2) . '/config/Database.php';
require_once dirname(__DIR__, 2) . '/classes/Schedule.php';

$db = new Database();
$conn = $db->connect();
$schedule = new Schedule();

$doc_id = intval($_SESSION['DOC_ID']);

// ---------- Add / Update ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $days = isset($_POST['day']) ? $_POST['day'] : [];
    $start = intval($_POST['start_time']);
    $end = intval($_POST['end_time']);
    $sched_id = $_POST['sched_id'] ?? '';

    if ($start >= $end) {
        $_SESSION['error'] = "Start time must be earlier than end time.";
        header("Location: schedule.php");
        exit;
    }

    // Check for overlap
    $overlap = false;
    foreach ($days as $day) {
        $sql = "SELECT SCHED_START_TIME, SCHED_END_TIME FROM schedule 
                WHERE DOC_ID=? AND FIND_IN_SET(?, SCHED_DAYS)";
        if ($sched_id !== '') $sql .= " AND SCHED_ID != ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute($sched_id !== '' ? [$doc_id, $day, $sched_id] : [$doc_id, $day]);
        $existing = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($existing as $e) {
            $e_start = intval(substr($e['SCHED_START_TIME'],0,2));
            $e_end   = intval(substr($e['SCHED_END_TIME'],0,2));
            // Check if ranges overlap
            if ($start < $e_end && $end > $e_start) {
                $overlap = true;
                break 2; // stop checking all days
            }
        }
    }

    if ($overlap) {
    $_SESSION['error'] = "The selected time overlaps with an existing schedule.";
    header("Location: schedule.php");
    exit;
}


    if ($sched_id == '') {
        $sql = "INSERT INTO schedule 
                (SCHED_DAYS, SCHED_START_TIME, SCHED_END_TIME, DOC_ID, SCHED_CREATED_AT, SCHED_UPDATED_AT)
                VALUES (?, ?, ?, ?, NOW(), NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->execute([implode(',', $days), "$start:00:00", "$end:00:00", $doc_id]);
    } else {
        $sql = "UPDATE schedule 
                SET SCHED_DAYS=?, SCHED_START_TIME=?, SCHED_END_TIME=?, SCHED_UPDATED_AT=NOW()
                WHERE SCHED_ID=? AND DOC_ID=?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([implode(',', $days), "$start:00:00", "$end:00:00", $sched_id, $doc_id]);
    }

    header("Location: schedule.php");
    exit;
}

// ---------- Delete ----------
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM schedule WHERE SCHED_ID=? AND DOC_ID=?");
    $stmt->execute([$id, $doc_id]);
    header("Location: schedule.php");
    exit;
}

// ---------- Fetch schedules ----------
$schedules = $schedule->getScheduleByDoctor($doc_id);

function esc($v) { return htmlspecialchars($v ?? '', ENT_QUOTES); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Doctor Schedule | Medicina</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="/Booking-System-For-Medical-Clinics/assets/css/style.css">
</head>
<body class="bg-[var(--secondary)] min-h-screen flex flex-col">

<?php include dirname(__DIR__, 2) . "/partials/header.php"; ?>

<main class="flex-1 p-10">
  <h2 class="text-3xl font-bold text-[var(--primary)] mb-6">My Schedule</h2>

  <!-- ---------- FORM ---------- -->
  <div class="bg-[var(--light)] p-6 rounded-[25px] shadow-md">
    <form method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <input type="hidden" name="sched_id" id="sched_id">

      <div>
        <label class="font-semibold text-[var(--primary)]">Days</label>
        <select name="day[]" id="day" multiple required class="border rounded-lg w-full min-h-[140px] px-2 py-2">
          <?php foreach(['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'] as $d): ?>
            <option value="<?= $d ?>"><?= $d ?></option>
          <?php endforeach ?>
        </select>
      </div>

      <div>
        <label class="font-semibold text-[var(--primary)]">Start Hour</label>
        <select name="start_time" id="start_time" required class="border rounded-lg w-full px-2 py-2"></select>
      </div>

      <div>
        <label class="font-semibold text-[var(--primary)]">End Hour</label>
        <select name="end_time" id="end_time" required class="border rounded-lg w-full px-2 py-2"></select>
      </div>

      <button type="submit" class="bg-[var(--primary)] text-white px-6 py-3 rounded-xl hover:opacity-90 transition h-fit">Save</button>
    </form>
  </div>

  <!-- ---------- TABLE ---------- -->
  <div class="bg-[var(--light)] p-6 rounded-[25px] shadow-md mt-6">
    <table class="w-full text-[var(--primary)]">
      <thead>
        <tr class="border-b text-left font-bold">
          <th class="py-3 px-4">Days</th>
          <th class="py-3 px-4">Start</th>
          <th class="py-3 px-4">End</th>
          <th class="py-3 px-4">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if(!$schedules): ?>
          <tr><td colspan="4" class="text-center py-3">No schedules yet</td></tr>
        <?php endif; ?>

        <?php foreach ($schedules as $s): ?>
          <tr class="border-b hover:bg-gray-100">
            <td class="py-3 px-4"><?= esc($s['SCHED_DAYS']) ?></td>
            <td class="py-3 px-4"><?= substr(esc($s['SCHED_START_TIME']),0,5) ?></td>
            <td class="py-3 px-4"><?= substr(esc($s['SCHED_END_TIME']),0,5) ?></td>
            <td class="py-3 px-4 flex gap-2">
              <button type="button" onclick='editSchedule(<?= json_encode($s) ?>)' class="btn bg-blue-600 text-white px-4 py-1 rounded-lg">Edit</button>
              <a href="?delete=<?= esc($s['SCHED_ID']) ?>" class="btn bg-red-600 text-white px-4 py-1 rounded-lg" onclick="return confirm('Remove schedule?')">Delete</a>
            </td>
          </tr>
        <?php endforeach ?>
      </tbody>
    </table>
  </div>
</main>
<?php if (!empty($_SESSION['error'])): ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
Swal.fire({
    icon: 'error',
    title: 'Oops!',
    text: <?= json_encode($_SESSION['error']) ?>,
    confirmButtonColor: '#d33'
});
</script>
<?php unset($_SESSION['error']); endif; ?>

<script>
const schedules = <?= json_encode($schedules) ?>;
const startSelect = document.getElementById('start_time');
const endSelect = document.getElementById('end_time');

// ---------- Populate options ----------
for(let h=7; h<=21; h++){
    const opt1 = document.createElement('option');
    opt1.value = h; opt1.textContent = `${h.toString().padStart(2,'0')}:00`;
    startSelect.appendChild(opt1);

    if(h >= 8){
        const opt2 = document.createElement('option');
        opt2.value = h; opt2.textContent = `${h.toString().padStart(2,'0')}:00`;
        endSelect.appendChild(opt2);
    }
}

// ---------- Edit schedule ----------
function editSchedule(s){
    document.getElementById('sched_id').value = s.SCHED_ID;

    // Multi-select days
    const savedDays = s.SCHED_DAYS.split(',').map(d=>d.trim());
    [...document.getElementById('day').options].forEach(opt => {
        opt.selected = savedDays.includes(opt.value);
    });

    document.getElementById('start_time').value = parseInt(s.SCHED_START_TIME.substring(0,2));
    document.getElementById('end_time').value = parseInt(s.SCHED_END_TIME.substring(0,2));

    window.scrollTo({top:0, behavior:'smooth'});
}
</script>

</body>
</html>
