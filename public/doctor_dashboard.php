<?php
//session_start();
//if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'doctor') {
 //   header("Location: ../index.php");
 //   exit;
//}
//?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Doctor Dashboard</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<style>
/* KEEP YOUR CSS — unchanged ✅ */
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
  gap: 35px;
}
.nav-links a {
  color: var(--white);
  text-decoration: none;
  font-size: 17px;
  font-weight: bold;
  padding: 8px 18px;
  border-radius: 30px;
  transition: .3s ease;
}
.nav-links a:hover, .nav-links a.active {
  background: var(--light);
  color: var(--primary);
}
main {
  flex: 1;
  padding: 60px;
  display: flex;
  align-items: center;
}
.profile-card {
  background: var(--light);
  width: 250px;
  height: 250px;
  padding: 20px;
  border-radius: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.profile-card img {
  width: 130px;
}
.doctor-info {
  margin-left: 50px;
}
.doctor-info h1 {
  font-size: 45px;
  font-weight: bold;
}
.doctor-info p {
  font-size: 20px;
  margin: 6px 0;
}
.btn-update {
  background: var(--light);
  padding: 10px 25px;
  margin-top: 15px;
  border: none;
  border-radius: 25px;
  font-weight: bold;
  cursor: pointer;
  transition: .3s;
}
.btn-update:hover {
  background: #bfe1eb;
}
footer {
  background: var(--primary);
  color: var(--white);
  text-align: center;
  padding: 20px 0;
  font-size: 14px;
}
@media(max-width: 768px) {
  main {
    flex-direction: column;
    text-align: center;
  }
  .doctor-info {
    margin-left: 0;
    margin-top: 20px;
  }
}
</style>

</head>
<body>

<!-- NAVIGATION BAR -->
<div class="navbar">
    <div class="navbar-brand">
      <img src="https://cdn-icons-png.flaticon.com/512/3209/3209999.png">
      Medicina
    </div>
    <div class="nav-links">
      <a class="active" href="/Booking-System-For-Medical-Clinics/public/doctor_dashboard.php">Home</a>
      <a href="/Booking-System-For-Medical-Clinics/public/doctor_pages/schedule.php">Schedule</a>
      <a href="/Booking-System-For-Medical-Clinics/public/doctor_pages/appointments.php">Appointment</a>
      <a href="/Booking-System-For-Medical-Clinics/public/doctor_pages/medical_records.php">Medical Records</a>
      <a href="/Booking-System-For-Medical-Clinics/index.php">Log out</a>
    </div>
</div>

<!-- MAIN CONTENT -->
<main>
    <div class="profile-card">
        <img src="https://cdn-icons-png.flaticon.com/512/387/387561.png">
    </div>

    <div class="doctor-info">
        <h1>Welcome Dr.</h1>
        <p>Peter Armstrong</p>
        <p>Doctor’s ID_num: 101</p>

        <a href="/Booking-System-For-Medical-Clinics/public/doctor_pages/update_info.php">
          <button class="btn-update">UPDATE INFO</button>
        </a>
    </div>
</main>

<!-- FOOTER -->
<footer>
    &copy; 2025 Medicina Clinic | All Rights Reserved
</footer>

</body>
</html>
