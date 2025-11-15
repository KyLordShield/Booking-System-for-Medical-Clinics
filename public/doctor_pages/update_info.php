<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Update Profile | Medicina</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- ✅ Tailwind CDN -->
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- ✅ Custom Global CSS -->
  <link rel="stylesheet" href="/Booking-System-For-Medical-Clinics/assets/css/style.css">
</head>

<body class="bg-[var(--secondary)] min-h-screen flex flex-col font-[Georgia]">

  <!-- NAVBAR -->
<!-- ✅ HEADER LINK -->
  <?php include dirname(__DIR__, 2) . "/partials/header.php"; ?>
<!--  HEADER LINK -->

  <!-- ✅ MAIN CONTENT -->
  <main class="flex-1 p-10">
    <h2 class="text-[38px] font-bold text-[var(--primary)] mb-6">Update Profile</h2>

    <div class="form-box bg-[var(--white)] border-2 border-[var(--primary)] rounded-xl p-8 max-w-3xl mx-auto shadow-md">
      <form action="#" method="POST" class="grid grid-cols-1 gap-6">

        <div>
          <label class="text-[var(--primary)] font-semibold">Doctor First Name</label>
          <input type="text" name="fname" placeholder="Enter first name" required
                 class="w-full border border-gray-300 rounded-lg px-4 py-3 mt-2 focus:outline-none">
        </div>

        <div>
          <label class="text-[var(--primary)] font-semibold">Doctor Last Name</label>
          <input type="text" name="lname" placeholder="Enter last name" required
                 class="w-full border border-gray-300 rounded-lg px-4 py-3 mt-2 focus:outline-none">
        </div>

        <div>
          <label class="text-[var(--primary)] font-semibold">Email</label>
          <input type="email" name="email" placeholder="Enter email" required
                 class="w-full border border-gray-300 rounded-lg px-4 py-3 mt-2 focus:outline-none">
        </div>

        <div>
          <label class="text-[var(--primary)] font-semibold">Contact Number</label>
          <input type="text" name="contact" placeholder="Enter contact number" required
                 class="w-full border border-gray-300 rounded-lg px-4 py-3 mt-2 focus:outline-none">
        </div>

        <div>
          <label class="text-[var(--primary)] font-semibold">Specialization</label>
          <input type="text" name="specialization" placeholder="Enter specialization" required
                 class="w-full border border-gray-300 rounded-lg px-4 py-3 mt-2 focus:outline-none">
        </div>

        <button type="submit"
                class="save-btn bg-[var(--primary)] text-[var(--white)] text-lg font-semibold py-3 rounded-xl hover:opacity-90 transition">
          SAVE CHANGES
        </button>
      </form>
    </div>
  </main>

  <!-- ✅ FOOTER -->
  <?php include dirname(__DIR__, 2) . "/partials/footer.php"; ?>


</body>
</html>
