<?php
include 'backend/init.php';

// Get username
$user_id = $_SESSION['user_id'];
$result = $conn->query("SELECT username FROM users WHERE id = $user_id");
$user = $result->fetch_assoc();
$username = $user['username'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bakery Production Tracker</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --brown: #8b4513;
            --light-brown: #c3814a;
            --cream: #fdf6f0;
            --white: #ffffff;
            --soft-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            background: var(--cream);
            color: #333;
            display: flex;
        }

        /* Sidebar */
        .sidebar {
            width: 240px;
            background: linear-gradient(180deg, var(--brown), #a0522d);
            color: var(--white);
            height: 100vh;
            position: fixed;
            display: flex;
            flex-direction: column;
            padding: 25px 20px;
            box-shadow: var(--soft-shadow);
        }
        .sidebar h2 {
            text-align: center;
            font-weight: 600;
            font-size: 22px;
            margin-bottom: 40px;
        }
        .sidebar a {
            display: block;
            color: var(--white);
            padding: 12px 18px;
            margin: 8px 0;
            text-decoration: none;
            border-radius: 10px;
            transition: 0.3s;
        }
        .sidebar a:hover {
            background: var(--light-brown);
            transform: translateX(4px);
        }

        /* Main Section */
        .main {
            margin-left: 260px;
            flex-grow: 1;
            padding: 25px 35px;
        }

        /* Top bar */
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        .welcome {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--brown);
        }
        .top-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .search-bar input {
            padding: 10px 14px;
            border: 1px solid #ddd;
            border-radius: 20px;
            width: 200px;
            transition: 0.3s;
        }
        .search-bar input:focus {
            border-color: var(--brown);
            outline: none;
        }
        .notif {
            font-size: 20px;
            cursor: pointer;
        }
        #live-time {
            font-weight: 500;
            color: #6d3f1a;
        }

        /* Dashboard grid */
        .dashboard {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
        }

        .stats {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }

        .stat-box {
            flex: 1;
            background: linear-gradient(135deg, #ffeed8, #ffe0b2);
            padding: 20px;
            border-radius: 16px;
            box-shadow: var(--soft-shadow);
            text-align: center;
            transition: 0.3s;
        }
        .stat-box:hover {
            transform: translateY(-4px);
        }
        .stat-box h3 {
            margin: 0;
            font-size: 26px;
            color: var(--brown);
        }
        .stat-box p {
            margin: 8px 0 0;
            color: #5a2d0c;
            font-weight: 500;
        }

        .card {
            background: var(--white);
            padding: 20px;
            border-radius: 16px;
            box-shadow: var(--soft-shadow);
        }

        .card h3 {
            color: var(--brown);
            margin-bottom: 15px;
            font-weight: 600;
        }

        /* Notifications */
        .notifications ul {
            list-style: none;
            padding: 0;
        }
        .notifications li {
            background: #fff5ea;
            padding: 10px 12px;
            margin-bottom: 10px;
            border-left: 5px solid var(--brown);
            border-radius: 6px;
            font-size: 0.95rem;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .dashboard {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <h2>🍞 SweetCrumb</h2>
    <a href="supply.php">📦 Supply</a>
    <a href="production.php">🧁 Production</a>
    <a href="my_requests.php">📋 My Requests</a>
    <a href="inventory.php">📊 Inventory</a>
    <a href="logout.php">🚪 Logout</a>
</div>

<!-- Main Content -->
<div class="main">
    <div class="top-bar">
        <div class="welcome">👋 Welcome, <?php echo htmlspecialchars($username); ?>!</div>
        <div class="top-right">
            <div id="live-time">⏰ Loading time...</div>
            <div class="search-bar"><input type="text" placeholder="Search..."></div>
            <span class="notif">🔔</span>
        </div>
    </div>

    <div class="dashboard">
        <div>
            <div class="stats">
                <div class="stat-box">
                    <h3>520</h3>
                    <p>Breads in Stock</p>
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

            <div class="card">
                <h3>📈 Inventory Trends</h3>
                <img src="https://via.placeholder.com/600x200?text=Bakery+Stock+Graph" 
                     alt="Stock Trends" style="width:100%; border-radius:10px;">
            </div>
        </div>

        <div>
            <div class="card notifications">
                <h3>🔔 Notifications</h3>
                <ul>
                    <li>🥖 Fresh batch of baguettes completed</li>
                    <li>🍰 Cake production rescheduled for 2PM</li>
                    <li>✔ Flour stock updated successfully</li>
                </ul>
            </div>

            <div class="card">
                <h3>💰 Sales Trends</h3>
                <img src="https://via.placeholder.com/300x120?text=Sales+Graph" 
                     alt="Sales Trends" style="width:100%; border-radius:10px;">
            </div>

            <div class="card">
                <h3>🗓 Production Schedule</h3>
                <p><strong>Next Batch:</strong> Chocolate Cake - <em>2:00 PM</em></p>
                <p><strong>Oven Status:</strong> Preheating 🔥</p>
            </div>
        </div>
    </div>
</div>

<script>
function updateTime() {
    const now = new Date();
    const options = { hour: '2-digit', minute: '2-digit', second: '2-digit', 
                      weekday: 'short', month: 'short', day: 'numeric' };
    document.getElementById("live-time").innerHTML = "⏰ " + now.toLocaleString('en-US', options);
}
setInterval(updateTime, 1000);
updateTime();
</script>

</body>
</html>
