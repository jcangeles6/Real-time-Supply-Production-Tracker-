<?php
include 'db.php';

$product_name_prefill = isset($_GET['product_name']) ? htmlspecialchars($_GET['product_name']) : '';
$quantity_prefill = isset($_GET['quantity']) ? intval($_GET['quantity']) : '';

$message = '';
$messageType = ''; // 'success' or 'error'

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_name = trim($_POST['product_name']);
    $quantity = intval($_POST['quantity']);

    // 🔹 Find inventory item with enough quantity
    $stockQuery = $conn->prepare("SELECT id, quantity FROM inventory WHERE item_name = ? AND quantity >= ? LIMIT 1");
    $stockQuery->bind_param("si", $product_name, $quantity);
    $stockQuery->execute();
    $stockResult = $stockQuery->get_result()->fetch_assoc();
    $stockQuery->close();

    if ($stockResult) {
        $stock_id = $stockResult['id'];
        $current_stock = $stockResult['quantity'];

        // 🔹 Start transaction
        $conn->begin_transaction();
        try {
            // 🔹 Insert batch with stock_id
            $stmt = $conn->prepare("INSERT INTO batches (product_name, stock_id, quantity, status, scheduled_at) VALUES (?, ?, ?, 'scheduled', NOW())");
            $stmt->bind_param("sii", $product_name, $stock_id, $quantity);
            $stmt->execute();
            $batch_id = $conn->insert_id; // get inserted batch ID
            $stmt->close();

            // 🔹 Log batch creation
            session_start();
            $user_id = $_SESSION['user_id'] ?? null;
            if ($user_id) {
                $log = $conn->prepare("INSERT INTO batch_log (batch_id, user_id, action, timestamp) VALUES (?, ?, 'Batch Created', NOW())");
                $log->bind_param("ii", $batch_id, $user_id);
                $log->execute();
                $log->close();
            }

            $conn->commit();

            $messageType = 'success';
            $message = "✅ Batch '$product_name' added successfully! Current stock: $current_stock left.";

        } catch (Exception $e) {
            $conn->rollback();
            $messageType = 'error';
            $message = "❌ Error adding batch: " . $e->getMessage();
        }
    } else {
        $stock_id = NULL;
        $messageType = 'error';
        $message = "⚠️ Product '$product_name' not found in inventory or insufficient stock.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Batch</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #fdf9f5;
            padding: 40px;
        }

        .form-box {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            margin: auto;
        }

        h2 {
            color: #5a2d0c;
            text-align: center;
        }

        label {
            display: block;
            margin: 10px 0 5px;
            font-weight: bold;
        }

        input,
        button {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 8px;
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

        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 90%;
            max-width: 400px;
            border-radius: 12px;
            text-align: center;
        }

        .close-btn {
            background: #8b4513;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            margin-top: 10px;
        }

        .close-btn:hover {
            background: #5a2d0c;
        }

        .success {
            color: green;
            font-weight: bold;
        }

        .error {
            color: red;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="form-box">
        <button type="button" onclick="window.location.href='production.php'" style="background:#555; margin-bottom:15px; font-size:14px;">← Back</button>
        <h2>➕ Add New Batch</h2>
        <form id="batchForm" method="POST">
            <label>Product Name</label>
            <input type="text" name="product_name" required value="<?php echo $product_name_prefill; ?>">

            <label>Quantity</label>
            <input type="number" name="quantity" min="1" required value="<?php echo $quantity_prefill; ?>">
            <span id="stockHint" style="font-size:13px; color:#555;"></span>


            <button type="submit">Save Batch</button>
        </form>
    </div>

    <!-- Modal -->
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

            // Reset form if success
            if (messageType === 'success') {
                document.getElementById('batchForm').reset();
            }
        }

        function closeModal() {
            document.getElementById('messageModal').style.display = 'none';
            // 🔹 Redirect to production page after closing modal
            window.location.href = "production.php";
        }
    </script>
</body>

<script>
const productInput = document.querySelector('input[name="product_name"]');
const stockHint = document.getElementById('stockHint');

productInput.addEventListener('input', function() {
    const productName = this.value.trim();
    if (!productName) {
        stockHint.innerText = '';
        return;
    }

    // 🔹 Fetch available stock via AJAX
    fetch('check_stock.php?product_name=' + encodeURIComponent(productName))
        .then(response => response.json())
        .then(data => {
            if (data.found) {
                stockHint.innerText = "Available stock: " + data.quantity;
                stockHint.style.color = data.quantity > 0 ? 'green' : 'red';
            } else {
                stockHint.innerText = "Product not found in inventory";
                stockHint.style.color = 'red';
            }
        });
});
</script>


</html>