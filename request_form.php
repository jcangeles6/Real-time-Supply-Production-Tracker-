<?php
include 'backend/init.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ingredient_name = $_POST['ingredient_name'];
    $quantity = $_POST['quantity'];
    $unit = $_POST['unit'];
    $notes = $_POST['notes'] ?? '';
    $user_id = $_SESSION['user_id'];

    // Get the ingredient ID and available quantity from inventory
    $stmt = $conn->prepare("SELECT id, quantity FROM inventory WHERE item_name = ? LIMIT 1");
    $stmt->bind_param("s", $ingredient_name);
    $stmt->execute();
    $res = $stmt->get_result();
    $ingredient = $res->fetch_assoc();
    $ingredient_id = $ingredient['id'];
    $available_qty = intval($ingredient['quantity']);

    // Insert request (no limit on quantity for restock requests)
    $stmt2 = $conn->prepare("INSERT INTO requests (ingredient_id, user_id, ingredient_name, quantity, unit, notes, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
    $stmt2->bind_param("iissss", $ingredient_id, $user_id, $ingredient_name, $quantity, $unit, $notes);
    $stmt2->execute();

    header("Location: supply.php");
    exit();
}

// Fetch inventory with thresholds
$stmt = $conn->prepare("
    SELECT i.id AS item_id, i.item_name, i.unit, i.quantity, t.threshold
    FROM inventory i
    LEFT JOIN stock_thresholds t ON i.id = t.item_id
    ORDER BY t.threshold ASC
");
$stmt->execute();
$inv_result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🌸 BloomLux Restock Request 🌸</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/home.css">
    <style>
        /* Page-specific refinements to align with Home theme */
        .request-container {
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0 auto;
            width: 40%;
            min-height: calc(100vh - 120px);
            box-sizing: border-box;
        }
        .request-card {
            max-width: 520px;
            width: 100%;
            margin: 0 auto;
        }
        .request-card h2 {
            color: var(--primary);
            margin-bottom: 12px;
            text-align: center;
        }
        .form-field-label {
            display: block;
            font-weight: 600;
            color: var(--primary);
            margin: 8px 0 6px;
        }
        .form-field-input,
        .form-field-select,
        .form-field-textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 10px;
            background: #fff;
            font-size: 15px;
            transition: 0.2s;
            box-sizing: border-box;
        }
        .form-field-input:focus,
        .form-field-select:focus,
        .form-field-textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 6px rgba(46, 26, 46, 0.25);
        }
        .submit-btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #ffb3ec, #2e1a2eff);
            color: #fff;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s ease;
            margin-top: 8px;
        }
        .submit-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(46, 26, 46, 0.25);
        }
        .back-link {
            display: inline-block;
            margin-top: 12px;
            color: #fff;
            background: linear-gradient(135deg, #ff9eb3, #ff4d4d);
            text-decoration: none;
            padding: 8px 14px;
            border-radius: 10px;
            font-weight: 600;
        }
        .notes-hint {
            font-size: 12px;
            color: #333;
            margin-top: 4px;
        }
        .max-note { font-size: 13px; color: #555; margin-bottom: 10px; }
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
        <div class="section-container request-container">
            <div class="card request-card">
                <h2>Restock Request</h2>
                <form method="POST" id="requestForm">
                    <label class="form-field-label">Material Name</label>
                    <select class="form-field-select" name="ingredient_name" id="ingredient" required>
                        <option value="">Select Material</option>
                        <?php while ($item = $inv_result->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($item['item_name']); ?>"
                                data-unit="<?= htmlspecialchars($item['unit']); ?>"
                                data-qty="<?= $item['quantity']; ?>"
                                data-threshold="<?= $item['threshold']; ?>">
                                <?= htmlspecialchars($item['item_name']); ?> (Available: <?= $item['quantity']; ?>, Threshold: <?= $item['threshold']; ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>

                    <label class="form-field-label">Quantity to Request</label>
                    <input class="form-field-input" type="number" name="quantity" id="quantity" min="1" placeholder="Enter quantity" required>
                    <div class="max-note" id="maxNote">Available: -</div>
                    <div class="warning-note" id="warningNote" style="color:red;font-size:13px;margin-bottom:10px;"></div>

                    <label class="form-field-label">Notes / Priority</label>
                    <textarea class="form-field-textarea" name="notes" id="notes" placeholder="Optional: urgent, special instructions..." rows="3" maxlength="100"></textarea>
                    <div class="notes-hint">Max 100 characters</div>

                    <!-- Hidden unit input -->
                    <input type="hidden" name="unit" id="unit">

                    <button class="submit-btn" type="submit">Submit Request</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const ingredientSelect = document.getElementById('ingredient');
            const unitInput = document.getElementById('unit');
            const quantityInput = document.getElementById('quantity');
            const maxNote = document.getElementById('maxNote');
            const warningNote = document.getElementById('warningNote');

            let inventoryData = {};
            let currentThreshold = 0;

            // Fetch real-time inventory
            const fetchInventory = async () => {
                try {
                    const res = await fetch('backend/supply_page/fetch_supply.php');
                    const data = await res.json();
                    inventoryData = {};

                    // Preserve the currently selected material
                    const currentValue = ingredientSelect.value;

                    ingredientSelect.innerHTML = '<option value="">Select Material</option>';
                    data.forEach(item => {
                        inventoryData[item.item_name] = item;
                        const option = document.createElement('option');
                        option.value = item.item_name;
                        option.dataset.unit = item.unit;
                        option.dataset.available = item.available_qty;
                        option.dataset.total = item.total_qty;
                        option.dataset.threshold = item.threshold;
                        option.textContent = `${item.item_name} (Available: ${item.available_qty}, Total: ${item.total_qty}, Threshold: ${item.threshold})`;
                        ingredientSelect.appendChild(option);
                    });

                    // Restore previously selected option
                    if (currentValue && inventoryData[currentValue]) {
                        ingredientSelect.value = currentValue;

                        // Update the maxNote display as well
                        const selected = inventoryData[currentValue];
                        unitInput.value = selected.unit;
                        currentThreshold = selected.threshold;
                        maxNote.textContent = `Available: ${selected.available_qty}, Total: ${selected.total_qty}, Threshold: ${currentThreshold}`;
                    }

                } catch (err) {
                    console.error('Failed to fetch inventory:', err);
                }
            };

            fetchInventory();

            ingredientSelect.addEventListener('change', function() {
                const selected = inventoryData[this.value];
                if (!selected) return;

                unitInput.value = selected.unit;
                currentThreshold = selected.threshold;
                maxNote.textContent = `Available: ${selected.available_qty}, Total: ${selected.total_qty}, Threshold: ${currentThreshold}`;

                warningNote.textContent = '';
                quantityInput.style.borderColor = '#ddd';
            });

            quantityInput.addEventListener('input', function() {
                const val = parseInt(this.value);
                if (val > currentThreshold) {
                    warningNote.textContent = `⚠️ Admin will review requests exceeding ${currentThreshold} units.`;
                    quantityInput.style.borderColor = 'red';
                } else {
                    warningNote.textContent = '';
                    quantityInput.style.borderColor = '#ddd';
                }
            });

            // Optional: refresh inventory every 5s so user sees live stock changes
            setInterval(fetchInventory, 5000);
        });
    </script>


</body>

</html>