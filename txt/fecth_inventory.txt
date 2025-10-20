<?php
header('Content-Type: application/json');

// Hide PHP errors to prevent breaking JSON
ini_set('display_errors', 0);
error_reporting(0);

// Include init.php correctly
include __DIR__ . '/../init.php';

try {
    // Fetch inventory with real-time available quantity, ordered by available_quantity DESC
    $result = $conn->query("
        SELECT 
            i.id, 
            i.item_name, 
            i.unit, 
            i.status,
            i.updated_at,
            i.quantity - IFNULL(SUM(CASE WHEN b.is_deleted = 0 THEN bm.quantity_reserved ELSE 0 END),0) AS available_quantity
        FROM inventory i
        LEFT JOIN batch_materials bm ON bm.stock_id = i.id
        LEFT JOIN batches b ON bm.batch_id = b.id
        GROUP BY i.id
        ORDER BY available_quantity DESC, i.item_name ASC
    ");

    $items = [];
    while ($row = $result->fetch_assoc()) {
        // Format updated_at: m/d/Y h:i A
        $updated_at = $row['updated_at'] 
            ? date('m/d/Y h:i A', strtotime($row['updated_at']))
            : null;

        $items[] = [
            'id' => $row['id'],
            'item_name' => $row['item_name'],
            'unit' => $row['unit'],
            'status' => ucfirst($row['status']),
            'updated_at' => $updated_at,
            'available_quantity' => (int)$row['available_quantity']
        ];
    }

    echo json_encode([
        'success' => true,
        'items' => $items
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}