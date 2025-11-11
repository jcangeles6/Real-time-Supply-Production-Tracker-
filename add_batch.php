<?php
include 'backend/init.php';

// --- DEVELOPMENT MODE ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// --- Get batch info ---
$current_batch_id = isset($_GET['batch_id']) ? intval($_GET['batch_id']) : 0;
$search = $_GET['search'] ?? '';
$is_edit = $current_batch_id > 0;

// --- Fetch inventory with available stock ---
$stmt = $conn->prepare("
    SELECT 
        i.id,
        i.item_name,
        COALESCE(SUM(ib.quantity),0) AS total_stock,
        COALESCE(SUM(CASE WHEN b.id IS NULL THEN 0 ELSE bm.quantity_reserved END),0) AS reserved_stock,
        COALESCE(SUM(ib.quantity),0) - COALESCE(SUM(CASE WHEN b.id IS NULL THEN 0 ELSE bm.quantity_reserved END),0) AS available_quantity
    FROM inventory i
    LEFT JOIN inventory_batches ib 
        ON ib.inventory_id = i.id 
        AND (ib.expiration_date IS NULL OR ib.expiration_date >= CURDATE())
    LEFT JOIN batch_materials bm 
        ON bm.stock_id = i.id
    LEFT JOIN batches b 
        ON bm.batch_id = b.id 
        AND b.is_deleted = 0
        AND bm.batch_id != ?
    WHERE i.item_name LIKE CONCAT('%', ?, '%')
    GROUP BY i.id
");
$stmt->bind_param("is", $current_batch_id, $search);
$stmt->execute();
$inventory_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// --- Prefill batch variables ---
$product_type_prefill = $_GET['product_name'] ?? '';
$quantity_prefill = $_GET['quantity'] ?? '';
$materials_prefill = [];

if ($is_edit) {
    $batch_id = $current_batch_id;

    // Fetch batch materials
    $matQuery = $conn->prepare("
        SELECT i.id as stock_id, i.item_name, bm.quantity_used
        FROM batch_materials bm
        JOIN inventory i ON bm.stock_id = i.id
        WHERE bm.batch_id = ?
    ");
    $matQuery->bind_param("i", $batch_id);
    $matQuery->execute();
    $materials_prefill = $matQuery->get_result()->fetch_all(MYSQLI_ASSOC);
    $matQuery->close();

    // Fetch batch info
    $stmt = $conn->prepare("SELECT product_name, quantity FROM batches WHERE id = ?");
    $stmt->bind_param("i", $batch_id);
    $stmt->execute();
    $batchInfo = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($batchInfo) {
        $product_type_prefill = $batchInfo['product_name'];
        $quantity_prefill = $batchInfo['quantity'];
    }
}

// --- Initialize messages ---
$message = '';
$messageType = '';

// --- Handle POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_type = trim($_POST['product_type'] ?? '');
    $batch_quantity = intval($_POST['batch_quantity'] ?? 0);
    $materials = $_POST['materials'] ?? [];

    if (empty($materials)) {
        $messageType = 'error';
        $message = "⚠️ You must have at least one material linked to the batch.";
    } else if ($batch_quantity <= 0) {
        $messageType = 'error';
        $message = "❌ Invalid batch quantity.";
    } else {
        $conn->begin_transaction();
        try {
            $batch_id = $is_edit ? $current_batch_id : null;

            if ($is_edit) {
                // --- Edit existing batch ---
                $stmt = $conn->prepare("SELECT status FROM batches WHERE id = ?");
                $stmt->bind_param("i", $batch_id);
                $stmt->execute();
                $oldBatch = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                if (!$oldBatch) throw new Exception("Batch not found");
                $old_status = $oldBatch['status'];

                // Update batch info
                $stmt = $conn->prepare("UPDATE batches SET product_name = ?, quantity = ? WHERE id = ?");
                $stmt->bind_param("sii", $product_type, $batch_quantity, $batch_id);
                $stmt->execute();
                $stmt->close();

                // Fetch old materials
                $stmt = $conn->prepare("SELECT stock_id, quantity_used FROM batch_materials WHERE batch_id = ?");
                $stmt->bind_param("i", $batch_id);
                $stmt->execute();
                $oldMaterials = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                $stmt->close();

                // Refund removed materials if batch in_progress
                if ($old_status === 'in_progress') {
                    foreach ($oldMaterials as $oMat) {
                        $found = false;
                        foreach ($materials as $mat) {
                            if ($mat['id'] == $oMat['stock_id']) $found = true;
                        }
                        if (!$found) {
                            // Refund using recorded usage so freshest stock returns first
                            $stmt = $conn->prepare("
                                SELECT bmu.inventory_batch_id, bmu.quantity_used
                                FROM batch_material_usage bmu
                                LEFT JOIN inventory_batches ib ON bmu.inventory_batch_id = ib.id
                                WHERE bmu.batch_id = ? AND bmu.stock_id = ?
                                ORDER BY 
                                    CASE WHEN ib.expiration_date IS NULL THEN 1 ELSE 0 END,
                                    ib.expiration_date ASC,
                                    bmu.inventory_batch_id ASC
                            ");
                            $stmt->bind_param("ii", $batch_id, $oMat['stock_id']);
                            $stmt->execute();
                            $usageRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                            $stmt->close();

                            $usageRows = array_reverse($usageRows); // refund fresh batches first
                            $remaining = $oMat['quantity_used'];
                            foreach ($usageRows as $usage) {
                                if ($remaining <= 0) break;
                                $refund = min($usage['quantity_used'], $remaining);

                                $stmt = $conn->prepare("UPDATE inventory_batches SET quantity = quantity + ? WHERE id = ?");
                                $stmt->bind_param("di", $refund, $usage['inventory_batch_id']);
                                $stmt->execute();
                                $stmt->close();

                                $stmt = $conn->prepare("
                                    UPDATE batch_material_usage
                                    SET quantity_used = quantity_used - ?
                                    WHERE batch_id = ? AND stock_id = ? AND inventory_batch_id = ?
                                ");
                                $stmt->bind_param("diii", $refund, $batch_id, $oMat['stock_id'], $usage['inventory_batch_id']);
                                $stmt->execute();
                                $stmt->close();

                                $remaining -= $refund;
                            }

                            // Remove depleted usage rows
                            $stmt = $conn->prepare("
                                DELETE FROM batch_material_usage
                                WHERE batch_id = ? AND stock_id = ? AND quantity_used <= 0
                            ");
                            $stmt->bind_param("ii", $batch_id, $oMat['stock_id']);
                            $stmt->execute();
                            $stmt->close();

                            // Refund main inventory too
                            $stmt = $conn->prepare("UPDATE inventory SET quantity = quantity + ? WHERE id = ?");
                            $stmt->bind_param("ii", $oMat['quantity_used'], $oMat['stock_id']);
                            $stmt->execute();
                            $stmt->close();
                        }
                    }
                }

                // Remove old batch materials
                $stmt = $conn->prepare("DELETE FROM batch_materials WHERE batch_id = ?");
                $stmt->bind_param("i", $batch_id);
                $stmt->execute();
                $stmt->close();

            } else {
                // --- New batch ---
                $stmt = $conn->prepare("INSERT INTO batches (product_name, status, scheduled_at, quantity) VALUES (?, 'scheduled', NOW(), ?)");
                $stmt->bind_param("si", $product_type, $batch_quantity);
                $stmt->execute();
                $batch_id = $conn->insert_id;
                $stmt->close();

                $oldMaterials = [];
                $old_status = 'scheduled';
            }

            // --- Materials Handling ---
            $insufficientStocks = [];
            foreach ($materials as $mat) {
                $stock_id = intval($mat['id']);
                $new_qty = intval($mat['quantity']);
                if ($new_qty <= 0) throw new Exception("Invalid quantity for material");

                // Lock inventory row
                $stmt = $conn->prepare("SELECT quantity, item_name FROM inventory WHERE id = ? FOR UPDATE");
                $stmt->bind_param("i", $stock_id);
                $stmt->execute();
                $stockData = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                if (!$stockData) throw new Exception("Material not found");

                $old_qty = 0;
                if ($is_edit) {
                    foreach ($oldMaterials as $oMat) {
                        if ($oMat['stock_id'] == $stock_id) $old_qty = $oMat['quantity_used'];
                    }
                }

                // Reserved stock in other batches
                $stmt = $conn->prepare("
                    SELECT SUM(bm.quantity_reserved) AS total_reserved
                    FROM batch_materials bm
                    JOIN batches b ON bm.batch_id = b.id
                    WHERE bm.stock_id = ? AND bm.batch_id != ? AND b.is_deleted = 0
                ");
                $stmt->bind_param("ii", $stock_id, $batch_id);
                $stmt->execute();
                $res = $stmt->get_result()->fetch_assoc();
                $reservedSum = $res['total_reserved'] ?? 0;
                $stmt->close();

                // Available stock from inventory_batches
                $stmt = $conn->prepare("
                    SELECT id, quantity
                    FROM inventory_batches
                    WHERE inventory_id = ? AND (expiration_date IS NULL OR expiration_date >= CURDATE())
                    ORDER BY expiration_date ASC
                    FOR UPDATE
                ");
                $stmt->bind_param("i", $stock_id);
                $stmt->execute();
                $batches = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                $stmt->close();

                $available_batches = array_sum(array_column($batches, 'quantity'));
                $available_stock = max(0, $available_batches - $reservedSum);

                // Check stock
                $maxAllowed = $available_stock;
                if ($is_edit && $old_status === 'in_progress') {
                    $maxAllowed = $old_qty + $available_stock;
                }
                if ($new_qty > $maxAllowed) {
                    $insufficientStocks[] = "⚠️ Not enough stock for '{$stockData['item_name']}'. Needed: {$new_qty}, Available: {$maxAllowed}";
                    continue;
                }

              // Deduct/refund stock for in-progress batch using batch_material_usage
if ($old_status === 'in_progress') {
    $diff = $new_qty - $old_qty;

    if ($diff != 0) {
        // Fetch how stock was originally used per inventory batch
        $stmt = $conn->prepare("
            SELECT bmu.inventory_batch_id, bmu.quantity_used
            FROM batch_material_usage bmu
            LEFT JOIN inventory_batches ib ON bmu.inventory_batch_id = ib.id
            WHERE bmu.batch_id = ? AND bmu.stock_id = ?
            ORDER BY 
                CASE WHEN ib.expiration_date IS NULL THEN 1 ELSE 0 END,
                ib.expiration_date ASC,
                bmu.inventory_batch_id ASC
        ");
        $stmt->bind_param("ii", $batch_id, $stock_id);
        $stmt->execute();
        $usage_rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        if ($diff > 0) {
            // Increase usage: deduct from oldest available batches (FIFO)
            $remaining = $diff;
            $stmt = $conn->prepare("
                SELECT id, quantity 
                FROM inventory_batches 
                WHERE inventory_id = ? AND (expiration_date IS NULL OR expiration_date >= CURDATE())
                ORDER BY expiration_date ASC
                FOR UPDATE
            ");
            $stmt->bind_param("i", $stock_id);
            $stmt->execute();
            $batches = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            foreach ($batches as $batch) {
                if ($remaining <= 0) break;
                $take = min($batch['quantity'], $remaining);

                $stmt = $conn->prepare("UPDATE inventory_batches SET quantity = quantity - ? WHERE id = ?");
                $stmt->bind_param("di", $take, $batch['id']);
                $stmt->execute();
                $stmt->close();

                // Record usage
                $stmt = $conn->prepare("
                    INSERT INTO batch_material_usage (batch_id, stock_id, inventory_batch_id, quantity_used, created_at)
                    VALUES (?, ?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE quantity_used = quantity_used + VALUES(quantity_used)
                ");
                $stmt->bind_param("iiid", $batch_id, $stock_id, $batch['id'], $take);
                $stmt->execute();
                $stmt->close();

                $remaining -= $take;
            }

        } else {
            // Decrease usage: refund freshest allocations first
            $remaining = abs($diff);
            $usage_rows = array_reverse($usage_rows);
foreach ($usage_rows as $usage) {
    if ($remaining <= 0) break;
    $refund = min($usage['quantity_used'], $remaining);

    // Refund this batch
    $stmt = $conn->prepare("UPDATE inventory_batches SET quantity = quantity + ? WHERE id = ?");
    $stmt->bind_param("di", $refund, $usage['inventory_batch_id']);
    $stmt->execute();
    $stmt->close();

    // Update usage
    $stmt = $conn->prepare("
        UPDATE batch_material_usage 
        SET quantity_used = quantity_used - ? 
        WHERE batch_id = ? AND stock_id = ? AND inventory_batch_id = ?
    ");
    $stmt->bind_param("diii", $refund, $batch_id, $stock_id, $usage['inventory_batch_id']);
    $stmt->execute();
    $stmt->close();

    $remaining -= $refund;
}

        }

        // Adjust main inventory
        $stmt = $conn->prepare("UPDATE inventory SET quantity = quantity - ? WHERE id = ?");
        $stmt->bind_param("ii", $diff, $stock_id);
        $stmt->execute();
        $stmt->close();
    }
}
                // Reserved quantity
                $reserved_qty = ($old_status === 'in_progress') ? 0 : $new_qty;

                // Insert or update batch_materials
                $stmt = $conn->prepare("
                    INSERT INTO batch_materials (batch_id, stock_id, quantity_used, quantity_reserved)
                    VALUES (?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE quantity_used = VALUES(quantity_used), quantity_reserved = VALUES(quantity_reserved)
                ");
                $stmt->bind_param("iiii", $batch_id, $stock_id, $new_qty, $reserved_qty);
                $stmt->execute();
                $stmt->close();
            }

            if (!empty($insufficientStocks)) {
                throw new Exception(implode(" | ", $insufficientStocks));
            }

            $conn->commit();

            // Log user action
            if (isset($_SESSION['user_id'])) {
                $user_id = $_SESSION['user_id'];
                $action = $is_edit ? "Batch Updated" : "Batch Created";
                $stmt = $conn->prepare("INSERT INTO batch_log (batch_id, user_id, action, timestamp) VALUES (?, ?, ?, NOW())");
                $stmt->bind_param("iis", $batch_id, $user_id, $action);
                $stmt->execute();
                $stmt->close();
            }

            $messageType = 'success';
            $message = $is_edit ? "✅ Batch '$product_type' updated successfully!" : "✅ Batch '$product_type' added successfully!";
        } catch (Exception $e) {
            $conn->rollback();
            $messageType = 'error';
            $message = "❌ Error: " . $e->getMessage();
        }
    }
}

?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add / Edit Batch | BloomLux</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/home.css">
    <link rel="stylesheet" href="css/add_batch.css">
</head>

<body>
    <!-- Sidebar (same as Home) -->
    <div class="sidebar">
        <h2>🌸 BloomLux Dashboard 🌸</h2>
        <a href="home.php">🏠 Home</a>
        <a href="supply.php">📦 Supply</a>
        <a href="production.php">🧁 Production</a>
        <a href="inventory.php">📊 Inventory</a>
        <a href="logout.php">🚪 Logout</a>
    </div>

    <!-- Main -->
    <div class="main">
        <div class="section-container batch-container">
            <div class="card batch-card">
                <h2>🌸 Add / Edit Batch 🌸</h2>
                <form id="batchForm" method="POST">
                    <label class="form-label">Product Type</label>
                    <input class="form-input" type="text" name="product_type" value="<?php echo htmlspecialchars($product_type_prefill); ?>" required>

                    <label class="form-label">Product Quantity</label>
                    <input class="form-input" type="number" name="batch_quantity" value="<?php echo htmlspecialchars($quantity_prefill); ?>" placeholder="Enter quantity" min="1" required>

                    <div id="materialsContainer">
                        <?php if ($materials_prefill): ?>
                            <?php foreach ($materials_prefill as $index => $mat): ?>
                                <div class="material-row">
                                    <label class="form-label">Material</label>
                                    <select class="form-select" name="materials[<?php echo $index; ?>][id]" required>
                                        <option value="">-- Select Material --</option>
                                        <?php foreach ($inventory_items as $item): ?>
                                            <option value="<?php echo $item['id']; ?>" <?php echo ($item['id'] == $mat['stock_id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($item['item_name']); ?> (Available: <?php echo max(0, $item['available_quantity']); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <label class="form-label">Quantity</label>
                                    <input class="form-input" type="number"
    name="materials[<?php echo $index; ?>][quantity]"
value="<?php echo intval($mat['quantity_used']); ?>"
    min="1"
    max="<?php
        $maxQty = 0;
        foreach ($inventory_items as $item) {
            if ($item['id'] == $mat['stock_id']) {
                $available = $item['available_quantity'];
                if ($is_edit) $available += $mat['quantity_used'];
                $maxQty = max($available, $mat['quantity_used'], 1);
                break;
            }
        }
        echo $maxQty;
    ?>"
    required>

                                    <button type="button" class="removeMaterialBtn">Remove</button>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="material-row">
                                <label class="form-label">Material</label>
                                <select class="form-select" name="materials[0][id]" required>
                                    <option value="">-- Select Material --</option>
                                    <?php foreach ($inventory_items as $item): ?>
                                        <option value="<?php echo $item['id']; ?>">
    <?php echo htmlspecialchars($item['item_name']); ?> (Available: <?php echo intval(max(0, $item['available_quantity'])); ?>)
</option>

                                    <?php endforeach; ?>
                                </select>

                                 <small class="wait-text">⏳ Please wait at least 3 - 5 seconds</small>

                                <label class="form-label">Quantity</label>
                                <input class="form-input" type="number" name="materials[0][quantity]" min="1" max="9999" required>
                                <button type="button" class="removeMaterialBtn">Remove</button>
                            </div>
                        <?php endif; ?>
                    </div>

                    <button type="button" class="action-btn" id="addMaterialBtn">+ Add Material</button>
                    <button type="submit" class="submit-btn">Save Batch</button>
                </form>
            </div>
        </div>
    </div>

    <div id="messageModal" class="modal">
        <div class="modal-content">
            <p id="modalMessage" class="<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></p>
            <button class="close-btn" onclick="closeModal()">OK</button>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const materialsContainer = document.getElementById('materialsContainer');
            const addMaterialBtn = document.getElementById('addMaterialBtn');
            let materialIndex = <?php echo count($materials_prefill) ?: 1; ?>;

            function attachSelectListeners(selectEl) {
                if (!selectEl) return;
                const triggerUpdate = () => updateStockOptions();
                selectEl.addEventListener('focus', triggerUpdate);
                selectEl.addEventListener('mousedown', triggerUpdate);
                selectEl.addEventListener('click', triggerUpdate);
            }

            // Function to create a new material row
            function createMaterialRow(index) {
                const div = document.createElement('div');
                div.classList.add('material-row');
                div.innerHTML = `
            <label class="form-label">Material</label>
            <select class="form-select" name="materials[${index}][id]" required>
                <option value="">-- Select Material --</option>
                <?php foreach ($inventory_items as $item): ?>
<option value="<?php echo $item['id']; ?>">
    <?php echo htmlspecialchars($item['item_name']); ?> (Available: <?php echo intval(max(0, $item['available_quantity'])); ?>)
</option>

                <?php endforeach; ?>
            </select>
            <label class="form-label">Quantity</label>
            <input class="form-input" type="number" name="materials[${index}][quantity]" min="1" required>
            <button type="button" class="removeMaterialBtn">Remove</button>
        `;
                div.querySelector('.removeMaterialBtn').addEventListener('click', () => div.remove());
                attachSelectListeners(div.querySelector('select'));
                return div;
            }

            // Make it globally accessible
            window.createMaterialRow = createMaterialRow;

            // Reset function
            function resetBatchForm() {
                const form = document.getElementById('batchForm');
                form.reset();
                materialsContainer.innerHTML = '';
                const newRow = createMaterialRow(0);
                materialsContainer.appendChild(newRow);
                materialIndex = 1;
            }

            // Highlight all material rows on page load
            document.querySelectorAll('#materialsContainer .material-row').forEach(row => {
                row.classList.add('highlight');
                setTimeout(() => row.classList.remove('highlight'), 1500);
            });

            // Add new material row
            addMaterialBtn.addEventListener('click', () => {
                const row = createMaterialRow(materialIndex);
                materialsContainer.appendChild(row);
                materialIndex++;
            });

            // Attach remove event for existing rows
            materialsContainer.querySelectorAll('.removeMaterialBtn').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.target.closest('.material-row').remove();
                });
            });

            // Stock updater (live)
            async function updateStockOptions() {
                try {
                    const response = await fetch('get_stock.php');
                    const data = await response.json();
                    if (!data.success) return;
                    const items = data.items;
                    document.querySelectorAll('#materialsContainer select').forEach(select => {
                        for (const option of select.options) {
                            const id = option.value;
                            if (items[id]) {
                                option.text = `${items[id].name} (Available: ${items[id].quantity})`;
                            }
                        }
                    });
                } catch (err) {
                    console.error('Error fetching stock:', err);
                }
            }

            setInterval(updateStockOptions, 5000);
            updateStockOptions();

            materialsContainer.querySelectorAll('select.form-select').forEach(attachSelectListeners);

            // Modal logic
            const messageModal = document.getElementById('messageModal');
            const closeBtn = messageModal.querySelector('.close-btn');
            closeBtn.addEventListener('click', () => {
                messageModal.style.display = 'none';

                // Redirect to production page after success
                <?php if ($messageType === 'success'): ?>
                    window.location.href = 'production.php';
                <?php endif; ?>
            });

            // Show modal when message exists
            <?php if (!empty($message) && $messageType === 'success'): ?>
                messageModal.style.display = 'block';
            <?php endif; ?>

            <?php if (!empty($message) && $messageType === 'error'): ?>
                messageModal.style.display = 'block';
            <?php endif; ?>
        });

        // Close modal function (for onclick handler)
        function closeModal() {
            const messageModal = document.getElementById('messageModal');
            messageModal.style.display = 'none';
            <?php if ($messageType === 'success'): ?>
                window.location.href = 'production.php';
            <?php endif; ?>
        }
    </script>