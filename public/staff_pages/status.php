<?php
// ✅ TODO: Restrict to Staff role only
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Status Management | Staff Dashboard</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<!-- ✅ Tailwind CSS -->
<script src="https://cdn.tailwindcss.com"></script>

<!-- ✅ Custom CSS -->
<link rel="stylesheet" href="/Booking-System-For-Medical-Clinics/assets/css/style.css">
</head>
<body class="bg-[var(--secondary)] min-h-screen flex flex-col font-[Georgia]">

<!-- ✅ NAVBAR -->
<div class="navbar flex justify-between items-center px-10 py-5 bg-[var(--primary)] rounded-b-[35px] shadow-lg">
  <div class="navbar-brand flex items-center text-white text-2xl font-bold">
    <img src="https://cdn-icons-png.flaticon.com/512/3209/3209999.png" alt="Medicina Logo" class="w-11 mr-3">
    Medicina
  </div>

  <div class="nav-links flex gap-4">
    <a href="/Booking-System-For-Medical-Clinics/public/staff_dashboard.php">Home</a>
    <a href="/Booking-System-For-Medical-Clinics/public/staff_pages/staff_manage.php">Staff</a>
    <a href="/Booking-System-For-Medical-Clinics/public/staff_pages/services.php">Services</a>
    <a class="active" href="/Booking-System-For-Medical-Clinics/public/staff_pages/status.php">Status</a>
    <a href="/Booking-System-For-Medical-Clinics/public/staff_pages/payments.php">Payments</a>
    <a href="/Booking-System-For-Medical-Clinics/public/staff_pages/specialization.php">Specialization</a>
    <a href="/Booking-System-For-Medical-Clinics/public/staff_pages/smedical_records.php">Medical Records</a>
    <a href="/Booking-System-For-Medical-Clinics/index.php">Log out</a>
  </div>
</div>

<!-- ✅ MAIN CONTENT -->
<main class="flex-1 px-10 py-10">
  <h2 class="text-[36px] font-bold text-[var(--primary)] mb-6">Appointment Status Management</h2>

  <!-- Top Controls: Search + Buttons -->
  <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
    <div class="search-box w-full sm:w-[300px]">
      <input type="text" placeholder="Search status..." class="w-full px-4 py-2 rounded-full border-none focus:ring-2 focus:ring-[var(--primary)] outline-none text-[16px]">
    </div>

    <div class="flex flex-wrap gap-2">
      <a href="add_status.php"><button class="create-btn">+ Add Status</button></a>
    </div>
  </div>

  <!-- Table -->
  <div class="table-container bg-[var(--light)] p-6 rounded-[25px] shadow-md">
    <table class="w-full border-collapse text-[var(--primary)]">
      <thead>
        <tr class="border-b border-gray-300">
          <th class="py-3 px-4 text-left">Status ID</th>
          <th class="py-3 px-4 text-left">Status Name</th>
          <th class="py-3 px-4 text-left">Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr class="border-b border-gray-300 hover:bg-gray-50">
          <td colspan="3" class="py-6 text-center text-gray-500">No status data...</td>
        </tr>
      </tbody>
    </table>
  </div>
</main>

<!-- ✅ FOOTER -->
<footer class="bg-[var(--primary)] text-[var(--white)] text-center py-4 rounded-t-[35px] text-sm mt-6">
  &copy; 2025 Medicina Clinic | All Rights Reserved
</footer>

</body>
</html>
