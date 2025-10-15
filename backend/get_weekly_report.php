<?php
include '../db.php';
header('Content-Type: application/json');

$data = [];

$query = "
    SELECT 
        b.id, b.product_name, b.quantity, b.completed_at,
        GROUP_CONCAT(CONCAT(i.item_name, ' (', bm.quantity_used, ')') SEPARATOR ', ') AS materials
    FROM batches b
    LEFT JOIN batch_materials bm ON b.id = bm.batch_id
    LEFT JOIN inventory i ON bm.stock_id = i.id
    WHERE b.status = 'completed' AND b.is_deleted = 0
    GROUP BY b.id, b.product_name, b.quantity, b.completed_at
    ORDER BY b.completed_at DESC
";

$result = $conn->query($query);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        // Include only batches from current week
        if (date('W', strtotime($row['completed_at'])) == date('W') &&
            date('Y', strtotime($row['completed_at'])) == date('Y')) {
            $data[] = $row;
        }
    }
}

// Return same format as daily report
$response = [
    'total_completed' => count($data),
    'total_quantity' => array_sum(array_map(fn($b) => (int)$b['quantity'], $data)),
    'batches' => $data
];

echo json_encode($response);
