<?php
include 'db.php';

$product_name = $_GET['product_name'] ?? '';

$response = ['found' => false, 'quantity' => 0];

if ($product_name) {
    $stmt = $conn->prepare("SELECT quantity FROM inventory WHERE item_name = ? LIMIT 1");
    $stmt->bind_param("s", $product_name);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($result) {
        $response['found'] = true;
        $response['quantity'] = intval($result['quantity']);
    }
}

header('Content-Type: application/json');
echo json_encode($response);
