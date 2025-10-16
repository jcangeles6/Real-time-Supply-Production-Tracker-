    <?php
    include 'backend/init.php';

    // ✅ Secure username fetch
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $username = $user['username'];
    ?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <title>🌸 BloomLux Dashboard 🌸</title>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="css/home.css">
    </head>

    <body>
        <!-- Sidebar -->
        <div class="sidebar">
            <h2>🌸 BloomLux Dashboard 🌸</h2>
            <a href="supply.php">📦 Supply</a>
            <a href="production.php">🧁 Production</a>
            <a href="inventory.php">📊 Inventory</a>
            <a href="logout.php">🚪 Logout</a>
        </div>
        <!-- Main -->
        <div class="main">
            <div class="top-bar">
                <div class="welcome">👋 Welcome, <?php echo htmlspecialchars($username); ?>!</div>
                <div class="top-right">
                    <div id="live-time">⏰ Loading time...</div>
                    <div class="search-bar"><input type="text" placeholder="Search..."></div>
                </div>
            </div>

            <div class="dashboard">
                <div>
                    <div class="stats">
                        <div class="stat-box">
                            <h3>520</h3>
                            <p>Materials in Stock</p>
                        </div>
                        <div class="stat-box">
                            <h3>150</h3>
                            <p>In Production</p>
                        </div>
                        <div class="stat-box">
                            <h3>95</h3>
                            <p>Completed Orders</p>
                        </div>
                    </div>
                    <div class="card" style="width: 450px; height: 350px;">
                        <h3>🏆 Top-Selling Products</h3>
                        <canvas id="topSellingChart"></canvas>
                    </div>


                    <div>
                        <div class="card" style="width: 600px; height: 250px;">
                            <h3>🔔 Notifications</h3>
                            <div class="notif-scroll">
                                <ul id="notifications-list"></ul>
                            </div>
                        </div>

                        <div class="card" style="width: 600px; height: 350px;">
                            <h3>📊 Sales Trend</h3>
                            <canvas id="salesTrendChart"></canvas>
                        </div>

                        <div id="production-schedule-container">
                            <div id="production-schedule-header">
                                <h3>🗓 Production Schedule</h3>
                                <button id="viewProductionBtn">Check Here</button>
                            </div>
                            <ul id="production-schedule-list"></ul>
                        </div>
                    </div>
                </div>
            </div>
            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
            <script src="js/inventory-overview.js"></script>
            <script src="js/sales-trend.js"></script>
            <script src="js/dashboard.js"></script>
            <script src="js/time.js"></script>
            <script>
                async function updateHomeStats() {
                    try {
                        const res = await fetch('backend/get_home_stats.php');
                        const data = await res.json();
                        if (!data.success) return;

                        document.querySelectorAll('.stat-box')[0].querySelector('h3').textContent = data.materials;
                        document.querySelectorAll('.stat-box')[1].querySelector('h3').textContent = data.inProduction;
                        document.querySelectorAll('.stat-box')[2].querySelector('h3').textContent = data.completed;
                    } catch (err) {
                        console.error('Failed to fetch home stats:', err);
                    }
                }

                // Initial load + auto refresh every 10s
                updateHomeStats();
                setInterval(updateHomeStats, 10000);
            </script>
    </body>

    </html>