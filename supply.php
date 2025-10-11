<?php
include 'backend/init.php';

// Get username
$user_id = $_SESSION['user_id'];
$result = $conn->query("SELECT username FROM users WHERE id = $user_id");
$user = $result->fetch_assoc();
$username = $user['username'];

// Fetch ingredient requests
$result_requests = $conn->query("SELECT * FROM requests ORDER BY requested_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>🍞 SweetCrumb | Supply</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #ffb3ecff;          /* main background */
            --card: #f5f0fa;        /* soft lavender card background */
            --primary: #2e1a2eff;     /* lavender accent */
            --text: #000000ff;        /* main text color */
            --highlight: #000000ff;   /* darker lavender for buttons/status */
            --shadow: 0 3px 10px rgba(0,0,0,0.08);
        }

        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            background: var(--bg);
            display: flex;
            color: var(--text);
        }

        /* Sidebar */
        .sidebar {
            width: 240px;
            background: var(--primary);
            color: #ffffff;
            height: 100vh;
            position: fixed;
            display: flex;
            flex-direction: column;
            padding: 40px 20px;
            box-shadow: var(--shadow);
        }

        .sidebar h2 {
            text-align: center;
            font-weight: 700;
            font-size: 30px;
            margin-bottom: 40px;
        }

        .sidebar a {
            display: block;
            color: #ffffff;
            padding: 20px 18px;
            margin: 8px 0;
            text-decoration: none;
            border-radius: 50px;
            transition: 0.3s;
        }

        .sidebar a:hover {
            background: var(--bg);
            transform: translateX(3px);
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
            font-weight: 700;
            color: var(--primary);
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
            background: #fff;
            color: var(--text);
        }

        .search-bar input:focus {
            border-color: var(--primary);
            outline: none;
        }

        .notif {
            font-size: 20px;
            cursor: pointer;
        }

        #live-time {
            font-weight: 500;
            color: var(--highlight);
        }

        /* Card styles */
        .card {
            background: var(--card);
            padding: 20px;
            border-radius: 16px;
            box-shadow: var(--shadow);
            margin-bottom: 25px;
        }

        .card h3 {
            color: var(--highlight);
            margin-bottom: 15px;
            font-weight: 600;
        }

        /* Table */
        table {
            width: 100%;
            border-collapse: collapse;
            background: var(--bg);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: var(--shadow);
            color: var(--text);
        }

        th, td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            text-align: center;
        }

        th {
            background: var(--primary);
            color: #fff;
            font-weight: 600;
        }

        tr:hover {
            background: #f3eafa;
        }

        .btn {
            display: inline-block;
            background: var(--primary);
            color: white;
            padding: 8px 14px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 500;
            transition: 0.3s;
        }

        .btn:hover {
            background: var(--highlight);
        }

        .badge {
            padding: 5px 10px;
            border-radius: 8px;
            font-weight: bold;
        }

        .pending { background: #f4e2ff; color: #6e54a3; }
        .approved { background: #d9f0ff; color: #0a5c91; }
        .cancelled { background: #f8d7da; color: #721c24; }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 15px;
            gap: 8px;
        }

        .pagination a {
            background: var(--bg);
            border: 1px solid #ccc;
            padding: 8px 14px;
            border-radius: 6px;
            text-decoration: none;
            color: var(--primary);
            transition: 0.3s;
        }

        .pagination a:hover {
            background: var(--highlight);
            color: white;
        }

        .pagination .active {
            background: var(--primary);
            color: white;
            font-weight: bold;
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <h2>🌸 BloomLux Production 🌸</h2>
    <a href="home.php">🏠 Dashboard</a>
    <a href="supply.php" style="background: var(--light-brown);">📦 Supply</a>
    <a href="production.php">🧁 Production</a>
    <a href="my_requests.php">📋 My Requests</a>
    <a href="inventory.php">📊 Inventory</a>
    <a href="logout.php">🚪 Logout</a>
</div>

<!-- Main -->
<div class="main">
    <div class="top-bar">
        <div class="welcome">📦 Supply Management</div>
        <div class="top-right">
            <div id="live-time">⏰ Loading...</div>
            <div class="search-bar"><input type="text" placeholder="Search ingredient..."></div>
            <span class="notif">🔔</span>
        </div>
    </div>

    <div class="card">
        <h3>🥖 Available Ingredients</h3>
        <table>
            <tr>
                <th>Ingredient</th>
                <th>Price</th>
                <th>Supplier</th>
                <th>Action</th>
            </tr>
            <tr>
                <td>Flour</td>
                <td>$20 / 25kg</td>
                <td>ABC Mills</td>
                <td><a href="request_form.php?ingredient=Flour" class="btn">Request</a></td>
            </tr>
            <tr>
                <td>Sugar</td>
                <td>$18 / 25kg</td>
                <td>Sweet Co.</td>
                <td><a href="request_form.php?ingredient=Sugar" class="btn">Request</a></td>
            </tr>
            <tr>
                <td>Butter</td>
                <td>$45 / 10kg</td>
                <td>Dairy Best</td>
                <td><a href="request_form.php?ingredient=Butter" class="btn">Request</a></td>
            </tr>
            <tr>
                <td>Yeast</td>
                <td>$12 / 5kg</td>
                <td>BakePro</td>
                <td><a href="request_form.php?ingredient=Yeast" class="btn">Request</a></td>
            </tr>
        </table>
    </div>

    <div class="card">
        <h3>📋 Ingredient Requests</h3>
        <table>
            <tr>
                <th>ID</th>
                <th>Ingredient</th>
                <th>Quantity</th>
                <th>Unit</th>
                <th>Status</th>
                <th>Requested At</th>
            </tr>
            <?php if ($result_requests->num_rows > 0): ?>
                <?php while ($row = $result_requests->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['id']; ?></td>
                        <td><?= htmlspecialchars($row['ingredient_name']); ?></td>
                        <td><?= htmlspecialchars($row['quantity']); ?></td>
                        <td><?= htmlspecialchars($row['unit']); ?></td>
                        <td>
                            <span class="badge <?= strtolower($row['status']); ?>">
                                <?= ucfirst($row['status']); ?>
                            </span>
                        </td>
                        <td><?= $row['requested_at']; ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" style="color:#8b4513;">No ingredient requests yet.</td>
                </tr>
            <?php endif; ?>
        </table>

        <div class="pagination">
            <a href="#" class="active">1</a>
            <a href="#">2</a>
            <a href="#">3</a>
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
