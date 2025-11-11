<?php
include 'backend/init.php';

// Dashboard Data
$daily_batches_stmt = $conn->prepare("
    SELECT COUNT(*) as count 
    FROM batches 
    WHERE DATE(scheduled_at) = CURDATE() 
      AND status = 'scheduled'
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

function formatDurationSeconds(int $seconds): string {
    $seconds = max(0, $seconds);
    $hours = intdiv($seconds, 3600);
    $minutes = intdiv($seconds % 3600, 60);
    $secs = $seconds % 60;

    if ($hours > 0) {
        return sprintf('%d:%02d:%02d', $hours, $minutes, $secs);
    }
    return sprintf('%d:%02d', $minutes, $secs);
}

$ingredients_stmt = $conn->prepare("
    SELECT COUNT(*) as count 
    FROM requests 
    WHERE status = 'pending'
");
$ingredients_stmt->execute();
$ingredients_needed = $ingredients_stmt->get_result()->fetch_assoc()['count'];

// Filter
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

        // Material Breakdown modal
        function showBreakdown(batchId) {
            fetch('backend/production_page/material_breakdown.php?batch_id=' + batchId)
                .then(res => res.json())
                .then(data => {
                    const content = document.getElementById('breakdownContent');
                    content.innerHTML = '';
                    if (data.length === 0) {
                        content.innerHTML = '<p>No material details found.</p>';
                    } else {
                        data.forEach(m => {
                            const div = document.createElement('div');
                            div.innerHTML = `<span style="color:${m.color}; font-weight:bold;">[${m.status}]</span> ${m.name}: Batch #${m.inventory_batch_id} ${m.expiration_date ? `(${m.expiration_date})` : ''} → ${m.quantity_used} used`;
                            content.appendChild(div);
                        });
                    }
                    document.getElementById('breakdownModal').style.display = 'flex';
                });
        }

        function closeBreakdownModal() {
            document.getElementById('breakdownModal').style.display = 'none';
        }

        function updateBatchTimers() {
            const now = Date.now();
            document.querySelectorAll('.batch-timer[data-start]').forEach(timer => {
                const start = Date.parse(timer.getAttribute('data-start'));
                if (!start || Number.isNaN(start)) return;
                const elapsedMs = now - start;
                if (elapsedMs < 0) {
                    timer.textContent = '⏱ 0:00';
                    return;
                }
                const totalSeconds = Math.floor(elapsedMs / 1000);
                const hours = Math.floor(totalSeconds / 3600);
                const minutes = Math.floor((totalSeconds % 3600) / 60);
                const seconds = totalSeconds % 60;
                const formatted = hours > 0
                    ? `${hours}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`
                    : `${minutes}:${String(seconds).padStart(2, '0')}`;
                timer.textContent = `⏱ ${formatted}`;
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            updateBatchTimers();
            setInterval(updateBatchTimers, 1000);
        });
        function openBreakdownModal() {
            document.getElementById("breakdownModal").style.display = "flex";
        }
        function closeBreakdownModal() {
            document.getElementById("breakdownModal").style.display = "none";
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


        <!-- Batch Error Modal -->
        <div id="batchErrorModal" class="modal">
            <div class="modal-content">
                <h3 style="color:#b22222;">⚠️ Warning</h3>
                <p id="batchErrorMessage"></p>
                <button onclick="closeBatchErrorModal()" class="btn" style="background:#6aa84f;">OK</button>
            </div>
        </div>

        <?php if (isset($_SESSION['batch_error'])): ?>
            <script>
                function showBatchErrorModal(message) {
                    const modal = document.getElementById('batchErrorModal');
                    document.getElementById('batchErrorMessage').innerText = message;
                    modal.style.display = 'flex';
                }

                function closeBatchErrorModal() {
                    document.getElementById('batchErrorModal').style.display = 'none';
                }

                // Show modal on page load
                showBatchErrorModal("<?= addslashes($_SESSION['batch_error']) ?>");
            </script>
            <?php unset($_SESSION['batch_error']); ?>
        <?php endif; ?>


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
                    <th>ID</th>
                    <th>Product</th>
                    <th>Current Stock</th>
                    <th>Material Status</th>
                    <th>Status</th>
                    <th>Scheduled At</th>
                    <th>Completed At</th>
                    <th>Actions</th>
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
                            if ($row['status'] === 'completed') {
                                echo "<span style='color:gray;'>—</span>";
                            } elseif ($row['status'] === 'in_progress') {
                                echo "<button class='btn' onclick='showBreakdown({$row['id']})'>🔍 Breakdown</button>";
                            } elseif (!empty($materials)) {
                                foreach ($materials as $m) {
                                    $status_text = ($row['status'] === 'scheduled') ? 'Reserved' : 'Used';
                                    $status_color = ($row['status'] === 'scheduled') ? 'orange' : 'green';
                                    echo "<span style='font-family:Poppins,sans-serif;'>{$m['name']}: {$status_text} (<b style='color:{$status_color}'>{$m['needed']}</b>)</span><br>";
                                }
                            } else {
                                echo "<span style='color:red;'>No materials linked</span>";
                            }
                            ?>
                        </td>
                        <td class="status-<?= $row['status'] ?>"><?= ucfirst($row['status']) ?></td>
                        <td><?= date("M d, Y, h:i A", strtotime($row['scheduled_at'])) ?></td>
                        <td>
                            <?php
                            $startedAt = $row['started_at'] ? strtotime($row['started_at']) : null;
                            $completedAt = $row['completed_at'] ? strtotime($row['completed_at']) : null;
                            if ($row['status'] === 'in_progress' && $startedAt):
                                $startIso = date(DATE_ATOM, $startedAt);
                            ?>
                                <div class="timestamp-label" style="display:none;">Started <?= date("M d, Y, h:i A", $startedAt) ?></div>
                                <div class="batch-timer" data-start="<?= htmlspecialchars($startIso) ?>">⏱ 0:00</div>
                            <?php elseif ($row['status'] === 'completed' && $completedAt): ?>
                                <div><?= date("M d, Y, h:i A", $completedAt) ?></div>
                                <?php if ($startedAt): ?>
                                    <div class="timer-duration">⏱ <?= formatDurationSeconds($completedAt - $startedAt) ?></div>
                                <?php endif; ?>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
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

        <!-- Delete Modal -->
        <div id="deleteModal" class="modal">
            <div class="modal-content">
                <p>Are you sure you want to delete this batch?</p>
                <button onclick="closeModal()" class="btn">Cancel</button>
                <button id="confirmDeleteBtn" class="btn" style="background:#b22222;">Delete</button>
            </div>
        </div>

        <!-- Material Breakdown Modal -->
        <div id="breakdownModal" class="modal">
            <div class="modal-content">
                <h3>Material Breakdown</h3>
                <div id="breakdownContent"></div>
                <button onclick="closeBreakdownModal()" class="btn">Close</button>
            </div>
        </div>

    </div>
</body>

</html>