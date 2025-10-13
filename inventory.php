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
                <div class="search-bar"><input type="text" placeholder="Search items..."></div>
            </div>
            <div class="notif" id="notif-icon">
                🔔
                <span id="notif-badge" style="background:red;color:white;font-size:0.75rem;border-radius:50%;padding:2px 6px;position:absolute;top:-5px;right:-5px;display:none;">0</span>
            </div>
            <div id="notif-dropdown" style="display:none;position:absolute;right:35px;top:50px;width:300px;max-height:400px;overflow-y:auto;background:#fff;border-radius:10px;box-shadow:0 4px 12px rgba(0,0,0,0.2);z-index:100;">
                <ul id="notif-feed" style="list-style:none;padding:10px;margin:0;"></ul>
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

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const notifFeed = document.querySelector('#notif-feed');
            const notifBadge = document.querySelector('#notif-badge');
            const notifDropdown = document.querySelector('#notif-dropdown');
            const notifIcon = document.querySelector('#notif-icon');

            // Keep track of read notifications
            let readNotifications = JSON.parse(localStorage.getItem('readNotifications') || '[]');

            // Live time clock
            function updateTime() {
                const now = new Date();
                const options = {
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                    weekday: 'short',
                    month: 'short',
                    day: 'numeric'
                };
                document.getElementById("live-time").innerHTML = "⏰ " + now.toLocaleString('en-US', options);
            }
            setInterval(updateTime, 1000);
            updateTime();

            // Toggle dropdown on bell click
            notifIcon.addEventListener('click', () => {
                const isVisible = notifDropdown.style.display === 'block';
                notifDropdown.style.display = isVisible ? 'none' : 'block';

                if (!isVisible) {
                    // Mark visible notifications as read
                    const visibleNotifs = Array.from(notifFeed.querySelectorAll('li.new-notif'));
                    visibleNotifs.forEach(li => li.classList.remove('new-notif'));

                    readNotifications = Array.from(new Set([
                        ...readNotifications,
                        ...visibleNotifs.map(li => li.textContent)
                    ]));
                    localStorage.setItem('readNotifications', JSON.stringify(readNotifications));
                    notifBadge.style.display = 'none';
                }
            });

            // Fetch and update notifications
            async function updateNotifications() {
                try {
                    const allNotifs = [];

                    // --- Low Stock Notifications ---
                    const stockRes = await fetch('get_stock.php');
                    const stockData = stockRes.ok ? await stockRes.json() : {
                        items: []
                    };
                    Object.values(stockData.items || [])
                        .filter(item => item.quantity <= item.threshold)
                        .forEach(item => allNotifs.push({
                            text: `⚠️ ${item.name} stock is low! (Available: ${item.quantity})`,
                            timestamp: new Date().toISOString(),
                            type: 'low-stock'
                        }));

                    // --- Production Updates ---
                    const prodRes = await fetch('get_production.php');
                    const prodData = prodRes.ok ? await prodRes.json() : [];
                    const latestBatchMap = new Map();
                    prodData.forEach(batch => {
                        const existing = latestBatchMap.get(batch.id);
                        if (!existing || new Date(batch.timestamp) > new Date(existing.timestamp)) {
                            latestBatchMap.set(batch.id, batch);
                        }
                    });
                    latestBatchMap.forEach(batch => {
                        const statusLower = batch.status.toLowerCase();
                        if (statusLower.includes('started') || statusLower.includes('completed')) {
                            let icon = '🛠️',
                                statusText = 'Batch Started';
                            if (statusLower.includes('completed')) {
                                icon = '✔️';
                                statusText = 'Batch Completed';
                            }
                            allNotifs.push({
                                text: `${icon} ${batch.product_name} - ${statusText} (${batch.timestamp})`,
                                timestamp: batch.timestamp,
                                type: statusLower.includes('started') ? 'in_progress' : 'completed',
                                batchId: batch.id
                            });
                        }
                    });

                    // --- Sort and keep latest 7 ---
                    allNotifs.sort((a, b) => new Date(b.timestamp) - new Date(a.timestamp));
                    const latestNotifs = allNotifs.slice(0, 7);

                    notifFeed.innerHTML = '';
                    let productionDividerAdded = false;

                    // --- Today Header + View All Button ---
                    const todayHeader = document.createElement('li');
                    todayHeader.classList.add('notif-header');
                    todayHeader.textContent = 'TODAY';
                    const viewAllBtn = document.createElement('button');
                    viewAllBtn.textContent = 'View All';
                    viewAllBtn.classList.add('view-all-btn');
                    viewAllBtn.addEventListener('click', () => window.location.href = 'notification.html');
                    todayHeader.appendChild(viewAllBtn);
                    notifFeed.appendChild(todayHeader);

                    // --- Append notifications ---
                    latestNotifs.forEach(n => {
                        const li = document.createElement('li');
                        li.dataset.batchId = n.batchId || '';
                        li.textContent = n.text;

                        if (n.type === 'low-stock') li.classList.add('low-stock');
                        else if (n.type === 'in_progress') li.classList.add('notif-in_progress');
                        else if (n.type === 'completed') li.classList.add('notif-completed');

                        if (!readNotifications.includes(n.text)) li.classList.add('new-notif');

                        // Append: low-stock first, then production updates
                        if (n.type === 'low-stock') {
                            notifFeed.appendChild(li);
                        } else {
                            if (!productionDividerAdded) {
                                const productionDivider = document.createElement('li');
                                productionDivider.classList.add('notif-divider', 'production-update');
                                productionDivider.textContent = 'Production Update';
                                notifFeed.appendChild(productionDivider);
                                productionDividerAdded = true;
                            }
                            notifFeed.appendChild(li);
                        }
                    });

                    // --- Update badge ---
                    const totalUnread = notifFeed.querySelectorAll('li.new-notif').length;
                    if (totalUnread > 0) {
                        notifBadge.style.display = 'inline-block';
                        notifBadge.textContent = totalUnread > 99 ? '99+' : totalUnread;
                        notifBadge.classList.remove('pulse');
                        void notifBadge.offsetWidth; // trigger reflow
                        notifBadge.classList.add('pulse');
                    } else {
                        notifBadge.style.display = 'none';
                    }

                } catch (err) {
                    console.error('Error updating notifications:', err);
                }
            }

            // Initial load + repeat every 5s
            updateNotifications();
            setInterval(updateNotifications, 5000);
        });
    </script>


</body>

</html>