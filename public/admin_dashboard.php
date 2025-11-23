<?php
session_start();

// ---------- 1. AUTH CHECK ----------
if (
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'admin' ||
    empty($_SESSION['USER_IS_SUPERADMIN'])
) {
    header("Location: ../index.php");
    exit;
}

// ---------- 2. CONNECT TO DATABASE ----------
require_once dirname(__DIR__) . "/config/Database.php";
$db = new Database();
$conn = $db->connect();

// ---------- 3. FETCH DASHBOARD COUNTS ----------
try {
    $stmt_doctors = $conn->query("SELECT COUNT(*) AS total FROM DOCTOR");
    $stmt_patients = $conn->query("SELECT COUNT(*) AS total FROM PATIENT");
    $stmt_staff = $conn->query("SELECT COUNT(*) AS total FROM STAFF");
    $stmt_pending = $conn->query("
        SELECT COUNT(*) AS total 
        FROM APPOINTMENT a
        JOIN STATUS s ON a.STAT_ID = s.STAT_ID
        WHERE s.STAT_NAME = 'Scheduled'
    ");

    $total_doctors = $stmt_doctors->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    $total_patients = $stmt_patients->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    $total_staff = $stmt_staff->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    $pending_appointments = $stmt_pending->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
} catch (PDOException $e) {
    die("Error fetching counts: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Admin Dashboard - Medical Clinic System</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link rel="stylesheet" href="../public/css/style.css">
  <link rel="stylesheet" href="../assets/css/style.css">

  <style>
/* FORMAL ADMIN DASHBOARD STYLES */
.dashboard-wrapper {
    max-width: 1400px;
    margin: 0 auto;
}

.dashboard-header {
    background: var(--light);
    border-radius: 15px;
    padding: 35px 40px;
    margin-bottom: 35px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
}

.dashboard-title-section h1 {
    font-size: 36px;
    color: var(--primary);
    margin: 0 0 8px 0;
    font-weight: 700;
    letter-spacing: -0.5px;
}

.dashboard-subtitle {
    font-size: 15px;
    color: #666;
    font-weight: 500;
    margin: 0;
}

.dashboard-date {
    font-size: 14px;
    color: var(--secondary);
    font-weight: 600;
    padding: 8px 20px;
    background: #f8f9fa;
    border-radius: 25px;
    border: 2px solid var(--light);
}

/* STATISTICS CARDS */
.dashboard-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 25px;
    margin-bottom: 35px;
}

.card {
    background: var(--light);
    border-radius: 15px;
    padding: 30px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    border-left: 5px solid var(--light);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-height: 160px;
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0,35,57,0.15);
}

.card:nth-child(1) { border-left-color: #0066cc; }
.card:nth-child(2) { border-left-color: #00a86b; }
.card:nth-child(3) { border-left-color: #ff8c00; }
.card:nth-child(4) { border-left-color: #dc143c; }

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 20px;
}

.card-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    font-weight: 700;
    color: var(--white);
    background: var(--primary);
}

.card:nth-child(1) .card-icon { background: #0066cc; }
.card:nth-child(2) .card-icon { background: #00a86b; }
.card:nth-child(3) .card-icon { background: #ff8c00; }
.card:nth-child(4) .card-icon { background: #dc143c; }

.card h2 {
    font-size: 48px;
    color: var(--primary);
    margin: 0 0 8px 0;
    font-weight: 700;
    line-height: 1;
    text-align: left;
}

.card p {
    font-size: 15px;
    color: #666;
    font-weight: 600;
    margin: 0;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    text-align: left;
}

/* QUICK ACTIONS SECTION */
.quick-actions-section {
    background: var(--light);
    border-radius: 15px;
    padding: 35px 40px;
    margin-bottom: 35px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.section-title {
    font-size: 22px;
    color: var(--primary);
    font-weight: 700;
    margin: 0 0 25px 0;
    letter-spacing: -0.3px;
}

.quick-actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.action-btn {
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    color: var(--white);
    border: none;
    padding: 18px 25px;
    border-radius: 12px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-align: center;
    text-decoration: none;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    letter-spacing: 0.3px;
}

.action-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(0,35,57,0.25);
}

.action-btn-icon {
    font-size: 18px;
}

/* SYSTEM INFO SECTION */
.system-info-section {
    background: var(--light);
    border-radius: 15px;
    padding: 35px 40px;
    margin-bottom: 35px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 25px;
}

.info-item {
    padding: 20px;
    background: var(--primary);
    border-radius: 10px;
    border-left: 4px solid var(--secondary);
}

.info-item-label {
    font-size: 13px;
    color: #f8efefff;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 8px;
}

.info-item-value {
    font-size: 18px;
    color: white;
    font-weight: 700;
}

/* RESPONSIVE */
@media(max-width: 768px) {
    .dashboard-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .dashboard-cards {
        grid-template-columns: 1fr;
    }
    
    .quick-actions-grid {
        grid-template-columns: 1fr;
    }
}
  </style>
</head>
<body>

<?php include dirname(__DIR__) . "/partials/header.php"; ?>

<main>
  <div class="dashboard-wrapper">
    
    <!-- DASHBOARD HEADER -->
    <div class="dashboard-header">
      <div class="dashboard-title-section">
        <h1>Administrative Dashboard</h1>
        <p class="dashboard-subtitle">System Overview & Management</p>
      </div>
      <div class="dashboard-date">
        <?= date('l, F j, Y') ?>
      </div>
    </div>

    <!-- STATISTICS CARDS -->
    <div class="dashboard-cards">
      <div class="card">
        <div class="card-header">
          <div class="card-icon">👨‍⚕️</div>
        </div>
        <div>
          <h2><?= $total_doctors ?></h2>
          <p>Total Doctors</p>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <div class="card-icon">👥</div>
        </div>
        <div>
          <h2><?= $total_patients ?></h2>
          <p>Total Patients</p>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <div class="card-icon">👔</div>
        </div>
        <div>
          <h2><?= $total_staff ?></h2>
          <p>Staff Members</p>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <div class="card-icon">📅</div>
        </div>
        <div>
          <h2><?= $pending_appointments ?></h2>
          <p>Scheduled Appointments</p>
        </div>
      </div>
    </div>

    <!-- QUICK ACTIONS -->
    <div class="quick-actions-section">
      <h3 class="section-title">Quick Actions</h3>
      <div class="quick-actions-grid">
        <a href="./admin_pages/doctors.php" class="action-btn">
          <span class="action-btn-icon">👨‍⚕️</span>
          Manage Doctors
        </a>
        <a href="./admin_pages/patients.php" class="action-btn">
          <span class="action-btn-icon">👥</span>
          Manage Patients
        </a>
        <a href="./admin_pages/staff.php" class="action-btn">
          <span class="action-btn-icon">👔</span>
          Manage Staff
        </a>
        <a href="./admin_pages/admin_appointments.php" class="action-btn">
          <span class="action-btn-icon">📅</span>
          View Appointments
        </a>
      </div>
    </div>

    <!-- SYSTEM INFORMATION -->
    <div class="system-info-section">
      <h3 class="section-title">System Information</h3>
      <div class="info-grid">
        <div class="info-item">
          <div class="info-item-label">System Status</div>
          <div class="info-item-value">✓ Operational</div>
        </div>
        <div class="info-item">
          <div class="info-item-label">Current User</div>
          <div class="info-item-value">Super Administrator</div>
        </div>
        <div class="info-item">
          <div class="info-item-label">Access Level</div>
          <div class="info-item-value">Full Access</div>
        </div>
        <div class="info-item">
          <div class="info-item-label">Last Login</div>
          <div class="info-item-value"><?= date('g:i A') ?></div>
        </div>
      </div>
    </div>

  </div>
</main>

<?php include dirname(__DIR__) . "/partials/footer.php"; ?>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    // open/close dropdowns on click for mobile & keyboard users
    const toggles = document.querySelectorAll('.dropdown > .dropdown-toggle');
    toggles.forEach(toggle => {
      toggle.addEventListener('click', function (ev) {
        ev.preventDefault();
        const parent = this.closest('.dropdown');

        // close other open dropdowns
        document.querySelectorAll('.dropdown.open').forEach(d => {
          if (d !== parent) d.classList.remove('open');
        });

        // toggle this
        const isOpen = parent.classList.toggle('open');
        this.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
      });
    });

    // close dropdowns when clicking outside
    document.addEventListener('click', function (ev) {
      if (!ev.target.closest('.dropdown')) {
        document.querySelectorAll('.dropdown.open').forEach(d => d.classList.remove('open'));
        document.querySelectorAll('.dropdown-toggle').forEach(t => t.setAttribute('aria-expanded', 'false'));
      }
    });

    // close with Escape key
    document.addEventListener('keydown', function (ev) {
      if (ev.key === 'Escape') {
        document.querySelectorAll('.dropdown.open').forEach(d => d.classList.remove('open'));
        document.querySelectorAll('.dropdown-toggle').forEach(t => t.setAttribute('aria-expanded', 'false'));
      }
    });
  });
</script>
</body>
</html>