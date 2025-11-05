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

$real_available = $available ? (float)$available['available_quantity'] : (float)$stock['quantity'];

// Handle POST (update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_name = $_POST['item_name'] ?? '';
    $add_quantity = floatval($_POST['add_quantity'] ?? 0);
    $unit = $_POST['unit'] ?? '';
    $threshold = intval($_POST['threshold'] ?? 10);

    if (empty($item_name) || empty($unit)) {
        echo "❌ Missing required fields!";
        exit();
    }

    // Fetch latest inventory total
    $inv_stmt = $conn->prepare("SELECT quantity FROM inventory WHERE id = ?");
    $inv_stmt->bind_param("i", $id);
    $inv_stmt->execute();
    $inv_result = $inv_stmt->get_result()->fetch_assoc();
    $current_total = $inv_result['quantity'] ?? 0;
    $inv_stmt->close();

    // 🧮 Compute new total (add replenishment)
    $new_total = $current_total + $add_quantity;

    // Determine new status
    $status = ($new_total == 0) ? 'out' : (($new_total <= $threshold) ? 'low' : 'available');

    // Update inventory record
    $update = $conn->prepare("
        UPDATE inventory 
        SET item_name=?, quantity=?, unit=?, status=?, updated_at=NOW()
        WHERE id=?
    ");
    $update->bind_param("sdssi", $item_name, $new_total, $unit, $status, $id);
    $update->execute();
    $update->close();

    // 🔹 Insert new batch for this replenishment
    if ($add_quantity > 0) {
        $expiration_date = !empty($_POST['expiration_date']) ? $_POST['expiration_date'] : $stock['expiration_date'];

        // Determine batch status based on expiration date
        $today = date('Y-m-d');
        if ($expiration_date < $today) {
            $batch_status = 'Expired';
        } else {
            $batch_status = 'Fresh';
        }

        $batch_stmt = $conn->prepare("
        INSERT INTO inventory_batches (inventory_id, quantity, expiration_date, status, created_at, updated_at)
        VALUES (?, ?, ?, ?, NOW(), NOW())
    ");
        $batch_stmt->bind_param("idss", $id, $add_quantity, $expiration_date, $batch_status);
        $batch_stmt->execute();
        $batch_stmt->close();
    }

    // ✅ Notify only if there’s an actual addition
    if ($add_quantity > 0) {
        $notif_msg = "♻️ $item_name stock replenished with {$add_quantity} {$unit}!";
        $notif_type = "replenished";

        // Insert notification
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

    // Redirect back
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

                <label>Add Quantity</label>
                <input
                    type="number"
                    name="add_quantity"
                    id="addQuantityInput"
                    placeholder="Enter amount to add"
                    min="0"
                    step="any"
                    required>

                <?php if ($stock['expiration_date'] !== null || ($stock['is_perishable'] ?? false)): ?>
                    <label>
                        <input type="checkbox" id="hasExpirationToggle" checked>
                        This stock has an expiration date
                    </label>

                    <label id="expirationLabel">
                        Expiration Date
                        <input type="date" name="expiration_date" id="expirationDateInput" value="" required> <!-- empty so user can set new date -->
                    </label>
                <?php endif; ?>

                <div class="indicator">
                    📦 <strong>Real Available Quantity:</strong>
                    <span style="color: <?= ($real_available <= $stock['threshold']) ? 'red' : 'green' ?>;">
                        <?= $real_available ?>
                    </span>
                </div>

                <div id="predictedDisplay" style="margin-top:8px; font-weight:bold; color:#555;"></div>

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

                <button type="submit">Update Stock</button>
            </form>
        </div>

        <script>
            const addInput = document.getElementById('addQuantityInput');
            const realIndicator = document.querySelector('.indicator span');
            const predictedDisplay = document.getElementById('predictedDisplay');
            const thresholdInput = parseInt(document.getElementById('thresholdInput').value) || 10;

            function updatePredicted() {
                const addQty = parseFloat(addInput.value) || 0;
                const currentReal = parseFloat(realIndicator.textContent) || 0;
                const predicted = currentReal + addQty;
                predictedDisplay.textContent = addQty > 0 ?
                    `After replenishment: ${predicted.toFixed(2)} total` :
                    '';
            }

            addInput.addEventListener('input', updatePredicted);

            // 🔁 Auto-refresh real quantity
            async function refreshRealQuantity() {
                try {
                    const res = await fetch(`admin_page/fetch_real_quantity.php?id=<?= $id ?>`);
                    const data = await res.json();
                    if (data.success) {
                        const realQty = data.available_quantity;
                        realIndicator.textContent = realQty;
                        realIndicator.style.color = (realQty <= thresholdInput) ? 'red' : 'green';
                        updatePredicted();
                    }
                } catch (err) {
                    console.error('Error fetching real quantity:', err);
                }
            }

            const hasExpirationToggle = document.getElementById('hasExpirationToggle');
            const expirationLabel = document.getElementById('expirationLabel');
            const expirationInput = document.getElementById('expirationDateInput');

            if (hasExpirationToggle) { // only initialize if toggle exists
                function toggleExpiration() {
                    if (hasExpirationToggle.checked) {
                        expirationLabel.style.display = 'block';
                        expirationInput.required = true;
                    } else {
                        expirationLabel.style.display = 'none';
                        expirationInput.required = false;
                        expirationInput.value = ''; // clear input if toggled off
                    }
                }

                hasExpirationToggle.addEventListener('change', toggleExpiration);
                toggleExpiration(); // initialize on page load
            }


            refreshRealQuantity();
            setInterval(refreshRealQuantity, 5000);
        </script>
    </div>
</body>

</html>