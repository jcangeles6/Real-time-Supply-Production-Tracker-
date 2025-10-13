<?php
include 'backend/init.php';
header('Content-Type: application/json');

// Fetch batches with latest log action
$result = $conn->query("
    SELECT 
        b.id,
        b.product_name,
        bl.action AS status,  -- use latest log action as status
        DATE_FORMAT(bl.timestamp, '%b %d, %Y %h:%i %p') AS timestamp
    FROM batches b
    LEFT JOIN batch_log bl ON bl.id = (
        SELECT id
        FROM batch_log
        WHERE batch_id = b.id
        ORDER BY id DESC
        LIMIT 1
    )
    WHERE b.is_deleted = 0
      AND DATE(b.scheduled_at) <= CURDATE()  -- include today's and past batches
    ORDER BY bl.timestamp DESC
");

$batches = [];
while ($row = $result->fetch_assoc()) {
    // Capitalize status for display
    $row['status'] = ucfirst($row['status']);
    $batches[] = $row;
}

echo json_encode($batches);
