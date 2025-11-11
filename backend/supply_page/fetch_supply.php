<?php
include __DIR__ . '/../init.php';
header('Content-Type: application/json');

$items = [];

// 1️⃣ Securely fetch inventory list
$stmt = $conn->prepare("SELECT id, item_name, unit, quantity FROM inventory ORDER BY item_name ASC");
$stmt->execute();
$result = $stmt->get_result();

// 2️⃣ Prepare statements for subqueries (reused in loop)
$reserved_stmt = $conn->prepare("
    SELECT COALESCE(SUM(quantity_reserved), 0) AS reserved
    FROM batch_materials bm
    JOIN batches b ON bm.batch_id = b.id
    WHERE bm.stock_id = ? 
      AND b.status IN ('scheduled','in_progress')
      AND (b.is_deleted = 0 OR b.is_deleted IS NULL)
");

$threshold_stmt = $conn->prepare("
    SELECT threshold 
    FROM stock_thresholds 
    WHERE item_id = ? 
    LIMIT 1
");

// 3️⃣ Loop through inventory items securely
while ($row = $result->fetch_assoc()) {
    $item_id = (int)$row['id'];
    $total_qty = (int)$row['quantity'];

    // ✅ Get reserved quantity safely
    $reserved_stmt->bind_param("i", $item_id);
    $reserved_stmt->execute();
    $reserved_result = $reserved_stmt->get_result();
    $reserved_data = $reserved_result->fetch_assoc();
    $reserved = (int)($reserved_data['reserved'] ?? 0);

    $available_qty = $total_qty - $reserved;

    // ✅ Get threshold safely
    $threshold_stmt->bind_param("i", $item_id);
    $threshold_stmt->execute();
    $threshold_result = $threshold_stmt->get_result();
    $threshold_data = $threshold_result->fetch_assoc();
    $threshold = $threshold_data ? (int)$threshold_data['threshold'] : 10;

    // ✅ Store formatted data
    $items[] = [
        'item_name' => $row['item_name'],
        'unit' => $row['unit'],
        'total_qty' => $total_qty,
        'available_qty' => $available_qty,
        'threshold' => $threshold
    ];
}

// 4️⃣ Send as JSON
echo json_encode($items);
