<?php
include 'backend/init.php';

// Dashboard Data
$daily_batches_stmt = $conn->prepare("
    SELECT COUNT(*) as count 
    FROM batches 
    WHERE DATE(scheduled_at) = CURDATE() 
      AND is_deleted = 0
");
$daily_batches_stmt->execute();
$daily_batches = $daily_batches_stmt->get_result()->fetch_assoc()['count'];

$in_progress_stmt = $conn->prepare("
    SELECT COUNT(*) as count 
    FROM batches 
    WHERE status = 'in_progress' AND is_deleted = 0
");
$in_progress_stmt->execute();
$in_progress = $in_progress_stmt->get_result()->fetch_assoc()['count'];

$completed_today_stmt = $conn->prepare("
    SELECT COUNT(*) as count 
    FROM batches 
    WHERE status = 'completed' AND DATE(completed_at) = CURDATE()
");
$completed_today_stmt->execute();
$completed_today = $completed_today_stmt->get_result()->fetch_assoc()['count'];

$ingredients_stmt = $conn->prepare("
    SELECT COUNT(*) as count 
    FROM requests 
    WHERE status = 'pending'
");
$ingredients_stmt->execute();
$ingredients_needed = $ingredients_stmt->get_result()->fetch_assoc()['count'];

// Filter (SECURE)
$status_filter = $_GET['status_filter'] ?? 'all';

if ($status_filter !== 'all') {
    $batches_stmt = $conn->prepare("
        SELECT * FROM batches 
        WHERE is_deleted = 0 AND status = ? 
        ORDER BY scheduled_at DESC
    ");
    $batches_stmt->bind_param("s", $status_filter);
} else {
    $batches_stmt = $conn->prepare("
        SELECT * FROM batches 
        WHERE is_deleted = 0 
        ORDER BY scheduled_at DESC
    ");
}
$batches_stmt->execute();
$batches = $batches_stmt->get_result();

if (!$batches) die("SQL Error: " . $conn->error);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>🌸 BloomLux Production 🌸</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="css/production.css">
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
                window.location.href = 'delete_batch.php?id=' + batchId;
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
        <h2>🌸 BloomLux Production 🌸</h2>
        <a href="home.php">🔙 Back to Dashboard </a>
        <a href="supply.php">📦 Supply</a>
        <a href="production.php">🧁 Production</a>
        <a href="inventory.php">📊 Inventory</a>
        <a href="logout.php">🚪 Logout</a>
    </div>

    <div class="main">
        <h1>🌸 BloomLux Production Dashboard 🌸</h1>
        <div id="clock"></div>

        <!-- 🌸 New Container for Dashboard Cards -->
        <div class="container-cards">
            <div class="dashboard">
                <div class="card">
                    <h2>Daily Batches</h2>
                    <p><?php echo $daily_batches; ?> Scheduled</p>
                    <a href="add_batch.php" class="btn">➕ Add Batch</a>
                </div>
                <div class="card loading">
                    <h2>In Progress</h2>
                    <div class="spinner"></div>
                    <h2></h2>
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
        </div>

        <!-- 🌸 New Container for Table -->
        <div class="container-table">
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
                    <th>
                        ID
                        <br>
                        <span class="tooltip">
                            <i class="fa fa-info-circle"></i>
                            <span class="tooltip-text">Batch ID</span>
                        </span>
                    </th>
                    <th>
                        Product
                        <br>
                        <span class="tooltip">
                            <i class="fa fa-info-circle"></i>
                            <span class="tooltip-text">Name of the product</span>
                        </span>
                    </th>
                    <th>
                        Current Stock
                        <br>
                        <span class="tooltip">
                            <i class="fa fa-info-circle"></i>
                            <span class="tooltip-text">Number of items currently in inventory</span>
                        </span>
                    </th>
                    <th>
                        Material Status
                        <br>
                        <span class="tooltip">
                            <i class="fa fa-info-circle"></i>
                            <span class="tooltip-text">Reserved or used material for this batch</span>
                        </span>
                    </th>
                    <th>
                        Status
                        <br>
                        <span class="tooltip">
                            <i class="fa fa-info-circle"></i>
                            <span class="tooltip-text">Current production status</span>
                        </span>
                    </th>
                    <th>
                        Scheduled At
                        <br>
                        <span class="tooltip">
                            <i class="fa fa-info-circle"></i>
                            <span class="tooltip-text">When the batch is scheduled</span>
                        </span>
                    </th>
                    <th>
                        Completed At
                        <br>
                        <span class="tooltip">
                            <i class="fa fa-info-circle"></i>
                            <span class="tooltip-text">When the batch was completed</span>
                        </span>
                    </th>
                    <th>
                        Actions
                        <br>
                        <span class="tooltip">
                            <i class="fa fa-info-circle"></i>
                            <span class="tooltip-text">Available actions for this batch</span>
                        </span>
                    </th>
                </tr>
                <?php while ($row = $batches->fetch_assoc()): ?>
                    <?php
                    $batch_id = $row['id'];
                    $stmt = $conn->prepare("
                        SELECT i.id AS stock_id, i.item_name, i.quantity AS current_stock,
                            bm.quantity_used, bm.quantity_reserved
                        FROM batch_materials bm
                        JOIN inventory i ON bm.stock_id = i.id
                        WHERE bm.batch_id = ?
                    ");
                    $stmt->bind_param("i", $batch_id);
                    $stmt->execute();
                    $materials_res = $stmt->get_result();

                    $materials = [];
                    $startDisabled = false;

                    while ($mat = $materials_res->fetch_assoc()) {
                        $needed_total = $mat['quantity_used'];
                        $after = $mat['current_stock'] - max($needed_total - $mat['quantity_reserved'], 0);

                        if ($after < 0) $startDisabled = true;

                        $materials[] = [
                            'stock_id' => $mat['stock_id'],
                            'name' => $mat['item_name'],
                            'current' => $mat['current_stock'],
                            'needed' => $needed_total,
                            'reserved' => $mat['quantity_reserved'],
                            'after' => $after
                        ];
                    }
                    $stmt->close();
                    ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td>
                            <?= htmlspecialchars($row['product_name']) ?>
                            <div class="quantity-info">Quantity: <b><?= $row['quantity'] ?></b></div>
                        </td>
                        <td>
                            <?php
                            if (!empty($materials)) {
                                foreach ($materials as $m) {
                                    echo "{$m['name']}: <b style='color:blue'>{$m['current']}</b>";
                                    if ($row['status'] === 'scheduled') {
                                        echo " | Needed: <b>{$m['needed']}</b>";
                                    }
                                    echo "<br>";
                                }
                            } else {
                                echo "<span style='color:red;'>No materials linked</span>";
                            }
                            ?>
                        </td>
                        <td>
                            <?php
                            if (!empty($materials)) {
                                foreach ($materials as $m) {
                                    if ($row['status'] === 'scheduled') {
                                        // Scheduled → Reserved
                                        echo "<span style='font-family:Poppins,sans-serif;'>{$m['name']}: Reserved (<b style='color:orange'>{$m['needed']}</b>)</span><br>";
                                    } elseif ($row['status'] === 'in_progress') {
                                        // In Progress → Used
                                        echo "<span style='font-family:Poppins,sans-serif;'>{$m['name']}: Used (<b style='color:green'>{$m['needed']}</b>)</span><br>";
                                    } else {
                                        // Completed
                                        echo "<span style='font-family:Poppins,sans-serif;'>—</span>";
                                    }
                                }
                            } else {
                                echo "<span style='font-family:Poppins,sans-serif;'>—</span>";
                            }
                            ?>
                        </td>
                        <td class="status-<?= $row['status'] ?>"><?= ucfirst($row['status']) ?></td>
                        <td><?= date("M d, Y, h:i A", strtotime($row['scheduled_at'])) ?></td>
                        <td><?= $row['completed_at'] ? date("M d, Y, h:i A", strtotime($row['completed_at'])) : '—' ?></td>
                        <td class="actions">
                            <?php if ($row['status'] === 'scheduled'): ?>
                                <?php if ($startDisabled): ?>
                                    <a href="#" onclick="showStockAlert()" class="btn">▶ Start</a>
                                <?php else: ?>
                                    <a href="update_batch.php?id=<?= $row['id'] ?>&status=in_progress" class="btn">▶ Start</a>
                                <?php endif; ?>
                            <?php elseif ($row['status'] === 'in_progress'): ?>
                                <a href="update_batch.php?id=<?= $row['id'] ?>&status=completed" class="btn" style="background:#6aa84f;">✔ Complete</a>
                            <?php else: ?>
                                <span style="color:gray;">✔ Done</span>
                            <?php endif; ?>
                            <?php if ($row['status'] !== 'completed'): ?>
                                <a href="#" onclick="showDeleteModal(<?= $row['id'] ?>)" class="btn" style="background:#b22222;">🗑️</a>
                                <a href="add_batch.php?batch_id=<?= $row['id'] ?>" class="btn" style="background:#228b22;">📝</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </table>                    
        </div>

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
    <script>
        document.querySelectorAll('.tooltip').forEach(t => {
            const icon = t.querySelector('i');
            const bubble = t.querySelector('.tooltip-text');

            icon.addEventListener('mouseenter', () => {
                const rect = icon.getBoundingClientRect();
            });
        });
    </script>
</body>

<?php if (!empty($_SESSION['batch_error'])): ?>
    <script>
        const modal = document.createElement('div');
        modal.style = "position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);display:flex;justify-content:center;align-items:center;z-index:999;";
        modal.innerHTML = `
        <div style="background:white;padding:20px;border-radius:15px;text-align:center;max-width:400px;">
            <p><?php echo $_SESSION['batch_error']; ?></p>
            <button onclick="this.parentElement.parentElement.remove();" style="margin-top:10px;padding:8px 15px;border:none;border-radius:10px;background:#c47a3f;color:white;cursor:pointer;">OK</button>
        </div>
    `;
        document.body.appendChild(modal);
    </script>
<?php unset($_SESSION['batch_error']);
endif; ?>
</html>