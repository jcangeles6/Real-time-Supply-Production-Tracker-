<?php
include 'backend/init.php';

// Get username securely
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$username = $user['username'] ?? 'Unknown';
$stmt->close();

// Pagination setup
$limit = 10; // 10 requests per page
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// Count total requests
$stmt = $conn->prepare("SELECT COUNT(*) AS c FROM requests");
$stmt->execute();
$total_requests = $stmt->get_result()->fetch_assoc()['c'];
$stmt->close();

$total_pages = ceil($total_requests / $limit);

// Fetch requests for current page, newest first
$stmt = $conn->prepare("SELECT * FROM requests ORDER BY requested_at DESC LIMIT ? OFFSET ?");
$stmt->bind_param("ii", $limit, $offset);
$stmt->execute();
$result_requests = $stmt->get_result();
$stmt->close();

// Count requests by status for summary
$statuses = ['pending', 'approved', 'denied'];
$count_pending = $count_approved = $count_denied = 0;

foreach ($statuses as $status) {
    $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM requests WHERE status = ?");
    $stmt->bind_param("s", $status);
    $stmt->execute();
    $count = $stmt->get_result()->fetch_assoc()['c'];
    $stmt->close();

    if ($status === 'pending') $count_pending = $count;
    if ($status === 'approved') $count_approved = $count;
    if ($status === 'denied') $count_denied = $count;
}

// Fetch inventory items
$stmt = $conn->prepare("SELECT * FROM inventory ORDER BY item_name ASC");
$stmt->execute();
$inv_result = $stmt->get_result();
$stmt->close();
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
        <a href="home.php">🔙 Back to Dashboard</a>
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
                    $stmt = $conn->prepare("SELECT * FROM inventory ORDER BY item_name ASC");
                    $stmt->execute();
                    $inv_result = $stmt->get_result();
                    $stmt->close();
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

    // --- Inventory ---
    const invTableBody = document.querySelector('.table-container table');

    const fetchInventory = async () => {
        try {
            const res = await fetch('backend/get_inventory.php');
            const data = await res.json();

            // Clear old rows except header
            invTableBody.querySelectorAll('tr:not(:first-child)').forEach(r => r.remove());

            data.forEach(item => {
                let quantity = parseInt(item.quantity);
                let status = item.status.toLowerCase();

                // Determine display status
                let displayStatus = status;

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
                    <td>${isRequestable ? `<a href="request_form.php?ingredient=${encodeURIComponent(item.item_name)}" class="btn">Request</a>` : '<span style="color:#FF0000;">Out of Stock</span>'}</td>
                `;
                invTableBody.appendChild(tr);
            });
        } catch (err) {
            console.error('Failed to fetch inventory:', err);
        }
    }

    // --- Requests ---
    const requestTableBody = document.querySelector('.request-table');
    const summaryDiv = document.querySelector('.summary');

    const fetchRequests = async () => {
        try {
            const res = await fetch('backend/get_request.php');
            const data = await res.json();
            if (!data.success) return;

            const requests = data.requests || [];

            // Update summary
            summaryDiv.textContent = `Pending: ${data.summary.pending} | Approved: ${data.summary.approved} | Denied: ${data.summary.denied}`;

            // Clear old table rows except header
            requestTableBody.querySelectorAll('tr:not(:first-child)').forEach(r => r.remove());

            // Populate table
            requests.forEach(row => {
                const status = row.status.toLowerCase();
                const tr = document.createElement('tr');
                tr.classList.add(status); // for approved toggle
                tr.innerHTML = `
                    <td>${row.id}</td>
                    <td>${row.ingredient_name}</td>
                    <td>${row.quantity}</td>
                    <td>${row.unit}</td>
                    <td><span class="badge ${status}">${status.charAt(0).toUpperCase() + status.slice(1)}</span></td>
                    <td>${row.requested_at}</td>
                `;
                requestTableBody.appendChild(tr);
            });

            // Update approved toggle visibility
            document.querySelectorAll('tr.approved').forEach(row => {
                row.style.display = hidden ? 'none' : '';
            });

        } catch (err) {
            console.error('Error fetching requests:', err);
        }
    }

    // Poll every 5 seconds
    setInterval(fetchInventory, 5000);
    setInterval(fetchRequests, 5000);

    // Initial load
    fetchInventory();
    fetchRequests();
</script>

    <script src="js/time.js"></script>
    <script src="js/notification.js"></script>
</body>

</html>