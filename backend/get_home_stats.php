<?php
include '../db.php';
session_start();

$response = ['success' => false];

try {
    // ✅ Materials in stock (available quantity minus reserved, same logic as supply/inventory views)
    $stmt = $conn->prepare("
        SELECT 
            COALESCE(SUM(
                GREATEST(
                    i.quantity - COALESCE(res.reserved_quantity, 0),
                    0
                )
            ), 0) AS totalMaterials
        FROM inventory i
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
    ");
    $stmt->execute();
    $materials = (float)$stmt->get_result()->fetch_assoc()['totalMaterials'];

    // ✅ In Production — exclude deleted batches
    $stmt = $conn->prepare("
        SELECT COUNT(*) as inProduction 
        FROM batches 
        WHERE status = ? 
          AND (is_deleted = 0 OR is_deleted IS NULL)
    ");
    $status = 'in_progress';
    $stmt->bind_param("s", $status);
    $stmt->execute();
    $inProduction = (int)$stmt->get_result()->fetch_assoc()['inProduction'];

    // ✅ Completed Orders — exclude deleted batches
    $stmt = $conn->prepare("
        SELECT COUNT(*) as completed 
        FROM batches 
        WHERE status = ? 
          AND (is_deleted = 0 OR is_deleted IS NULL)
    ");
    $status = 'completed';
    $stmt->bind_param("s", $status);
    $stmt->execute();
    $completed = (int)$stmt->get_result()->fetch_assoc()['completed'];

    $response = [
        'success' => true,
        'materials' => $materials,
        'inProduction' => $inProduction,
        'completed' => $completed
    ];
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response);
?>
