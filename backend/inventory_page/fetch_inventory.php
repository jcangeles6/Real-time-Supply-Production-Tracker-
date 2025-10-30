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
            i.created_at,
            i.updated_at,
            i.quantity - IFNULL(SUM(CASE WHEN b.is_deleted = 0 THEN bm.quantity_reserved ELSE 0 END), 0) AS available_quantity,
            t.threshold
        FROM inventory i
        LEFT JOIN batch_materials bm ON bm.stock_id = i.id
        LEFT JOIN batches b ON bm.batch_id = b.id
        LEFT JOIN stock_thresholds t ON i.id = t.item_id
        GROUP BY i.id
        ORDER BY available_quantity DESC, i.item_name ASC
    ");
    $stmt->execute();
    $result = $stmt->get_result();

    $items = [];
    while ($row = $result->fetch_assoc()) {
        $available_quantity = (int)$row['available_quantity'];
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
            'available_quantity' => $available_quantity,
            'status' => $status,
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