<?php
include 'backend/init.php';

// Get completed batches today
$query = "
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
";
$batches = $conn->query($query);
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
    <style>
        :root {
            --primary: #2e1a2eff;
            --accent: #ffb3ecff;
            --card-bg: #f5f0fa;
            --white: #ffffff;
            --shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            background: var(--accent);
            color: var(--primary);
            display: flex;
        }

        /* Sidebar */
        .sidebar {
            width: 240px;
            background: linear-gradient(180deg, var(--primary), #4a2d4a);
            color: var(--white);
            height: 100vh;
            position: fixed;
            padding: 40px 20px;
            display: flex;
            flex-direction: column;
            box-shadow: var(--shadow);
        }

        .sidebar h2 {
            text-align: center;
            font-weight: 600;
            font-size: 30px;
            margin-bottom: 40px;
            letter-spacing: 0.5px;
        }

        .sidebar a {
            display: block;
            color: var(--white);
            padding: 20px 18px;
            margin: 8px 0;
            text-decoration: none;
            border-radius: 50px;
            transition: 0.3s ease;
        }

        .sidebar a:hover {
            background: #a87fbf;
            transform: translateX(4px);
        }

        /* Main Section */
        .main {
            margin-left: 260px;
            flex: 1;
            padding: 30px;
        }

        h1 {
            color: var(--primary);
            text-align: center;
            margin-bottom: 10px;
            font-size: 28px;
            font-weight: 700;
        }

        #clock {
            text-align: center;
            color: #3d2240;
            font-weight: 500;
            margin-bottom: 30px;
            font-size: 15px;
        }

        /* Summary Cards */
        .summary {
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
            margin-bottom: 30px;
        }

        .card {
            background: var(--card-bg);
            padding: 20px;
            border-radius: 20px;
            width: 230px;
            text-align: center;
            box-shadow: var(--shadow);
            transition: transform 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(46, 26, 46, 0.2);
        }

        .card h2 {
            margin: 0;
            color: var(--primary);
            font-size: 24px;
        }

        .card p {
            margin: 6px 0 0;
            color: #3d2240;
            font-weight: 500;
        }

        /* Table */
        table {
            width: 100%;
            border-collapse: collapse;
            background: var(--card-bg);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--shadow);
        }

        th, td {
            padding: 12px 14px;
            text-align: center;
            border-bottom: 1px solid #e4d9ef;
        }

        th {
            background: var(--primary);
            color: var(--white);
            font-weight: 500;
        }

        tr:hover {
            background: #f7ecfb;
        }

        /* Controls */
        .controls {
            text-align: center;
            margin-bottom: 20px;
        }

        .btn {
            background: var(--primary);
            color: var(--white);
            padding: 10px 16px;
            border-radius: 25px;
            text-decoration: none;
            margin: 0 8px;
            display: inline-block;
            transition: 0.3s ease;
            font-weight: 600;
        }

        .btn:hover {
            background: #4a2d4a;
            transform: scale(1.05);
        }

        @media (max-width: 768px) {
            .summary {
                flex-direction: column;
                align-items: center;
            }

            .main {
                margin-left: 0;
                padding: 20px;
            }

            .sidebar {
                display: none;
            }
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

        function printReport() {
            window.print();
        }

        function exportCSV() {
            const table = document.querySelector("table");
            let csv = [];

            for (const row of table.rows) {
                const cols = Array.from(row.cells).map(cell => {
                    // Escape double quotes by doubling them
                    let text = cell.innerText.replace(/"/g, '""');
                    // Wrap every cell in double quotes
                    return `"${text}"`;
                });
                csv.push(cols.join(",")); // Join columns with comma
            }

            const blob = new Blob([csv.join("\n")], {
                type: "text/csv"
            });
            const link = document.createElement("a");
            link.href = URL.createObjectURL(blob);
            link.download = "sweetcrumb_report.csv";
            link.click();
        }
    </script>
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
            <tr>
                <th>ID</th>
                <th>Product</th>
                <th>Quantity</th>
                <th>Materials Used</th>
                <th>Completed At</th>
            </tr>
            <?php if (!empty($data)): ?>
                <?php foreach ($data as $row): ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                        <td><?php echo $row['quantity']; ?></td>
                        <td><?php echo htmlspecialchars($row['materials']); ?></td>
                        <td><?php echo date("M d, Y h:i A", strtotime($row['completed_at'])); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5">No batches completed today.</td>
                </tr>
            <?php endif; ?>
        </table>
    </div>
</body>

</html>