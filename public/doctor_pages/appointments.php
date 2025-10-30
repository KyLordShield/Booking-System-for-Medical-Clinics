<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Appointments</title>
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

/* MAIN */
main {
  flex: 1;
  padding: 40px 60px;
}

h2 {
  font-size: 35px;
  font-weight: bold;
  margin-bottom: 20px;
  color: var(--primary);
}

/* Tabs */
.tabs {
  display: flex;
  gap: 20px;
  margin-bottom: 20px;
}
.tab-btn {
  background: var(--primary);
  color: var(--white);
  padding: 10px 25px;
  border-radius: 25px;
  font-size: 15px;
  border: none;
  cursor: pointer;
  transition: .3s;
}
.tab-btn.active, .tab-btn:hover {
  background: #00121f;
}

/* Table */
.app-table {
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
}
th, td {
  padding: 12px;
  text-align: center;
  border-bottom: 1px solid #c4d3dd;
}
tbody tr:hover {
  background: #bfe1eb;
}

/* Action Buttons */
.status-btn {
  padding: 6px 15px;
  border-radius: 20px;
  font-size: 13px;
  text-decoration: none;
  font-weight: bold;
  color: var(--white);
}
.btn-complete { background: #2e8b57; }
.btn-cancel { background: #b30000; }

/* FOOTER */
footer {
  background: var(--primary);
  color: var(--white);
  text-align: center;
  padding: 15px 0;
  font-size: 14px;
}

/* HIDE Tabs */
.tab-content {
  display: none;
}
.tab-content.active {
  display: block;
}

/* RESPONSIVE */
@media(max-width: 768px) {
  main { padding: 20px; }
  .tabs { flex-direction: column; gap: 10px; }
}
</style>

</head>
<body>

<!-- NAVBAR -->
<div class="navbar">
    <div class="navbar-brand">
      <img src="https://cdn-icons-png.flaticon.com/512/3209/3209999.png">
      Medicina
    </div>
    <div class="nav-links">
      <a  href="/Booking-System-For-Medical-Clinics/public/doctor_dashboard.php">Home</a>
      <a href="/Booking-System-For-Medical-Clinics/public/doctor_pages/schedule.php">Schedule</a>
      <a class="active" href="/Booking-System-For-Medical-Clinics/public/doctor_pages/appointments.php">Appointment</a>
      <a href="/Booking-System-For-Medical-Clinics/public/doctor_pages/medical_records.php">Medical Records</a>
      <a href="/Booking-System-For-Medical-Clinics/index.php">Log out</a>
    </div>
</div>

<!-- MAIN CONTENT -->
<main>
  <h2>Appointments</h2>

  <!-- Tabs -->
  <div class="tabs">
    <button class="tab-btn active" onclick="showTab('today')">Today</button>
    <button class="tab-btn" onclick="showTab('upcoming')">Upcoming</button>
    <button class="tab-btn" onclick="showTab('completed')">Completed</button>
  </div>

  <!-- TODAY -->
  <div id="today" class="tab-content active">
    <div class="app-table">
      <table>
        <thead>
          <tr>
            <th>Time</th>
            <th>Patient</th>
            <th>Service</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>09:00 AM</td>
            <td>Maria Santos</td>
            <td>Consultation</td>
            <td>
              <a href="#" class="status-btn btn-complete">Done</a>
              <a href="#" class="status-btn btn-cancel">Cancel</a>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <!-- UPCOMING -->
  <div id="upcoming" class="tab-content">
    <div class="app-table">
      <table>
        <thead>
          <tr>
            <th>Time</th>
            <th>Patient</th>
            <th>Service</th>
            <th>Date</th>
          </tr>
        </thead>
        <tbody>
          <tr><td colspan="4">No upcoming appointments</td></tr>
        </tbody>
      </table>
    </div>
  </div>

  <!-- COMPLETED -->
  <div id="completed" class="tab-content">
    <div class="app-table">
      <table>
        <thead>
          <tr>
            <th>Time</th>
            <th>Patient</th>
            <th>Service</th>
            <th>Date</th>
          </tr>
        </thead>
        <tbody>
          <tr><td colspan="4">No appointments completed yet</td></tr>
        </tbody>
      </table>
    </div>
  </div>

</main>

<!-- FOOTER -->
<footer>
  &copy; 2025 Medicina Clinic | All Rights Reserved
</footer>

<script>
function showTab(tab) {
  document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  document.getElementById(tab).classList.add('active');
  event.target.classList.add('active');
}
</script>

</body>
</html>
