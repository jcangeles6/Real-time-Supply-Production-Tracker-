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
    <title>🍞 SweetCrumb - Daily Report</title>
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
            margin: 0;
            background: var(--cream);
            color: #333;
            display: flex;
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
            margin-bottom: 10px;
        }

        #clock {
            text-align: center;
            color: #6d3f1a;
            font-weight: 500;
            margin-bottom: 30px;
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
            background: var(--white);
            padding: 20px;
            border-radius: 16px;
            width: 220px;
            text-align: center;
            box-shadow: var(--soft-shadow);
        }

        .card h2 {
            margin: 0;
            color: var(--brown);
        }

        .card p {
            margin: 6px 0 0;
            color: #5a2d0c;
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

        th,
        td {
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

        /* Controls */
        .controls {
            text-align: center;
            margin-bottom: 20px;
        }

        .btn {
            background: var(--brown);
            color: var(--white);
            padding: 10px 16px;
            border-radius: 20px;
            text-decoration: none;
            margin: 0 8px;
            display: inline-block;
            transition: 0.3s;
        }

        .btn:hover {
            background: var(--light-brown);
        }

        @media (max-width: 768px) {
            .summary {
                flex-direction: column;
                align-items: center;
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
        <h2>🍞 SweetCrumb</h2>
        <a href="home.php">🏠 Dashboard</a>
        <a href="production.php">🧁 Production</a>
        <a href="my_requests.php">📋 My Requests</a>
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