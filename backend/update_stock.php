<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: add_stock.php");
    exit();
}

$id = $_GET['id'];

$stmt = $conn->prepare("SELECT * FROM inventory WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$stock = $result->fetch_assoc();

if (!$stock) {
    echo "Stock item not found!";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_name = $_POST['item_name'];
    $quantity = $_POST['quantity'];
    $unit = $_POST['unit'];
    $status = $_POST['status'];

    $update = $conn->prepare("UPDATE inventory SET item_name=?, quantity=?, unit=?, status=?, updated_at=NOW() WHERE id=?");
    $update->bind_param("sissi", $item_name, $quantity, $unit, $status, $id);
    $update->execute();

    $success = true;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Update Stock - Bakery</title>
<style>
body {
    font-family: Arial, sans-serif;
    background: #fdf6f0;
    margin: 0;
    padding: 0;
}

.main {
    padding: 20px;
    max-width: 900px;
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
.back-btn:hover { background:#5a2d0c; }

.form-box {
    background: white;
    padding: 25px 30px;
    border-radius: 12px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    margin: 0 auto 30px;
    width: 100%;
    max-width: 600px; /* same as Add Stock form */
    box-sizing: border-box; /* ensure padding doesn’t overflow */
}

label {
    display: block;
    margin: 10px 0 5px;
    font-weight: bold;
}

input, select, button {
    width: 100%;
    padding: 12px;
    margin-bottom: 15px;
    border: 1px solid #ccc;
    border-radius: 8px;
    box-sizing: border-box; /* prevents overlap */
}

button {
    background: #8b4513;
    color: white;
    font-weight: bold;
    border: none;
    cursor: pointer;
}

button:hover { background: #5a2d0c; }

.success-msg {
    color: green;
    font-weight: bold;
    text-align: center;
    margin-bottom: 15px;
}
</style>
</head>
<body>
<div class="main">
    <a href="add_stock.php" class="back-btn">⬅ Back to Inventory</a>
    <h1>✏️ Update Stock</h1>

    <?php if (isset($success) && $success): ?>
        <div class="success-msg">Stock updated successfully! ✅</div>
    <?php endif; ?>

    <div class="form-box">
        <form method="POST">
            <label>Item Name</label>
            <input type="text" name="item_name" value="<?= htmlspecialchars($stock['item_name']); ?>" required>

            <label>Quantity</label>
            <input type="number" name="quantity" value="<?= $stock['quantity']; ?>" min="0" required>

            <label>Unit</label>
            <select name="unit" required>
                <option value="kg" <?= $stock['unit']=='kg'?'selected':'' ?>>Kilograms (kg)</option>
                <option value="g" <?= $stock['unit']=='g'?'selected':'' ?>>Grams (g)</option>
                <option value="L" <?= $stock['unit']=='L'?'selected':'' ?>>Liters (L)</option>
                <option value="ml" <?= $stock['unit']=='ml'?'selected':'' ?>>Milliliters (ml)</option>
                <option value="pcs" <?= $stock['unit']=='pcs'?'selected':'' ?>>Pieces</option>
            </select>

            <label>Status</label>
            <select name="status" required>
                <option value="available" <?= $stock['status']=='available'?'selected':'' ?>>Available</option>
                <option value="low" <?= $stock['status']=='low'?'selected':'' ?>>Low</option>
                <option value="out" <?= $stock['status']=='out'?'selected':'' ?>>Out of Stock</option>
            </select>

            <button type="submit">Update Stock</button>
        </form>
    </div>
</div>
</body>
</html>
