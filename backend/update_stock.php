<?php

// ADMIN UPDATE FORM

session_start();
include '../db.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Redirect if no ID
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
    echo "❌ Stock item not found!";
    exit();
}

// Compute real-time available quantity (after reservations)
$available_query = $conn->prepare("
    SELECT 
        i.quantity - IFNULL(SUM(CASE WHEN b.is_deleted = 0 THEN bm.quantity_reserved ELSE 0 END), 0) AS available_quantity
    FROM inventory i
    LEFT JOIN batch_materials bm ON bm.stock_id = i.id
    LEFT JOIN batches b ON bm.batch_id = b.id
    WHERE i.id = ?
    GROUP BY i.id
");
$available_query->bind_param("i", $id);
$available_query->execute();
$available_result = $available_query->get_result();
$available = $available_result->fetch_assoc();
$available_query->close();

$real_available = $available ? (int)$available['available_quantity'] : (int)$stock['quantity'];

// Handle POST (update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = $_POST;

    if (empty($data['item_name']) || !isset($data['quantity']) || empty($data['unit'])) {
        echo "❌ Missing required fields!";
        exit();
    }

    $item_name = $data['item_name'];
    $quantity = floatval($data['quantity']);
    $unit = $data['unit'];
    $threshold = intval($data['threshold'] ?? 10);

    // Determine new status
    $status = ($quantity == 0) ? 'out' : (($quantity <= $threshold) ? 'low' : 'available');

    $old_quantity = floatval($stock['quantity']);

    // Update inventory
    $update = $conn->prepare("
        UPDATE inventory 
        SET item_name=?, quantity=?, unit=?, status=?, updated_at=NOW() 
        WHERE id=?
    ");
    $update->bind_param("sdssi", $item_name, $quantity, $unit, $status, $id);
    $update->execute();
    $update->close();

    // ✅ Always notify when quantity increases (no duplicate limiter)
    if ($quantity > $old_quantity) {
        $added = $quantity - $old_quantity;
        $notif_msg = "♻️ $item_name stock replenished with {$added} {$unit}!";
        $notif_type = "replenished";

        // Insert new notification
        $stmt = $conn->prepare("
        INSERT INTO notifications (type, message, created_at)
        VALUES (?, ?, NOW())
    ");
        $stmt->bind_param("ss", $notif_type, $notif_msg);
        $stmt->execute();
        $notification_id = $stmt->insert_id;
        $stmt->close();

        // Notify all users
        $stmt2 = $conn->prepare("
        INSERT IGNORE INTO user_notifications (user_id, notification_id, is_read)
        SELECT id, ?, 0 FROM users
    ");
        $stmt2->bind_param("i", $notification_id);
        $stmt2->execute();
        $stmt2->close();
    }
    
    // Redirect back to add_stock page after update
    header("Location: add_stock.php");
    exit();
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

        <div class="form-box">
            <form method="POST" id="updateForm">
                <label>Item Name</label>
                <input type="text" name="item_name" value="<?= htmlspecialchars($stock['item_name']); ?>" required>

                <label>Stored Quantity</label>
                <input
                    type="number"
                    name="quantity"
                    id="quantityInput"
                    value="<?= $real_available ?>"
                    min="0"
                    step="any"
                    required>

                <div class="indicator">
                    📦 <strong>Real Available Quantity:</strong>
                    <span style="color: <?= ($real_available <= $stock['threshold']) ? 'red' : 'green' ?>;">
                        <?= $real_available ?>
                    </span>
                </div>

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
            const realIndicator = document.querySelector('.indicator span');
            const thresholdInput = parseInt(document.getElementById('thresholdInput').value) || 10;

            function updateStatus() {
                const qty = parseFloat(quantityInput.value);
                let status = '';

                if (isNaN(qty) || qty < 0) {
                    status = '';
                    statusDisplay.textContent = 'Enter a valid number';
                    statusDisplay.className = 'status-display status-out';
                    return;
                }

                if (qty === 0) {
                    status = 'out';
                    statusDisplay.textContent = 'Out of Stock';
                    statusDisplay.className = 'status-display status-out';
                } else if (qty <= thresholdInput) {
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

            // Auto-refresh real quantity every 5 seconds
            async function refreshRealQuantity() {
                try {
                    const res = await fetch(`admin_page/fetch_real_quantity.php?id=<?= $id ?>`);
                    const data = await res.json();
                    if (data.success) {
                        const realQty = data.available_quantity;

                        // ✅ Update the "📦 Real Available Quantity" label
                        realIndicator.textContent = realQty;
                        realIndicator.style.color = (realQty <= thresholdInput) ? 'red' : 'green';

                        // ✅ Update Stored Quantity ONLY if user is not typing
                        if (!quantityInput.matches(':focus')) {
                            const currentValue = parseFloat(quantityInput.value);
                            if (!isNaN(realQty) && realQty !== currentValue) {
                                quantityInput.value = realQty;
                                updateStatus(); // auto-update status display
                            }
                        }

                    }
                } catch (err) {
                    console.error('Error fetching real quantity:', err);
                }
            }

            // 🔥 Fetch once immediately when form opens
            refreshRealQuantity();

            // 🔁 Then refresh every 5 seconds
            setInterval(refreshRealQuantity, 5000);
        </script>
    </div>
</body>

</html>