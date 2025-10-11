<?php
include 'backend/init.php';

// --- DEVELOPMENT MODE ---
// Show all errors on screen for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Enable exceptions for mysqli
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// --- Load inventory items ---
$inventory_items = $conn->query("
    SELECT id, item_name, quantity 
    FROM inventory 
    ORDER BY item_name ASC
")->fetch_all(MYSQLI_ASSOC);

// --- AJAX endpoint for stock hint ---
if (isset($_GET['get_stock'])) {
    $item_id = intval($_GET['get_stock']); // assume front-end sends item ID
    header('Content-Type: application/json');

    if (!$item_id) {
        echo json_encode(['success' => false]);
        exit;
    }

    $conn->begin_transaction();

    $stmt = $conn->prepare("SELECT quantity FROM inventory WHERE id = ? FOR UPDATE");
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $stockData = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $conn->commit();

    if ($stockData) {
        echo json_encode(['success' => true, 'quantity' => intval($stockData['quantity'])]);
    } else {
        echo json_encode(['success' => false]);
    }
    exit;
}


// --- Normal batch form logic ---
$message = '';
$messageType = ''; // 'success' or 'error'

// Prefill for editing or duplicating batch
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

    // Prefill batch info for editing
    $batchInfo = $conn->query("SELECT product_name, quantity FROM batches WHERE id = $batch_id")->fetch_assoc();
    if ($batchInfo) {
        $product_type_prefill = $batchInfo['product_name'];
        $quantity_prefill = $batchInfo['quantity'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_type = trim($_POST['product_type'] ?? '');
    $batch_quantity = trim($_POST['batch_quantity'] ?? '');
    $materials = $_POST['materials'] ?? [];

    if (!is_numeric($batch_quantity) || intval($batch_quantity) <= 0) {
        $messageType = 'error';
        $message = "❌ Invalid batch quantity.";
    } else {
        $batch_quantity = intval($batch_quantity);

        $conn->begin_transaction(); // Start transaction
        try {
            $batch_id = isset($_GET['batch_id']) ? intval($_GET['batch_id']) : null;
            $is_edit = $batch_id !== null;

            if ($is_edit) {
                // Fetch existing batch info securely
                $stmt = $conn->prepare("SELECT status FROM batches WHERE id = ?");
                $stmt->bind_param("i", $batch_id);
                $stmt->execute();
                $batchInfo = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if (!$batchInfo) throw new Exception("Batch not found");
                $old_status = $batchInfo['status'];

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

                // Refund removed materials if batch in progress
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
                // Insert new batch
                $stmt = $conn->prepare("INSERT INTO batches (product_name, status, scheduled_at, quantity) VALUES (?, 'scheduled', NOW(), ?)");
                $stmt->bind_param("si", $product_type, $batch_quantity);
                $stmt->execute();
                $batch_id = $conn->insert_id;
                $stmt->close();

                $oldMaterials = [];
                $old_status = 'scheduled';
            }

            // Insert new materials with concurrency-safe stock deduction
            foreach ($materials as $mat) {
                $stock_id = intval($mat['id']);
                $new_qty = intval($mat['quantity']);
                if ($new_qty <= 0) throw new Exception("Invalid quantity for material");

                // Lock the row to prevent race conditions
                $stmt = $conn->prepare("SELECT quantity FROM inventory WHERE id = ? FOR UPDATE");
                $stmt->bind_param("i", $stock_id);
                $stmt->execute();
                $stockData = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if (!$stockData) throw new Exception("Invalid material selected");

                // Deduct stock safely
                if ($is_edit && $old_status === 'in_progress') {
                    $old_qty = 0;
                    foreach ($oldMaterials as $oMat) {
                        if ($oMat['stock_id'] == $stock_id) $old_qty = $oMat['quantity_used'];
                    }
                    $diff = $new_qty - $old_qty;
                    $new_stock = $stockData['quantity'] - $diff;
                } else {
                    $new_stock = $stockData['quantity'] - $new_qty;
                }

                if ($new_stock < 0) throw new Exception("⚠️ Not enough stock for material ID $stock_id");

                $stmt = $conn->prepare("UPDATE inventory SET quantity = ? WHERE id = ?");
                $stmt->bind_param("ii", $new_stock, $stock_id);
                $stmt->execute();
                $stmt->close();

                // Insert batch materials
                $stmt = $conn->prepare("INSERT INTO batch_materials (batch_id, stock_id, quantity_used) VALUES (?, ?, ?)");
                $stmt->bind_param("iii", $batch_id, $stock_id, $new_qty);
                $stmt->execute();
                $stmt->close();
            }

            // Log batch action
            $user_id = $_SESSION['user_id'] ?? null;
            if ($user_id) {
                $action = $is_edit ? "Batch Updated" : "Batch Created";
                $stmt = $conn->prepare("INSERT INTO batch_log (batch_id, user_id, action, timestamp) VALUES (?, ?, ?, NOW())");
                $stmt->bind_param("iis", $batch_id, $user_id, $action);
                $stmt->execute();
                $stmt->close();
            }

            $conn->commit();
            $messageType = 'success';
            $message = $is_edit ? "✅ Batch '$product_type' updated successfully!" : "✅ Batch '$product_type' added successfully!";
        } catch (Exception $e) {
            $conn->rollback(); // rollback on any error
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
    <title>Add / Edit Batch | SweetCrumb</title>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(to bottom right, #fff7f3, #ffe9dc);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .form-box {
            background: white;
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            width: 400px;
            padding: 30px;
            text-align: center;
            animation: fadeIn 0.6s ease-in-out;
        }

        h2 {
            color: #7b3f00;
            margin-bottom: 20px;
        }

        label {
            text-align: left;
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #5a2d0c;
        }

        input,
        select {
            width: 93%;
            padding: 10px 14px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 10px;
            font-size: 15px;
            transition: border 0.2s;
        }

        input:focus,
        select:focus {
            border: 1px solid #c47a3f;
            outline: none;
        }

        button {
            width: 100%;
            padding: 12px;
            background: #c47a3f;
            color: white;
            font-weight: 600;
            border: none;
            border-radius: 40px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        button:hover {
            background: #9b5a26;
            transform: scale(1.03);
        }

        .back-btn {
            background: #7b3f00;
            margin-bottom: 15px;
            font-size: 14px;
        }

        .back-btn:hover {
            background: #5a2d0c;
        }

        .material-row {
            margin-bottom: 10px;
        }

        #addMaterialBtn {
            margin-bottom: 15px;
            background: #7b3f00;
            width: 100%;
        }

        #addMaterialBtn:hover {
            background: #5a2d0c;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(4px);
        }

        .modal-content {
            background: white;
            border-radius: 15px;
            padding: 25px;
            max-width: 400px;
            margin: 15% auto;
            text-align: center;
            animation: pop 0.3s ease-in-out;
        }

        .close-btn {
            background: #7b3f00;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            cursor: pointer;
            margin-top: 15px;
        }

        .success {
            color: #2e8b57;
            font-weight: 600;
        }

        .error {
            color: #cc0000;
            font-weight: 600;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes pop {
            from {
                transform: scale(0.9);
                opacity: 0;
            }

            to {
                transform: scale(1);
                opacity: 1;
            }
        }
    </style>
</head>

<body>
    <div class="form-box">
        <button type="button" class="back-btn" onclick="window.location.href='production.php'">← Back</button>
        <h2>🍞 Add / Edit Batch</h2>
        <form id="batchForm" method="POST">
            <label>Product Type</label>
            <input type="text" name="product_type" value="<?php echo htmlspecialchars($product_type_prefill); ?>" required>

            <label>Product Quantity</label>
            <input type="text" name="batch_quantity" value="<?php echo htmlspecialchars($quantity_prefill); ?>" placeholder="Enter quantity" required>

            <div id="materialsContainer">
                <?php if ($materials_prefill): ?>
                    <?php foreach ($materials_prefill as $index => $mat): ?>
                        <div class="material-row">
                            <label>Material</label>
                            <select name="materials[<?php echo $index; ?>][id]" required>
                                <option value="">-- Select Material --</option>
                                <?php foreach ($inventory_items as $item): ?>
                                    <option value="<?php echo $item['id']; ?>" <?php echo ($item['id'] == $mat['stock_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($item['item_name']); ?> (Available: <?php echo $item['quantity']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <label>Quantity</label>
                            <input type="number" name="materials[<?php echo $index; ?>][quantity]" value="<?php echo htmlspecialchars($mat['quantity_used']); ?>" min="1" required>
                            <button type="button" class="removeMaterialBtn" style="background:#b22222;color:white;border:none;border-radius:40px;padding:1px 10px;margin-top:5px;cursor:pointer;">Remove</button>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="material-row">
                        <label>Material</label>
                        <select name="materials[0][id]" required>
                            <option value="">-- Select Material --</option>
                            <?php foreach ($inventory_items as $item): ?>
                                <option value="<?php echo $item['id']; ?>">
                                    <?php echo htmlspecialchars($item['item_name']); ?> (Available: <?php echo $item['quantity']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <label>Quantity</label>
                        <input type="number" name="materials[0][quantity]" min="1" required>
                        <button type="button" class="removeMaterialBtn" style="background:#b22222;color:white;border:none;border-radius:40px;padding:10px 10px;margin-top:5px;cursor:pointer;">Remove</button>
                    </div>
                <?php endif; ?>
            </div>

            <button type="button" id="addMaterialBtn">+ Add Material</button>
            <button type="submit">Save Batch</button>
        </form>
    </div>

    <div id="messageModal" class="modal">
        <div class="modal-content">
            <p id="modalMessage" class="<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></p>
            <button class="close-btn" onclick="closeModal()">OK</button>
        </div>
    </div>

    <script>
        const message = "<?php echo addslashes($message); ?>";
        const messageType = "<?php echo $messageType; ?>";

        if (message) {
            const modal = document.getElementById('messageModal');
            modal.style.display = 'block';
            if (messageType === 'success') document.getElementById('batchForm').reset();
        }

        function closeModal() {
            document.getElementById('messageModal').style.display = 'none';
            window.location.href = 'production.php';
        }

        let materialIndex = <?php echo max(1, count($materials_prefill)); ?>;
        const materialsContainer = document.getElementById('materialsContainer');
        const addMaterialBtn = document.getElementById('addMaterialBtn');

        function createMaterialRow(index) {
            const row = document.createElement('div');
            row.classList.add('material-row');

            let options = '<option value="">-- Select Material --</option>';
            <?php foreach ($inventory_items as $item): ?>
                options += `<option value="<?php echo $item['id']; ?>"><?php echo htmlspecialchars($item['item_name']); ?> (Available: <?php echo $item['quantity']; ?>)</option>`;
            <?php endforeach; ?>

            row.innerHTML = `
    <label>Material</label>
    <select name="materials[${index}][id]" required>${options}</select>
    <label>Quantity</label>
    <input type="number" name="materials[${index}][quantity]" min="1" required>
    <button type="button" class="removeMaterialBtn" style="background:#b22222;color:white;border:none;border-radius:5px;padding:5px 10px;margin-top:5px;cursor:pointer;">Remove</button>
    `;
            materialsContainer.appendChild(row);

            row.querySelector('.removeMaterialBtn').addEventListener('click', () => {
                row.remove();
            });
        }

        addMaterialBtn.addEventListener('click', () => {
            createMaterialRow(materialIndex);
            materialIndex++;
        });

        document.querySelectorAll('.material-row').forEach((row) => {
            const btn = row.querySelector('.removeMaterialBtn');
            if (btn) btn.addEventListener('click', () => row.remove());
        });
    </script>
</body>

</html>