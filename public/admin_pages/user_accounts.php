<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Users | Medicina Admin</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- ✅ Tailwind CSS CDN -->
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- ✅ External Style -->
  <link rel="stylesheet" href="/Booking-System-For-Medical-Clinics/assets/css/style.css"> 
  <!-- adjust path if style.css is in another folder -->

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

<body class="font-serif bg-[var(--secondary)] flex flex-col min-h-screen">

  <!-- NAVBAR -->
  <div class="navbar">
    <div class="navbar-brand">
      <img src="https://cdn-icons-png.flaticon.com/512/3209/3209999.png" alt="Logo">
      Medicina Admin
    </div>

    <div class="nav-links">
      <a href="/Booking-System-For-Medical-Clinics/public/admin_dashboard.php">Dashboard</a>
      <a class="active" href="#">Manage Users</a>
      <a href="/Booking-System-For-Medical-Clinics/index.php">Logout</a>
    </div>
  </div>

  <!-- MAIN -->
  <main class="flex-1 p-16">
    <h1 class="text-center text-[var(--primary)] text-4xl font-bold mb-8">Manage Users</h1>

    <div class="text-right mb-5">
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
    <div id="doctors" class="table-container hidden">
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
    <div id="staff" class="table-container hidden">
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
