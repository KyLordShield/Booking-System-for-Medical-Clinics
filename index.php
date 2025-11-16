<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Medicina — Smart Medical Appointment Platform</title>

  <!-- ONLY ONE CSS FILE -->
  <link rel="stylesheet" href="/Booking-System-For-Medical-Clinics/assets/css/landing.css">

  <!-- AOS Animation -->
  <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
</head>

<body class="landing-page">

  <!-- HEADER -->
  <?php include __DIR__ . '/partials/guest_header.php'; ?>

  <!-- ========== HERO SECTION ========== -->
  <section id="home" class="hero-section">
    <div class="hero-content" data-aos="fade-right">
      <h1>Your Modern Medical Appointment Platform</h1>
      <p>
        Manage appointments, patient records, doctor schedules, and consultations —
        all in one fast, secure, and intuitive platform designed for clinics of all sizes.
      </p>
      <a href="/Booking-System-For-Medical-Clinics/login_page.php" class="cta-btn">Get Started</a>
    </div>

    <div class="hero-media" data-aos="fade-left">
      <video autoplay muted loop playsinline class="doctor-video">
        <source src="/Booking-System-For-Medical-Clinics/assets/images/doctor_video.mp4" type="video/mp4" />
        Your browser does not support the video tag.
      </video>
    </div>
  </section>

 <!-- ========== FEATURES SECTION ========== -->
<section class="features-section">
  <h2 data-aos="fade-up">Powerful Features for Modern Clinics</h2>

  <div class="features-grid">

    <!-- Smart Scheduling -->
    <div class="feature-box" data-aos="fade-up" data-aos-delay="100">
      <img 
        src="https://images.pexels.com/photos/6823398/pexels-photo-6823398.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1"
        alt="Doctor scheduling appointments on digital calendar"
        loading="lazy"
      >
      <h3>Smart Scheduling</h3>
      <p>Manage appointments efficiently with real-time updates and automated tracking.</p>
    </div>

    <!-- Digital Records -->
    <div class="feature-box" data-aos="fade-up" data-aos-delay="200">
      <img 
        src="https://images.pexels.com/photos/4266938/pexels-photo-4266938.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1"
        alt="Doctor and patient reviewing digital medical records"
        loading="lazy"
      >
      <h3>Digital Records</h3>
      <p>Secure patient records that are easy to access, update, and organize.</p>
    </div>

    <!-- Doctor Dashboard -->
    <div class="feature-box" data-aos="fade-up" data-aos-delay="300">
      <img 
        src="https://images.pexels.com/photos/3952137/pexels-photo-3952137.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1"
        alt="Doctor reviewing dashboard data on clinic computer"
        loading="lazy"
      >
      <h3>Doctor Dashboard</h3>
      <p>Doctors get a clean dashboard to view schedules, consultations, and history.</p>
    </div>

    <!-- Secure System -->
    <div class="feature-box" data-aos="fade-up" data-aos-delay="400">
      <img 
        src="https://images.pexels.com/photos/5327656/pexels-photo-5327656.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1"
        alt="Healthcare worker ensuring secure medical data safety"
        loading="lazy"
      >
      <h3>Secure System</h3>
      <p>Encrypted and protected to ensure patient privacy and clinic data security.</p>
    </div>

  </div>
</section>

  <!-- ========== TEAM SECTION (Hover Reveal Name) ========== -->
  <section id="about" class="team-section">
    <h2 data-aos="fade-up">Meet the Team Behind Medicina</h2>

    <div class="team-grid">

      <!-- Team Member 1 -->
      <div class="team-card" data-aos="zoom-in">
        <img src="/Booking-System-For-Medical-Clinics/assets/images/profile3.png" alt="Francis Kyle">
        <div class="overlay">
          <h3>Lumayaga, Francis Kyle</h3>
          <p>Planning / Full Stack</p>
        </div>
      </div>

      <!-- Team Member 2 -->
      <div class="team-card" data-aos="zoom-in" data-aos-delay="100">
        <img src="/Booking-System-For-Medical-Clinics/assets/images/profile2.png" alt="Patrich">
        <div class="overlay">
          <h3>Olaran, Patrich</h3>
          <p>Back-End Developer</p>
        </div>
      </div>

      <!-- Team Member 3 -->
      <div class="team-card" data-aos="zoom-in" data-aos-delay="200">
        <img src="/Booking-System-For-Medical-Clinics/assets/images/profile1.png" alt="Rejean Mae">
        <div class="overlay">
          <h3>Libando, Rejean Mae A.</h3>
          <p>Web Designer/Front End</p>
        </div>
      </div>

    </div>
  </section>

  <!-- ========== CTA SECTION ========== -->
  <section class="cta-section" data-aos="fade-up">
    <h2>Start Managing Your Clinic Smarter</h2>
    <p>Experience a modern approach to healthcare management.</p>
    <a href="/Booking-System-For-Medical-Clinics/login_page.php" class="cta-btn cta-white">Join Medicina Now</a>
  </section>

  <!-- FOOTER -->
  <footer class="footer">
    &copy; <?php echo date('Y'); ?> Medicina. All rights reserved.
  </footer>

  <!-- AOS Script -->
  <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
  <script>
    AOS.init({
      duration: 800,
      easing: 'ease-out-quart',
      once: false,        // Repeat animations on scroll up/down
      mirror: true        // Animations trigger when scrolling back up
    });
  </script>
</body>
</html>