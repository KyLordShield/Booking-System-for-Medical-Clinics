<?php
//session_start();
//if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
//   header("Location: ../index.php");
//   exit;
//}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<style>
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

/* NAVBAR */
.navbar {
  background: var(--primary);
  padding: 20px 50px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-radius: 0 0 35px 35px;
  position: relative;
  z-index: 1000;
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
  gap: 25px;
  align-items: center;
  position: relative;
}
.nav-links a {
  color: var(--white);
  text-decoration: none;
  font-size: 17px;
  font-weight: bold;
  padding: 8px 16px;
  border-radius: 30px;
  transition: .3s ease;
}
.nav-links a:hover, .nav-links a.active {
  background: var(--light);
  color: var(--primary);
}

/* DROPDOWN */
.dropdown {
  position: relative;
}
.dropdown > a {
  display: inline-block;
}
.dropdown-content {
  display: none;
  position: absolute;
  left: 0;
  top: 100%;
  background: var(--white);
  min-width: 180px;
  box-shadow: 0 4px 10px rgba(0,0,0,0.1);
  border-radius: 15px;
  overflow: hidden;
  z-index: 1001;
  opacity: 0;
  visibility: hidden;
  transition: opacity 0.2s ease, visibility 0.2s ease;
}
.dropdown-content a {
  color: var(--primary);
  padding: 10px 15px;
  display: block;
  font-weight: normal;
}
.dropdown-content a:hover {
  background: var(--light);
}
.dropdown:hover .dropdown-content,
.dropdown:focus-within .dropdown-content {
  display: block;
  opacity: 1;
  visibility: visible;
}

/* MAIN CONTENT */
main {
  flex: 1;
  padding: 60px;
}

h1 {
  color: var(--primary);
  font-size: 45px;
  margin-bottom: 30px;
  font-weight: bold;
}

/* DASHBOARD CARDS */
.dashboard-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 25px;
  margin-bottom: 50px;
}
.card {
  background: var(--light);
  border-radius: 25px;
  padding: 25px;
  text-align: center;
  box-shadow: 0 4px 10px rgba(0,0,0,0.1);
}
.card h2 {
  color: var(--primary);
  font-size: 40px;
  margin: 10px 0;
}
.card p {
  font-size: 18px;
  color: #333;
}

/* FOOTER */
footer {
  background: var(--primary);
  color: var(--white);
  text-align: center;
  padding: 20px 0;
  font-size: 14px;
  border-radius: 35px 35px 0 0;
}

/* RESPONSIVE */
@media(max-width: 768px) {
  main { padding: 30px; }
  .navbar { flex-direction: column; gap: 15px; }
}
</style>
</head>
<body>

<!-- NAVBAR -->
<div class="navbar">
  <div class="navbar-brand">
    <img src="https://cdn-icons-png.flaticon.com/512/3209/3209999.png">
    Medicina Admin
  </div>

  <div class="nav-links">
    <a class="active" href="/Booking-System-For-Medical-Clinics/public/admin_dashboard.php">Dashboard</a>
    <a href="/Booking-System-For-Medical-Clinics/public/admin_pages/user_accounts.php">User Accounts</a>

    <div class="dropdown">
      <a href="#">People ▾</a>
      <div class="dropdown-content">
        <a href="/Booking-System-For-Medical-Clinics/public/admin_pages/staff.php">Staff</a>
        <a href="/Booking-System-For-Medical-Clinics/public/admin_pages/patients.php">Patients</a>
        <a href="/Booking-System-For-Medical-Clinics/public/admin_pages/doctors.php">Doctors</a>
      </div>
    </div>

    <div class="dropdown">
      <a href="#">Clinic ▾</a>
      <div class="dropdown-content">
        <a href="/Booking-System-For-Medical-Clinics/public/admin_pages/specialization.php">Specializations</a>
        <a href="/Booking-System-For-Medical-Clinics/public/admin_pages/services.php">Services</a>
        <a href="/Booking-System-For-Medical-Clinics/public/admin_pages/schedule.php">Schedules</a>
        <a href="/Booking-System-For-Medical-Clinics/public/admin_pages/medical_records.php">Medical Records</a>
        <a href="/Booking-System-For-Medical-Clinics/public/admin_pages/admin_status.php">Status</a>
        <a href="/Booking-System-For-Medical-Clinics/public/admin_pages/admin_payments.php">Payments</a>
      </div>
    </div>

    <a href="/Booking-System-For-Medical-Clinics/public/admin_pages/appointments_status.php">Appointments</a>

    <a href="/Booking-System-For-Medical-Clinics/index.php">Logout</a>
  </div>
</div>

<!-- MAIN CONTENT -->
<main>
  <h1>Welcome, Super Admin</h1>

  <!-- DASHBOARD STAT CARDS -->
  <div class="dashboard-cards">
    <div class="card">
      <h2>12</h2>
      <p>Total Doctors</p>
    </div>
    <div class="card">
      <h2>58</h2>
      <p>Total Patients</p>
    </div>
    <div class="card">
      <h2>8</h2>
      <p>Staff Members</p>
    </div>
    <div class="card">
      <h2>24</h2>
      <p>Pending Appointments</p>
    </div>
  </div>

  <p style="font-size:18px; color:#002339; text-align:center;">
    Use the navigation above to manage users, appointments, and clinic data.
  </p>
</main>

<!-- FOOTER -->
<footer>
  &copy; 2025 Medicina Clinic | All Rights Reserved
</footer>

</body>
</html>
