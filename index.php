<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Landing Page</title>
  <link rel="stylesheet" href="/Booking-System-For-Medical-Clinics/assets/css/landing.css" />
  <link rel="stylesheet" href="/Booking-System-For-Medical-Clinics/assets/css/style.css">
</head>
<body>

  <!-- ===== Header ===== -->
  <?php
// Include the guest header at the very top
include __DIR__ . '/partials/guest_header.php';
?>

</header>
  <!-- ===== Welcome Section (Home) ===== -->
  <section id="home" class="welcome-section">
    <div class="text-content">
      <h2>Welcome to Medicina</h2>
      <div class="welcome-message">
        <p>
          Your trusted online platform for managing medical records and consultations.
          <br>With Medicina, you can easily track patient information, doctor schedules, and consultation histories in one organized system.
          Stay efficient, connected, and secure — anytime, anywhere.
        </p>
      </div>
    </div>

    <div class="image-container">
      <video autoplay muted loop playsinline class="doctor-video">
        <source src="/Booking-System-For-Medical-Clinics/assets/images/doctor_video.mp4" type="video/mp4" />
        Your browser does not support the video tag.
      </video>
    </div>
  </section>

  <!-- ===== About Section ===== -->
  <section id="about" class="info-section">
    <div class="info-boxes">
      <div class="box">
        <img src="/Booking-System-For-Medical-Clinics/assets/images/profile3.png" alt="profile" />
        <div class="overlay">Lumayaga, Francis Kyle</div>
      </div>
      <div class="box">
        <img src="/Booking-System-For-Medical-Clinics/assets/images/profile2.png" alt="profile" />
        <div class="overlay">Olaran, Patrich</div>
      </div>
      <div class="box">
        <img src="/Booking-System-For-Medical-Clinics/assets/images/profile1.png" alt="profile" />
        <div class="overlay">Libando, Rejean Mae A.</div>
      </div>
    </div>

    <div class="name-boxes">
      <div class="name-card">PLANNING/FULL STACK</div>
      <div class="name-card">BACK END</div>
      <div class="name-card">WEB DESIGNER</div>
    </div>
  </section>

</body>
</html>
