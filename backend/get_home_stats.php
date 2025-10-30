<?php
include '../db.php';
session_start();

$response = ['success' => false];

try {
    // Materials in stock
    $stmt = $conn->prepare("SELECT SUM(quantity) as totalMaterials FROM inventory");
    $stmt->execute();
    $materials = (int)$stmt->get_result()->fetch_assoc()['totalMaterials'];

    // In Production
    $stmt = $conn->prepare("SELECT COUNT(*) as inProduction FROM batches WHERE status=? AND is_deleted=0");
    $status = 'in_progress';
    $stmt->bind_param("s", $status);
    $stmt->execute();
    $inProduction = (int)$stmt->get_result()->fetch_assoc()['inProduction'];

    // Completed Orders
    $stmt = $conn->prepare("SELECT COUNT(*) as completed FROM batches WHERE status=? AND is_deleted=0");
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
