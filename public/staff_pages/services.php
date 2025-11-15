<?php
session_start();

/* ---------- 1. AUTH CHECK ---------- */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../../index.php");
    exit;
}

/* ---------- 2. CSRF TOKEN ---------- */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

/* ---------- 3. INCLUDE CLASSES ---------- */
require_once '../../classes/Service.php';
require_once '../../classes/Specialization.php';
require_once '../../classes/Appointment.php'; // added for view appointments

$serviceObj = new Service();
$specializationObj = new Specialization();
$appointmentObj = new Appointment(); // instance for fetching appointments

/* ---------- 4. AJAX HANDLER ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    // CSRF Validation
    if (!hash_equals($csrf, $_POST['csrf'] ?? '')) {
        echo json_encode(['success' => false, 'msg' => 'Invalid CSRF token']);
        exit;
    }

    $action = $_POST['action'];

    // ADD SERVICE
    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $description = $_POST['description'] ?? '';
        $price = (float)($_POST['price'] ?? 0);
        $spec_id = (int)($_POST['spec_id'] ?? 0);

        if (empty($name)) {
            echo json_encode(['success' => false, 'msg' => 'Service name is required']);
            exit;
        }
        if ($price < 0) {
            echo json_encode(['success' => false, 'msg' => 'Price cannot be negative']);
            exit;
        }
        if ($spec_id <= 0) {
            echo json_encode(['success' => false, 'msg' => 'Please select a specialization']);
            exit;
        }

        $result = $serviceObj->createService($name, $description, $price, $spec_id);
        echo json_encode(['success' => $result !== false]);
        exit;
    }

    // EDIT SERVICE
    if ($action === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $description = $_POST['description'] ?? '';
        $price = (float)($_POST['price'] ?? 0);
        $spec_id = (int)($_POST['spec_id'] ?? 0);

        if ($id <= 0 || empty($name) || $price < 0 || $spec_id <= 0) {
            echo json_encode(['success' => false, 'msg' => 'Invalid data']);
            exit;
        }

        $result = $serviceObj->updateService($id, $name, $description, $price, $spec_id);
        echo json_encode(['success' => $result]);
        exit;
    }

    // DELETE SERVICE
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'msg' => 'Invalid ID']);
            exit;
        }

        $result = $serviceObj->deleteService($id);
        echo json_encode(['success' => $result]);
        exit;
    }

    // VIEW APPOINTMENTS
    if ($action === 'view_appointments') {
        $servId = (int)($_POST['id'] ?? 0);
        if ($servId <= 0) {
            echo json_encode(['success' => false, 'msg' => 'Invalid service ID']);
            exit;
        }

        $appointments = $appointmentObj->getAppointmentsByService($servId);
        // ensure always return array for JS
        if (!is_array($appointments)) $appointments = [];
        echo json_encode(['success' => true, 'data' => $appointments]);
        exit;
    }

    echo json_encode(['success' => false, 'msg' => 'Invalid action']);
    exit;
}

/* ---------- 5. LOAD DATA FOR PAGE ---------- */
$services = $serviceObj->getAllServices();
$specializations = $specializationObj->getAll(); // must return SPEC_ID + SPEC_NAME
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Services | Staff Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <link rel="stylesheet" href="/Booking-System-For-Medical-Clinics/assets/css/style.css">
</head>
<body class="bg-[var(--secondary)] min-h-screen flex flex-col font-[Georgia]">

<!-- NAVBAR -->
<?php include dirname(__DIR__, 2) . "/partials/header.php"; ?>

<main class="flex-1 px-10 py-10">
    <div class="flex flex-col md:flex-row justify-between items-center mb-10 gap-6">
        <div>
            <h1 class="text-[36px] font-bold text-[var(--primary)]">Healthcare Services</h1>
            <p class="text-gray-600 mt-2">Manage all your medical services below</p>
        </div>
        <div>
            <img src="https://cdn-icons-png.flaticon.com/512/2965/2965567.png" alt="Healthcare Icon" class="w-32 h-32 md:w-40 md:h-40">
        </div>
    </div>

    <!-- TOP CONTROLS -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <div class="search-box w-full sm:w-[300px]">
            <input type="text" id="searchInput" placeholder="Search service..." class="w-full px-4 py-2 rounded-full border-none focus:ring-2 focus:ring-[var(--primary)] outline-none text-[16px]">
        </div>
        <button onclick="openModal('addModal')" class="px-4 py-2 bg-[var(--primary)] text-white rounded-lg hover:bg-[var(--primary-dark)]">+ Add Service</button>
    </div>

    <!-- SERVICES GRID -->
    <div id="servicesGrid" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
        <?php if (empty($services)): ?>
            <p class="col-span-full text-center text-gray-500 py-6">No services found...</p>
        <?php else: ?>
            <?php foreach ($services as $service): ?>
                <div class="service-card bg-white p-5 rounded-[20px] shadow-md hover:shadow-lg transition flex justify-between items-center" data-id="<?= $service['SERV_ID'] ?>">
                    <div>
                        <h3 class="text-xl font-bold mb-1 text-[var(--primary)]"><?= htmlspecialchars($service['SERV_NAME']) ?></h3>
                        <p class="text-gray-600 mb-1"><?= htmlspecialchars($service['SPEC_NAME'] ?? 'No specialization') ?></p>
                        <p class="text-gray-600 mb-2 description"><?= htmlspecialchars($service['SERV_DESCRIPTION'] ?? '') ?></p>
                        <p class="text-gray-600 mb-2 price">Price: ₱<?= number_format($service['SERV_PRICE'], 2) ?></p>
                        <div class="flex gap-2">
                            <button onclick="openEditModal(<?= $service['SERV_ID'] ?>, <?= $service['SPEC_ID'] ?? 0 ?>)" class="px-3 py-1 bg-[var(--primary)] text-white rounded-lg hover:bg-[var(--primary-dark)]">Edit</button>
                            <button onclick="openDeleteModal(<?= $service['SERV_ID'] ?>)" class="px-3 py-1 bg-red-500 text-white rounded-lg hover:bg-red-600">Delete</button>
                            <button onclick="viewAppointments(<?= $service['SERV_ID'] ?>)" class="px-3 py-1 bg-green-500 text-white rounded-lg hover:bg-green-600">View Appointments</button>
                        </div>
                    </div>
                    <div>
                        <img src="https://cdn-icons-png.flaticon.com/512/3446/3446250.png" alt="Service Icon" class="w-12 h-12">
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- ADD MODAL -->
    <div id="addModal" class="modal hidden fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50">
        <div class="bg-white p-6 rounded-xl w-11/12 max-w-md">
            <h2 class="text-xl font-bold mb-4">Add New Service</h2>
            <form id="addForm">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="csrf" value="<?= $csrf ?>">

                <label class="block mb-2">Service Name</label>
                <input type="text" name="name" required class="w-full px-3 py-2 border rounded mb-4">

                <label class="block mb-2">Description</label>
                <textarea name="description" class="w-full px-3 py-2 border rounded mb-4"></textarea>

                <label class="block mb-2">Price</label>
                <input type="number" step="0.01" name="price" required class="w-full px-3 py-2 border rounded mb-4">

                <label class="block mb-2">Specialization</label>
                <select name="spec_id" required class="w-full px-3 py-2 border rounded mb-4">
                    <option value="">-- Select Specialization --</option>
                    <?php foreach ($specializations as $spec): ?>
                        <option value="<?= $spec['SPEC_ID'] ?>"><?= htmlspecialchars($spec['SPEC_NAME']) ?></option>
                    <?php endforeach; ?>
                </select>

                <div class="flex justify-end gap-2">
                    <button type="button" onclick="closeModal('addModal')" class="px-4 py-2 bg-gray-300 rounded">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-[var(--primary)] text-white rounded">Add Service</button>
                </div>
            </form>
        </div>
    </div>

    <!-- EDIT MODAL -->
    <div id="editModal" class="modal hidden fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50">
        <div class="bg-white p-6 rounded-xl w-11/12 max-w-md">
            <h2 class="text-xl font-bold mb-4">Edit Service</h2>
            <form id="editForm">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="csrf" value="<?= $csrf ?>">
                <input type="hidden" name="id">

                <label class="block mb-2">Service Name</label>
                <input type="text" name="name" required class="w-full px-3 py-2 border rounded mb-4">

                <label class="block mb-2">Description</label>
                <textarea name="description" class="w-full px-3 py-2 border rounded mb-4"></textarea>

                <label class="block mb-2">Price</label>
                <input type="number" step="0.01" name="price" required class="w-full px-3 py-2 border rounded mb-4">

                <label class="block mb-2">Specialization</label>
                <select name="spec_id" required class="w-full px-3 py-2 border rounded mb-4">
                    <option value="">-- Select Specialization --</option>
                    <?php foreach ($specializations as $spec): ?>
                        <option value="<?= $spec['SPEC_ID'] ?>"><?= htmlspecialchars($spec['SPEC_NAME']) ?></option>
                    <?php endforeach; ?>
                </select>

                <div class="flex justify-end gap-2">
                    <button type="button" onclick="closeModal('editModal')" class="px-4 py-2 bg-gray-300 rounded">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-[var(--primary)] text-white rounded">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- DELETE MODAL -->
    <div id="deleteModal" class="modal hidden fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50">
        <div class="bg-white p-6 rounded-xl w-11/12 max-w-md">
            <h2 class="text-xl font-bold mb-4">Delete Service</h2>
            <p class="mb-4">Are you sure you want to delete this service?</p>
            <form id="deleteForm">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="csrf" value="<?= $csrf ?>">
                <input type="hidden" name="id">

                <div class="flex justify-end gap-2">
                    <button type="button" onclick="closeModal('deleteModal')" class="px-4 py-2 bg-gray-300 rounded">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-red-500 text-white rounded">Delete</button>
                </div>
            </form>
        </div>
    </div>

    <!-- VIEW APPOINTMENTS MODAL -->
    <div id="viewAppointmentsModal" class="modal hidden fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50">
        <div class="bg-white p-6 rounded-xl w-11/12 max-w-3xl max-h-[80vh] overflow-y-auto">
            <h2 class="text-xl font-bold mb-4">Appointments</h2>
            <div id="appointmentsList" class="text-gray-700"></div>
            <div class="flex justify-end mt-4">
                <button type="button" onclick="closeModal('viewAppointmentsModal')" class="px-4 py-2 bg-gray-300 rounded">Close</button>
            </div>
        </div>
    </div>
</main>

<!-- ✅ FOOTER LINK -->
<?php include dirname(__DIR__, 2) . "/partials/footer.php"; ?>

<script>
function openModal(id) { document.getElementById(id).classList.remove('hidden'); }
function closeModal(id) { document.getElementById(id).classList.add('hidden'); }

document.querySelectorAll('.modal').forEach(modal => {
    modal.addEventListener('click', e => { if (e.target === modal) closeModal(modal.id); });
});

// Search functionality
document.getElementById('searchInput').addEventListener('keyup', function() {
    const query = this.value.toLowerCase();
    document.querySelectorAll('.service-card').forEach(card => {
        const name = card.querySelector('h3').textContent.toLowerCase();
        card.style.display = name.includes(query) ? '' : 'none';
    });
});

// Populate Edit Modal
function openEditModal(id, specId) {
    const card = document.querySelector(`.service-card[data-id="${id}"]`);
    const name = card.querySelector('h3').textContent;
    const desc = card.querySelector('.description').textContent;
    const price = card.querySelector('.price').textContent.replace('Price: ₱', '').replace(/,/g, '');

    const form = $('#editForm');
    form.find('[name=id]').val(id);
    form.find('[name=name]').val(name);
    form.find('[name=description]').val(desc);
    form.find('[name=price]').val(price);
    form.find('[name=spec_id]').val(specId);

    openModal('editModal');
}

function openDeleteModal(id) {
    $('#deleteForm [name=id]').val(id);
    openModal('deleteModal');
}

// AJAX for forms (Add / Edit / Delete)
$('#addForm, #editForm, #deleteForm').on('submit', function(e) {
    e.preventDefault();
    const form = $(this);

    $.post('', form.serialize(), function(data) {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + (data.msg || 'Operation failed'));
        }
    }, 'json').fail(() => {
        alert('Request failed. Check console.');
    });
});

// VIEW APPOINTMENTS
function viewAppointments(serviceId) {
    $.post('', { action: 'view_appointments', id: serviceId, csrf: '<?= $csrf ?>' }, function(res) {
        if (res.success) {
            let html = '';
            if (!res.data || res.data.length === 0) {
                html = '<p class="text-gray-500">No appointments for this service.</p>';
            } else {
                html = '<table class="w-full text-left border-collapse">';
                html += '<thead><tr class="bg-gray-200"><th class="p-2 border">Patient</th><th class="p-2 border">Doctor</th><th class="p-2 border">Date</th><th class="p-2 border">Time</th><th class="p-2 border">Status</th></tr></thead>';
                html += '<tbody>';
                res.data.forEach(a => {
                    const patientName = a.PATIENT_NAME || ( (a.PAT_FIRST_NAME || '') + ' ' + (a.PAT_LAST_NAME || '') ).trim() || '-';
                    const doctorName = a.DOCTOR_NAME || ( (a.DOC_FIRST_NAME || '') + ' ' + (a.DOC_LAST_NAME || '') ).trim() || '-';
                    const status = a.APPT_STATUS || a.STATUS_NAME || '-';
                    html += `<tr>
                        <td class="p-2 border">${patientName}</td>
                        <td class="p-2 border">${doctorName}</td>
                        <td class="p-2 border">${a.APPT_DATE}</td>
                        <td class="p-2 border">${a.APPT_TIME}</td>
                        <td class="p-2 border">${status}</td>
                    </tr>`;
                });
                html += '</tbody></table>';
            }
            $('#appointmentsList').html(html);
            openModal('viewAppointmentsModal');
        } else {
            alert('Error fetching appointments: ' + (res.msg || 'Unknown error'));
        }
    }, 'json').fail(function() {
        alert('Failed to fetch appointments. Check console.');
    });
}
</script>
</body>
</html>
