<?php
include '../db.php';
header('Content-Type: application/json');

$items = [];
$result = $conn->query("
    SELECT i.id, i.item_name, i.quantity, i.unit, 
           COALESCE(st.threshold, 5) AS threshold
    FROM inventory i
    LEFT JOIN stock_thresholds st ON i.id = st.item_id
    ORDER BY i.item_name ASC
");

while ($row = $result->fetch_assoc()) {
    $item_id = $row['id'];
    $quantity = (int)$row['quantity'];
    $threshold = (int)$row['threshold'];

    // Get reserved quantity for scheduled/in-progress batches
    $reserved_result = $conn->query("
        SELECT COALESCE(SUM(bm.quantity_reserved), 0) AS reserved
        FROM batch_materials bm
        JOIN batches b ON bm.batch_id = b.id
        WHERE bm.stock_id = $item_id
          AND b.status IN ('scheduled','in_progress')
    ");
    $reserved = (int)$reserved_result->fetch_assoc()['reserved'];

    $available_quantity = $quantity - $reserved;

    // Determine dynamic status using threshold
    if ($available_quantity <= 0) {
        $status = 'out';
    } elseif ($available_quantity <= $threshold) {
        $status = 'low';
    } else {
        $status = 'available';
    }

    $items[] = [
        'item_name' => $row['item_name'],
        'quantity' => $available_quantity,
        'unit' => $row['unit'],
        'status' => $status
    ];
}

echo json_encode($items);
