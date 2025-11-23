<?php
include 'backend/init.php';

// Redirect if not logged in or not admin
if (!isset($_SESSION['user_id']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
    exit();
}

// Safe count function
function safeCount($conn, $query) {
    try {
        $res = $conn->query($query);
        if ($res && $row = $res->fetch_assoc()) return $row['count'] ?? 0;
    } catch (Exception $e) {
        return 0;
    }
    return 0;
}

// Stats
$totalUsers = safeCount($conn, "SELECT COUNT(*) AS count FROM users");
$totalRequests = safeCount($conn, "SELECT COUNT(*) AS count FROM requests");
$totalProductions = safeCount($conn, "SELECT COUNT(*) AS count FROM production");
$totalInventory = safeCount($conn, "SELECT COUNT(*) AS count FROM inventory");

// Fetch Production data dynamically (In Progress vs Completed)
$inProduction = safeCount($conn, "SELECT COUNT(*) AS count FROM batches WHERE status='in_progress' AND (is_deleted=0 OR is_deleted IS NULL)");
$completed = safeCount($conn, "SELECT COUNT(*) AS count FROM batches WHERE status='completed' AND (is_deleted=0 OR is_deleted IS NULL)");

// Fetch inventory for Available vs Expired
$available = 0;
$expired = 0;
$res = $conn->query("SELECT quantity, expiration_date FROM inventory_batches");
while ($row = $res->fetch_assoc()) {
    if (!$row['expiration_date'] || $row['expiration_date'] >= date('Y-m-d')) {
        $available += (float)$row['quantity'];
    } else {
        $expired += (float)$row['quantity'];
    }
}

// Fetch requests per status dynamically
$approved = safeCount($conn, "SELECT COUNT(*) AS count FROM requests WHERE status='approved'");
$pending  = safeCount($conn, "SELECT COUNT(*) AS count FROM requests WHERE status='pending'");
$denied   = safeCount($conn, "SELECT COUNT(*) AS count FROM requests WHERE status='denied'");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>🌸 BloomLux | Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/admin_dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<div class="sidebar">
    <h2>🌸 Admin Dashboard 🌸</h2>
    <a href="my_requests.php">📋 My Requests</a>
    <a href="backend/add_stock.php">📦 Add Stock</a>
    <a href="logout.php">🚪 Logout</a>
</div>

<div class="main">
    <h1>👨‍🍳 Welcome, Admin <?= htmlspecialchars($_SESSION['username']); ?>!</h1>
    <p>Here’s an overview of the BloomLux system performance.</p>

    <!-- Overview Cards -->
    <div class="overview-cards">
        <div class="card stat"><h3>🌸 Total Users</h3><p><?= $totalUsers ?></p></div>
        <div class="card stat"><h3>📦 Inventory Items</h3><p><?= $totalInventory ?></p></div>
        <div class="card stat"><h3>🧁 Productions</h3><p><?= $totalProductions ?></p></div>
        <div class="card stat"><h3>📋 Requests</h3><p><?= $totalRequests ?></p></div>
    </div>

    <!-- Charts Section -->
    <div class="chart-section">
        <div class="card chart-card">
            <h3>Production Trends</h3>
            <canvas id="productionChart"></canvas>
        </div>
        <div class="card chart-card">
            <h3>Inventory Distribution</h3>
            <canvas id="inventoryChart"></canvas>
        </div>
        <div class="card chart-card">
            <h3>Requests Overview</h3>
            <canvas id="requestsChart"></canvas>
        </div>
    </div>

    <!-- Chart Scripts -->
    <script>
    // Production Chart (Line)
    new Chart(document.getElementById('productionChart'), {
        type: 'line',
        data: {
            labels: ['In Progress', 'Completed'],
            datasets: [{
                label: 'Produced Items',
                data: [<?= $inProduction ?>, <?= $completed ?>],
                borderColor: '#b86bff',
                backgroundColor: 'rgba(184,107,255,0.3)',
                fill: true,
                tension: 0.4
            }]
        }
    });

    // Inventory Chart (Pie: Available vs Expired)
    new Chart(document.getElementById('inventoryChart'), {
        type: 'pie',
        data: {
            labels: ['Available', 'Expired'],
            datasets: [{
                data: [<?= $available ?>, <?= $expired ?>],
                backgroundColor: ['#6aa84f', '#e06666']
            }]
        }
    });

    // Requests Chart (Bar) - Dynamic
    new Chart(document.getElementById('requestsChart'), {
        type: 'bar',
        data: {
            labels: ['Approved', 'Pending', 'Denied'],
            datasets: [{
                label: 'Requests',
                data: [<?= $approved ?>, <?= $pending ?>, <?= $denied ?>],
                backgroundColor: ['#6a9eff', '#ffb3ec', '#e06666']
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } }
        }
    });
    </script>
</div>
</body>
</html>
