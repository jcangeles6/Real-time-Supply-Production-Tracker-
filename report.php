<?php
include 'backend/init.php';

// Get completed batches today
$stmt = $conn->prepare("
    SELECT 
        b.id, 
        b.product_name, 
        b.quantity, 
        b.completed_at,
        GROUP_CONCAT(CONCAT(i.item_name, ' (', bm.quantity_used, ')') SEPARATOR ', ') AS materials
    FROM batches b
    LEFT JOIN batch_materials bm ON b.id = bm.batch_id
    LEFT JOIN inventory i ON bm.stock_id = i.id
    WHERE b.status = 'completed'
      AND DATE(b.completed_at) = CURDATE()
      AND b.is_deleted = 0
    GROUP BY b.id, b.product_name, b.quantity, b.completed_at
    ORDER BY b.completed_at DESC
");
$stmt->execute();
$batches = $stmt->get_result();
$total_completed = $batches->num_rows;
$total_quantity = 0;
$data = [];
while ($row = $batches->fetch_assoc()) {
    $total_quantity += $row['quantity'];
    $data[] = $row;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>🌸 BloomLux | Daily Report</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/report.css">
</head>
<body>
    <div class="sidebar">
        <h2>🌸 BloomLux Reports 🌸</h2>
        <a href="home.php">🌸 Back to Dashboard 🌸</a>
        <a href="supply.php">📦 Supply</a>
        <a href="production.php">🧁 Production</a>
        <a href="inventory.php">📦 Inventory</a>
        <a href="logout.php">🚪 Logout</a>
    </div>

    <div class="main">
        <h1>📊 Daily Production Report</h1>
        <div id="clock"></div>

        <div class="summary">
            <div class="card">
                <h2><?php echo $total_completed; ?></h2>
                <p>Batches Completed Today</p>
            </div>
            <div class="card">
                <h2><?php echo $total_quantity; ?></h2>
                <p>Total Quantity Produced</p>
            </div>
        </div>

        <div class="controls">
            <a href="production.php" class="btn">⬅ Back</a>
            <a href="#" onclick="printReport()" class="btn">🖨 Print</a>
            <a href="#" onclick="exportCSV()" class="btn">📁 Export CSV</a>
        </div>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Product</th>
                    <th>Quantity</th>
                    <th>Materials Used</th>
                    <th>Completed At</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($data)): ?>
                    <?php foreach ($data as $row): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                            <td><?php echo $row['quantity']; ?></td>
                            <td style="text-align: left;">
                                <?php
                                if (!empty($row['materials'])) {
                                    $materials = explode(', ', $row['materials']);
                                    echo implode('<br>', array_map('htmlspecialchars', $materials));
                                } else {
                                    echo "None";
                                }
                                ?>
                            </td>
                            <td><?php echo date("M d, Y h:i A", strtotime($row['completed_at'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5">No batches completed today.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <script src="js/report.js" defer></script>
</body>
</html>
