<?php
session_start();
include '../db.php'; // Database connection

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}


// Handle delete request
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $stmt = $conn->prepare("DELETE FROM inventory WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $stmt->close();
    $deleted = true; // flag to show deleted message
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_name = $_POST['item_name'];
    $quantity = $_POST['quantity'];
    $unit = $_POST['unit'];
    $status = $_POST['status'];

    $stmt = $conn->prepare("INSERT INTO inventory (item_name, quantity, unit, status) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("siss", $item_name, $quantity, $unit, $status);
    $stmt->execute();

    $success = true; // show success message
}

// Fetch all stock items
$result = $conn->query("SELECT * FROM inventory ORDER BY updated_at DESC");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Inventory - Bakery</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #fdf6f0;
            margin: 0;
            padding: 0;
        }

        .main {
            padding: 20px 40px;
            max-width: 1400px;
            margin: 0 auto;
        }

        h1 {
            color: #8b4513;
            margin-bottom: 20px;
            text-align: center;
        }

        .back-btn {
            display: inline-block;
            margin-bottom: 20px;
            padding: 6px 12px;
            background: #8b4513;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            font-size: 14px;
        }

        .back-btn:hover {
            background: #5a2d0c;
        }

        .add-btn-container {
            text-align: left;
            margin-bottom: 15px;
        }

        .form-box {
            display: none;
            background: white;
            padding: 25px 30px;
            border-radius: 12px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
            margin: 0 auto 30px;
            width: 100%;
            max-width: 600px;
            box-sizing: border-box;
        }

        label {
            display: block;
            margin: 10px 0 5px;
            font-weight: bold;
        }

        input,
        select,
        button {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 8px;
            box-sizing: border-box;
        }

        button {
            background: #8b4513;
            color: white;
            font-weight: bold;
            border: none;
            cursor: pointer;
        }

        button:hover {
            background: #5a2d0c;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff8f0;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            margin-bottom: 50px;
        }

        th,
        td {
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

        .status-available {
            color: green;
            font-weight: bold;
        }

        .status-low {
            color: orange;
            font-weight: bold;
        }

        .status-out {
            color: red;
            font-weight: bold;
        }

        .success-msg {
            color: green;
            font-weight: bold;
            text-align: center;
            margin-bottom: 15px;
        }

        /* Buttons */
        .btn,
        .btn-delete {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 5px;
            font-weight: bold;
            text-align: center;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            border: none;
        }

        .btn {
            background: #8b4513;
            color: white;
        }

        .btn:hover {
            background: #5a2d0c;
        }

        .btn-delete {
            background: #b22222;
            color: white;
        }

        .btn-delete:hover {
            background: #8b0000;
        }

        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .modal {
            background: #fff;
            padding: 20px 30px;
            border-radius: 12px;
            width: 90%;
            max-width: 400px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        .modal h3 {
            margin-bottom: 20px;
        }

        .modal button {
            width: 45%;
            margin: 5px;
        }
    </style>
    <script>
        function toggleForm() {
            const formBox = document.getElementById('addStockForm');
            formBox.style.display = (formBox.style.display === 'block') ? 'none' : 'block';
        }

        // Modal Delete
        let deleteId = 0;

        function showDeleteModal(id) {
            deleteId = id;
            document.getElementById('modalOverlay').style.display = 'flex';
        }

        function confirmDelete() {
            window.location.href = 'add_stock.php?delete_id=' + deleteId;
        }

        function cancelDelete() {
            document.getElementById('modalOverlay').style.display = 'none';
        }
    </script>
</head>

<body>
    <div class="main">
        <a href="../admin_dashboard.php" class="back-btn">⬅ Back to Admin Dashboard</a>
        <h1>📦 Inventory</h1>

        <?php if (isset($success) && $success): ?>
            <div class="success-msg">Stock added successfully! ✅</div>
        <?php endif; ?>
        <?php if (isset($deleted) && $deleted): ?>
            <div class="success-msg">Stock deleted successfully! ✅</div>
        <?php endif; ?>

        <div class="add-btn-container">
            <button class="btn" onclick="toggleForm()">➕ Add Stock</button>
        </div>

        <div id="addStockForm" class="form-box">
            <h2>➕ Add Stock Item</h2>
            <form method="POST">
                <label>Item Name</label>
                <input type="text" name="item_name" required>

                <label>Quantity</label>
                <input type="number" name="quantity" min="1" required>

                <label>Unit</label>
                <select name="unit" required>
                    <option value="kg">Kilograms (kg)</option>
                    <option value="g">Grams (g)</option>
                    <option value="L">Liters (L)</option>
                    <option value="ml">Milliliters (ml)</option>
                    <option value="pcs">Pieces</option>
                </select>

                <label>Status</label>
                <select name="status" required>
                    <option value="available">Available</option>
                    <option value="low">Low</option>
                    <option value="out">Out of Stock</option>
                </select>

                <button type="submit">Add Stock</button>
            </form>
        </div>

        <table>
            <tr>
                <th>ID</th>
                <th>Item Name</th>
                <th>Quantity</th>
                <th>Unit</th>
                <th>Status</th>
                <th>Updated At</th>
                <th>Actions</th>
            </tr>
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['id']; ?></td>
                        <td><?= htmlspecialchars($row['item_name']); ?></td>
                        <td><?= $row['quantity']; ?></td>
                        <td><?= $row['unit']; ?></td>
                        <td class="status-<?= $row['status']; ?>"><?= ucfirst($row['status']); ?></td>
                        <td><?= $row['updated_at']; ?></td>
                        <td>
                            <button class="btn" onclick="window.location.href='update_stock.php?id=<?= $row['id']; ?>'">Edit</button>
                            <button class="btn btn-delete" onclick="showDeleteModal(<?= $row['id']; ?>)">Delete</button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" style="color:#8b4513;">No stock items found.</td>
                </tr>
            <?php endif; ?>
        </table>
    </div>

    <!-- Modal -->
    <div class="modal-overlay" id="modalOverlay">
        <div class="modal">
            <h3>⚠️ Are you sure you want to delete this stock?</h3>
            <button onclick="confirmDelete()" class="btn btn-delete">Yes, Delete</button>
            <button onclick="cancelDelete()" class="btn">Cancel</button>
        </div>
    </div>

</body>

</html>