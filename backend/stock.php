<?php
header('Content-Type: application/json');
session_start();
include '../db.php';

// Check user authentication
// if (!isset($_SESSION['user_id'])) {
//     http_response_code(401);
//     echo json_encode(['error' => 'Unauthorized']);
//     exit;
// }

// Helper function to get JSON input
function getJsonInput()
{
    $input = file_get_contents('php://input');
    return json_decode($input, true);
}

// GET → fetch all stocks or single stock by ID
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['id']) && !empty($_GET['id'])) {
        $id = intval($_GET['id']);
        $stmt = $conn->prepare("SELECT * FROM inventory WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stock = $result->fetch_assoc();

        if ($stock) {
            echo json_encode($stock);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Stock not found']);
        }
        exit;
    }

    // If no ID provided, return all stocks
    $result = $conn->query("SELECT * FROM inventory ORDER BY updated_at DESC");
    $stocks = [];
    while ($row = $result->fetch_assoc()) {
        $stocks[] = $row;
    }
    echo json_encode($stocks);
    exit;
}

// POST → add new stock
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = getJsonInput();

    if (empty($data['item_name']) || empty($data['quantity']) || empty($data['unit']) || empty($data['status'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }

    $item_name = $data['item_name'];
    $quantity = intval($data['quantity']);
    $unit = $data['unit'];
    $status = $data['status'];

    $stmt = $conn->prepare("INSERT INTO inventory (item_name, quantity, unit, status) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("siss", $item_name, $quantity, $unit, $status);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to add stock']);
    }
    $stmt->close();
    exit;
}

// DELETE → delete stock by ID
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    if (!isset($_GET['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing stock ID']);
        exit;
    }

    $id = intval($_GET['id']);
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid stock ID']);
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM inventory WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Stock item not found']);
    }

    $stmt->close();
    exit;
}

// If method is not GET, POST, or DELETE
http_response_code(405);
echo json_encode(['error' => 'Method Not Allowed']);
