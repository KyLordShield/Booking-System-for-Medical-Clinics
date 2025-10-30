<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Update Profile</title>
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
.nav-links a:hover,
.nav-links a.active {
  background: var(--light);
  color: var(--primary);
}

/* MAIN CONTENT */
main {
  flex: 1;
  padding: 40px 60px;
}
h2 {
  font-size: 35px;
  font-weight: bold;
  color: var(--primary);
  margin-bottom: 20px;
}

/* FORM BOX */
.form-box {
  background: var(--white);
  padding: 30px;
  border-radius: 12px;
  width: 100%;
  border: 2px solid var(--primary);
}
label {
  font-weight: bold;
  font-size: 16px;
}
input {
  width: 100%;
  padding: 12px;
  margin: 10px 0 20px;
  border-radius: 8px;
  border: 1px solid #777;
  font-size: 15px;
}
.save-btn {
  background: var(--primary);
  color: var(--white);
  border: none;
  width: 100%;
  padding: 12px;
  font-size: 17px;
  border-radius: 12px;
  cursor: pointer;
}
.save-btn:hover {
  background: #00121f;
}

/* FOOTER */
footer {
  background: var(--primary);
  color: var(--white);
  text-align: center;
  padding: 15px 0;
}

/* RESPONSIVE */
@media(max-width: 768px) {
  main { padding: 20px; }
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
    <a href="/booking-system/doctor/dashboard.php">Home</a>
    <a href="/booking-system/doctor/appointment.php">Appointment</a>
    <a href="/booking-system/doctor/medical_records.php">Medical Records</a>
    <a href="/booking-system/doctor/schedule.php">Schedule</a>
    <a class="active" href="/booking-system/doctor/update_info.php">Update Info</a>
    <a href="/booking-system/logout.php">Log out</a>
  </div>
</div>

<!-- CONTENT -->
<main>
  <h2>Update Profile</h2>

  <div class="form-box">
    <form action="#" method="POST">

      <label>Doctor First Name</label>
      <input type="text" name="fname" placeholder="Enter first name" required>

      <label>Doctor Last Name</label>
      <input type="text" name="lname" placeholder="Enter last name" required>

      <label>Email</label>
      <input type="email" name="email" placeholder="Enter email" required>

      <label>Contact Number</label>
      <input type="text" name="contact" placeholder="Enter contact number" required>

      <label>Specialization</label>
      <input type="text" name="specialization" placeholder="Enter specialization" required>

      <button class="save-btn">SAVE CHANGES</button>

    </form>
  </div>

</main>

<!-- FOOTER -->
<footer>
  &copy; 2025 Medicina Clinic | All Rights Reserved
</footer>

</body>
</html>
