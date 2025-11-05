<?php
include 'backend/init.php';

// Pagination setup
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

// Fetch total rows
// Fetch total rows
$stmt_total = $conn->prepare("SELECT COUNT(*) AS total FROM inventory");
$stmt_total->execute();
$total_row = $stmt_total->get_result()->fetch_assoc();
$stmt_total->close();
$total_items = $total_row['total'];
$total_pages = ceil($total_items / $limit);

// Get username safely
$user_id = $_SESSION['user_id'];
$stmt_user = $conn->prepare("SELECT username FROM users WHERE id = ?");
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$user = $stmt_user->get_result()->fetch_assoc();
$stmt_user->close();
$username = $user['username'] ?? 'User';

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
    <a href="home.php">🔙 Back to Dashboard </a>
    <a href="supply.php">📦 Supply</a>
    <a href="production.php">🧁 Production</a>
    <a href="inventory.php">📊 Inventory</a>
    <a href="logout.php">🚪 Logout</a>
</div>

<!-- Main -->
<div class="main">
    <div class="top-bar">
        <div class="welcome">🍰 Materials Inventory List</div>
        <div class="top-right">
            <div id="live-time">⏰ Loading...</div>
            <div class="search-bar">
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
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Image</th>
                    <th>Item Name</th>
                    <th>Available Quantity</th>
                    <th>Unit</th>
                    <th>Status</th>
                    <th>Last Updated</th>
                </tr>
            </thead>
            <tbody id="inventoryBody">
                <!-- Rows will be dynamically populated -->
            </tbody>
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
<script>
const currentPage = <?= $page; ?>;
const limit = <?= $limit; ?>;

let fetchTimeout = null; // debounce timer

async function fetchInventory() {
    try {
        const response = await fetch(`backend/inventory_page/fetch_inventory.php`);
        const data = await response.json();
        if (!data.success) return;

        const tbody = document.getElementById('inventoryBody');
        tbody.innerHTML = '';

        // Pagination: only display rows for current page
        const startIndex = (currentPage - 1) * limit;
        const endIndex = startIndex + limit;
        const pageItems = data.items.slice(startIndex, endIndex);

        pageItems.forEach(row => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${row.id}</td>
                <td class="product-image-cell">
                    <div class="product-image product-${row.item_name.toLowerCase().replace(/\s+/g, '-')}">
                    </div>
                </td> 
                <td>${row.item_name}</td>
                <td>${row.available_quantity}</td>
                <td>${row.unit}</td>
                <td class="status-${row.status.toLowerCase()}">${row.status}</td>
                <td>${row.updated_at}</td>
            `;
            tbody.appendChild(tr);
        });
    } catch (err) {
        console.error('Error fetching inventory:', err);
    }
}

// Fetch immediately
fetchInventory();

// Optional: slower periodic refresh (15s instead of 5s) so it doesn't interrupt typing
setInterval(() => {
    const searchValue = document.getElementById('searchInput').value.trim();
    if (searchValue === '') fetchInventory();
}, 15000);

</script>

</body>
</html>
