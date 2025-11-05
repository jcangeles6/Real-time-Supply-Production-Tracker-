<?php
include __DIR__ . '/../init.php';

$items = [];

$stmt = $conn->prepare("
    SELECT 
        i.id, 
        i.item_name AS name,
        COALESCE(SUM(
            CASE 
                WHEN b.expiration_date IS NULL OR b.expiration_date >= CURDATE() THEN b.quantity
                ELSE 0
            END
        ), 0) AS available_quantity,
        COALESCE(SUM(
            CASE 
                WHEN b.expiration_date IS NOT NULL AND b.expiration_date < CURDATE() THEN b.quantity
                ELSE 0
            END
        ), 0) AS expired_quantity,
        COALESCE(st.threshold, 10) AS threshold
    FROM inventory i
    LEFT JOIN inventory_batches b ON b.inventory_id = i.id
    LEFT JOIN stock_thresholds st ON i.id = st.item_id
    GROUP BY i.id
");
$stmt->execute();
$result = $stmt->get_result();

$reserved_stmt = $conn->prepare("
    SELECT COALESCE(SUM(bm.quantity_reserved),0) AS reserved
    FROM batch_materials bm
    JOIN batches b ON bm.batch_id = b.id
    WHERE bm.stock_id = ?
      AND b.status IN ('scheduled','in_progress')
");

while ($row = $result->fetch_assoc()) {
    $item_id = (int)$row['id'];
    $available = (float)$row['available_quantity'];
    $expired_qty = (float)$row['expired_quantity'];
    $threshold = (float)$row['threshold'];

    $reserved_stmt->bind_param("i", $item_id);
    $reserved_stmt->execute();
    $reserved_result = $reserved_stmt->get_result();
    $reserved_row = $reserved_result->fetch_assoc();
    $reserved = (float)($reserved_row['reserved'] ?? 0);

    $final_quantity = max($available - $reserved, 0);

if ($final_quantity <= 0) {
    $status = 'out';
} elseif ($final_quantity > $threshold) {
    $status = 'available';
} else {
    $status = 'low';
}

    $items[] = [
        'id' => $item_id,
        'name' => $row['name'],
        'quantity' => $final_quantity,
        'threshold' => $threshold,
        'status' => $status,
        'expired_quantity' => $expired_qty
    ];
}

$stmt->close();
$reserved_stmt->close();

echo json_encode([
    'success' => true,
    'items' => $items
]);
