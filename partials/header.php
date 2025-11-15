<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$base_url = '/Booking-System-For-Medical-Clinics';

?>

<!-- ===== HEADER ===== -->
<header class="custom-header">
    <!-- Logo -->
    <div class="logo">
        <a href="<?= $base_url ?>/index.php">
            <img src="<?= $base_url ?>/assets/images/logo.png" alt="Medicina Logo">
        </a>
        <h1>Medicina</h1>
    </div>

    <!-- Navigation links -->
    <nav class="nav-links">
        <?php if (!isset($_SESSION['role'])): ?>
            <a href="<?= $base_url ?>/login_page.php">Login</a>

        <?php elseif ($_SESSION['role'] === 'admin'): ?>
            <a href="<?= $base_url ?>/public/admin_dashboard.php">Dashboard</a>
            <a href="<?= $base_url ?>/public/admin_pages/user_accounts.php">User Accounts</a>

            <div class="dropdown">
                <a href="#">People ▾</a>
                <div class="dropdown-content">
                    <a href="<?= $base_url ?>/public/admin_pages/staff.php">Staff</a>
                    <a href="<?= $base_url ?>/public/admin_pages/patients.php">Patients</a>
                    <a href="<?= $base_url ?>/public/admin_pages/doctors.php">Doctors</a>
                </div>
            </div>

            <div class="dropdown">
                <a href="#">Clinic ▾</a>
                <div class="dropdown-content">
                    <a href="<?= $base_url ?>/public/admin_pages/admin_specialization.php">Specializations</a>
                    <a href="<?= $base_url ?>/public/admin_pages/admin_services.php">Services</a>
                    <a href="<?= $base_url ?>/public/admin_pages/admin_schedules.php">Schedules</a>
                    <a href="<?= $base_url ?>/public/admin_pages/admin_medical_records.php">Medical Records</a>
                    <a href="<?= $base_url ?>/public/admin_pages/admin_status.php">Status</a>
                    <a href="<?= $base_url ?>/public/admin_pages/admin_payments.php">Payments</a>
                </div>
            </div>

            <a href="<?= $base_url ?>/public/admin_pages/admin_appointments.php">Appointments</a>
            <a href="<?= $base_url ?>/login_page.php">Logout</a>

        <?php elseif ($_SESSION['role'] === 'staff'): ?>
            <a href="<?= $base_url ?>/public/staff_dashboard.php">Home</a>
            <a href="<?= $base_url ?>/public/staff_pages/staff_manage.php">Staff</a>
            <a href="<?= $base_url ?>/public/staff_pages/services.php">Services</a>
            <a href="<?= $base_url ?>/public/staff_pages/status.php">Status</a>
            <a href="<?= $base_url ?>/public/staff_pages/payments.php">Payments</a>
            <a href="<?= $base_url ?>/public/staff_pages/specialization.php">Specialization</a>
            <a href="<?= $base_url ?>/public/staff_pages/smedical_records.php">Medical Records</a>
            <a href="<?= $base_url ?>/login_page.php">Logout</a>

        <?php elseif ($_SESSION['role'] === 'doctor'): ?>
            <a href="<?= $base_url ?>/public/doctor_dashboard.php">Home</a>
            <a href="<?= $base_url ?>/public/doctor_pages/doctor_manage.php">Doctor</a>
            <a href="<?= $base_url ?>/public/doctor_pages/schedule.php">Schedule</a>
            <a href="<?= $base_url ?>/public/doctor_pages/appointments.php">Appointments</a>
            <a href="<?= $base_url ?>/public/doctor_pages/medical_records.php">Medical Records</a>
            <a href="<?= $base_url ?>/login_page.php">Logout</a>

        <?php elseif ($_SESSION['role'] === 'patient'): ?>
            <a href="<?= $base_url ?>/public/patient_dashboard.php">Home</a>
            <a href="<?= $base_url ?>/public/patient_pages/view_patients.php">Patients</a>
            <a href="<?= $base_url ?>/public/patient_pages/create_appointment.php">Book Appointment</a>
            <a href="<?= $base_url ?>/login_page.php">Logout</a>
        <?php endif; ?>
    </nav>
</header>
