<?php
include 'backend/init.php';

// --- DEVELOPMENT MODE ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$stmt = $conn->prepare("
    SELECT 
        i.id, 
        i.item_name, 
        i.quantity - IFNULL(SUM(bm.quantity_reserved), 0) AS available_quantity
    FROM inventory i
    LEFT JOIN batch_materials bm 
        ON bm.stock_id = i.id
    LEFT JOIN batches b 
        ON bm.batch_id = b.id AND b.is_deleted = 0
    WHERE i.item_name LIKE CONCAT('%', ?, '%')
    GROUP BY i.id
    ORDER BY i.item_name ASC
");

$search = $_GET['search'] ?? '';
$stmt->bind_param("s", $search);
$stmt->execute();
$inventory_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();


// Prefill variables
$product_type_prefill = $_GET['product_name'] ?? '';
$quantity_prefill = $_GET['quantity'] ?? '';
$materials_prefill = [];

if (isset($_GET['batch_id'])) {
    $batch_id = intval($_GET['batch_id']);

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

$message = '';
$messageType = '';

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
            $batch_id = isset($_GET['batch_id']) ? intval($_GET['batch_id']) : null;
            $is_edit = $batch_id !== null;

            if ($is_edit) {
                // Fetch old batch
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
                // New batch insert
                $stmt = $conn->prepare("INSERT INTO batches (product_name, status, scheduled_at, quantity) VALUES (?, 'scheduled', NOW(), ?)");
                $stmt->bind_param("si", $product_type, $batch_quantity);
                $stmt->execute();
                $batch_id = $conn->insert_id;
                $stmt->close();

                $oldMaterials = [];
                $old_status = 'scheduled';
            }

            // --- MATERIALS HANDLING ---
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

                // Old quantity
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
                WHERE bm.stock_id = ?
                AND bm.batch_id != ?
                AND b.is_deleted = 0
            ");
            $stmt->bind_param("ii", $stock_id, $batch_id);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();
            $reservedSum = $res['total_reserved'] ?? 0;
            $stmt->close();

                $available_stock = $stockData['quantity'] - $reservedSum;
                if (!$is_edit || $old_status !== 'in_progress') {
                    if ($new_qty > $available_stock) {
                        throw new Exception("⚠️ Not enough stock for '{$stockData['item_name']}'. Needed: {$new_qty}, Available: {$available_stock}");
                    }
                }


                // Adjust inventory if in-progress
                if ($old_status === 'in_progress') {
                    $diff = $new_qty - $old_qty;
                    if ($diff != 0) {
                        $stmt = $conn->prepare("UPDATE inventory SET quantity = quantity - ? WHERE id = ?");
                        $stmt->bind_param("ii", $diff, $stock_id);
                        $stmt->execute();
                        $stmt->close();
                    }
                }

                // Reserved quantity
                $reserved_qty = ($old_status === 'in_progress') ? 0 : $new_qty;

                // Insert/update
                $stmt = $conn->prepare("
        INSERT INTO batch_materials (batch_id, stock_id, quantity_used, quantity_reserved)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE quantity_used = VALUES(quantity_used), quantity_reserved = VALUES(quantity_reserved)
    ");
                $stmt->bind_param("iiii", $batch_id, $stock_id, $new_qty, $reserved_qty);
                $stmt->execute();
                $stmt->close();
            }



            $conn->commit();
            // --- Log user action (Batch Created or Updated) ---
            if (isset($_SESSION['user_id'])) {
                $user_id = $_SESSION['user_id'];
                $action = $is_edit ? "Batch Updated" : "Batch Created";

                $stmt = $conn->prepare("INSERT INTO batch_log (batch_id, user_id, action, timestamp) VALUES (?, ?, ?, NOW())");
                $stmt->bind_param("iis", $batch_id, $user_id, $action);
                $stmt->execute();
                $stmt->close();
            }


            // --- REFRESH materials_prefill so form shows latest DB data ---
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

            // --- Log user action (Batch Created or Updated) ---
            if (isset($_SESSION['user_id'])) {
                $user_id = $_SESSION['user_id'];
                $action = $is_edit ? "Batch Updated" : "Batch Created";
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
    <style>
        .batch-container { display:flex; justify-content:center; align-items:flex-start; }
        .batch-card { max-width: 600px; width:100%; margin:10px auto; }
        .batch-card h2 { color: var(--primary); margin-bottom: 20px; text-align: center; }
        .form-label { display:block; font-weight:600; color: var(--primary); margin:12px 0 6px; }
        .form-input, .form-select { width:100%; padding:12px; border:1px solid #ddd; border-radius:10px; background:#fff; font-size:15px; transition:0.2s; box-sizing:border-box; margin-bottom:10px; }
        .form-input:focus, .form-select:focus { outline:none; border-color: var(--primary); box-shadow: 0 0 6px rgba(46,26,46,0.25); }
        .material-row { background: rgba(255,255,255,0.5); padding:15px; border-radius:12px; margin-bottom:15px; border:1px solid rgba(255,255,255,0.3); }
        .submit-btn, .action-btn { width:100%; padding:12px; background: linear-gradient(135deg, #ffb3ec, #2e1a2eff); color:#fff; border:none; border-radius:12px; font-weight:600; cursor:pointer; transition:0.2s ease; margin-top:8px; margin-bottom:10px; }
        .submit-btn:hover, .action-btn:hover { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(46,26,46,0.25); }
        .action-btn { background: linear-gradient(135deg, #2e1a2eff, #4a2a4a); }
        .back-link { display:inline-block; margin-bottom:15px; color:#fff; background: linear-gradient(135deg, #ff9eb3, #ff4d4d); text-decoration:none; padding:10px 18px; border-radius:10px; font-weight:600; transition:0.2s; }
        .back-link:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(255,77,77,0.3); }
        .removeMaterialBtn { background:#b22222 !important; color:#fff !important; border:none !important; border-radius:8px !important; padding:8px 14px !important; margin-top:8px !important; cursor:pointer !important; font-weight:500 !important; transition:0.2s !important; }
        .removeMaterialBtn:hover { background:#8b1a1a !important; transform: translateY(-1px); }
        .modal { display:none; position:fixed; z-index:999; left:0; top:0; width:100%; height:100%; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); }
        .modal-content { background:#fff; border-radius:15px; padding:25px; max-width:400px; margin:15% auto; text-align:center; box-shadow: 0 8px 32px rgba(0,0,0,0.3); animation: popIn 0.3s ease; }
        @keyframes popIn { from { transform: scale(0.9); opacity:0; } to { transform: scale(1); opacity:1; } }
        .close-btn { background: linear-gradient(135deg, #ffb3ec, #2e1a2eff); color:#fff; border:none; border-radius:8px; padding:10px 20px; cursor:pointer; margin-top:15px; transition:0.2s; font-weight:600; }
        .close-btn:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(46,26,46,0.25); }
    </style>
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
                <a href="production.php" class="back-link">← Back to Production</a>
                <h2>🍞 Add / Edit Batch</h2>
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
                                        value="<?php echo htmlspecialchars($mat['quantity_used']); ?>"
                                        min="1"
                                        max="<?php
                                                foreach ($inventory_items as $item) {
                                                    if ($item['id'] == $mat['stock_id']) {
                                                        echo max(1, $item['available_quantity'] + $mat['quantity_used']);
                                                    }
                                                }
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
                                            <?php echo htmlspecialchars($item['item_name']); ?> (Available: <?php echo max(0, $item['available_quantity']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
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
                    <?php echo htmlspecialchars($item['item_name']); ?> (Available: <?php echo max(0, $item['available_quantity']); ?>)
                </option>
                <?php endforeach; ?>
            </select>
            <label class="form-label">Quantity</label>
            <input class="form-input" type="number" name="materials[${index}][quantity]" min="1" required>
            <button type="button" class="removeMaterialBtn">Remove</button>
        `;
                div.querySelector('.removeMaterialBtn').addEventListener('click', () => div.remove());
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