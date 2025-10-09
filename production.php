<?php
include 'backend/init.php';

// Dashboard Data
$daily_batches = $conn->query("SELECT COUNT(*) as count FROM batches WHERE DATE(scheduled_at) = CURDATE()")->fetch_assoc()['count'];
$in_progress = $conn->query("SELECT COUNT(*) as count FROM batches WHERE status = 'in_progress'")->fetch_assoc()['count'];
$completed_today = $conn->query("SELECT COUNT(*) as count FROM batches WHERE status = 'completed' AND DATE(completed_at) = CURDATE()")->fetch_assoc()['count'];
$ingredients_needed = $conn->query("SELECT COUNT(*) as count FROM requests WHERE status = 'pending'")->fetch_assoc()['count'];

// Filter
$status_filter = $_GET['status_filter'] ?? 'all';
$where = ($status_filter != 'all') ? "AND b.status='$status_filter'" : '';

// Fetch batches
$batches = $conn->query("
    SELECT b.id, b.product_name, b.status, b.scheduled_at, b.completed_at,
           b.quantity AS batch_qty,
           i.item_name AS stock_item, i.quantity AS stock_qty
    FROM batches b
    LEFT JOIN inventory i ON b.stock_id = i.id
    WHERE 1 $where
    ORDER BY b.scheduled_at DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>🍞 SweetCrumb Production</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --brown: #8b4513;
            --light-brown: #c3814a;
            --cream: #fdf6f0;
            --white: #ffffff;
            --soft-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--cream);
            margin: 0;
            display: flex;
            color: #333;
        }

        /* Sidebar */
        .sidebar {
            width: 240px;
            background: linear-gradient(180deg, var(--brown), #a0522d);
            color: var(--white);
            height: 100vh;
            position: fixed;
            padding: 25px 20px;
            display: flex;
            flex-direction: column;
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
            margin: 6px 0;
            text-decoration: none;
            border-radius: 10px;
            transition: 0.3s;
        }
        .sidebar a:hover {
            background: var(--light-brown);
            transform: translateX(4px);
        }

        /* Main Section */
        .main {
            margin-left: 260px;
            flex: 1;
            padding: 30px;
        }

        h1 {
            color: var(--brown);
            text-align: center;
            font-weight: 600;
            margin-bottom: 5px;
        }

        #clock {
            text-align: center;
            color: #6d3f1a;
            font-weight: 500;
            margin-bottom: 30px;
        }

        /* Dashboard cards */
        .dashboard {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
            margin-bottom: 40px;
        }

        .card {
            background: var(--white);
            border-radius: 18px;
            box-shadow: var(--soft-shadow);
            text-align: center;
            padding: 22px;
            width: 230px;
            transition: 0.3s ease;
            position: relative;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .card h2 {
            color: var(--brown);
            margin: 0 0 10px;
        }
        .card p {
            margin: 0 0 15px;
            color: #5a2d0c;
            font-size: 15px;
        }

        .btn {
            background: var(--brown);
            color: var(--white);
            padding: 8px 16px;
            border-radius: 20px;
            text-decoration: none;
            font-size: 14px;
            transition: 0.3s;
        }
        .btn:hover {
            background: var(--light-brown);
        }

        /* Animated Loading Card */
        .card.loading {
            background: linear-gradient(135deg, #fff3e0, #ffe0b2);
            box-shadow: 0 4px 15px rgba(255, 193, 7, 0.4);
            overflow: hidden;
        }
        .card.loading::after {
            content: "";
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, rgba(255,255,255,0) 0%, rgba(255,255,255,0.5) 50%, rgba(255,255,255,0) 100%);
            animation: loadingGlow 1.5s infinite;
        }
        @keyframes loadingGlow {
            0% { left: -100%; }
            100% { left: 100%; }
        }
        .spinner {
            margin: 8px auto 10px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid var(--brown);
            border-radius: 50%;
            width: 28px;
            height: 28px;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Controls */
        .controls {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        input[type="text"], select {
            padding: 10px 14px;
            border: 1px solid #ccc;
            border-radius: 10px;
            font-size: 14px;
            transition: 0.3s;
        }
        input[type="text"]:focus, select:focus {
            border-color: var(--brown);
            outline: none;
        }

        /* Table */
        table {
            width: 100%;
            border-collapse: collapse;
            background: var(--white);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: var(--soft-shadow);
        }

        th, td {
            padding: 12px 14px;
            text-align: center;
            border-bottom: 1px solid #eee;
        }

        th {
            background: var(--brown);
            color: var(--white);
            font-weight: 500;
        }

        tr:hover {
            background: #fff5ea;
        }

        /* Status colors */
        .status-scheduled { color: gray; font-weight: 500; }
        .status-in_progress { color: #d2691e; font-weight: 600; }
        .status-completed { color: green; font-weight: 600; }

        /* Actions */
        td.actions {
            display: flex;
            justify-content: center;
            gap: 6px;
        }
        td.actions .btn {
            padding: 6px 10px;
            font-size: 13px;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: var(--soft-shadow);
        }
    </style>

    <script>
        function updateClock() {
            const now = new Date();
            document.getElementById('clock').innerText =
                "📅 " + now.toLocaleDateString() + " | ⏰ " + now.toLocaleTimeString();
        }
        setInterval(updateClock, 1000);
        window.onload = updateClock;

        function filterTable() {
            const filter = document.getElementById('searchBatch').value.toLowerCase();
            const rows = document.querySelectorAll('table tr');
            rows.forEach((row, index) => {
                if (index === 0) return;
                row.style.display = row.textContent.toLowerCase().includes(filter) ? '' : 'none';
            });
        }

        function showDeleteModal(batchId) {
            const modal = document.getElementById('deleteModal');
            modal.style.display = 'flex';
            document.getElementById('confirmDeleteBtn').onclick = function() {
                window.location.href = 'remove_batch.php?id=' + batchId;
            };
        }

        function closeModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }

        function showStockAlert() {
            alert("⚠️ Insufficient stock to start this batch!");
        }
    </script>
</head>
<body>
    <div class="sidebar">
        <h2>🍞 SweetCrumb</h2>
        <a href="home.php">🏠 Dashboard</a>
        <a href="supply.php">📦 Supply</a>
        <a href="production.php" style="background: var(--light-brown);">🧁 Production</a>
        <a href="my_requests.php">📋 My Requests</a>
        <a href="inventory.php">📊 Inventory</a>
        <a href="logout.php">🚪 Logout</a>
    </div>

    <div class="main">
        <h1>🍰 Production Dashboard</h1>
        <div id="clock"></div>

        <div class="dashboard">
            <div class="card">
                <h2>Daily Batches</h2>
                <p><?php echo $daily_batches; ?> Scheduled</p>
                <a href="add_batch.php" class="btn">➕ Add Batch</a>
            </div>

            <div class="card loading">
                <h2>In Progress</h2>
                <div class="spinner"></div>
                <p><?php echo $in_progress; ?> Ongoing</p>
            </div>

            <div class="card">
                <h2>Completed Today</h2>
                <p><?php echo $completed_today; ?> Done</p>
                <a href="report.php" class="btn">📊 Report</a>
            </div>

            <div class="card">
                <h2>Ingredients Needed</h2>
                <p><?php echo $ingredients_needed; ?> Pending</p>
                <a href="supply.php" class="btn">📦 Supply</a>
            </div>
        </div>

        <div class="controls">
            <input type="text" id="searchBatch" placeholder="🔍 Search batch..." onkeyup="filterTable()">
            <form method="GET">
                <select name="status_filter" onchange="this.form.submit()">
                    <option value="all" <?php if ($status_filter == 'all') echo 'selected'; ?>>All</option>
                    <option value="scheduled" <?php if ($status_filter == 'scheduled') echo 'selected'; ?>>Scheduled</option>
                    <option value="in_progress" <?php if ($status_filter == 'in_progress') echo 'selected'; ?>>In Progress</option>
                    <option value="completed" <?php if ($status_filter == 'completed') echo 'selected'; ?>>Completed</option>
                </select>
            </form>
        </div>

        <table>
            <tr>
                <th>ID</th>
                <th>Product</th>
                <th>Stock</th>
                <th>Qty</th>
                <th>Status</th>
                <th>Scheduled At</th>
                <th>Completed At</th>
                <th>Actions</th>
            </tr>
            <?php while ($row = $batches->fetch_assoc()): ?>
                <?php $startDisabled = ($row['stock_qty'] < 1 || !$row['stock_item']); ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                    <td>
                        <?php
                        if ($row['stock_item']) {
                            $stockDisplay = htmlspecialchars($row['stock_item']) . " (" . $row['stock_qty'] . ")";
                            echo $row['stock_qty'] <= 10
                                ? "<span style='color:red;font-weight:bold;'>$stockDisplay</span>"
                                : "<span style='color:green;'>$stockDisplay</span>";
                        } else echo "<span style='color:red;'>Not linked</span>";
                        ?>
                    </td>
                    <td><?php echo $row['batch_qty']; ?></td>
                    <td class="status-<?php echo $row['status']; ?>"><?php echo ucfirst($row['status']); ?></td>
                    <td><?php echo date("M d, Y, h:i A", strtotime($row['scheduled_at'])); ?></td>
                    <td><?php echo $row['completed_at'] ? date("M d, Y, h:i A", strtotime($row['completed_at'])) : '—'; ?></td>
                    <td class="actions">
                        <?php if ($row['status'] === 'scheduled'): ?>
                            <?php if ($startDisabled): ?>
                                <a href="#" onclick="showStockAlert()" class="btn">▶ Start</a>
                            <?php else: ?>
                                <a href="update_batch.php?id=<?php echo $row['id']; ?>&status=in_progress" class="btn">▶ Start</a>
                            <?php endif; ?>
                        <?php elseif ($row['status'] === 'in_progress'): ?>
                            <a href="update_batch.php?id=<?php echo $row['id']; ?>&status=completed" class="btn">✅ Complete</a>
                        <?php else: ?>
                            <span style="color:gray;">✔ Done</span>
                        <?php endif; ?>
                        <a href="#" onclick="showDeleteModal(<?php echo $row['id']; ?>)" class="btn" style="background:#b22222;">🗑</a>
                        <a href="add_batch.php?product_name=<?php echo urlencode($row['product_name']); ?>&quantity=<?php echo $row['quantity'] ?? 1; ?>" class="btn" style="background:#228b22;">📄</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>

        <div style="text-align:center;margin-top:15px;">
            <a href="production.php" class="btn">🔄 Refresh</a>
        </div>

        <div id="deleteModal" class="modal">
            <div class="modal-content">
                <p>Are you sure you want to delete this batch?</p>
                <button onclick="closeModal()" class="btn">Cancel</button>
                <button id="confirmDeleteBtn" class="btn" style="background:#b22222;">Delete</button>
            </div>
        </div>
    </div>
</body>
</html>
