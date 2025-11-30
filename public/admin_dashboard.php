<?php
session_start();

// ---------- 1. AUTH CHECK ----------
if (
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'admin' ||
    empty($_SESSION['USER_IS_SUPERADMIN'])
) {
    header("Location: /index.php");
    exit;
}

// ---------- 2. CONNECT TO DATABASE ----------
require_once dirname(__DIR__) . "/config/Database.php";
$db = new Database();
$conn = $db->connect();

// ---------- 3. FETCH DASHBOARD COUNTS ----------
try {
    // Basic counts
    $stmt_doctors = $conn->query("SELECT COUNT(*) AS total FROM doctor");
    $stmt_patients = $conn->query("SELECT COUNT(*) AS total FROM patient");
    $stmt_staff = $conn->query("SELECT COUNT(*) AS total FROM staff");
    $stmt_appointments = $conn->query("SELECT COUNT(*) AS total FROM appointment");
    
    $total_doctors = $stmt_doctors->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    $total_patients = $stmt_patients->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    $total_staff = $stmt_staff->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    $total_appointments = $stmt_appointments->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    // Appointment statistics by status
    $stmt_appt_status = $conn->query("
        SELECT s.STAT_NAME, COUNT(a.APPT_ID) as count
        FROM status s
        LEFT JOIN appointment a ON s.STAT_ID = a.STAT_ID
        GROUP BY s.STAT_ID, s.STAT_NAME
        ORDER BY count DESC
    ");
    $appointment_stats = $stmt_appt_status->fetchAll(PDO::FETCH_ASSOC);

    // Revenue statistics
        // Revenue statistics - FIXED & 100% ACCURATE
    $stmt_revenue = $conn->query("
        SELECT 
            COALESCE(SUM(p.PYMT_AMOUNT_PAID), 0) AS total_revenue,
            COUNT(*) AS total_transactions
        FROM payment p
        INNER JOIN payment_status ps ON p.PYMT_STAT_ID = ps.PYMT_STAT_ID
        WHERE ps.PYMT_STAT_NAME = 'Paid'
    ");
    $revenue_data = $stmt_revenue->fetch(PDO::FETCH_ASSOC);
    $total_revenue = $revenue_data['total_revenue'] ?? 0;
    $total_transactions = $revenue_data['total_transactions'] ?? 0;

        // Pending payments - FIXED VERSION
// SHOW ONLY APPOINTMENTS WITH PAYMENT STATUS = "Pending"
$stmt_pending = $conn->prepare("
    SELECT 
        a.APPT_ID,
        CONCAT(pat.PAT_FIRST_NAME, ' ', pat.PAT_LAST_NAME) AS patient_name,
        COALESCE(s.SERV_NAME, '—') AS SERV_NAME,
        COALESCE(s.SERV_PRICE, 0) AS service_fee,
        a.APPT_DATE,
        COALESCE(p.PYMT_AMOUNT_PAID, 0) AS amount_paid,
        (COALESCE(s.SERV_PRICE, 0) - COALESCE(p.PYMT_AMOUNT_PAID, 0)) AS balance_due,
        ps.PYMT_STAT_NAME AS payment_status
    FROM appointment a
    JOIN patient pat ON a.PAT_ID = pat.PAT_ID
    LEFT JOIN service s ON a.SERV_ID = s.SERV_ID
    INNER JOIN payment p ON a.APPT_ID = p.APPT_ID
    INNER JOIN payment_status ps ON p.PYMT_STAT_ID = ps.PYMT_STAT_ID
    WHERE ps.PYMT_STAT_NAME = 'Pending'
    ORDER BY a.APPT_DATE DESC
    LIMIT 20
");

$stmt_pending->execute();
$pending_payments = $stmt_pending->fetchAll(PDO::FETCH_ASSOC);

          // MONTTLY
    $stmt_monthly = $conn->prepare("
        SELECT 
            DATE_FORMAT(APPT_DATE, '%b %Y') AS month,
            COUNT(*) AS count
        FROM appointment
        WHERE APPT_DATE >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY 
            YEAR(APPT_DATE),
            MONTH(APPT_DATE),
            DATE_FORMAT(APPT_DATE, '%b %Y')
        ORDER BY 
            YEAR(APPT_DATE) DESC, 
            MONTH(APPT_DATE) DESC
    ");
    $stmt_monthly->execute();
    $monthly_appointments = $stmt_monthly->fetchAll(PDO::FETCH_ASSOC);

    // Specialization distribution
    $stmt_specialization = $conn->query("
        SELECT 
            sp.SPEC_NAME,
            COUNT(d.DOC_ID) as doctor_count
        FROM specialization sp
        LEFT JOIN doctor d ON sp.SPEC_ID = d.SPEC_ID
        GROUP BY sp.SPEC_ID, sp.SPEC_NAME
        ORDER BY doctor_count DESC
    ");
    $specialization_data = $stmt_specialization->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error fetching data: " . $e->getMessage());
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
  <link rel="stylesheet" href="../assets/css/admin_dashboard.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <style>
/* DASHBOARD LAYOUT */

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
        <p class="dashboard-subtitle">System Overview & Analytics</p>
      </div>
      <div class="dashboard-date">
        <?= date('l, F j, Y') ?>
      </div>
    </div>

    <!-- STATISTICS CARDS -->
    <div class="stats-grid">
      <div class="stat-card blue">
        <div class="stat-header">
          <div class="stat-icon">👨‍⚕️</div>
        </div>
        <div class="stat-value"><?= $total_doctors ?></div>
        <div class="stat-label">Total Doctors</div>
      </div>

      <div class="stat-card green">
        <div class="stat-header">
          <div class="stat-icon">👥</div>
        </div>
        <div class="stat-value"><?= $total_patients ?></div>
        <div class="stat-label">Total Patients</div>
      </div>

      <div class="stat-card orange">
        <div class="stat-header">
          <div class="stat-icon">👔</div>
        </div>
        <div class="stat-value"><?= $total_staff ?></div>
        <div class="stat-label">Staff Members</div>
      </div>

      <div class="stat-card purple">
        <div class="stat-header">
          <div class="stat-icon">📅</div>
        </div>
        <div class="stat-value"><?= $total_appointments ?></div>
        <div class="stat-label">Total Appointments</div>
      </div>

      <div class="stat-card teal">
        <div class="stat-header">
          <div class="stat-icon">💵</div>
        </div>
        <div class="stat-value">₱<?= number_format($total_revenue, 2) ?></div>
        <div class="stat-label">Total Revenue</div>
      </div>

      <div class="stat-card red">
        <div class="stat-header">
          <div class="stat-icon">💳</div>
        </div>
        <div class="stat-value"><?= $total_transactions ?></div>
        <div class="stat-label">Transactions</div>
      </div>
    </div>

    <!-- CHARTS SECTION -->
    <div class="charts-grid">
      <!-- Appointment Status Chart -->
      <div class="chart-card">
        <h3 class="chart-title">Appointment Status Distribution</h3>
        <div class="chart-container">
          <canvas id="appointmentStatusChart"></canvas>
        </div>
      </div>

      <!-- Monthly Appointments Chart -->
      <div class="chart-card">
        <h3 class="chart-title">Monthly Appointments Trend</h3>
        <div class="chart-container">
          <canvas id="monthlyAppointmentsChart"></canvas>
        </div>
      </div>

      <!-- Specialization Distribution Chart -->
      <div class="chart-card">
        <h3 class="chart-title">Doctors by Specialization</h3>
        <div class="chart-container">
          <canvas id="specializationChart"></canvas>
        </div>
      </div>
    </div>

    <!-- PENDING PAYMENTS TABLE -->
    <div class="table-card">
      <div class="table-header">
        <h3 class="table-title">Pending Payments</h3>
        <span class="table-badge"><?= count($pending_payments) ?> Pending</span>
      </div>
      
      <?php if (count($pending_payments) > 0): ?>
      <table class="data-table">
        <thead>
          <tr>
            <th>Appointment ID</th>
            <th>Patient Name</th>
            <th>Service</th>
            <th>Service Price</th>
            <th>Amount Paid</th>
            <th>Balance Due</th>
            <th>Appointment Date</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
         <?php foreach ($pending_payments as $payment): ?>
          <tr>
              <td><strong>#<?= $payment['APPT_ID'] ?></strong></td>
              <td><?= htmlspecialchars($payment['patient_name']) ?></td>
              <td><?= htmlspecialchars($payment['SERV_NAME']) ?></td>
              <td><strong>₱<?= number_format($payment['service_fee'], 2) ?></strong></td>
              <td class="text-success fw-bold">₱<?= number_format($payment['amount_paid'], 2) ?></td>
              <td class="text-danger fw-bold">₱<?= number_format($payment['balance_due'], 2) ?></td>
              <td><?= date('M d, Y', strtotime($payment['APPT_DATE'])) ?></td>
              
              <!-- Fixed badge: always shows "Pending" (clean & simple) -->
              <td>
                  <span class="status-badge status-pending">
                      Pending
                  </span>
              </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php else: ?>
      <div class="empty-state">
        No pending payments at this time
      </div>
      <?php endif; ?>
    </div>

    <!-- QUICK ACTIONS -->
    <div class="quick-actions">
      <h3 class="chart-title">Quick Actions</h3>
      <div class="actions-grid">
        <a href="./admin_pages/doctors.php" class="action-btn">
          <span>👨‍⚕️</span> Manage Doctors
        </a>
        <a href="./admin_pages/patients.php" class="action-btn">
          <span>👥</span> Manage Patients
        </a>
        <a href="./admin_pages/staff.php" class="action-btn">
          <span>👔</span> Manage Staff
        </a>
        <a href="./admin_pages/admin_appointments.php" class="action-btn">
          <span>📅</span> View Appointments
        </a>
      </div>
    </div>

  </div>
</main>

<?php include dirname(__DIR__) . "/partials/footer.php"; ?>

<script>
// Prepare data for charts
const appointmentStatusData = <?= json_encode($appointment_stats) ?>;
const monthlyAppointmentsData = <?= json_encode($monthly_appointments) ?>;
const specializationData = <?= json_encode($specialization_data) ?>;

// Chart colors
const chartColors = {
    blue: '#0066cc',
    green: '#00a86b',
    orange: '#ff8c00',
    purple: '#9b59b6',
    red: '#e74c3c',
    teal: '#1abc9c',
    yellow: '#f39c12',
    pink: '#e91e63'
};

// 1. Appointment Status Pie Chart
const statusCtx = document.getElementById('appointmentStatusChart').getContext('2d');
new Chart(statusCtx, {
    type: 'doughnut',
    data: {
        labels: appointmentStatusData.map(item => item.STAT_NAME),
        datasets: [{
            data: appointmentStatusData.map(item => item.count),
            backgroundColor: [
                chartColors.blue,
                chartColors.green,
                chartColors.orange,
                chartColors.red,
                chartColors.purple
            ],
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    padding: 15,
                    font: {
                        size: 12,
                        weight: '600'
                    }
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.label + ': ' + context.parsed + ' appointments';
                    }
                }
            }
        }
    }
});

// 2. Monthly Appointments Line Chart
const monthlyCtx = document.getElementById('monthlyAppointmentsChart').getContext('2d');
new Chart(monthlyCtx, {
    type: 'line',
    data: {
        labels: monthlyAppointmentsData.map(item => item.month),
        datasets: [{
            label: 'Appointments',
            data: monthlyAppointmentsData.map(item => item.count),
            borderColor: chartColors.blue,
            backgroundColor: 'rgba(0, 102, 204, 0.1)',
            borderWidth: 3,
            tension: 0.4,
            fill: true,
            pointRadius: 5,
            pointHoverRadius: 7,
            pointBackgroundColor: chartColors.blue,
            pointBorderColor: '#fff',
            pointBorderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return 'Appointments: ' + context.parsed.y;
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1,
                    font: {
                        size: 11
                    }
                },
                grid: {
                    color: 'rgba(0,0,0,0.05)'
                }
            },
            x: {
                ticks: {
                    font: {
                        size: 11
                    }
                },
                grid: {
                    display: false
                }
            }
        }
    }
});

// 3. Specialization Bar Chart
const specCtx = document.getElementById('specializationChart').getContext('2d');
new Chart(specCtx, {
    type: 'bar',
    data: {
        labels: specializationData.map(item => item.SPEC_NAME),
        datasets: [{
            label: 'Number of Doctors',
            data: specializationData.map(item => item.doctor_count),
            backgroundColor: [
                chartColors.blue,
                chartColors.green,
                chartColors.orange,
                chartColors.purple,
                chartColors.teal,
                chartColors.red,
                chartColors.yellow,
                chartColors.pink
            ],
            borderWidth: 0,
            borderRadius: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return 'Doctors: ' + context.parsed.y;
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1,
                    font: {
                        size: 11
                    }
                },
                grid: {
                    color: 'rgba(0,0,0,0.05)'
                }
            },
            x: {
                ticks: {
                    font: {
                        size: 11
                    }
                },
                grid: {
                    display: false
                }
            }
        }
    }
});

// Dropdown functionality
document.addEventListener('DOMContentLoaded', function () {
    const toggles = document.querySelectorAll('.dropdown > .dropdown-toggle');
    toggles.forEach(toggle => {
        toggle.addEventListener('click', function (ev) {
            ev.preventDefault();
            const parent = this.closest('.dropdown');
            document.querySelectorAll('.dropdown.open').forEach(d => {
                if (d !== parent) d.classList.remove('open');
            });
            const isOpen = parent.classList.toggle('open');
            this.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });
    });

    document.addEventListener('click', function (ev) {
        if (!ev.target.closest('.dropdown')) {
            document.querySelectorAll('.dropdown.open').forEach(d => d.classList.remove('open'));
            document.querySelectorAll('.dropdown-toggle').forEach(t => t.setAttribute('aria-expanded', 'false'));
        }
    });

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