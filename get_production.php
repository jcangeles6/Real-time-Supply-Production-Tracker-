<?php
include 'backend/init.php';
header('Content-Type: application/json');

// Fetch batches with latest log action
$result = $conn->query("
 SELECT 
    b.id,
    b.product_name,
    COALESCE(bl.action, b.status) AS status,  -- fallback to batch.status if no log
    b.scheduled_at
FROM batches b
LEFT JOIN batch_log bl ON bl.id = (
    SELECT id
    FROM batch_log
    WHERE batch_id = b.id
    AND action IN ('in_progress','completed') -- only take these actions
    ORDER BY id DESC
    LIMIT 1
)
WHERE b.is_deleted = 0
ORDER BY b.scheduled_at DESC
");

$batches = [];
while ($row = $result->fetch_assoc()) {
    // Capitalize status for display
    $row['status'] = ucfirst($row['status']);
    $batches[] = $row;
}

echo json_encode($batches);
