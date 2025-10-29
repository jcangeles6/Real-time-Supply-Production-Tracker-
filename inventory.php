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
    <h1>🌸 BloomLux Inventory Dashboard 🌸</h1>
    <div id="clock"></div>

    <div class="card">
        <h3>🍰 Materials Inventory List</h3>
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
function updateClock() {
    const now = new Date();
    document.getElementById('clock').innerText =
        "📅 " + now.toLocaleDateString() + " | ⏰ " + now.toLocaleTimeString();
}
setInterval(updateClock, 1000);
window.onload = updateClock;

// Fetch immediately
fetchInventory();

// Optional: slower periodic refresh (15s instead of 5s) so it doesn't interrupt typing
setInterval(() => {
    const searchValue = document.getElementById('searchInput').value.trim();
    if (searchValue === '') fetchInventory();
}, 15000);

// Live search (frontend filter) with debounce
document.getElementById('searchInput').addEventListener('input', function() {
    const filter = this.value.toLowerCase();

    // Clear any scheduled fetchInventory call
    if (fetchTimeout) clearTimeout(fetchTimeout);

    // Debounce: only apply filter 300ms after user stops typing
    fetchTimeout = setTimeout(() => {
        document.querySelectorAll('#inventoryBody tr').forEach(tr => {
            const name = tr.children[1].textContent.toLowerCase();
            tr.style.display = name.includes(filter) ? '' : 'none';
        });
    }, 300);
});

</script>

</body>
</html>
