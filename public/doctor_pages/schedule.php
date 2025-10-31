<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Doctor Schedule | Medicina</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- ✅ Tailwind CDN -->
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- ✅ Custom CSS -->
  <link rel="stylesheet" href="/Booking-System-For-Medical-Clinics/assets/css/style.css">
</head>

<body class="bg-[var(--secondary)] min-h-screen flex flex-col">

  <!-- ✅ NAVIGATION -->
  <div class="navbar flex justify-between items-center px-10 py-4 rounded-b-[35px] bg-[var(--primary)] shadow-lg">
    <div class="navbar-brand flex items-center text-white text-2xl font-bold">
      <img src="https://cdn-icons-png.flaticon.com/512/3209/3209999.png" alt="Medicina Logo" class="w-10 mr-3">
      Medicina
    </div>
    <div class="nav-links flex gap-6">
      <a href="/Booking-System-For-Medical-Clinics/public/doctor_dashboard.php">Home</a>
      <a href="doctor_manage.php">Doctor</a>
      <a class="active" href="/Booking-System-For-Medical-Clinics/public/doctor_pages/schedule.php">Schedule</a>
      <a href="/Booking-System-For-Medical-Clinics/public/doctor_pages/appointments.php">Appointment</a>
      <a href="/Booking-System-For-Medical-Clinics/public/doctor_pages/medical_records.php">Medical Records</a>
      <a href="/Booking-System-For-Medical-Clinics/index.php">Log out</a>
    </div>
  </div>

  <!-- ✅ MAIN CONTENT -->
  <main class="flex-1 p-10">
    <h2 class="text-[38px] font-bold text-[var(--primary)] mb-6">Doctor Schedule</h2>

    <!-- ADD SCHEDULE FORM -->
    <div class="table-container bg-[var(--light)] p-6 rounded-[25px] shadow-md">
      <form action="#" method="POST" class="flex flex-wrap gap-4 items-center">
        <div class="flex flex-col">
          <label class="text-[var(--primary)] font-semibold mb-1">Day</label>
          <select name="day" required class="border border-gray-300 rounded-lg px-3 py-2 focus:outline-none">
            <option value="Monday">Monday</option>
            <option value="Tuesday">Tuesday</option>
            <option value="Wednesday">Wednesday</option>
            <option value="Thursday">Thursday</option>
            <option value="Friday">Friday</option>
            <option value="Saturday">Saturday</option>
          </select>
        </div>

        <div class="flex flex-col">
          <label class="text-[var(--primary)] font-semibold mb-1">Start Time</label>
          <input type="time" name="start_time" required class="border border-gray-300 rounded-lg px-3 py-2 focus:outline-none">
        </div>

        <div class="flex flex-col">
          <label class="text-[var(--primary)] font-semibold mb-1">End Time</label>
          <input type="time" name="end_time" required class="border border-gray-300 rounded-lg px-3 py-2 focus:outline-none">
        </div>

        <button type="submit" class="create-btn bg-[var(--primary)] text-white px-6 py-2 rounded-xl hover:opacity-90 transition">
          Add Schedule
        </button>
      </form>
    </div>

    <!-- ✅ SCHEDULE TABLE -->
    <div class="table-container bg-[var(--light)] p-6 rounded-[25px] shadow-md mt-8">
      <table class="w-full border-collapse text-[var(--primary)]">
        <thead>
          <tr class="border-b border-gray-300">
            <th class="py-3 px-4 text-left text-lg">Day</th>
            <th class="py-3 px-4 text-left text-lg">Start</th>
            <th class="py-3 px-4 text-left text-lg">End</th>
            <th class="py-3 px-4 text-left text-lg">Action</th>
          </tr>
        </thead>
        <tbody>
          <tr class="border-b border-gray-300 hover:bg-gray-50">
            <td class="py-3 px-4">Monday</td>
            <td class="py-3 px-4">08:00 AM</td>
            <td class="py-3 px-4">05:00 PM</td>
            <td class="py-3 px-4">
              <button class="btn bg-[#b30000] text-white px-4 py-1 rounded-lg hover:opacity-90 transition">Remove</button>
            </td>
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
