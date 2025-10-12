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
    <title>🌸 BloomLux Inventory 🌸</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #ffb3ecff;
            --card: #f5f0fa;
            --primary: #2e1a2eff;
            --text: #000000ff;
            --highlight: #000000ff;
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

        /* Main */
        .main {
            margin-left: 260px;
            flex-grow: 1;
            padding: 25px 35px;
        }

        /* Top Bar */
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

        /* Card */
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

        .low-stock {
            background: #ffebee;
            color: #b71c1c;
            font-weight: bold;
            border-radius: 8px;
            padding: 4px 8px;
        }

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
    <h2>🌸 BloomLux Inventory 🌸</h2>
    <a href="home.php">🌸 Back to Dashboard 🌸</a>
    <a href="supply.php">📦 Supply</a>
    <a href="production.php">🧁 Production</a>
    <a href="inventory.php">📊 Inventory</a>
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
