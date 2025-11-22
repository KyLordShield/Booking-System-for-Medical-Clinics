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

  <!-- ================= HEADER ================= -->
  <?php include __DIR__ . '/partials/guest_header.php'; ?>

  <!-- ================= HERO SECTION ================= -->
  <section id="home" class="hero-section">
    <div class="hero-content" data-aos="fade-right">
      <h1>Your Modern Medical Appointment Platform</h1>
      <p>
        Empower your clinic with automated scheduling, organized patient records, 
        and real-time doctor availability — built for faster, safer, and smarter healthcare.
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

  <!-- ================= FEATURES SECTION ================= -->
  <section class="features-section">
    <h2 data-aos="fade-up">Powerful Features for Modern Clinics</h2>

    <div class="features-grid">

      <div class="feature-box" data-aos="fade-up" data-aos-delay="100">
        <img src="https://images.pexels.com/photos/6823398/pexels-photo-6823398.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1" alt="Doctor scheduling" loading="lazy">
        <h3>Smart Scheduling</h3>
        <p>Manage appointments efficiently with real-time updates and automated tracking.</p>
      </div>

      <div class="feature-box" data-aos="fade-up" data-aos-delay="200">
        <img src="https://images.pexels.com/photos/4266938/pexels-photo-4266938.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1" alt="Digital Records" loading="lazy">
        <h3>Digital Records</h3>
        <p>Secure patient records that are easy to access, update, and organize.</p>
      </div>

      <div class="feature-box" data-aos="fade-up" data-aos-delay="300">
        <img src="https://images.pexels.com/photos/3952137/pexels-photo-3952137.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1" alt="Dashboard" loading="lazy">
        <h3>Doctor Dashboard</h3>
        <p>Doctors get a clean dashboard to view schedules, consultations, and history.</p>
      </div>

      <div class="feature-box" data-aos="fade-up" data-aos-delay="400">
        <img src="https://images.pexels.com/photos/5327656/pexels-photo-5327656.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1" alt="Security" loading="lazy">
        <h3>Secure System</h3>
        <p>Encrypted and protected to ensure patient privacy and clinic data security.</p>
      </div>

    </div>
  </section>

  <!-- ================= WHY CHOOSE US ================= -->
  <section class="why-section">
    <h2 data-aos="fade-up">Why Clinics Choose Medicina</h2>

    <div class="why-grid">
      <div class="why-box" data-aos="fade-up" data-aos-delay="100">
        <h3>Reliable for Daily Operations</h3>
        <p>Built to handle clinic traffic, walk-ins, reschedules, and diagnostics smoothly.</p>
      </div>

      <div class="why-box" data-aos="fade-up" data-aos-delay="200">
        <h3>Designed for Doctors & Staff</h3>
        <p>Simple workflows allowing your team to focus on patients — not paperwork.</p>
      </div>

      <div class="why-box" data-aos="fade-up" data-aos-delay="300">
        <h3>Patient-Centered Experience</h3>
        <p>Patients can easily book, track, and manage their appointments anytime.</p>
      </div>

      <div class="why-box" data-aos="fade-up" data-aos-delay="400">
        <h3>Secure & Compliant</h3>
        <p>Protected with modern encryption and strict data-privacy standards.</p>
      </div>
    </div>
  </section>

  <!-- ================= HOW IT WORKS ================= -->
  <section class="how-section">
    <h2 data-aos="fade-up">How Medicina Works</h2>

    <div class="how-grid">
      <div class="how-step" data-aos="fade-up" data-aos-delay="100">
        <h3>1. Book & Manage Appointments</h3>
        <p>Patients schedule instantly — no long lines or phone calls.</p>
      </div>

      <div class="how-step" data-aos="fade-up" data-aos-delay="200">
        <h3>2. Clinic Dashboard</h3>
        <p>Doctors and staff manage schedules, consultations, and patient flow.</p>
      </div>

      <div class="how-step" data-aos="fade-up" data-aos-delay="300">
        <h3>3. Organized Medical Records</h3>
        <p>All records are securely stored and accessible when needed.</p>
      </div>
    </div>
  </section>

  <!-- ================= SECURITY SECTION ================= -->
  <section class="security-section">
    <h2 data-aos="fade-up">Data Privacy & Security You Can Trust</h2>
    <p class="security-text" data-aos="fade-up" data-aos-delay="100">
      We use modern encryption, secure authentication, and strict access control 
      to protect patient data. Medicina follows healthcare privacy standards to 
      ensure all information remains safe and confidential.
    </p>
  </section>

  <!-- ================= TESTIMONIALS ================= -->
  <section class="testimonials-section">
    <h2 data-aos="fade-up">Trusted by Clinics and Healthcare Professionals</h2>

    <div class="testimonials-grid">

      <div class="testimonial" data-aos="fade-up">
        <p>“Medicina made our appointment system 2x faster and reduced daily congestion.”</p>
        <h4>— St. Matthew Clinic</h4>
      </div>

      <div class="testimonial" data-aos="fade-up" data-aos-delay="150">
        <p>“Our doctors love the clean dashboard. Very easy to use.”</p>
        <h4>— Dr. Ramirez Family Clinic</h4>
      </div>

    </div>
  </section>

  <!-- ================= TEAM SECTION ================= -->
  <section id="about" class="team-section">
    <h2 data-aos="fade-up">Meet the Team Behind Medicina</h2>

    <div class="team-grid">

      <div class="team-card" data-aos="zoom-in">
        <img src="/Booking-System-For-Medical-Clinics/assets/images/profile3.png" alt="Francis Kyle">
        <div class="overlay">
          <h3>Lumayaga, Francis Kyle</h3>
          <p>Planning / Full Stack</p>
        </div>
      </div>

      <div class="team-card" data-aos="zoom-in" data-aos-delay="100">
        <img src="/Booking-System-For-Medical-Clinics/assets/images/profile2.png" alt="Patrich">
        <div class="overlay">
          <h3>Olaran, Patrich</h3>
          <p>Back-End Developer</p>
        </div>
      </div>

      <div class="team-card" data-aos="zoom-in" data-aos-delay="200">
        <img src="/Booking-System-For-Medical-Clinics/assets/images/profile1.png" alt="Rejean Mae">
        <div class="overlay">
          <h3>Libando, Rejean Mae A.</h3>
          <p>Web Designer / Front End</p>
        </div>
      </div>

    </div>
  </section>

  <!-- ================= CTA SECTION ================= -->
  <section class="cta-section" data-aos="fade-up">
    <h2>Start Managing Your Clinic Smarter</h2>
    <p>Experience a modern approach to healthcare management.</p>
    <a href="/Booking-System-For-Medical-Clinics/login_page.php" class="cta-btn cta-white">Join Medicina Now</a>
  </section>

  <!-- ================= FOOTER ================= -->
  <footer class="footer">
    <p>&copy; <?php echo date('Y'); ?> Medicina. All rights reserved.</p>
    <p class="footer-sub">Smart Appointment & Clinic Management Platform</p>
  </footer>

  <!-- AOS Script -->
  <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
  <script>
    AOS.init({
      duration: 800,
      easing: 'ease-out-quart',
      once: false,
      mirror: true
    });
  </script>
</body>
</html>
