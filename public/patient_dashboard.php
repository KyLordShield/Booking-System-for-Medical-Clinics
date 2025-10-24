<?php 
// Assume user is logged in as Patient
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Patient Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f4f7f9;
            margin: 0;
            padding: 0;
        }
        header {
            background-color: #007bff;
            color: #fff;
            padding: 20px;
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }
        .container {
            max-width: 1000px;
            margin: 30px auto;
            padding: 0 20px;
        }
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        .card {
            background: #fff;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            transition: transform 0.2s, box-shadow 0.2s;
            text-align: center;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }
        .card h2 {
            margin-top: 0;
            margin-bottom: 20px;
            color: #333;
            font-size: 20px;
        }
        .card a, button {
            display: block;
            text-decoration: none;
            color: #fff;
            background-color: #28a745;
            padding: 12px;
            margin: 10px auto;
            width: 80%;
            border-radius: 6px;
            font-weight: 500;
            transition: background 0.2s;
            border: none;
            cursor: pointer;
        }
        .card a:hover, button:hover {
            background-color: #218838;
        }
        /* Search Input */
        .search-container {
            margin: 20px 0;
            text-align: center;
        }
        .search-container input[type="text"] {
            padding: 10px;
            width: 60%;
            border-radius: 6px;
            border: 1px solid #ccc;
            font-size: 16px;
        }
        .search-container button {
            width: auto;
            margin-left: 10px;
        }
        /* Display results */
        #search-results {
            margin-top: 15px;
            background: #fff;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            min-height: 50px;
        }
    </style>
</head>
<body>

<header>
    Patient Dashboard
</header>

<div class="container">
    <div class="dashboard-grid">
        <!-- Patient Section -->
        <div class="card">
            <h2>Patient Info</h2>
            <a href="patient_pages/patient_management.php">Manage Info</a>

            <!-- Search Patient -->
            <div class="search-container">
                <input type="text" placeholder="Search Patient (First or Last Name)">
                <button>Search</button>
            </div>
            <div id="search-results">
                <!-- Placeholder for search results -->
                Search results will appear here.
            </div>
        </div>

        <!-- Appointments Section -->
        <div class="card">
            <h2>Appointments</h2>
            <a href="patient_pages/create_appointment.php">Create Appointment</a>
            <a href="patient_pages/appointment_status.php">View Appointment Status</a>
        </div>
    </div>
</div>

</body>
</html>
