<?php
include 'backend/init.php';

// Pagination setup
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

// Fetch total rows
$total_result = $conn->query("SELECT COUNT(*) AS total FROM inventory");
$total_row = $total_result->fetch_assoc();
$total_items = $total_row['total'];
$total_pages = ceil($total_items / $limit);

// Fetch inventory with limit
$result = $conn->query("
    SELECT id, item_name, quantity, unit, status, updated_at
    FROM inventory
    ORDER BY item_name ASC
    LIMIT $start, $limit
");

// Get username
$user_id = $_SESSION['user_id'];
$user_query = $conn->query("SELECT username FROM users WHERE id = $user_id");
$user = $user_query->fetch_assoc();
$username = $user['username'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Inventory | SweetCrumb</title>
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
        .sidebar a:hover, .sidebar a.active {
            background: var(--light-brown);
            transform: translateX(4px);
        }

        /* Main */
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

        /* Table Card */
        .card {
            background: var(--white);
            padding: 25px;
            border-radius: 16px;
            box-shadow: var(--soft-shadow);
            margin-top: 20px;
        }
        .card h3 {
            color: var(--brown);
            margin-bottom: 20px;
            font-weight: 600;
            text-align: center;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
        }
        th, td {
            padding: 14px;
            text-align: center;
            border-bottom: 1px solid #eee;
        }
        th {
            background: var(--brown);
            color: white;
            font-weight: 500;
        }
        tr:hover {
            background: #fff7ef;
        }

        .status-available {
            color: green;
            font-weight: 600;
        }
        .status-low {
            color: #d2691e;
            font-weight: 600;
        }
        .status-out {
            color: red;
            font-weight: 600;
        }

        /* Pagination */
        .pagination {
            text-align: center;
            margin-top: 25px;
        }
        .pagination a {
            display: inline-block;
            padding: 8px 14px;
            margin: 0 4px;
            background: var(--brown);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 14px;
            transition: 0.3s;
        }
        .pagination a.active {
            background: var(--light-brown);
            font-weight: 600;
        }
        .pagination a:hover {
            background: var(--light-brown);
        }

        @media (max-width: 768px) {
            .main {
                margin-left: 0;
                padding: 20px;
            }
            .sidebar {
                display: none;
            }
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <h2>🍞 SweetCrumb</h2>
    <a href="home.php">🏠 Dashboard</a>
    <a href="supply.php">📦 Supply</a>
    <a href="production.php">🧁 Production</a>
    <a href="my_requests.php">📋 My Requests</a>
    <a href="inventory.php" class="active">📊 Inventory</a>
    <a href="logout.php">🚪 Logout</a>
</div>

<!-- Main -->
<div class="main">
    <div class="top-bar">
        <div class="welcome">📊 Inventory Overview</div>
        <div class="top-right">
            <div id="live-time">⏰ Loading...</div>
            <div class="search-bar"><input type="text" placeholder="Search items..."></div>
            <span class="notif">🔔</span>
        </div>
    </div>

    <div class="card">
        <h3>🍰 Bakery Inventory List</h3>
        <table>
            <tr>
                <th>ID</th>
                <th>Item Name</th>
                <th>Quantity</th>
                <th>Unit</th>
                <th>Status</th>
                <th>Last Updated</th>
            </tr>
            <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id']; ?></td>
                <td><?= htmlspecialchars($row['item_name']); ?></td>
                <td><?= $row['quantity']; ?></td>
                <td><?= $row['unit']; ?></td>
                <td class="status-<?= strtolower($row['status']); ?>">
                    <?= ucfirst($row['status']); ?>
                </td>
                <td><?= $row['updated_at']; ?></td>
            </tr>
            <?php endwhile; ?>
        </table>

        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1; ?>">⮜ Prev</a>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?= $i; ?>" class="<?= ($i == $page) ? 'active' : ''; ?>"><?= $i; ?></a>
            <?php endfor; ?>
            <?php if ($page < $total_pages): ?>
                <a href="?page=<?= $page + 1; ?>">Next ⮞</a>
            <?php endif; ?>
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
