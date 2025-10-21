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
$inv_result = $conn->query("
    SELECT i.id as item_id, i.item_name, i.unit, i.quantity, t.threshold 
    FROM inventory i
    LEFT JOIN stock_thresholds t ON i.id = t.item_id
    ORDER BY t.threshold ASC
");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🌸 BloomLux Restock Request 🌸</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #ffb3ecff;
            --card: #f5f0fa;
            --primary: #2e1a2eff;
            --text: #000000ff;
            --highlight: #000000ff;
            --shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--bg);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            color: var(--text);
        }

        .form-box {
            background: var(--card);
            padding: 40px 35px;
            border-radius: 20px;
            box-shadow: var(--shadow);
            width: 100%;
            max-width: 400px;
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .form-box:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(46, 26, 46, 0.15);
        }

        .emoji {
            font-size: 40px;
            margin-bottom: 10px;
        }

        h2 {
            color: var(--primary);
            font-size: 26px;
            font-weight: 700;
            margin-bottom: 20px;
        }

        label {
            display: block;
            text-align: left;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 6px;
            margin-top: 10px;
        }

        input,
        select,
        textarea {
            width: 94%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 10px;
            margin-bottom: 8px;
            font-size: 15px;
            background-color: #fff;
            transition: 0.3s;
        }

        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 6px rgba(46, 26, 46, 0.3);
        }

        button {
            width: 100%;
            padding: 12px;
            background-color: var(--primary);
            color: white;
            font-weight: 600;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s ease, transform 0.2s ease;
        }

        button:hover {
            background-color: var(--highlight);
            transform: scale(1.03);
        }

        .back-btn {
            display: inline-block;
            margin-top: 15px;
            padding: 10px 15px;
            background-color: #e7d4f9;
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            border-radius: 10px;
            transition: 0.3s;
        }

        .back-btn:hover {
            background-color: var(--primary);
            color: #fff;
        }

        .max-note {
            font-size: 13px;
            color: #555;
            margin-bottom: 15px;
        }

        @media (max-width: 500px) {
            .form-box {
                width: 90%;
                padding: 30px;
            }

            h2 {
                font-size: 22px;
            }
        }
    </style>
</head>

<body>
    <div class="form-box">
        <div class="emoji">🌸</div>
        <h2>Restock Request</h2>
        <form method="POST" id="requestForm">
            <label>Material Name</label>
            <select name="ingredient_name" id="ingredient" required>
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

            <label>Quantity to Request</label>
            <input type="number" name="quantity" id="quantity" min="1" placeholder="Enter quantity" required>
            <div class="max-note" id="maxNote">Available: -</div>
            <div class="warning-note" id="warningNote" style="color:red;font-size:13px;margin-bottom:10px;"></div>

            <label>Notes / Priority</label>
            <textarea name="notes" id="notes" placeholder="Optional: urgent, special instructions..." rows="3" maxlength="100"></textarea>
            <div style="font-size:12px;color:#555;">Max 100 characters</div>


            <!-- Hidden unit input -->
            <input type="hidden" name="unit" id="unit">

            <button type="submit">Submit Request</button>
        </form>

        <a href="supply.php" class="back-btn">⬅ Back to Supply</a>
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