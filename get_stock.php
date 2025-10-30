<?php
include 'backend/init.php';

// Main inventory + thresholds
$stmt = $conn->prepare("
    SELECT 
        i.id, 
        i.item_name AS name, 
        i.quantity, 
        i.updated_at, 
        t.threshold
    FROM inventory i
    LEFT JOIN stock_thresholds t ON i.id = t.item_id
");
$stmt->execute();
$result = $stmt->get_result();

$items = [];

$reserved_stmt = $conn->prepare("
    SELECT SUM(quantity_reserved) AS total_reserved 
    FROM batch_materials bm 
    JOIN batches b ON bm.batch_id = b.id 
    WHERE bm.stock_id = ? 
      AND b.is_deleted = 0
");

while ($row = $result->fetch_assoc()) {
    $stock_id = $row['id'];

    // Use prepared statement safely for reserved quantities
    $reserved_stmt->bind_param("i", $stock_id);
    $reserved_stmt->execute();
    $reserved_res = $reserved_stmt->get_result();
    $reserved_data = $reserved_res->fetch_assoc();
    $reserved = $reserved_data['total_reserved'] ?? 0;

    $available = $row['quantity'] - $reserved;

    $items[$row['id']] = [
        'name' => $row['name'],
        'quantity' => $available,
        'threshold' => intval($row['threshold'] ?? 0),
        'updated_at' => $row['updated_at'] ?? null
    ];
}

echo json_encode(['success' => true, 'items' => $items]);
