<?php
session_start();
include '../db.php';

header('Content-Type: application/json');

// Helper function to get JSON input
function getJsonInput() {
    $input = file_get_contents('php://input');
    return json_decode($input, true);
}

function isJsonRequest(): bool {
    if (!isset($_SERVER['CONTENT_TYPE'])) {
        return false;
    }
    return stripos($_SERVER['CONTENT_TYPE'], 'application/json') === 0;
}

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// GET → fetch stocks
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    // Fetch single stock by ID (optional)
    if (isset($_GET['id']) && !empty($_GET['id'])) {
        $id = intval($_GET['id']);
        $stmt = $conn->prepare("SELECT * FROM inventory WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stock = $result->fetch_assoc();
        $stmt->close();

        if ($stock) echo json_encode($stock);
        else {
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
    $allowedFilters = ['item_name', 'quantity', 'status'];
    if (!in_array($filter, $allowedFilters)) $filter = 'item_name';

    // Main query → sum only non-expired batches & subtract reserved
    $sql = "
    SELECT 
        i.id,
        i.item_name,
        i.unit,
        i.created_at,
        i.updated_at,
        i.image_path,
        COALESCE(SUM(
            CASE 
                WHEN (ib.expiration_date IS NULL OR ib.expiration_date >= CURDATE())
                THEN ib.quantity 
                ELSE 0 
            END
        ), 0) 
        - COALESCE((
            SELECT SUM(bm.quantity_reserved)
            FROM batch_materials bm
            JOIN batches b ON bm.batch_id = b.id
            WHERE bm.stock_id = i.id 
              AND b.status IN ('scheduled','in_progress')
        ), 0) AS available_quantity,
        COALESCE(st.threshold, 10) AS threshold
    FROM inventory i
    LEFT JOIN inventory_batches ib ON ib.inventory_id = i.id
    LEFT JOIN stock_thresholds st ON i.id = st.item_id
    ";

    $params = [];
    $types = '';

    if ($search !== '') {
        $sql .= " WHERE i.$filter LIKE ?";
        $params[] = "%$search%";
        $types .= 's';
    }

    $sql .= " GROUP BY i.id ORDER BY i.item_name ASC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $types .= 'ii';

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $stocks = [];
    while ($row = $result->fetch_assoc()) {
        $available_quantity = (int)$row['available_quantity'];
        $threshold = (int)$row['threshold'];

        if ($available_quantity <= 0) $status = 'out';
        elseif ($available_quantity <= $threshold) $status = 'low';
        else $status = 'available';

        $stocks[] = [
            'id' => $row['id'],
            'item_name' => $row['item_name'],
            'quantity' => $available_quantity, // show real available
            'unit' => $row['unit'],
            'status' => $status,
            'image_path' => $row['image_path'],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at']
        ];
    }
    $stmt->close();

    // Total count for pagination
    $countSql = "SELECT COUNT(*) AS total FROM inventory i";
    $countParams = [];
    $countTypes = '';
    if ($search !== '') {
        $countSql .= " WHERE i.$filter LIKE ?";
        $countParams[] = "%$search%";
        $countTypes .= 's';
    }
    $countStmt = $conn->prepare($countSql);
    if ($countParams) $countStmt->bind_param($countTypes, ...$countParams);
    $countStmt->execute();
    $total = $countStmt->get_result()->fetch_assoc()['total'];
    $countStmt->close();

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
    if (isJsonRequest()) {
        $data = getJsonInput() ?? [];
    } else {
        $data = $_POST;
    }

    if (!isset($data['item_name'], $data['quantity'], $data['unit']) || trim($data['item_name']) === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit();
    }

    $item_name = trim($data['item_name']);
    $quantity = isset($data['quantity']) ? intval($data['quantity']) : 0;
    $unit = trim($data['unit']);
    $threshold = isset($data['threshold']) ? max(1, intval($data['threshold'])) : 10;

    // ✅ Optional shelf life support
    $has_shelf_life = !empty($data['has_shelf_life']) && $data['has_shelf_life'] !== '0';
    $expiration_date = ($has_shelf_life && !empty($data['expiration_date'])) ? $data['expiration_date'] : NULL;
    $near_expiry_days = ($has_shelf_life && !empty($data['near_expiry_days'])) ? intval($data['near_expiry_days']) : 7;


    // Determine status
    $status = ($quantity === 0) ? 'out' : (($quantity <= $threshold) ? 'low' : 'available');

    if ($quantity < 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Quantity must be 0 or greater']);
        exit();
    }

    $image_path = NULL;
    $upload_error = null;

    if (!empty($_FILES['item_image']) && $_FILES['item_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $fileError = $_FILES['item_image']['error'];
        if ($fileError === UPLOAD_ERR_OK) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $detectedType = $_FILES['item_image']['type'] ?? '';
            if (function_exists('finfo_open')) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                if ($finfo) {
                    $detectedType = finfo_file($finfo, $_FILES['item_image']['tmp_name']);
                    finfo_close($finfo);
                }
            }
            if (!in_array($detectedType, $allowedTypes, true)) {
                http_response_code(400);
                echo json_encode(['error' => 'Unsupported image format. Use JPG, PNG, GIF or WEBP.']);
                exit();
            }

            $uploadDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'inventory';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0775, true);
            }

            $extension = pathinfo($_FILES['item_image']['name'], PATHINFO_EXTENSION);
            $extension = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $extension));
            if ($extension === '') {
                $extension = 'jpg';
            }
            $filename = uniqid('stock_', true) . '.' . $extension;
            $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $filename;
            if (move_uploaded_file($_FILES['item_image']['tmp_name'], $targetPath)) {
                // Store web-friendly path
                $image_path = 'uploads/inventory/' . $filename;
            } else {
                $upload_error = 'Failed to save uploaded image.';
            }
        } else {
            $upload_error = 'Image upload error (code ' . $fileError . ').';
        }
    }

    if ($upload_error) {
        http_response_code(400);
        echo json_encode(['error' => $upload_error]);
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

    // ✅ Insert new stock with expiration support
$stmt = $conn->prepare("INSERT INTO inventory 
    (item_name, quantity, unit, expiration_date, near_expiry_days, status, created_at, updated_at, image_path)
    VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW(), ?)");
$stmt->bind_param("sississ", $item_name, $quantity, $unit, $expiration_date, $near_expiry_days, $status, $image_path);

if ($stmt->execute()) {
    $new_item_id = $stmt->insert_id;

    // Save threshold
    $thresh_stmt = $conn->prepare("INSERT INTO stock_thresholds (item_id, threshold, created_at, updated_at) 
                                   VALUES (?, ?, NOW(), NOW())");
    $thresh_stmt->bind_param("ii", $new_item_id, $threshold);
    $thresh_stmt->execute();
    $thresh_stmt->close();

    // Insert batch automatically
if ($quantity > 0) {
    $freshness = 'Fresh';
    if ($expiration_date && $expiration_date != '0000-00-00') {
        $today = new DateTime();
        $exp_date = new DateTime($expiration_date);
        $interval = (int)$today->diff($exp_date)->format('%r%a');

        if ($interval < 0) {
            $freshness = 'Expired';
        } elseif ($interval <= $near_expiry_days) {
            $freshness = 'Near Expired';
        }
    }

    $batch_stmt = $conn->prepare("INSERT INTO inventory_batches 
        (inventory_id, quantity, expiration_date, status, created_at, updated_at)
        VALUES (?, ?, ?, ?, NOW(), NOW())");
    $batch_stmt->bind_param("idss", $new_item_id, $quantity, $expiration_date, $freshness);
    $batch_stmt->execute();
    $batch_stmt->close();
}


        // Insert notification
        $notif_message = "📦 New product {$item_name} ({$quantity} {$unit}) has been added!";
        $notif_stmt = $conn->prepare("INSERT INTO notifications (message, created_at) VALUES (?, NOW())");
        $notif_stmt->bind_param("s", $notif_message);
        $notif_stmt->execute();
        $new_notification_id = $notif_stmt->insert_id;
        $notif_stmt->close();

        // Assign notification to all users
        $users_select = $conn->prepare("SELECT id FROM users");
        $users_select->execute();
        $users_result = $users_select->get_result();

        $user_insert = $conn->prepare("INSERT INTO user_notifications (user_id, notification_id, is_read) VALUES (?, ?, 0)");
        while ($u = $users_result->fetch_assoc()) {
            $uid = (int)$u['id'];
            $user_insert->bind_param("ii", $uid, $new_notification_id);
            $user_insert->execute();
        }
        $user_insert->close();
        $users_select->close();

        // ✅ Return success + freshness info (for production sync)
        echo json_encode([
            'success' => true,
            'message' => "Item added successfully!",
            'item' => [
                'id' => $new_item_id,
                'item_name' => $item_name,
                'quantity' => $quantity,
                'unit' => $unit,
                'expiration_date' => $expiration_date,
                'status' => $status,
                'image_path' => $image_path
            ]
        ]);
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
