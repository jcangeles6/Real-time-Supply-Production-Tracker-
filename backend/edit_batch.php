<?php
session_start();
include '../db.php';

//EDIT BUTTON SA VIEW STOCK PAGE



if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if (!isset($_GET['id'], $_GET['inventory_id'])) {
    header("Location: add_stock.php");
    exit();
}

$batch_id = intval($_GET['id']);
$inventory_id = intval($_GET['inventory_id']);

// Fetch batch
$stmt = $conn->prepare("SELECT * FROM inventory_batches WHERE id=?");
$stmt->bind_param("i", $batch_id);
$stmt->execute();
$batch = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$batch) {
    echo "❌ Batch not found!";
    exit();
}

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $quantity = floatval($_POST['quantity']);
    $expiration_date = $_POST['expiration_date'] ?? null;

    $batch_status = 'Fresh';
    if ($expiration_date && $expiration_date != '0000-00-00') {
        $today = date('Y-m-d');
        $batch_status = ($expiration_date < $today) ? 'Expired' : 'Fresh';
    }

    $update = $conn->prepare("
        UPDATE inventory_batches
        SET quantity=?, expiration_date=?, status=?, updated_at=NOW()
        WHERE id=?
    ");
    $update->bind_param("dssi", $quantity, $expiration_date, $batch_status, $batch_id);
    $update->execute();
    $update->close();

    // Recalculate inventory total and status
    $total_stmt = $conn->prepare("SELECT SUM(quantity) as total_qty FROM inventory_batches WHERE inventory_id=?");
    $total_stmt->bind_param("i", $inventory_id);
    $total_stmt->execute();
    $total = $total_stmt->get_result()->fetch_assoc()['total_qty'] ?? 0;
    $total_stmt->close();

    // Fetch threshold
    $th_stmt = $conn->prepare("SELECT COALESCE(threshold,10) as threshold FROM stock_thresholds WHERE item_id=?");
    $th_stmt->bind_param("i", $inventory_id);
    $th_stmt->execute();
    $threshold = $th_stmt->get_result()->fetch_assoc()['threshold'] ?? 10;
    $th_stmt->close();

    $status = ($total == 0) ? 'out' : (($total <= $threshold) ? 'low' : 'available');

    $inv_update = $conn->prepare("UPDATE inventory SET quantity=?, status=?, updated_at=NOW() WHERE id=?");
    $inv_update->bind_param("dsi", $total, $status, $inventory_id);
    $inv_update->execute();
    $inv_update->close();

    header("Location: view_stock.php?id=$inventory_id");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>✏️ Edit Batch</title>
    <link rel="stylesheet" href="../css/add_stock.css">
</head>
<body>
<div class="main">
    <a href="view_stock.php?id=<?= $inventory_id ?>" class="back-btn">⬅ Back</a>
    <h1>✏️ Edit Batch #<?= $batch_id ?></h1>
    <form method="POST">
        <label>Quantity</label>
        <input type="number" step="any" min="0" name="quantity" value="<?= $batch['quantity'] ?>" required>
        <label>Expiration Date</label>
        <input type="date" name="expiration_date" value="<?= ($batch['expiration_date'] != '0000-00-00') ? $batch['expiration_date'] : '' ?>">
        <button type="submit">Update Batch</button>
    </form>
</div>
</body>
</html>
