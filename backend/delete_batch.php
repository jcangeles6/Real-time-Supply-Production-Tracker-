<?php
session_start();
include '../db.php';

//DELETE BUTTON SA VIEW STOCK PAGE


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

// Delete batch
$del = $conn->prepare("DELETE FROM inventory_batches WHERE id=?");
$del->bind_param("i", $batch_id);
$del->execute();
$del->close();

// Recalculate inventory
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
?>
