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
require_once '../../classes/Appointment.php';

$serviceObj = new Service();
$specializationObj = new Specialization();
$appointmentObj = new Appointment();

/* ---------- 4. AJAX HANDLER ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json');

    // CSRF Validation
    if (!hash_equals($csrf, $_POST['csrf'] ?? '')) {
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit;
    }

    $action = $_POST['action'] ?? '';

    // ADD SERVICE
    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $description = $_POST['description'] ?? '';
        $price = (float)($_POST['price'] ?? 0);
        $spec_id = (int)($_POST['spec_id'] ?? 0);

        if (empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Service name is required']);
            exit;
        }
        if ($price < 0) {
            echo json_encode(['success' => false, 'message' => 'Price cannot be negative']);
            exit;
        }
        if ($spec_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Please select a specialization']);
            exit;
        }

        $result = $serviceObj->createService($name, $description, $price, $spec_id);
        echo json_encode([
            'success' => $result !== false,
            'message' => $result !== false ? 'Service added successfully!' : 'Failed to add service'
        ]);
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
            echo json_encode(['success' => false, 'message' => 'Invalid data']);
            exit;
        }

        $result = $serviceObj->updateService($id, $name, $description, $price, $spec_id);
        echo json_encode([
            'success' => $result,
            'message' => $result ? 'Service updated successfully!' : 'Failed to update service'
        ]);
        exit;
    }

    // DELETE SERVICE
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid ID']);
            exit;
        }

        $result = $serviceObj->deleteService($id);
        echo json_encode([
            'success' => $result,
            'message' => $result ? 'Service deleted successfully!' : 'Failed to delete service'
        ]);
        exit;
    }

    // VIEW APPOINTMENTS
    if ($action === 'view_appointments') {
        $servId = (int)($_POST['id'] ?? 0);
        if ($servId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid service ID']);
            exit;
        }

        $appointments = $appointmentObj->getAppointmentsByService($servId);
        if (!is_array($appointments)) $appointments = [];
        echo json_encode(['success' => true, 'data' => $appointments]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
}

/* ---------- 5. LOAD DATA FOR PAGE ---------- */
$services = $serviceObj->getAllServices();
$specializations = $specializationObj->getAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Services | Admin Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="/Booking-System-For-Medical-Clinics/assets/css/style.css">
    <style>
        .modal { display: none; justify-content: center; align-items: center; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,.5); z-index: 1000; }
        .modal-content { background: #fff; padding: 20px; border-radius: 12px; max-width: 500px; width: 90%; position: relative; max-height: 90vh; overflow-y: auto; }
        .close-btn { position: absolute; top: 8px; right: 12px; font-size: 24px; cursor: pointer; color: #666; }
        .close-btn:hover { color: #000; }
    </style>
</head>
<body class="bg-[var(--secondary)] min-h-screen flex flex-col font-[Georgia]">

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
        <button onclick="openAddModal()" class="px-4 py-2 bg-[var(--primary)] text-white rounded-lg hover:bg-[var(--primary-dark)]">+ Add Service</button>
    </div>

    <!-- SERVICES GRID -->
    <div id="servicesGrid" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
        <?php if (empty($services)): ?>
            <p class="col-span-full text-center text-gray-500 py-6">No services found...</p>
        <?php else: ?>
            <?php foreach ($services as $service): ?>
                <div class="service-card bg-white p-5 rounded-[20px] shadow-md hover:shadow-lg transition" 
                     data-id="<?= $service['SERV_ID'] ?>"
                     data-name="<?= htmlspecialchars($service['SERV_NAME']) ?>"
                     data-desc="<?= htmlspecialchars($service['SERV_DESCRIPTION'] ?? '') ?>"
                     data-price="<?= $service['SERV_PRICE'] ?>"
                     data-spec="<?= $service['SPEC_ID'] ?? 0 ?>">
                    <div class="flex justify-between items-start mb-3">
                        <div class="flex-1">
                            <h3 class="text-xl font-bold mb-1 text-[var(--primary)]"><?= htmlspecialchars($service['SERV_NAME']) ?></h3>
                            <p class="text-sm text-gray-600 mb-1"><?= htmlspecialchars($service['SPEC_NAME'] ?? 'No specialization') ?></p>
                        </div>
                        <img src="https://cdn-icons-png.flaticon.com/512/3446/3446250.png" alt="Service Icon" class="w-12 h-12">
                    </div>
                    <p class="text-gray-600 mb-2 text-sm"><?= htmlspecialchars($service['SERV_DESCRIPTION'] ?? '') ?></p>
                    <p class="text-lg font-bold text-[var(--primary)] mb-3">₱<?= number_format($service['SERV_PRICE'], 2) ?></p>
                    <div class="flex flex-wrap gap-2">
                        <button onclick="openEditModal(<?= $service['SERV_ID'] ?>)" class="px-3 py-1 bg-blue-500 text-white text-sm rounded-lg hover:bg-blue-600">Edit</button>
                        <button onclick="viewAppointments(<?= $service['SERV_ID'] ?>)" class="px-3 py-1 bg-green-500 text-white text-sm rounded-lg hover:bg-green-600">Appointments</button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- ADD MODAL -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal('addModal')">&times;</span>
            <h2 class="text-xl font-bold mb-4">Add New Service</h2>
            <form id="addForm">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="csrf" value="<?= $csrf ?>">

                <label class="block mb-2 font-semibold">Service Name</label>
                <input type="text" name="name" required class="w-full px-3 py-2 border rounded mb-4">

                <label class="block mb-2 font-semibold">Description</label>
                <textarea name="description" rows="3" class="w-full px-3 py-2 border rounded mb-4"></textarea>

                <label class="block mb-2 font-semibold">Price</label>
                <input type="number" step="0.01" name="price" required class="w-full px-3 py-2 border rounded mb-4">

                <label class="block mb-2 font-semibold">Specialization</label>
                <select name="spec_id" required class="w-full px-3 py-2 border rounded mb-4">
                    <option value="">-- Select Specialization --</option>
                    <?php foreach ($specializations as $spec): ?>
                        <option value="<?= $spec['SPEC_ID'] ?>"><?= htmlspecialchars($spec['SPEC_NAME']) ?></option>
                    <?php endforeach; ?>
                </select>

                <div class="flex justify-end gap-2">
                    <button type="button" onclick="closeModal('addModal')" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-[var(--primary)] text-white rounded hover:opacity-90">Add Service</button>
                </div>
            </form>
        </div>
    </div>

    <!-- EDIT MODAL -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal('editModal')">&times;</span>
            <h2 class="text-xl font-bold mb-4">Edit Service</h2>
            <form id="editForm">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="csrf" value="<?= $csrf ?>">
                <input type="hidden" name="id">

                <label class="block mb-2 font-semibold">Service Name</label>
                <input type="text" name="name" required class="w-full px-3 py-2 border rounded mb-4">

                <label class="block mb-2 font-semibold">Description</label>
                <textarea name="description" rows="3" class="w-full px-3 py-2 border rounded mb-4"></textarea>

                <label class="block mb-2 font-semibold">Price</label>
                <input type="number" step="0.01" name="price" required class="w-full px-3 py-2 border rounded mb-4">

                <label class="block mb-2 font-semibold">Specialization</label>
                <select name="spec_id" required class="w-full px-3 py-2 border rounded mb-4">
                    <option value="">-- Select Specialization --</option>
                    <?php foreach ($specializations as $spec): ?>
                        <option value="<?= $spec['SPEC_ID'] ?>"><?= htmlspecialchars($spec['SPEC_NAME']) ?></option>
                    <?php endforeach; ?>
                </select>

                <div class="flex justify-end gap-2">
                    <button type="button" onclick="closeModal('editModal')" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-[var(--primary)] text-white rounded hover:opacity-90">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- VIEW APPOINTMENTS MODAL -->
    <div id="viewAppointmentsModal" class="modal">
        <div class="modal-content" style="max-width: 800px;">
            <span class="close-btn" onclick="closeModal('viewAppointmentsModal')">&times;</span>
            <h2 class="text-xl font-bold mb-4">Appointments for this Service</h2>
            <div id="appointmentsList" class="text-gray-700"></div>
            <div class="flex justify-end mt-4">
                <button type="button" onclick="closeModal('viewAppointmentsModal')" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Close</button>
            </div>
        </div>
    </div>
</main>

<?php include dirname(__DIR__, 2) . "/partials/footer.php"; ?>

<script>
function openModal(id) { 
    document.getElementById(id).style.display = 'flex'; 
}

function closeModal(id) { 
    document.getElementById(id).style.display = 'none'; 
}

// Close on backdrop click
document.querySelectorAll('.modal').forEach(modal => {
    modal.addEventListener('click', e => { 
        if (e.target === modal) closeModal(modal.id); 
    });
});

// Search functionality
document.getElementById('searchInput').addEventListener('keyup', function() {
    const query = this.value.toLowerCase();
    document.querySelectorAll('.service-card').forEach(card => {
        const name = card.dataset.name.toLowerCase();
        card.style.display = name.includes(query) ? '' : 'none';
    });
});

// Open Add Modal
function openAddModal() {
    document.getElementById('addForm').reset();
    openModal('addModal');
}

// Populate Edit Modal
function openEditModal(id) {
    const card = document.querySelector(`.service-card[data-id="${id}"]`);
    
    const form = $('#editForm');
    form.find('[name=id]').val(id);
    form.find('[name=name]').val(card.dataset.name);
    form.find('[name=description]').val(card.dataset.desc);
    form.find('[name=price]').val(card.dataset.price);
    form.find('[name=spec_id]').val(card.dataset.spec);

    openModal('editModal');
}

function openDeleteModal(id) {
    Swal.fire({
        title: 'Are you sure?',
        text: "Delete this service?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6'
    }).then((result) => {
        if (result.isConfirmed) {
            deleteService(id);
        }
    });
}

// Delete Service
function deleteService(id) {
    $.ajax({
        url: '',
        type: 'POST',
        data: { 
            action: 'delete', 
            id: id, 
            csrf: '<?= $csrf ?>' 
        },
        dataType: 'json',
        success: function(data) {
            Swal.fire({
                icon: data.success ? 'success' : 'error',
                title: data.success ? 'Deleted!' : 'Error',
                text: data.message
            }).then(() => {
                if (data.success) location.reload();
            });
        },
        error: function() {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Request failed. Check console.'
            });
        }
    });
}

// AJAX for Add/Edit forms
$('#addForm, #editForm').on('submit', function(e) {
    e.preventDefault();
    const form = $(this);
    const submitBtn = form.find('button[type=submit]');
    submitBtn.prop('disabled', true).text('Saving...');

    $.ajax({
        url: '',
        type: 'POST',
        data: form.serialize(),
        dataType: 'json',
        success: function(data) {
            Swal.fire({
                icon: data.success ? 'success' : 'error',
                title: data.success ? 'Success!' : 'Error',
                text: data.message
            }).then(() => {
                if (data.success) {
                    location.reload();
                } else {
                    submitBtn.prop('disabled', false).text(form.attr('id') === 'addForm' ? 'Add Service' : 'Save Changes');
                }
            });
        },
        error: function() {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Request failed. Check console.'
            });
            submitBtn.prop('disabled', false).text(form.attr('id') === 'addForm' ? 'Add Service' : 'Save Changes');
        }
    });
});

// VIEW APPOINTMENTS
function viewAppointments(serviceId) {
    $.ajax({
        url: '',
        type: 'POST',
        data: { 
            action: 'view_appointments', 
            id: serviceId, 
            csrf: '<?= $csrf ?>' 
        },
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                let html = '';
                if (!res.data || res.data.length === 0) {
                    html = '<p class="text-gray-500 text-center py-4">No appointments for this service.</p>';
                } else {
                    html = '<div class="overflow-x-auto"><table class="w-full text-left border-collapse">';
                    html += '<thead><tr class="bg-gray-200"><th class="p-2 border">Patient</th><th class="p-2 border">Doctor</th><th class="p-2 border">Date</th><th class="p-2 border">Time</th><th class="p-2 border">Status</th></tr></thead>';
                    html += '<tbody>';
                    res.data.forEach(a => {
                        const patientName = a.PATIENT_NAME || ( (a.PAT_FIRST_NAME || '') + ' ' + (a.PAT_LAST_NAME || '') ).trim() || '-';
                        const doctorName = a.DOCTOR_NAME || ( (a.DOC_FIRST_NAME || '') + ' ' + (a.DOC_LAST_NAME || '') ).trim() || '-';
                        const status = a.APPT_STATUS || a.STATUS_NAME || '-';
                        html += `<tr class="hover:bg-gray-50">
                            <td class="p-2 border">${patientName}</td>
                            <td class="p-2 border">${doctorName}</td>
                            <td class="p-2 border">${a.APPT_DATE}</td>
                            <td class="p-2 border">${a.APPT_TIME}</td>
                            <td class="p-2 border">${status}</td>
                        </tr>`;
                    });
                    html += '</tbody></table></div>';
                }
                $('#appointmentsList').html(html);
                openModal('viewAppointmentsModal');
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: res.message || 'Failed to fetch appointments'
                });
            }
        },
        error: function() {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to fetch appointments. Check console.'
            });
        }
    });
}
</script>
</body>
</html>