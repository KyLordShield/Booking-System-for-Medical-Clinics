<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Appointments | Medicina</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- ✅ Tailwind CSS CDN -->
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- ✅ Global Shared Styles -->
  <link rel="stylesheet" href="/Booking-System-For-Medical-Clinics/assets/css/style.css">
</head>
<body class="bg-[var(--secondary)] flex flex-col min-h-screen">

  <!-- ✅ NAVBAR -->
  <div class="navbar flex justify-between items-center bg-[var(--primary)] px-10 py-4 rounded-b-[35px] shadow-lg">
    <div class="navbar-brand flex items-center text-[var(--white)] text-2xl font-bold">
      <img src="https://cdn-icons-png.flaticon.com/512/3209/3209999.png" alt="Medicina Logo" class="w-10 mr-3">
      Medicina
    </div>
    <div class="nav-links flex gap-6">
      <a href="/Booking-System-For-Medical-Clinics/public/doctor_dashboard.php">Home</a>
      <a href="doctor_manage.php">Doctor</a>
      <a href="/Booking-System-For-Medical-Clinics/public/doctor_pages/schedule.php">Schedule</a>
      <a class="active" href="/Booking-System-For-Medical-Clinics/public/doctor_pages/appointments.php">Appointments</a>
      <a href="/Booking-System-For-Medical-Clinics/public/doctor_pages/medical_records.php">Medical Records</a>
      <a href="/Booking-System-For-Medical-Clinics/index.php">Logout</a>
    </div>
  </div>

  <!-- ✅ MAIN CONTENT -->
  <main class="flex-1 p-10">
    <h1 class="text-[38px] font-bold text-[var(--primary)] mb-8">Appointments</h1>

    <!-- ✅ Tabs -->
    <div class="tabs flex flex-wrap gap-4 mb-6">
      <button class="tab-btn active" data-tab="today" onclick="showTab('today')">Today</button>
      <button class="tab-btn" data-tab="upcoming" onclick="showTab('upcoming')">Upcoming</button>
      <button class="tab-btn" data-tab="completed" onclick="showTab('completed')">Completed</button>
    </div>

    <!-- ✅ TODAY -->
    <div id="today" class="table-container bg-[var(--light)] p-6 rounded-[25px] shadow-md">
      <h2 class="text-[var(--primary)] mb-4 text-2xl font-semibold">Today’s Appointments</h2>
      <table class="w-full border-collapse text-[var(--primary)]">
        <thead>
          <tr class="border-b border-gray-300">
            <th class="py-3 px-4 text-left">Time</th>
            <th class="py-3 px-4 text-left">Patient</th>
            <th class="py-3 px-4 text-left">Service</th>
            <th class="py-3 px-4 text-left">Action</th>
          </tr>
        </thead>
        <tbody>
          <tr class="border-b border-gray-300 hover:bg-gray-50">
            <td class="py-3 px-4">09:00 AM</td>
            <td class="py-3 px-4">Maria Santos</td>
            <td class="py-3 px-4">Consultation</td>
            <td class="py-3 px-4 flex gap-2">
              <button class="btn bg-green-700 hover:bg-green-800">Done</button>
              <button class="btn bg-red-700 hover:bg-red-800">Cancel</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- ✅ UPCOMING -->
    <div id="upcoming" class="table-container bg-[var(--light)] p-6 rounded-[25px] shadow-md mt-6 hidden">
      <h2 class="text-[var(--primary)] mb-4 text-2xl font-semibold">Upcoming Appointments</h2>
      <table class="w-full border-collapse text-[var(--primary)]">
        <thead>
          <tr class="border-b border-gray-300">
            <th class="py-3 px-4 text-left">Time</th>
            <th class="py-3 px-4 text-left">Patient</th>
            <th class="py-3 px-4 text-left">Service</th>
            <th class="py-3 px-4 text-left">Date</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td colspan="4" class="py-3 px-4 text-center text-gray-500">No upcoming appointments</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- ✅ COMPLETED -->
    <div id="completed" class="table-container bg-[var(--light)] p-6 rounded-[25px] shadow-md mt-6 hidden">
      <h2 class="text-[var(--primary)] mb-4 text-2xl font-semibold">Completed Appointments</h2>
      <table class="w-full border-collapse text-[var(--primary)]">
        <thead>
          <tr class="border-b border-gray-300">
            <th class="py-3 px-4 text-left">Time</th>
            <th class="py-3 px-4 text-left">Patient</th>
            <th class="py-3 px-4 text-left">Service</th>
            <th class="py-3 px-4 text-left">Date</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td colspan="4" class="py-3 px-4 text-center text-gray-500">No appointments completed yet</td>
          </tr>
        </tbody>
      </table>
    </div>
  </main>

  <!-- ✅ FOOTER -->
  <footer class="bg-[var(--primary)] text-[var(--white)] text-center py-4 rounded-t-[35px] text-sm mt-6">
    &copy; 2025 Medicina Clinic | All Rights Reserved
  </footer>

  <!-- ✅ JS for Tabs -->
  <script>
    function showTab(tabName) {
      document.querySelectorAll('.table-container').forEach(c => c.classList.add('hidden'));
      document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
      document.getElementById(tabName).classList.remove('hidden');
      document.querySelector(`[data-tab="${tabName}"]`).classList.add('active');
    }
    window.onload = () => showTab('today');
  </script>
</body>
</html>
