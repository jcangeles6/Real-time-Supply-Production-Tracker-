<?php
include '../db.php';
session_start();

$response = ['success' => false];

try {
    // ✅ Materials in stock (only non-expired)
    // Uses inventory_batches safely without assuming an is_deleted column
    $stmt = $conn->prepare("
        SELECT 
            COALESCE(SUM(
                CASE 
                    WHEN expiration_date IS NULL OR expiration_date >= CURDATE()
                    THEN quantity
                    ELSE 0
                END
            ), 0) AS totalMaterials
        FROM inventory_batches
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
