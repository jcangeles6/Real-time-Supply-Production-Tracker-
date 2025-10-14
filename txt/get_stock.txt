<?php
include 'backend/init.php';

$result = $conn->query("SELECT id, item_name, quantity FROM inventory") or die($conn->error);
$items = [];

while ($row = $result->fetch_assoc()) {
    // Reserved quantity for this item (exclude deleted batches)
    $reserved = $conn->query("
        SELECT SUM(quantity_reserved) as total_reserved 
        FROM batch_materials bm 
        JOIN batches b ON bm.batch_id = b.id 
        WHERE bm.stock_id = {$row['id']} 
        AND b.is_deleted = 0
    ")->fetch_assoc()['total_reserved'] ?? 0;

    $available = $row['quantity'] - $reserved;
    $items[$row['id']] = [
        'name' => $row['item_name'],
        'quantity' => $available
    ];
}

echo json_encode(['success' => true, 'items' => $items]);
