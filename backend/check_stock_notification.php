<?php
header('Content-Type: application/json');

// Include DB properly
include __DIR__ . '/../db.php'; // go up one folder if db.php is in root

// Include init only if needed (and also fix its includes)


$result = $conn->query("
    SELECT 
        i.id, 
        i.item_name AS name, 
        i.quantity, 
        i.updated_at, 
        t.threshold
    FROM inventory i
    LEFT JOIN stock_thresholds t ON i.id = t.item_id
") or die(json_encode(['success' => false, 'error' => $conn->error]));

$items = [];

while ($row = $result->fetch_assoc()) {
    $reservedQuery = $conn->query("
        SELECT SUM(quantity_reserved) AS total_reserved 
        FROM batch_materials bm 
        JOIN batches b ON bm.batch_id = b.id 
        WHERE bm.stock_id = {$row['id']} 
        AND b.is_deleted = 0
    ");
    $reserved = $reservedQuery->fetch_assoc()['total_reserved'] ?? 0;
    $available = $row['quantity'] - $reserved;

    $items[$row['id']] = [
        'name' => $row['name'],
        'quantity' => $available,
        'threshold' => intval($row['threshold'] ?? 0),
        'updated_at' => $row['updated_at'] ?? null
    ];
}

echo json_encode(['success' => true, 'items' => $items]);
