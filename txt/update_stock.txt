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
  <title>🌸 Update Stock - BloomLux</title>
  <style>
    :root {
      --bg: #ffb3ecff;          /* soft pink background */
      --card: #f5f0fa;          /* light lavender card */
      --primary: #2e1a2eff;     /* deep lavender */
      --accent: #2e1a2eff;        /* bright pink */
      --white: #ffffff;
      --shadow: 0 4px 12px rgba(0,0,0,0.1);
    }

    body {
      font-family: 'Poppins', sans-serif;
      background: var(--bg);
      margin: 0;
      padding: 0;
      color: var(--primary);
    }

    .main {
      padding: 30px;
      max-width: 900px;
      margin: 0 auto;
      animation: fadeIn 0.5s ease;
    }

    h1 {
      color: var(--primary);
      margin-bottom: 25px;
      text-align: center;
      font-weight: 700;
    }

    /* Back Button */
    .back-btn {
      display: inline-block;
      margin-bottom: 20px;
      padding: 8px 16px;
      background: var(--accent);
      color: var(--white);
      text-decoration: none;
      border-radius: 25px;
      font-weight: 600;
      font-size: 14px;
      transition: 0.3s ease;
    }

    .back-btn:hover {
      background: var(--primary);
      transform: scale(1.05);
    }

    /* Form Box */
    .form-box {
      background: var(--white);
      padding: 25px 30px;
      border-radius: 16px;
      box-shadow: var(--shadow);
      margin: 0 auto 30px;
      width: 100%;
      max-width: 600px;
      box-sizing: border-box;
      animation: fadeIn 0.4s ease;
    }

    label {
      display: block;
      margin: 10px 0 5px;
      font-weight: 600;
      color: var(--primary);
    }

    input,
    select,
    button {
      width: 100%;
      padding: 12px;
      margin-bottom: 15px;
      border: 1px solid #ddd;
      border-radius: 10px;
      box-sizing: border-box;
      font-family: 'Poppins', sans-serif;
    }

    input:focus,
    select:focus {
      border-color: var(--accent);
      outline: none;
      box-shadow: 0 0 6px rgba(255, 125, 216, 0.5);
    }

    button {
      background: var(--accent);
      color: white;
      font-weight: 600;
      border: none;
      cursor: pointer;
      border-radius: 25px;
      transition: 0.3s;
    }

    button:hover {
      background: var(--primary);
      transform: scale(1.05);
    }

    /* Status */
    .status-display {
      font-weight: 600;
      margin-bottom: 15px;
      text-align: center;
    }

    .status-available {
      color: #2e8b57;
    }

    .status-low {
      color: #e4a11b;
    }

    .status-out {
      color: #d11a2a;
    }

    .success-msg {
      color: #2e8b57;
      font-weight: 600;
      text-align: center;
      margin-bottom: 15px;
    }

    /* Animation */
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
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
