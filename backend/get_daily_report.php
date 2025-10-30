<?php
include '../db.php';

$stmt = $conn->prepare("
    SELECT 
        b.id, 
        b.product_name, 
        b.quantity, 
        b.completed_at,
        GROUP_CONCAT(CONCAT(i.item_name, ' (', bm.quantity_used, ')') SEPARATOR ', ') AS materials
    FROM batches b
    LEFT JOIN batch_materials bm ON b.id = bm.batch_id
    LEFT JOIN inventory i ON bm.stock_id = i.id
    WHERE b.status = 'completed'
      AND b.completed_at >= CURDATE() 
      AND b.completed_at < CURDATE() + INTERVAL 1 DAY
      AND b.is_deleted = 0
    GROUP BY b.id, b.product_name, b.quantity, b.completed_at
    ORDER BY b.completed_at DESC
");
$stmt->execute();
$result = $stmt->get_result();
$total_completed = $result->num_rows;
$total_quantity = 0;
$data = [];

while ($row = $result->fetch_assoc()) {
    $total_quantity += $row['quantity'];
    $data[] = $row;
}

echo json_encode([
    'total_completed' => $total_completed,
    'total_quantity' => $total_quantity,
    'batches' => $data
]);
?>
