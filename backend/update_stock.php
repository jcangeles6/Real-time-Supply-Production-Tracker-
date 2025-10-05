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

$id = $_GET['id'];

// Ensure ID is numeric
$id = intval($_GET['id']);

// Fetch stock item
$stmt = $conn->prepare("SELECT * FROM inventory WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$stock = $result->fetch_assoc();

if (!$stock) {
    echo "Stock item not found!";
    exit();
}

// Handle POST (update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Accept JSON input if sent
    $data = json_decode(file_get_contents('php://input'), true);
    $data = $data ?? $_POST; // Fallback to form POST

    // Validate required fields (quantity 0 is allowed)
    if (empty($data['item_name']) || !isset($data['quantity']) || empty($data['unit'])) {
        echo "Missing required fields!";
        exit();
    }

    $item_name = $data['item_name'];
    $quantity = intval($data['quantity']);
    $unit = $data['unit'];

    // Automatically determine status
    if ($quantity === 0) {
        $status = 'out';
    } elseif ($quantity > 0 && $quantity <= 4) {
        $status = 'low';
    } else {
        $status = 'available';
    }

    $update = $conn->prepare("UPDATE inventory SET item_name=?, quantity=?, unit=?, status=?, updated_at=NOW() WHERE id=?");
    $update->bind_param("sissi", $item_name, $quantity, $unit, $status, $id);
    $update->execute();

    $success = true;

    // If called via API (JSON), return JSON response
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
    <title>Update Stock - Bakery</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #fdf6f0;
            margin: 0;
            padding: 0;
        }

        .main {
            padding: 20px;
            max-width: 900px;
            margin: 0 auto;
        }

        h1 {
            color: #8b4513;
            margin-bottom: 20px;
            text-align: center;
        }

        .back-btn {
            display: inline-block;
            margin-bottom: 20px;
            padding: 6px 12px;
            background: #8b4513;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            font-size: 14px;
        }

        .back-btn:hover {
            background: #5a2d0c;
        }

        .form-box {
            background: white;
            padding: 25px 30px;
            border-radius: 12px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
            margin: 0 auto 30px;
            width: 100%;
            max-width: 600px;
            box-sizing: border-box;
        }

        label {
            display: block;
            margin: 10px 0 5px;
            font-weight: bold;
        }

        input,
        select,
        button {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 8px;
            box-sizing: border-box;
        }

        button {
            background: #8b4513;
            color: white;
            font-weight: bold;
            border: none;
            cursor: pointer;
        }

        button:hover {
            background: #5a2d0c;
        }

        .success-msg {
            color: green;
            font-weight: bold;
            text-align: center;
            margin-bottom: 15px;
        }

        .status-display {
            font-weight: bold;
            margin-bottom: 15px;
        }

        .status-available {
            color: green;
        }

        .status-low {
            color: orange;
        }

        .status-out {
            color: red;
        }
    </style>
</head>

<body>
    <div class="main">
        <a href="add_stock.php" class="back-btn">⬅ Back to Inventory</a>
        <h1>✏️ Update Stock</h1>

        <?php if (isset($success) && $success): ?>
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

                <!-- Hidden status input -->
                <input type="hidden" name="status" id="statusInput" value="<?= $stock['status']; ?>">

                <!-- Status display -->
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
                let status = '';
                if (qty === 0) {
                    status = 'out';
                    statusDisplay.textContent = 'Out of Stock';
                    statusDisplay.className = 'status-display status-out';
                } else if (qty > 0 && qty <= 4) {
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
            updateStatus(); // initialize on page load
        </script>
    </div>
</body>

</html>
