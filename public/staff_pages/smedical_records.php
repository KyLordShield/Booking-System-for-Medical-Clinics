<?php
// ✅ TODO: Staff access check later
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Medical Records</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<style>
/* ✅ SAME DESIGN AS OTHER STAFF PAGES */
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
  margin-bottom: 25px;
}
.search-box {
  margin-bottom: 20px;
}
.search-box input {
  padding: 8px 15px;
  border-radius: 20px;
  border: none;
}
.table-container {
  background: var(--white);
  padding: 20px;
  border-radius: 20px;
}
table {
  width: 100%;
  border-collapse: collapse;
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

/* ✅ Consistent button style */
.view-btn {
  padding: 6px 12px;
  background: var(--primary);
  color: var(--white);
  border-radius: 20px;
  font-size: 12px;
  text-decoration: none;
}
.view-btn:hover {
  background: var(--secondary);
}

footer {
  background: var(--primary);
  color: var(--white);
  text-align: center;
  padding: 20px 0;
  font-size: 14px;
}
</style>
</head>

<body>

<!-- ✅ NAVIGATION BAR -->
<div class="navbar">
    <div class="navbar-brand">
      <img src="https://cdn-icons-png.flaticon.com/512/3209/3209999.png">
      Medicina
    </div>
    <div class="nav-links">
      <a href="/Booking-System-For-Medical-Clinics/public/staff_dashboard.php">Home</a>
      <a href="staff_manage.php">Staff</a>
      <a href="users_manage.php">Users</a>
      <a href="services.php">Services</a>
      <a href="status.php">Status</a>
      <a href="payments.php">Payments</a>
      <a href="specialization.php">Specialization</a>
      <a class="active" href="#">Medical Records</a>
      <a href="/Booking-System-For-Medical-Clinics/index.php">Log out</a>
    </div>
</div>

<!-- ✅ CONTENT -->
<main>
  <div class="page-title">Medical Records</div>

  <div class="search-box">
    <input type="text" placeholder="Search record / patient name">
  </div>

  <div class="table-container">
      <table>
          <thead>
              <tr>
                  <th>Record ID</th>
                  <th>Patient Name</th>
                  <th>Diagnosis</th>
                  <th>Prescription</th>
                  <th>Visit Date</th>
                  <th>Action</th>
              </tr>
          </thead>
          <tbody>
              <tr>
                <td colspan="6" style="text-align:center;">No medical records found...</td>
              </tr>
          </tbody>
      </table>
  </div>
</main>

<footer>
  &copy; 2025 Medicina Clinic | All Rights Reserved
</footer>

</body>
</html>
