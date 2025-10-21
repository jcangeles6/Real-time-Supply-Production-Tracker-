<?php
include __DIR__ . '/../init.php';

header('Content-Type: application/json');

$items = [];
$result = $conn->query("SELECT * FROM inventory ORDER BY item_name ASC");

while ($row = $result->fetch_assoc()) {
    $item_id = $row['id'];
    $total_qty = (int)$row['quantity'];

    // Get reserved quantity for scheduled/in-progress batches
    $reserved_result = $conn->query("
        SELECT COALESCE(SUM(quantity_reserved), 0) AS reserved
        FROM batch_materials bm
        JOIN batches b ON bm.batch_id = b.id
        WHERE bm.stock_id = $item_id
          AND b.status IN ('scheduled','in_progress')
    ");
    $reserved = (int)$reserved_result->fetch_assoc()['reserved'];

    $available_qty = $total_qty - $reserved;

    // Get threshold
    $threshold_result = $conn->query("SELECT threshold FROM stock_thresholds WHERE item_id = $item_id LIMIT 1");
    $threshold = $threshold_result->num_rows ? (int)$threshold_result->fetch_assoc()['threshold'] : 10;

    $items[] = [
        'item_name' => $row['item_name'],
        'unit' => $row['unit'],
        'total_qty' => $total_qty,
        'available_qty' => $available_qty,
        'threshold' => $threshold
    ];
}

echo json_encode($items);
