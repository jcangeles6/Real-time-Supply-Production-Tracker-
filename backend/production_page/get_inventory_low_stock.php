<?php
include __DIR__ . '/../init.php';

$result = $conn->query("
    SELECT i.id, i.item_name, i.quantity, COALESCE(st.threshold, 10) AS threshold
    FROM inventory i
    LEFT JOIN stock_thresholds st ON i.id = st.item_id
");

$items = [];
while($row = $result->fetch_assoc()) {
    if ($row['quantity'] <= $row['threshold']) {
        $items[] = [
            'id' => $row['id'],
            'name' => $row['item_name'],
            'quantity' => $row['quantity'],
            'threshold' => $row['threshold'],
        ];
    }
}

echo json_encode(['items' => $items]);
