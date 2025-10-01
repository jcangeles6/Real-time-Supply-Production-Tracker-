<?php
session_start();
include 'db.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch ingredient requests
$result = $conn->query("SELECT * FROM requests ORDER BY requested_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Supply - Bakery</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #fdf6f0;
            margin: 0;
            padding: 0;
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
        h1 {
            color: #8b4513;
            margin-bottom: 20px;
        }

        /* Back button */
        .back-btn {
            display: inline-block;
            float: right;
            margin-top: -10px;
            margin-bottom: 20px;
            padding: 8px 16px;
            background: #8b4513;
            color: white;
            border-radius: 20px;
            text-decoration: none;
            font-weight: bold;
        }
        .back-btn:hover {
            background: #a0522d;
        }

        /* Table */
        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff8f0;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0px 2px 5px rgba(0,0,0,0.1);
        }
        th, td {
            padding: 12px 15px;
            border-bottom: 1px solid #ddd;
            text-align: center;
        }
        th {
            background: #8b4513;
            color: white;
        }
        tr:hover {
            background: #f1e3d3;
        }
        .btn {
            display: inline-block;
            background: #8b4513;
            color: white;
            padding: 6px 12px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
        }
        .btn:hover {
            background: #a0522d;
        }
        .badge {
            padding: 5px 10px;
            border-radius: 5px;
            font-weight: bold;
        }
        .pending {
            background: #ffeb99;
            color: #665c00;
        }
        .approved {
            background: #c6f6c6;
            color: #006600;
        }
        .cancelled {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
        <h2>🍞 Bakery</h2>
        <a href="home.php" style="background:#5a2d0c; border-radius:6px; margin:0 10px 15px; text-align:center;">
            ⬅ Back to Dashboard
        </a>
        <a href="supply.php">Supply</a>
        <a href="production.php">Production</a>
        <a href="my_requests.php">My Requests</a>
        <a href="inventory.php">Inventory</a>
        <a href="logout.php">Logout</a>
    </div>

<!-- Main -->
<div class="main">
    <h1>🥖 Available Ingredients</h1>

    <table>
        <tr>
            <th>Ingredient</th>
            <th>Price</th>
            <th>Supplier</th>
            <th>Action</th>
        </tr>
        <tr>
            <td>Flour</td>
            <td>$20 / 25kg</td>
            <td>ABC Mills</td>
            <td><a href="request_form.php?ingredient=Flour" class="btn">Request</a></td>
        </tr>
        <tr>
            <td>Sugar</td>
            <td>$18 / 25kg</td>
            <td>Sweet Co.</td>
            <td><a href="request_form.php?ingredient=Sugar" class="btn">Request</a></td>
        </tr>
        <tr>
            <td>Butter</td>
            <td>$45 / 10kg</td>
            <td>Dairy Best</td>
            <td><a href="request_form.php?ingredient=Butter" class="btn">Request</a></td>
        </tr>
        <tr>
            <td>Yeast</td>
            <td>$12 / 5kg</td>
            <td>BakePro</td>
            <td><a href="request_form.php?ingredient=Yeast" class="btn">Request</a></td>
        </tr>
    </table>

    <h1 style="margin-top:40px;">📦 Ingredient Requests</h1>
    <table>
        <tr>
            <th>ID</th>
            <th>Ingredient</th>
            <th>Quantity</th>
            <th>Unit</th>
            <th>Status</th>
            <th>Requested At</th>
        </tr>
        <?php if ($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['id']; ?></td>
                    <td><?= $row['ingredient_name']; ?></td>
                    <td><?= $row['quantity']; ?></td>
                    <td><?= $row['unit']; ?></td>
                    <td>
                        <span class="badge <?= strtolower($row['status']); ?>">
                            <?= ucfirst($row['status']); ?>
                        </span>
                    </td>
                    <td><?= $row['requested_at']; ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="6" style="color:#8b4513;">No ingredient requests yet.</td></tr>
        <?php endif; ?>
    </table>
</div>

</body>
</html>
