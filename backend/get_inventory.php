<?php
include '../db.php';
header('Content-Type: application/json');

$items = [];

// ✅ 1️⃣ Main inventory fetch — no user input, safe but keep structure clean
$stmt = $conn->prepare("
    SELECT 
        i.id, 
        i.item_name, 
        i.quantity, 
        i.unit, 
        COALESCE(st.threshold, 5) AS threshold
    FROM inventory i
    LEFT JOIN stock_thresholds st ON i.id = st.item_id
    ORDER BY i.item_name ASC
");
$stmt->execute();
$result = $stmt->get_result();

// ✅ 2️⃣ Prepare reserved quantity query (to reuse)
$reserved_stmt = $conn->prepare("
    SELECT COALESCE(SUM(bm.quantity_reserved), 0) AS reserved
    FROM batch_materials bm
    JOIN batches b ON bm.batch_id = b.id
    WHERE bm.stock_id = ?
      AND b.status IN ('scheduled','in_progress')
");

// ✅ 3️⃣ Loop through inventory items securely
while ($row = $result->fetch_assoc()) {
    $item_id = (int)$row['id'];
    $quantity = (int)$row['quantity'];
    $threshold = (int)$row['threshold'];

    // Securely bind item_id
    $reserved_stmt->bind_param("i", $item_id);
    $reserved_stmt->execute();
    $reserved_result = $reserved_stmt->get_result();
    $reserved_row = $reserved_result->fetch_assoc();
    $reserved = (int)($reserved_row['reserved'] ?? 0);

    $available_quantity = $quantity - $reserved;

    // Determine dynamic stock status
    if ($available_quantity <= 0) {
        $status = 'out';
    } elseif ($available_quantity <= $threshold) {
        $status = 'low';
    } else {
        $status = 'available';
    }

    // Append to results
    $items[] = [
        'item_name' => $row['item_name'],
        'quantity' => $available_quantity,
        'unit' => $row['unit'],
        'status' => $status
    ];
}

// ✅ 4️⃣ Output JSON response
echo json_encode($items);
