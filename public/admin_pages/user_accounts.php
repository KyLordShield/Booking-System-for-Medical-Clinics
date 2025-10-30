<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Users | Medicina Admin</title>
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
  text-align: center;
}

/* TABS */
.tabs {
  display: flex;
  justify-content: center;
  margin-bottom: 30px;
  flex-wrap: wrap;
  gap: 15px;
}
.tab-btn {
  background: var(--light);
  border: none;
  color: var(--primary);
  padding: 12px 25px;
  border-radius: 25px;
  cursor: pointer;
  font-size: 17px;
  font-weight: bold;
  transition: .3s;
}
.tab-btn.active, .tab-btn:hover {
  background: var(--primary);
  color: var(--white);
}

/* CREATE BUTTON */
.create-btn {
  background: var(--primary);
  color: var(--white);
  border: none;
  padding: 12px 25px;
  border-radius: 25px;
  font-size: 16px;
  font-weight: bold;
  cursor: pointer;
  transition: .3s;
  margin-bottom: 20px;
}
.create-btn:hover {
  background: #014769;
}

/* TABLE CARD */
.table-container {
  background: var(--light);
  border-radius: 25px;
  padding: 25px;
  box-shadow: 0 4px 10px rgba(0,0,0,0.1);
}
table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
  color: var(--primary);
}
th, td {
  padding: 12px 15px;
  border-bottom: 1px solid #ccc;
}
th {
  font-size: 18px;
}
td {
  font-size: 16px;
}

/* BUTTONS */
.btn {
  background: var(--primary);
  color: var(--white);
  border: none;
  padding: 8px 18px;
  border-radius: 20px;
  cursor: pointer;
  font-size: 15px;
  transition: .3s;
}
.btn:hover {
  background: #014769;
}

/* MODAL */
.modal {
  display: none;
  position: fixed;
  z-index: 2000;
  left: 0; top: 0;
  width: 100%; height: 100%;
  background: rgba(0,0,0,0.5);
  justify-content: center;
  align-items: flex-start;
  padding-top: 40px;
}
.modal-content {
  background: var(--white);
  padding: 30px;
  border-radius: 25px;
  width: 600px;
  max-width: 90%;
  max-height: 80vh;
  overflow-y: auto;
  box-shadow: 0 8px 20px rgba(0,0,0,0.3);
  position: relative;
  scrollbar-width: thin;
}
.modal-content::-webkit-scrollbar {
  width: 8px;
}
.modal-content::-webkit-scrollbar-thumb {
  background-color: #ccc;
  border-radius: 4px;
}
.modal-content h2 {
  text-align: center;
  color: var(--primary);
  margin-bottom: 20px;
}
.close-btn {
  position: absolute;
  right: 15px;
  top: 10px;
  font-size: 22px;
  cursor: pointer;
  color: var(--primary);
}

/* FORM */
.modal form {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 15px;
}
.modal label {
  font-weight: bold;
  color: var(--primary);
}
.modal input, .modal select, .modal textarea {
  width: 100%;
  padding: 8px;
  border-radius: 10px;
  border: 1px solid #ccc;
  margin-bottom: 10px;
}
.modal button {
  grid-column: span 2;
  background: var(--primary);
  color: var(--white);
  border: none;
  padding: 12px;
  border-radius: 25px;
  font-size: 16px;
  cursor: pointer;
  transition: .3s;
}
.modal button:hover {
  background: #014769;
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
  .modal form { grid-template-columns: 1fr; }
}
</style>

<script>
function showTab(tabName) {
  document.querySelectorAll('.table-container').forEach(c => c.style.display = 'none');
  document.querySelector('#' + tabName).style.display = 'block';
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  document.querySelector(`[data-tab="${tabName}"]`).classList.add('active');
}
window.onload = () => showTab('patients');

function openModal() {
  document.getElementById('createModal').style.display = 'flex';
}
function closeModal() {
  document.getElementById('createModal').style.display = 'none';
}
</script>
</head>

<body>
<!-- NAVBAR -->
<div class="navbar">
  <div class="navbar-brand">
    <img src="https://cdn-icons-png.flaticon.com/512/3209/3209999.png">
    Medicina Admin
  </div>

  <div class="nav-links">
    <a href="/Booking-System-For-Medical-Clinics/public/admin_dashboard.php">Dashboard</a>
    <a class="active" href="#">Manage Users</a>
    <a href="/Booking-System-For-Medical-Clinics/index.php">Logout</a>
  </div>
</div>

<!-- MAIN -->
<main>
  <h1>Manage Users</h1>

  <div style="text-align:right;">
    <button class="create-btn" onclick="openModal()">+ Create User</button>
  </div>

  <div class="tabs">
    <button class="tab-btn active" data-tab="patients" onclick="showTab('patients')">Patients</button>
    <button class="tab-btn" data-tab="doctors" onclick="showTab('doctors')">Doctors</button>
    <button class="tab-btn" data-tab="staff" onclick="showTab('staff')">Staff</button>
  </div>

  <!-- PATIENTS TABLE -->
  <div id="patients" class="table-container">
    <h2 style="color:#002339; margin-bottom:15px;">Patients List</h2>
    <table>
      <tr>
        <th>ID</th><th>Name</th><th>Email</th><th>Username</th><th>Password</th><th>Action</th>
      </tr>
      <tr>
        <td>1</td><td>Maria Cruz</td><td>maria@gmail.com</td><td>mariacruz</td><td>••••••</td>
        <td><button class="btn">Edit</button> <button class="btn">Delete</button></td>
      </tr>
    </table>
  </div>

  <!-- DOCTORS TABLE -->
  <div id="doctors" class="table-container" style="display:none;">
    <h2 style="color:#002339; margin-bottom:15px;">Doctors List</h2>
    <table>
      <tr>
        <th>ID</th><th>Name</th><th>Specialization</th><th>Username</th><th>Password</th><th>Action</th>
      </tr>
      <tr>
        <td>1</td><td>Dr. Ana Lim</td><td>Cardiology</td><td>dralim</td><td>••••••</td>
        <td><button class="btn">Edit</button> <button class="btn">Delete</button></td>
      </tr>
    </table>
  </div>

  <!-- STAFF TABLE -->
  <div id="staff" class="table-container" style="display:none;">
    <h2 style="color:#002339; margin-bottom:15px;">Staff List</h2>
    <table>
      <tr>
        <th>ID</th><th>Name</th><th>Role</th><th>Username</th><th>Password</th><th>Action</th>
      </tr>
      <tr>
        <td>1</td><td>Jane Dela Cruz</td><td>Receptionist</td><td>janedc</td><td>••••••</td>
        <td><button class="btn">Edit</button> <button class="btn">Delete</button></td>
      </tr>
    </table>
  </div>
</main>

<!-- CREATE USER MODAL -->
<div id="createModal" class="modal">
  <div class="modal-content">
    <span class="close-btn" onclick="closeModal()">&times;</span>
    <h2>Create User</h2>
    <form method="POST" action="create_user.php">
      <label>Last Name</label>
      <input type="text" name="lname" required>

      <label>First Name</label>
      <input type="text" name="fname" required>

      <label>M.I</label>
      <input type="text" name="mname" maxlength="1">

      <label>DOB</label>
      <input type="date" name="dob">

      <label>Sex</label>
      <select name="gender">
        <option value="">--Select--</option>
        <option>Male</option>
        <option>Female</option>
      </select>

      <label>Address</label>
      <textarea name="address" rows="2"></textarea>

      <label>Contact No.</label>
      <input type="text" name="contact">

      <label>Email</label>
      <input type="email" name="email">

      <label>Username</label>
      <input type="text" name="username" required>

      <label>Password</label>
      <input type="password" name="password" required>

      <label>Role</label>
      <select name="role" required>
        <option value="">--Select Role--</option>
        <option value="Patient">Patient</option>
        <option value="Doctor">Doctor</option>
        <option value="Staff">Staff</option>
      </select>

      <button type="submit">Create User</button>
    </form>
  </div>
</div>

<footer>
  &copy; 2025 Medicina Clinic | All Rights Reserved
</footer>

</body>
</html>
