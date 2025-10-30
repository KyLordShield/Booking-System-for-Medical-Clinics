<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Doctor Schedule</title>
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
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

/* NAV */
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
  padding: 9px 18px;
  border-radius: 30px;
  transition: .3s ease;
}
.nav-links a:hover,
.nav-links a.active {
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
  color: var(--primary);
  margin-bottom: 25px;
}

/* ADD FORM */
.form-container {
  background: var(--white);
  padding: 25px;
  border-radius: 15px;
  margin-bottom: 30px;
  border: 2px solid var(--primary);
}
label {
  font-size: 16px;
  font-weight: bold;
}
input, select {
  width: 100%;
  padding: 10px;
  margin: 8px 0 15px;
  border-radius: 8px;
  border: 1px solid #888;
  font-size: 15px;
}
.add-btn {
  background: var(--primary);
  color: var(--white);
  border: none;
  width: 100%;
  padding: 12px;
  border-radius: 12px;
  font-size: 16px;
  font-weight: bold;
  cursor: pointer;
}
.add-btn:hover {
  background: #00121f;
}

/* TABLE */
.table-container {
  background: var(--white);
  padding: 15px;
  border-radius: 12px;
  border: 2px solid var(--primary);
}
table { width: 100%; border-collapse: collapse; }
thead tr {
  background: var(--primary);
  color: var(--white);
}
th, td {
  padding: 12px;
  text-align: center;
  border-bottom: 1px solid #c4d3dd;
}
.delete-btn {
  background: #b30000;
  color: var(--white);
  padding: 6px 15px;
  border-radius: 20px;
  font-size: 13px;
  font-weight: bold;
  text-decoration: none;
}
.delete-btn:hover {
  background: #800000;
}

/* FOOTER */
footer {
  background: var(--primary);
  color: var(--white);
  text-align: center;
  padding: 15px 0;
}
</style>

</head>
<body>

<!-- NAVIGATION -->
<div class="navbar">
  <div class="navbar-brand">
    <img src="https://cdn-icons-png.flaticon.com/512/3209/3209999.png">
    Medicina
  </div>
  <div class="nav-links">
    <a href="/Booking-System-For-Medical-Clinics/public/doctor_dashboard.php">Home</a>
      <a class="active" href="/Booking-System-For-Medical-Clinics/public/doctor_pages/schedule.php">Schedule</a>
      <a href="/Booking-System-For-Medical-Clinics/public/doctor_pages/appointments.php">Appointment</a>
      <a href="/Booking-System-For-Medical-Clinics/public/doctor_pages/medical_records.php">Medical Records</a>
      <a href="/Booking-System-For-Medical-Clinics/index.php">Log out</a>
  </div>
</div>

<main>

<h2>Doctor Schedule</h2>

<!-- ADD SCHEDULE FORM -->
<div class="form-container">
  <form action="#" method="POST">
    
    <label>Day</label>
    <select name="day">
      <option value="Monday">Monday</option>
      <option value="Tuesday">Tuesday</option>
      <option value="Wednesday">Wednesday</option>
      <option value="Thursday">Thursday</option>
      <option value="Friday">Friday</option>
      <option value="Saturday">Saturday</option>
    </select>

    <label>Start Time</label>
    <input type="time" name="start_time" required>

    <label>End Time</label>
    <input type="time" name="end_time" required>

    <button class="add-btn">ADD SCHEDULE</button>

  </form>
</div>

<!-- SCHEDULE TABLE -->
<div class="table-container">
  <table>
    <thead>
      <tr>
        <th>Day</th>
        <th>Start</th>
        <th>End</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>Monday</td>
        <td>08:00 AM</td>
        <td>05:00 PM</td>
        <td><a href="#" class="delete-btn">Remove</a></td>
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
