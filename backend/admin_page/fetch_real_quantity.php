<?php

// FOR INVENTORY AND ADD_STOCK PAGE

include __DIR__ . '/../init.php';

$id = intval($_GET['id'] ?? 0);
if (!$id) exit(json_encode(['success' => false]));

$query = $conn->prepare("
    SELECT 
        i.quantity - IFNULL(SUM(CASE WHEN b.is_deleted = 0 THEN bm.quantity_reserved ELSE 0 END), 0) AS available_quantity
    FROM inventory i
    LEFT JOIN batch_materials bm ON bm.stock_id = i.id
    LEFT JOIN batches b ON bm.batch_id = b.id
    WHERE i.id = ?
    GROUP BY i.id
");
$query->bind_param("i", $id);
$query->execute();
$result = $query->get_result()->fetch_assoc();
$query->close();

$available = $result ? (int)$result['available_quantity'] : 0;
echo json_encode(['success' => true, 'available_quantity' => $available]);
