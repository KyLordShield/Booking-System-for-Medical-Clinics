<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Medical Records</title>
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

/* MAIN CONTENT */
main {
  flex: 1;
  padding: 40px 60px;
}
h2 {
  margin-bottom: 20px;
  font-size: 35px;
  font-weight: bold;
  color: var(--primary);
}
.records-table {
  width: 100%;
  background: var(--white);
  border-radius: 12px;
  overflow: hidden;
  border: 2px solid var(--primary);
}
table {
  width: 100%;
  border-collapse: collapse;
}
thead tr {
  background: var(--primary);
  color: var(--white);
  font-size: 18px;
}
th, td {
  padding: 14px;
  text-align: center;
  border-bottom: 1px solid #c4d3dd;
}
tbody tr:hover {
  background: #bfe1eb;
}
.view-btn {
  padding: 8px 18px;
  border-radius: 20px;
  background: var(--primary);
  color: var(--white);
  text-decoration: none;
  font-size: 14px;
  transition: .3s;
}
.view-btn:hover {
  background: #00121f;
}

/* FOOTER */
footer {
  background: var(--primary);
  color: var(--white);
  text-align: center;
  padding: 15px 0;
  margin-top: 20px;
  font-size: 14px;
}

/* RESPONSIVE */
@media(max-width: 768px) {
  main {
    padding: 20px;
  }
  th, td {
    font-size: 14px;
  }
}
</style>
</head>

<body>

<!-- NAV BAR -->
<div class="navbar">
    <div class="navbar-brand">
      <img src="https://cdn-icons-png.flaticon.com/512/3209/3209999.png">
      Medicina
    </div>
    <div class="nav-links">
      <a  href="/Booking-System-For-Medical-Clinics/public/doctor_dashboard.php">Home</a>
      <a href="/Booking-System-For-Medical-Clinics/public/doctor_pages/schedule.php">Schedule</a>
      <a href="/Booking-System-For-Medical-Clinics/public/doctor_pages/appointments.php">Appointment</a>
      <a class="active" href="/Booking-System-For-Medical-Clinics/public/doctor_pages/medical_records.php">Medical Records</a>
      <a href="/Booking-System-For-Medical-Clinics/index.php">Log out</a>
    </div>
</div>

<!-- CONTENT -->
<main>
  <h2>Medical Records</h2>

  <div class="records-table">
      <table>
          <thead>
              <tr>
                  <th>Record No.</th>
                  <th>Patient Name</th>
                  <th>Diagnosis</th>
                  <th>Perscription</th>
                  <th>Visit Date</th>
                  <th>Action</th>
              </tr>
          </thead>

          <tbody>
              <tr>
                  <td>001</td>
                  <td>Maria Santos</td>
                  <td>Flu</td>
                  <td>Antiviral Medication</td>
                  <td>2025-10-05</td>
                  <td><a class="view-btn" href="/booking-system/doctor/record_view.php?id=1">View</a></td>
              </tr>
              <tr>
                  <td>002</td>
                  <td>Pedro Cruz</td>
                  <td>Stomach Pain</td>
                  <td>Antacids</td>
                  <td>2025-10-10</td>
                  <td><a class="view-btn" href="/booking-system/doctor/record_view.php?id=2">View</a></td>
              </tr>
          </tbody>
      </table>
  </div>
</main>

<!-- FOOTER -->
<footer>
  &copy; 2025 Medicina Clinic | All Rights Reserved
</footer>

</body>
</html>
