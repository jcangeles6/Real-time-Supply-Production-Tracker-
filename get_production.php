<?php
include 'backend/init.php';
header('Content-Type: application/json');

// Fetch batches with latest log action
$stmt = $conn->prepare("
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
        AND action IN ('in_progress','completed')
        ORDER BY id DESC
        LIMIT 1
    )
    WHERE b.is_deleted = 0
    ORDER BY b.scheduled_at DESC
");

$stmt->execute();
$result = $stmt->get_result();

$batches = [];
while ($row = $result->fetch_assoc()) {
    $row['status'] = ucfirst($row['status']); // Capitalize for display
    $batches[] = $row;
}

$stmt->close();

echo json_encode($batches);
