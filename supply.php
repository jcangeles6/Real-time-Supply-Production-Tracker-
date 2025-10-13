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
    <title>🌸 BloomLux Supply 🌸</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/supply.css">
</head>

<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <h2>🌸 BloomLux Supply 🌸</h2>
        <a href="home.php">🌸 Back to Dashboard 🌸</a>
        <a href="supply.php">📦 Supply</a>
        <a href="production.php">🧁 Production</a>
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
                <div class="notif" id="notif-icon" style="position:relative;cursor:pointer;">
                    🔔
                    <span id="notif-badge"></span>
                </div>
                <div id="notif-dropdown" style="display:none;position:absolute;right:20px;top:50px;width:300px;max-height:400px;overflow-y:auto;background:#fff;border-radius:10px;box-shadow:0 4px 12px rgba(0,0,0,0.2);z-index:100;">
                    <ul id="notif-feed" style="list-style:none;padding:10px;margin:0;">
                        <!-- Notifications will be dynamically inserted here -->
                    </ul>
                </div>
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
                    <td>Design</td>
                    <td>₱1000 / 100 PCS</td>
                    <td>ABC Mills</td>
                    <td><a href="request_form.php?ingredient=Flour" class="btn">Request</a></td>
                </tr>
                <tr>
                    <td>Paper</td>
                    <td>₱200 / 250 PCS</td>
                    <td>Sweet Co.</td>
                    <td><a href="request_form.php?ingredient=Sugar" class="btn">Request</a></td>
                </tr>
                <tr>
                    <td>Ribbon</td>
                    <
                        <td>₱1500 / 100 PCS</td>
                        <td>Dairy Best</td>
                        <td><a href="request_form.php?ingredient=Butter" class="btn">Request</a></td>
                </tr>
                <tr>
                    <td>Rose</td>
                    <td>₱1000 / 160 PCS</td>
                    <td>BakePro</td>
                    <td><a href="request_form.php?ingredient=Yeast" class="btn">Request</a></td>
                </tr>
                <tr>
                    <td>Base</td>
                    <td>₱1300 / 50 PCS</td>
                    <td>ABC Mills</td>
                    <td><a href="request_form.php?ingredient=Flour" class="btn">Request</a></td>
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
        document.addEventListener('DOMContentLoaded', () => {
            const notifFeed = document.querySelector('#notif-feed');
            const notifBadge = document.querySelector('#notif-badge');
            const notifDropdown = document.querySelector('#notif-dropdown');
            const notifIcon = document.querySelector('#notif-icon');

            let readNotifications = JSON.parse(localStorage.getItem('readNotifications') || '[]');

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

            notifIcon.addEventListener('click', () => {
                const isVisible = notifDropdown.style.display === 'block';
                notifDropdown.style.display = isVisible ? 'none' : 'block';

                if (!isVisible) {
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

            async function updateNotifications() {
                try {
                    const allNotifs = [];

                    // --- Low stock ---
                    const stockRes = await fetch('get_stock.php');
                    const stockData = stockRes.ok ? await stockRes.json() : {
                        items: []
                    };
                    Object.values(stockData.items || []).filter(i => i.quantity <= i.threshold)
                        .forEach(item => allNotifs.push({
                            text: `⚠️ ${item.name} stock is low! (Available: ${item.quantity})`,
                            timestamp: new Date().toISOString(),
                            type: 'low-stock'
                        }));

                    // --- Production ---
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

                    // --- Sort and slice to latest 7 ---
                    allNotifs.sort((a, b) => new Date(b.timestamp) - new Date(a.timestamp));
                    const latestNotifs = allNotifs.slice(0, 7);

                    notifFeed.innerHTML = '';
                    let lowStockDividerAdded = false;

                    // --- Today header + view all ---
                    const todayHeader = document.createElement('li');
                    todayHeader.classList.add('notif-header');
                    todayHeader.dataset.date = 'Today';
                    const headerSpan = document.createElement('span');
                    headerSpan.textContent = 'TODAY'; // <-- caps lock here
                    todayHeader.appendChild(headerSpan);

                    const viewAllBtn = document.createElement('button');
                    viewAllBtn.textContent = 'View All';
                    viewAllBtn.classList.add('view-all-btn');
                    viewAllBtn.style.cssText = `
                background: #ff4d4d; color: #fff; border: none;
                border-radius: 5px; padding: 2px 8px; cursor: pointer;
                font-size: 0.75rem; margin-left: 10px;
            `;
                    viewAllBtn.addEventListener('click', () => window.location.href = 'notification.html');
                    todayHeader.appendChild(viewAllBtn);
                    notifFeed.appendChild(todayHeader);

                    // --- Production divider ---
                    const productionDivider = document.createElement('li');
                    productionDivider.classList.add('notif-divider', 'production-update');
                    productionDivider.style.textAlign = 'center';
                    productionDivider.style.color = '#888';
                    productionDivider.style.fontWeight = '600';
                    productionDivider.style.fontSize = '0.8rem';
                    productionDivider.style.padding = '5px 0';
                    productionDivider.textContent = 'Production Update';

                    // --- Append notifications ---
                    latestNotifs.forEach(n => {
                        const li = document.createElement('li');
                        li.dataset.batchId = n.batchId || '';
                        li.textContent = n.text;

                        if (n.type === 'low-stock') li.classList.add('low-stock');
                        else if (n.type === 'in_progress') li.classList.add('notif-in_progress');
                        else if (n.type === 'completed') li.classList.add('notif-completed');

                        if (!readNotifications.includes(n.text)) li.classList.add('new-notif');

                        if (n.type === 'low-stock') {
                            notifFeed.appendChild(li);
                        } else {
                            if (!lowStockDividerAdded) {
                                notifFeed.appendChild(productionDivider);
                                lowStockDividerAdded = true;
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
                        void notifBadge.offsetWidth;
                        notifBadge.classList.add('pulse');
                    } else {
                        notifBadge.style.display = 'none';
                    }

                } catch (err) {
                    console.error('Error updating notifications:', err);
                }
            }

            updateNotifications();
            setInterval(updateNotifications, 5000);
        });
    </script>


</body>

</html>