<?php
include 'backend/init.php';

// Redirect if not logged in or not admin
if (!isset($_SESSION['user_id']) || $_SESSION['is_admin'] != 1) {
  header("Location: login.php");
  exit();
}

// Fallback-safe counts (avoid crashes if tables missing)
function safeCount($conn, $query) {
  try {
    $res = $conn->query($query);
    if ($res && $row = $res->fetch_assoc()) return $row['count'] ?? 0;
  } catch (Exception $e) {
    return 0;
  }
  return 0;
}

$totalUsers = safeCount($conn, "SELECT COUNT(*) AS count FROM users");
$totalRequests = safeCount($conn, "SELECT COUNT(*) AS count FROM requests");
$totalProductions = safeCount($conn, "SELECT COUNT(*) AS count FROM production");
$totalInventory = safeCount($conn, "SELECT COUNT(*) AS count FROM inventory");

// Fetch all users for management table
$stmt = $conn->prepare("SELECT id, username, email, reset_requested FROM users");
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();
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
  <!-- Sidebar -->
  <div class="sidebar">
    <h2>🌸 Admin Dashboard 🌸</h2>
    <a href="my_requests.php">📋 My Requests</a>
    <a href="backend/add_stock.php">📦 Add Stock</a>
    <a href="logout.php">🚪 Logout</a>
  </div>

  <!-- Main Content -->
  <div class="main">
    <h1>👨‍🍳 Welcome, Admin <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
    <p>Here’s an overview of the BloomLux system performance.</p>

    <!-- Overview Cards -->
    <div class="overview-cards">
      <div class="card stat">
        <h3>🌸 Total Users</h3>
        <p><?php echo $totalUsers; ?></p>
      </div>
      <div class="card stat">
        <h3>📦 Inventory Items</h3>
        <p><?php echo $totalInventory; ?></p>
      </div>
      <div class="card stat">
        <h3>🧁 Productions</h3>
        <p><?php echo $totalProductions; ?></p>
      </div>
      <div class="card stat">
        <h3>📋 Requests</h3>
        <p><?php echo $totalRequests; ?></p>
      </div>
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
        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
        datasets: [{
          label: 'Produced Items',
          data: [20, 45, 30, 60, 75, 50],
          borderColor: '#b86bff',
          backgroundColor: 'rgba(184,107,255,0.3)',
          fill: true,
          tension: 0.4
        }]
      }
    });

    // Inventory Chart (Pie)
    new Chart(document.getElementById('inventoryChart'), {
      type: 'pie',
      data: {
        labels: ['Flowers', 'Vases', 'Packaging', 'Decor'],
        datasets: [{
          data: [35, 25, 20, 20],
          backgroundColor: ['#ffb3ec', '#d47fff', '#c79fff', '#9c6eff']
        }]
      }
    });

    // Requests Chart (Bar)
    new Chart(document.getElementById('requestsChart'), {
      type: 'bar',
      data: {
        labels: ['Approved', 'Pending', 'Denied'],
        datasets: [{
          label: 'Requests',
          data: [8, 5, 2],
          backgroundColor: ['#a14bcf', '#ffb3ec', '#ff6fa0']
        }]
      }
    });
  </script>

</body>
</html>
