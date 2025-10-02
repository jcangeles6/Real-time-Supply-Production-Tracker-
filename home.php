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
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background: #fdf6f0;
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

        /* Main content */
        .main {
            margin-left: 240px;
            padding: 20px;
        }

        /* Top bar */
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .search-bar input {
            padding: 8px;
            width: 200px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .notif {
            font-size: 20px;
            cursor: pointer;
            margin-left: 15px;
        }

        /* Live time */
        #live-time {
            font-weight: bold;
            color: #5a2d0c;
            margin-right: 10px;
        }

        /* Dashboard grid */
        .dashboard {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-top: 20px;
        }

        .card {
            background: #fff8f0;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0px 2px 5px rgba(0,0,0,0.1);
        }

        .stats {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        .stat-box {
            flex: 1;
            background: #ffe4c4;
            padding: 20px;
            text-align: center;
            border-radius: 8px;
        }
        .stat-box h3 {
            margin: 0;
            font-size: 24px;
        }

        /* Notifications */
        .notifications ul {
            list-style: none;
            padding: 0;
        }
        .notifications li {
            margin: 10px 0;
            padding: 8px;
            background: #fff0e0;
            border-left: 4px solid #d2691e;
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <h2>🍞 Bakery</h2>
    <a href="supply.php">Supply</a>
    <a href="production.php">Production</a>
    <a href="my_requests.php">My Requests</a>
    <a href="inventory.php">Inventory</a>
    <a href="logout.php">Logout</a>
</div>

<!-- Main Content -->
<div class="main">
    <div class="top-bar">
        <h1>👋 Welcome, <?php echo htmlspecialchars($username); ?>!</h1>
        <div class="search-bar">
            <div id="live-time">⏰ Loading time...</div>
            <input type="text" placeholder="Search...">
            <span class="notif">🔔</span>
        </div>
    </div>

    <!-- Dashboard -->
    <div class="dashboard">
        <div>
            <div class="stats">
                <div class="stat-box">
                    <h3>520</h3>
                    <p>Breads in Stock</p>
                </div>
                <div class="stat-box">
                    <h3>150</h3>
                    <p>Items in Production</p>
                </div>
                <div class="stat-box">
                    <h3>95</h3>
                    <p>Completed Orders</p>
                </div>
            </div>

            <div class="card">
                <h3>Inventory Trends</h3>
                <img src="https://via.placeholder.com/600x200?text=Bakery+Stock+Graph" alt="Stock Trends">
            </div>
        </div>

        <div>
            <div class="card notifications">
                <h3>Notifications</h3>
                <ul>
                    <li>🥖 Fresh batch of baguettes completed</li>
                    <li>⏰ Cake production delayed</li>
                    <li>✔ Flour stock updated</li>
                </ul>
            </div>

            <div class="card">
                <h3>Sales Trends</h3>
                <img src="https://via.placeholder.com/300x100?text=Sales+Graph" alt="Sales Trends">
            </div>

            <div class="card">
                <h3>Production Schedule</h3>
                <p>Next batch: Chocolate Cake - 2PM</p>
            </div>
        </div>
    </div>
</div>

<script>
function updateTime() {
    const now = new Date();
    const options = {
        weekday: 'long', year: 'numeric', month: 'long',
        day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit'
    };
    document.getElementById("live-time").innerHTML = "⏰ " + now.toLocaleString('en-US', options);
}
setInterval(updateTime, 1000);
updateTime();
</script>

</body>
</html>
