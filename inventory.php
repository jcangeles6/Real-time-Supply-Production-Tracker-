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
    <link rel="stylesheet" href="css/inventory.css">
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
                <div class="search-bar">
                    <input type="text" placeholder="Search items...">
                </div>
                <div class="notif" id="notif-icon">
                    🔔
                    <span id="notif-badge" style="background:red;color:white;font-size:0.75rem;border-radius:50%;padding:2px 6px;position:absolute;top:-5px;right:-5px;display:none;">0</span>
                </div>
                <div id="notif-dropdown">
                    <ul id="notif-feed"></ul>
                </div>
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
                <?php while ($row = $result->fetch_assoc()): ?>
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
    <script src="js/time.js"></script>
    <script src="js/notification.js"></script>
</body>

</html>