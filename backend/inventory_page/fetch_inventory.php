<?php
header('Content-Type: application/json');

// Hide PHP errors to prevent breaking JSON output
ini_set('display_errors', 0);
error_reporting(0);

// ✅ Correct include for database connection
include __DIR__ . '/../init.php';

try {
    $stmt = $conn->prepare("
    SELECT 
        i.id,
        i.item_name,
        i.unit,
        i.quantity AS total_quantity,
        i.created_at,
        i.updated_at,
        i.image_path,
        COALESCE(t.threshold, 10) AS threshold,
        COALESCE(res.reserved_quantity, 0) AS reserved_quantity
    FROM inventory i
    LEFT JOIN stock_thresholds t ON i.id = t.item_id
    LEFT JOIN (
        SELECT 
            bm.stock_id,
            SUM(bm.quantity_reserved) AS reserved_quantity
        FROM batch_materials bm
        JOIN batches b ON bm.batch_id = b.id
        WHERE b.status IN ('scheduled', 'in_progress')
          AND (b.is_deleted = 0 OR b.is_deleted IS NULL)
        GROUP BY bm.stock_id
    ) res ON res.stock_id = i.id
    ORDER BY i.item_name ASC
");
    $stmt->execute();
    $result = $stmt->get_result();

    $items = [];
    while ($row = $result->fetch_assoc()) {
        $total_quantity = isset($row['total_quantity']) ? (float)$row['total_quantity'] : 0;
        $reserved = isset($row['reserved_quantity']) ? (float)$row['reserved_quantity'] : 0;
        $available_quantity = max($total_quantity - $reserved, 0);
        $threshold = isset($row['threshold']) ? (int)$row['threshold'] : 0;

        if ($available_quantity <= 0) $status = 'Out of Stock';
        elseif ($available_quantity <= $threshold) $status = 'Low';
        else $status = 'Available';

        $created_at = $row['created_at'] ? date('M d, Y h:i A', strtotime($row['created_at'])) : '—';
        $updated_at = $row['updated_at'] ? date('M d, Y h:i A', strtotime($row['updated_at'])) : '—';

        $items[] = [
            'id' => $row['id'],
            'item_name' => $row['item_name'],
            'unit' => $row['unit'],
            'total_quantity' => $total_quantity,
            'available_quantity' => $available_quantity,
            'status' => $status,
            'image_path' => $row['image_path'],
            'created_at' => $created_at,
            'updated_at' => $updated_at
        ];
    }

    echo json_encode([
        'success' => true,
        'items' => $items
    ]);

    $stmt->close();

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}