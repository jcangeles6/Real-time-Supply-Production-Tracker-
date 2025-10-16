<?php
include 'backend/init.php';

// Get username
$user_id = $_SESSION['user_id'];
$result = $conn->query("SELECT username FROM users WHERE id = $user_id");
$user = $result->fetch_assoc();
$username = $user['username'];

// Pagination setup
$limit = 10; // 10 requests per page
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// Count total requests
$total_requests = $conn->query("SELECT COUNT(*) as c FROM requests")->fetch_assoc()['c'];
$total_pages = ceil($total_requests / $limit);

// Fetch requests for current page, newest first
$result_requests = $conn->query("SELECT * FROM requests ORDER BY requested_at DESC LIMIT $limit OFFSET $offset");

// Count requests by status for summary
$count_pending = $conn->query("SELECT COUNT(*) as c FROM requests WHERE status='pending'")->fetch_assoc()['c'];
$count_approved = $conn->query("SELECT COUNT(*) as c FROM requests WHERE status='approved'")->fetch_assoc()['c'];
$count_denied = $conn->query("SELECT COUNT(*) as c FROM requests WHERE status='denied'")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>🌸 BloomLux Supply 🌸</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/supply.css">
    <style>
        .summary {
            font-weight: 600;
            margin-bottom: 10px;
        }

        .collapse-toggle {
            cursor: pointer;
            color: var(--primary);
            font-size: 14px;
            margin-bottom: 10px;
            display: inline-block;
        }

        .pagination a {
            margin: 0 5px;
            text-decoration: none;
            color: var(--primary);
            font-weight: 500;
        }

        .pagination a.active {
            font-weight: 700;
            text-decoration: underline;
        }
    </style>
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
            <h3>📦 Available Materials</h3>
            <div class="table-container">
                <table>
                    <tr>
                        <th>Ingredient</th>
                        <th>Quantity</th>
                        <th>Unit</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                    <?php
                    $inv_result = $conn->query("SELECT * FROM inventory ORDER BY item_name ASC");
                    if ($inv_result->num_rows > 0):
                        while ($item = $inv_result->fetch_assoc()):
                            $isAvailable = strtolower($item['status']) === 'available' && intval($item['quantity']) > 0;
                    ?>
                            <tr>
                                <td><?= htmlspecialchars($item['item_name']); ?></td>
                                <td><?= htmlspecialchars($item['quantity']); ?></td>
                                <td><?= htmlspecialchars($item['unit']); ?></td>
                                <td>
                                    <span class="badge <?= strtolower($item['status']); ?>">
                                        <?= ucfirst($item['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($isAvailable): ?>
                                        <a href="request_form.php?ingredient=<?= urlencode($item['item_name']); ?>" class="btn">Request</a>
                                    <?php else: ?>
                                        <span style="color:#8b4513;">Out of Stock</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php
                        endwhile;
                    else:
                        ?>
                        <tr>
                            <td colspan="5" style="color:#8b4513;">No items found in inventory.</td>
                        </tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>

        <div class="card">
            <h3>📋Request Materials</h3>

            <!-- Compact summary -->
            <div class="summary">
                Pending: <?= $count_pending ?> | Approved: <?= $count_approved ?> | Denied: <?= $count_denied ?>
            </div>

            <!-- Collapse toggle for approved requests -->
            <div class="collapse-toggle" id="toggleApproved">Hide Approved Requests ▼</div>

            <table class="request-table">
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
                        <tr class="<?= strtolower($row['status']); ?>">
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

            <!-- Pagination links -->
            <div class="pagination">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?= $i ?>" class="<?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
        </div>
    </div>
    <script>
        const toggleBtn = document.getElementById('toggleApproved');

        // Load state from localStorage
        let hidden = localStorage.getItem('hideApproved') === 'true';

        const updateRows = () => {
            document.querySelectorAll('tr.approved').forEach(row => {
                row.style.display = hidden ? 'none' : '';
            });
            toggleBtn.textContent = hidden ? 'Show Approved Requests ▼' : 'Hide Approved Requests ▼';
        }

        // Initialize on page load
        updateRows();

        toggleBtn.addEventListener('click', () => {
            hidden = !hidden;
            localStorage.setItem('hideApproved', hidden);
            updateRows();
        });

        const invTableBody = document.querySelector('.table-container table');

        const fetchInventory = async () => {
            try {
                const res = await fetch('backend/get_inventory.php'); // your JSON endpoint
                const data = await res.json();

                // Clear old rows except header
                invTableBody.querySelectorAll('tr:not(:first-child)').forEach(r => r.remove());

                data.forEach(item => {
                    let quantity = parseInt(item.quantity);
                    let status = item.status.toLowerCase();

                    // Determine display status
                    let displayStatus = status;
                    if (status === 'available' && quantity <= 5) displayStatus = 'low';

                    // Requestable if quantity > 0
                    let isRequestable = quantity > 0;

                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                    <td>${item.item_name}</td>
                    <td>${item.quantity}</td>
                    <td>${item.unit}</td>
                    <td><span class="badge ${displayStatus}">
                        ${displayStatus.charAt(0).toUpperCase() + displayStatus.slice(1)}
                    </span></td>
                    <td>${isRequestable ? `<a href="request_form.php?ingredient=${encodeURIComponent(item.item_name)}" class="btn">Request</a>` : '<span style="color:#8b4513;">Out of Stock</span>'}</td>
                `;
                    invTableBody.appendChild(tr);
                });
            } catch (err) {
                console.error('Failed to fetch inventory:', err);
            }
        }

        // Fetch every 5 seconds
        setInterval(fetchInventory, 5000);
        fetchInventory(); // initial load
    </script>
    <script src="js/time.js"></script>
    <script src="js/notification.js"></script>
</body>

</html>