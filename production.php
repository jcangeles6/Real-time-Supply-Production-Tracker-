<?php
include 'db.php'; // connection file

// Daily Batches
$daily_batches = $conn->query("SELECT COUNT(*) as count FROM batches WHERE DATE(scheduled_at) = CURDATE()")->fetch_assoc()['count'];

// In Progress
$in_progress = $conn->query("SELECT COUNT(*) as count FROM batches WHERE status = 'in_progress'")->fetch_assoc()['count'];

// Completed Today
$completed_today = $conn->query("
    SELECT COUNT(*) as count 
    FROM batches 
    WHERE status = 'completed' AND DATE(completed_at) = CURDATE()
")->fetch_assoc()['count'];

// Ingredients Needed
$ingredients_needed = $conn->query("SELECT COUNT(*) as count FROM requests WHERE status = 'pending'")->fetch_assoc()['count'];

// Fetch batches for tracker
$batches = $conn->query("SELECT id, product_name, status, scheduled_at, completed_at FROM batches ORDER BY scheduled_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bakery Production Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #fdf9f5;
            margin: 0;
        }
        h1, h2 {
            color: #5a2d0c;
            text-align: center;
        }
        #clock {
            text-align: center;
            font-size: 18px;
            margin-bottom: 20px;
            color: #8b4513;
            font-weight: bold;
        }

        /* Sidebar */
        .sidebar {
            width: 220px;
            background: #8b4513;
            color: #fff;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            padding: 20px 0;
        }
        .sidebar h2 {
            text-align: center;
            margin-bottom: 30px;
        }
        .sidebar a {
            display: block;
            color: #fff;
            padding: 12px 20px;
            text-decoration: none;
            font-weight: bold;
        }
        .sidebar a:hover {
            background: #a0522d;
        }

        /* Main Content */
        .main {
            margin-left: 240px;
            padding: 20px;
        }

        .back-btn {
            display: inline-block;
            margin-bottom: 20px;
            padding: 8px 16px;
            background: #8b4513;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 14px;
        }
        .back-btn:hover {
            background: #5a2d0c;
        }

        /* Dashboard cards */
        .dashboard {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 40px;
        }
        .card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            text-align: center;
            width: 220px;
        }
        .card h2 {
            color: #8b4513;
            font-size: 18px;
            margin-bottom: 10px;
        }
        .card p {
            font-size: 14px;
            margin: 5px 0 15px;
        }
        .btn {
            display: inline-block;
            padding: 10px 18px;
            background: #8b4513;
            color: white;
            border-radius: 20px;
            text-decoration: none;
            font-size: 14px;
        }
        .btn:hover {
            background: #5a2d0c;
        }

        /* Tracker Table */
        table {
            width: 90%;
            margin: auto;
            border-collapse: collapse;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }
        th, td {
            padding: 12px;
            text-align: center;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #8b4513;
            color: white;
        }
        tr:hover {
            background: #f3e9e2;
        }
        .status-in_progress {
            color: #d2691e;
            font-weight: bold;
        }
        .status-completed {
            color: green;
            font-weight: bold;
        }
        .status-pending {
            color: gray;
            font-weight: bold;
        }
    </style>
    <script>
        // Live Clock
        function updateClock() {
            const now = new Date();
            const time = now.toLocaleTimeString();
            const date = now.toLocaleDateString();
            document.getElementById('clock').innerText = "📅 " + date + " | ⏰ " + time;
        }
        setInterval(updateClock, 1000);
        window.onload = updateClock;

        // Auto refresh every 10s
        setTimeout(function(){
            location.reload();
        }, 10000);
    </script>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <h2>🍞 Bakery</h2>
        <a href="home.php" style="background:#5a2d0c; border-radius:6px; margin:0 10px 15px; text-align:center;">
            ⬅ Back to Dashboard
        </a>
        <a href="supply.php">Supply</a>
        <a href="production.php">Production</a>
        <a href="my_requests.php">My Requests</a>
        <a href="inventory.php">Inventory</a>
        <a href="logout.php">Logout</a>
    </div>


    <!-- Main -->
    <div class="main">
        <h1>🍞 Bakery Production Dashboard</h1>
        <div id="clock"></div>

        <!-- Stats Cards -->
        <div class="dashboard">
            <div class="card">
                <h2>Daily Batches</h2>
                <p><?php echo $daily_batches; ?> Batches Scheduled</p>
                <a href="add_batch.php" class="btn">Add Batch</a>
            </div>

            <div class="card">
                <h2>In Progress</h2>
                <p><?php echo $in_progress; ?> Batches Ongoing</p>
                <a href="batches.php" class="btn">View Details</a>
            </div>

            <div class="card">
                <h2>Completed Today</h2>
                <p><?php echo $completed_today; ?> Batches Done</p>
                <a href="report.php" class="btn">View Report</a>
            </div>

            <div class="card">
                <h2>Ingredients Needed</h2>
                <p><?php echo $ingredients_needed; ?> Pending Requests</p>
                <a href="supply.php" class="btn">View Supply</a>
            </div>
        </div>

        <!-- Production Tracker -->
        <h2>📊 Production Tracker</h2>
        <table>
            <tr>
                <th>ID</th>
                <th>Product</th>
                <th>Status</th>
                <th>Scheduled At</th>
                <th>Completed At</th>
            </tr>
            <?php while ($row = $batches->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                <td class="status-<?php echo $row['status']; ?>">
                    <?php echo ucfirst($row['status']); ?>
                </td>
                <td><?php echo $row['scheduled_at']; ?></td>
                <td><?php echo $row['completed_at'] ?? '—'; ?></td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>

</body>
</html>
