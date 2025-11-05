<?php
include 'backend/init.php';

// Main inventory + thresholds
$stmt = $conn->prepare("
    SELECT 
        i.id, 
        i.item_name AS name, 
        i.unit,
        i.updated_at, 
        t.threshold
    FROM inventory i
    LEFT JOIN stock_thresholds t ON i.id = t.item_id
");
$stmt->execute();
$result = $stmt->get_result();

$items = [];

// Prepared statement to sum reserved quantities
$reserved_stmt = $conn->prepare("
    SELECT SUM(quantity_reserved) AS total_reserved 
    FROM batch_materials bm 
    JOIN batches b ON bm.batch_id = b.id 
    WHERE bm.stock_id = ? 
      AND b.is_deleted = 0
");

while ($row = $result->fetch_assoc()) {
    $stock_id = $row['id'];

    // 1️⃣ Get total reserved
    $reserved_stmt->bind_param("i", $stock_id);
    $reserved_stmt->execute();
    $reserved_res = $reserved_stmt->get_result();
    $reserved_data = $reserved_res->fetch_assoc();
    $reserved = $reserved_data['total_reserved'] ?? 0;

    // 2️⃣ Get total non-expired quantity
    $batch_stmt = $conn->prepare("
        SELECT COALESCE(SUM(quantity),0) AS total_available 
        FROM inventory_batches 
        WHERE inventory_id = ? 
          AND (expiration_date IS NULL OR expiration_date >= CURDATE())
    ");
    $batch_stmt->bind_param("i", $stock_id);
    $batch_stmt->execute();
    $batch_res = $batch_stmt->get_result();
    $batch_data = $batch_res->fetch_assoc();
    $available_batches = $batch_data['total_available'] ?? 0;
    $batch_stmt->close();

    // 3️⃣ Subtract reserved
    $available = max(0, $available_batches - $reserved);

    $items[$row['id']] = [
        'name' => $row['name'],
        'quantity' => (int)$available, // available now respects expiration + reserved
        'threshold' => intval($row['threshold'] ?? 0),
        'updated_at' => $row['updated_at'] ?? null
    ];
}

echo json_encode(['success' => true, 'items' => $items]);
