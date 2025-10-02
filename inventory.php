<?php
include 'backend/init.php';

// Fetch inventory
$result = $conn->query("SELECT id, item_name, quantity, unit, status, updated_at FROM inventory ORDER BY item_name ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bakery Inventory</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #fdf9f5;
            margin: 0;
        }

        /* Sidebar */
        .sidebar {
            width: 220px;
            background: #8b4513;
            color: #fff;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            padding: 20px 0;
        }
        .sidebar h2 {
            text-align: center;
            margin-bottom: 30px;
        }
        .sidebar a {
            display: block;
            color: #fff;
            padding: 12px 20px;
            text-decoration: none;
            font-weight: bold;
        }
        .sidebar a:hover {
            background: #a0522d;
        }

        /* Main */
        .main {
            margin-left: 240px;
            padding: 20px;
        }

        .back-btn {
            display: inline-block;
            margin-bottom: 20px;
            padding: 8px 16px;
            background: #8b4513;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 14px;
        }
        .back-btn:hover {
            background: #5a2d0c;
        }

        h1 {
            color: #5a2d0c;
            text-align: center;
            margin-bottom: 20px;
        }

        /* Table */
        table {
            width: 90%;
            margin: auto;
            border-collapse: collapse;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }
        th, td {
            padding: 12px;
            text-align: center;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #8b4513;
            color: white;
        }
        tr:hover {
            background: #f3e9e2;
        }

        .status-available {
            color: green;
            font-weight: bold;
        }
        .status-low {
            color: #d2691e;
            font-weight: bold;
        }
        .status-out {
            color: red;
            font-weight: bold;
        }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <h2>🍞 Bakery</h2>
        <a href="home.php" style="background:#5a2d0c; border-radius:6px; margin:0 10px 15px; text-align:center;">
            ⬅ Back to Dashboard</a>
        <a href="supply.php">Supply</a>
        <a href="production.php">Production</a>
        <a href="my_requests.php">My Requests</a>
        <a href="inventory.php">Inventory</a>
        <a href="logout.php">Logout</a>
    </div>

    <!-- Main -->
    <div class="main">
        <a href="home.php" class="back-btn">⬅ Back to Dashboard</a>
        <h1>📦 Bakery Inventory</h1>

        <table>
            <tr>
                <th>ID</th>
                <th>Item</th>
                <th>Quantity</th>
                <th>Unit</th>
                <th>Status</th>
                <th>Last Updated</th>
            </tr>
            <?php while($row = $result->fetch_assoc()): ?>
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
    </div>

</body>
</html>
