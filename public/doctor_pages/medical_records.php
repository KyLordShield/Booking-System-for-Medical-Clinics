<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Medical Records</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- ✅ Tailwind CDN -->
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- ✅ Custom CSS -->
  <link rel="stylesheet" href="/Booking-System-For-Medical-Clinics/assets/css/style.css">
</head>
<body class="bg-[var(--secondary)] min-h-screen flex flex-col">

  <!-- ✅ NAV BAR -->
  <div class="navbar flex justify-between items-center px-10 py-4 rounded-b-[35px] bg-[var(--primary)] shadow-lg">
    <div class="navbar-brand flex items-center text-white text-2xl font-bold">
      <img src="https://cdn-icons-png.flaticon.com/512/3209/3209999.png" alt="Logo" class="w-10 mr-3">
      Medicina
    </div>
    <div class="nav-links flex gap-6">
      <a href="/Booking-System-For-Medical-Clinics/public/doctor_dashboard.php">Home</a>
      <a href="doctor_manage.php">Doctor</a>
      <a href="/Booking-System-For-Medical-Clinics/public/doctor_pages/schedule.php">Schedule</a>
      <a href="/Booking-System-For-Medical-Clinics/public/doctor_pages/appointments.php">Appointment</a>
      <a class="active" href="/Booking-System-For-Medical-Clinics/public/doctor_pages/medical_records.php">Medical Records</a>
      <a href="/Booking-System-For-Medical-Clinics/index.php">Log out</a>
    </div>
  </div>

  <!-- ✅ CONTENT -->
  <main class="flex-1 p-10">
    <h2 class="text-[38px] font-bold text-[var(--primary)] mb-6">Medical Records</h2>

    <div class="table-container bg-[var(--light)] p-6 rounded-[25px] shadow-md">
      <table class="w-full border-collapse text-[var(--primary)]">
        <thead>
          <tr class="border-b border-gray-300">
            <th class="py-3 px-4 text-left text-lg">Record No.</th>
            <th class="py-3 px-4 text-left text-lg">Patient Name</th>
            <th class="py-3 px-4 text-left text-lg">Diagnosis</th>
            <th class="py-3 px-4 text-left text-lg">Prescription</th>
            <th class="py-3 px-4 text-left text-lg">Visit Date</th>
            <th class="py-3 px-4 text-left text-lg">Action</th>
          </tr>
        </thead>
        <tbody>
          <tr class="border-b border-gray-300 hover:bg-gray-50">
            <td class="py-3 px-4">001</td>
            <td class="py-3 px-4">Maria Santos</td>
            <td class="py-3 px-4">Flu</td>
            <td class="py-3 px-4">Antiviral Medication</td>
            <td class="py-3 px-4">2025-10-05</td>
            <td class="py-3 px-4"><button class="btn">View</button></td>
          </tr>
          <tr class="border-b border-gray-300 hover:bg-gray-50">
            <td class="py-3 px-4">002</td>
            <td class="py-3 px-4">Pedro Cruz</td>
            <td class="py-3 px-4">Stomach Pain</td>
            <td class="py-3 px-4">Antacids</td>
            <td class="py-3 px-4">2025-10-10</td>
            <td class="py-3 px-4"><button class="btn">View</button></td>
          </tr>
        </tbody>
      </table>
    </div>
  </main>

  <!-- ✅ FOOTER -->
  <footer class="bg-[var(--primary)] text-[var(--white)] text-center py-4 rounded-t-[35px] text-sm">
    &copy; 2025 Medicina Clinic | All Rights Reserved
  </footer>

</body>
</html>
