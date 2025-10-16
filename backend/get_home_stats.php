<?php
include '../db.php';
session_start();

$response = ['success' => false];

try {
    // Materials in stock (sum of all inventory quantities)
    $res = $conn->query("SELECT SUM(quantity) as totalMaterials FROM inventory");
    $materials = $res ? (int)$res->fetch_assoc()['totalMaterials'] : 0;

    // In Production (batches with status='in_progress')
    $res = $conn->query("SELECT COUNT(*) as inProduction FROM batches WHERE status='in_progress' AND is_deleted=0");
    $inProduction = $res ? (int)$res->fetch_assoc()['inProduction'] : 0;

    // Completed Orders (batches with status='completed')
    $res = $conn->query("SELECT COUNT(*) as completed FROM batches WHERE status='completed' AND is_deleted=0");
    $completed = $res ? (int)$res->fetch_assoc()['completed'] : 0;

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