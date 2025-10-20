<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: add_stock.php");
    exit();
}

$id = intval($_GET['id']);

// Fetch stock item with threshold
$stmt = $conn->prepare("
    SELECT i.*, COALESCE(st.threshold, 10) AS threshold
    FROM inventory i
    LEFT JOIN stock_thresholds st ON i.id = st.item_id
    WHERE i.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$stock = $result->fetch_assoc();
$stmt->close();

if (!$stock) {
    echo "Stock item not found!";
    exit();
}

// Handle POST (update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    if (empty($data['item_name']) || !isset($data['quantity']) || empty($data['unit'])) {
        echo "Missing required fields!";
        exit();
    }

    $item_name = $data['item_name'];
    $quantity = intval($data['quantity']);
    $unit = $data['unit'];
    $threshold = intval($data['threshold'] ?? 10);

    // Determine status automatically
    $status = ($quantity === 0) ? 'out' : (($quantity <= $threshold) ? 'low' : 'available');

    $old_quantity = intval($stock['quantity']);

    // Update inventory
    $update = $conn->prepare("UPDATE inventory SET item_name=?, quantity=?, unit=?, status=?, updated_at=NOW() WHERE id=?");
    $update->bind_param("sissi", $item_name, $quantity, $unit, $status, $id);
    $update->execute();
    $update->close();

    // Add replenishment notification if quantity increased
    if ($quantity > $old_quantity) {
        $notif_msg = "♻️ $item_name stock has been replenished with " . ($quantity - $old_quantity) . " $unit!";
        $notif_type = "replenished";

        $check_stmt = $conn->prepare("
            SELECT id FROM notifications
            WHERE type = ? AND message = ?
            AND created_at >= (NOW() - INTERVAL 1 MINUTE)
            LIMIT 1
        ");
        $check_stmt->bind_param("ss", $notif_type, $notif_msg);
        $check_stmt->execute();
        $check_stmt->store_result();

        if ($check_stmt->num_rows === 0) {
            $stmt = $conn->prepare("INSERT INTO notifications (type, message, created_at) VALUES (?, ?, NOW())");
            $stmt->bind_param("ss", $notif_type, $notif_msg);
            $stmt->execute();
            $notification_id = $stmt->insert_id;
            $stmt->close();

            $stmt2 = $conn->prepare("
                INSERT IGNORE INTO user_notifications (user_id, notification_id, is_read)
                SELECT id, ?, 0 FROM users
            ");
            $stmt2->bind_param("i", $notification_id);
            $stmt2->execute();
            $stmt2->close();
        }
        $check_stmt->close();
    }

    $success = true;

    // Refresh stock after update
    $stmt = $conn->prepare("
        SELECT i.*, COALESCE(st.threshold, 10) AS threshold
        FROM inventory i
        LEFT JOIN stock_thresholds st ON i.id = st.item_id
        WHERE i.id = ?
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stock = $result->fetch_assoc();
    $stmt->close();

    // JSON response for API requests
    if (isset($_SERVER['CONTENT_TYPE']) && str_contains($_SERVER['CONTENT_TYPE'], 'application/json')) {
        echo json_encode(['success' => true]);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>🌸 Update Stock - BloomLux</title>
    <link rel="stylesheet" href="../css/update_stock.css">
</head>
<body>
<div class="main">
    <a href="add_stock.php" class="back-btn">⬅ Back to Inventory</a>
    <h1>✏️ Update Stock</h1>

    <?php if (!empty($success)): ?>
        <div class="success-msg">Stock updated successfully! ✅</div>
    <?php endif; ?>

    <div class="form-box">
        <form method="POST" id="updateForm">
            <label>Item Name</label>
            <input type="text" name="item_name" value="<?= htmlspecialchars($stock['item_name']); ?>" required>

            <label>Quantity</label>
            <input type="number" name="quantity" id="quantityInput" value="<?= $stock['quantity']; ?>" min="0" required>

            <label>Unit</label>
            <select name="unit" required>
                <option value="kg" <?= $stock['unit'] == 'kg' ? 'selected' : '' ?>>Kilograms (kg)</option>
                <option value="g" <?= $stock['unit'] == 'g' ? 'selected' : '' ?>>Grams (g)</option>
                <option value="L" <?= $stock['unit'] == 'L' ? 'selected' : '' ?>>Liters (L)</option>
                <option value="ml" <?= $stock['unit'] == 'ml' ? 'selected' : '' ?>>Milliliters (ml)</option>
                <option value="pcs" <?= $stock['unit'] == 'pcs' ? 'selected' : '' ?>>Pieces</option>
            </select>

            <input type="hidden" name="threshold" id="thresholdInput" value="<?= $stock['threshold']; ?>">
            <input type="hidden" name="status" id="statusInput" value="<?= $stock['status']; ?>">
            <div id="statusDisplay" class="status-display"></div>

            <button type="submit">Update Stock</button>
        </form>
    </div>

    <script>
        const quantityInput = document.getElementById('quantityInput');
        const statusInput = document.getElementById('statusInput');
        const statusDisplay = document.getElementById('statusDisplay');

        function updateStatus() {
            const qty = parseInt(quantityInput.value) || 0;
            const threshold = parseInt(document.getElementById('thresholdInput').value) || 10;
            let status = '';

            if (qty === 0) {
                status = 'out';
                statusDisplay.textContent = 'Out of Stock';
                statusDisplay.className = 'status-display status-out';
            } else if (qty <= threshold) {
                status = 'low';
                statusDisplay.textContent = 'Low';
                statusDisplay.className = 'status-display status-low';
            } else {
                status = 'available';
                statusDisplay.textContent = 'Available';
                statusDisplay.className = 'status-display status-available';
            }

            statusInput.value = status;
        }

        quantityInput.addEventListener('input', updateStatus);
        updateStatus();
    </script>
</div>
</body>
</html>
