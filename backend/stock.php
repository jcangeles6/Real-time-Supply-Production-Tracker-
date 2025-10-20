<?php
header('Content-Type: application/json');
session_start();
include '../db.php';

// Helper function to get JSON input
function getJsonInput() {
    $input = file_get_contents('php://input');
    return json_decode($input, true);
}

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// GET → fetch stocks
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    // Fetch single stock by ID
    if (isset($_GET['id']) && !empty($_GET['id'])) {
        $id = intval($_GET['id']);
        $stmt = $conn->prepare("SELECT * FROM inventory WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stock = $result->fetch_assoc();
        $stmt->close();

        if ($stock) {
            echo json_encode($stock);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Stock not found']);
        }
        exit;
    }

    // Pagination params
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? max(1, intval($_GET['limit'])) : 10;
    $offset = ($page - 1) * $limit;

    // Filter & search
    $filter = isset($_GET['filter']) ? trim($_GET['filter']) : 'item_name';
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';

    // Allowed filter columns
    $allowedFilters = ['item_name', 'quantity', 'status'];
    if (!in_array($filter, $allowedFilters)) {
        $filter = 'item_name';
    }

    $stocks = [];
    $total = 0;

    if ($search !== '') {
        if ($filter === 'quantity' && is_numeric($search)) {
            $searchParam = "%{$search}%";
            $stmt = $conn->prepare("SELECT * FROM inventory WHERE quantity LIKE ? ORDER BY quantity ASC, id ASC LIMIT ? OFFSET ?");
            $stmt->bind_param("sii", $searchParam, $limit, $offset);

            $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM inventory WHERE quantity LIKE ?");
            $countStmt->bind_param("s", $searchParam);

        } else {
            $searchParam = "%{$search}%";
            $stmt = $conn->prepare("SELECT * FROM inventory WHERE $filter LIKE ? ORDER BY quantity ASC, id ASC LIMIT ? OFFSET ?");
            $stmt->bind_param("sii", $searchParam, $limit, $offset);

            $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM inventory WHERE $filter LIKE ?");
            $countStmt->bind_param("s", $searchParam);
        }

        $countStmt->execute();
        $total = $countStmt->get_result()->fetch_assoc()['total'];
        $countStmt->close();

    } else {
        $stmt = $conn->prepare("SELECT * FROM inventory ORDER BY quantity ASC, id ASC LIMIT ? OFFSET ?");
        $stmt->bind_param("ii", $limit, $offset);

        $totalResult = $conn->query("SELECT COUNT(*) as total FROM inventory");
        $total = $totalResult->fetch_assoc()['total'];
    }

    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $stocks[] = $row;
    }
    $stmt->close();

    echo json_encode([
        'page' => $page,
        'limit' => $limit,
        'total' => $total,
        'stocks' => $stocks
    ]);
    exit;
}

// POST → add new stock
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = getJsonInput();

    if (empty($data['item_name']) || !isset($data['quantity']) || !isset($data['unit'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit();
    }

    $item_name = trim($data['item_name']);
    $quantity = intval($data['quantity']);
    $unit = trim($data['unit']);
    $status = isset($data['status']) ? trim($data['status']) : 'available';
    $threshold = isset($data['threshold']) ? max(1, intval($data['threshold'])) : 10;

    if ($quantity < 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Quantity must be 0 or greater']);
        exit();
    }

    // Check if item already exists
    $check_stmt = $conn->prepare("SELECT id FROM inventory WHERE item_name = ?");
    $check_stmt->bind_param("s", $item_name);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $existing_item = $result->fetch_assoc();
    $check_stmt->close();

    if ($existing_item) {
        echo json_encode([
            'success' => false,
            'message' => "Item '{$item_name}' already exists. Use Update Stock to add quantity."
        ]);
        exit();
    }

    // Insert new stock
    $stmt = $conn->prepare("INSERT INTO inventory (item_name, quantity, unit, status) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("siss", $item_name, $quantity, $unit, $status);

    if ($stmt->execute()) {
        $new_item_id = $stmt->insert_id;

        // Save threshold
        $thresh_stmt = $conn->prepare("INSERT INTO stock_thresholds (item_id, threshold, created_at, updated_at) VALUES (?, ?, NOW(), NOW())");
        $thresh_stmt->bind_param("ii", $new_item_id, $threshold);
        $thresh_stmt->execute();
        $thresh_stmt->close();

        // Insert notification
        $notif_message = "📦 New product {$item_name} ({$quantity} {$unit}) has been added!";
        $notif_stmt = $conn->prepare("INSERT INTO notifications (message, created_at) VALUES (?, NOW())");
        $notif_stmt->bind_param("s", $notif_message);
        $notif_stmt->execute();
        $new_notification_id = $notif_stmt->insert_id;
        $notif_stmt->close();

        // Assign notification to all users
        $user_stmt = $conn->prepare("INSERT INTO user_notifications (user_id, notification_id, is_read) VALUES (?, ?, 0)");
        $users = $conn->query("SELECT id FROM users");
        while ($u = $users->fetch_assoc()) {
            $user_stmt->bind_param("ii", $u['id'], $new_notification_id);
            $user_stmt->execute();
        }
        $user_stmt->close();

        echo json_encode(['success' => true, 'message' => $notif_message]);
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
        exit();
    }

    $id = intval($_GET['id']);
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

// Method not allowed
http_response_code(405);
echo json_encode(['error' => 'Method Not Allowed']);
