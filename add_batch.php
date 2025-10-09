<?php
include 'db.php';

$product_name_prefill = isset($_GET['product_name']) ? htmlspecialchars($_GET['product_name']) : '';
$quantity_prefill = isset($_GET['quantity']) ? intval($_GET['quantity']) : '';

$message = '';
$messageType = ''; // 'success' or 'error'

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_name = trim($_POST['product_name']);
    $quantity = intval($_POST['quantity']);

    $stockQuery = $conn->prepare("SELECT id, quantity FROM inventory WHERE item_name = ? AND quantity >= ? LIMIT 1");
    $stockQuery->bind_param("si", $product_name, $quantity);
    $stockQuery->execute();
    $stockResult = $stockQuery->get_result()->fetch_assoc();
    $stockQuery->close();

    if ($stockResult) {
        $stock_id = $stockResult['id'];
        $current_stock = $stockResult['quantity'];

        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("INSERT INTO batches (product_name, stock_id, quantity, status, scheduled_at) VALUES (?, ?, ?, 'scheduled', NOW())");
            $stmt->bind_param("sii", $product_name, $stock_id, $quantity);
            $stmt->execute();
            $batch_id = $conn->insert_id;
            $stmt->close();

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
    <title>Add Batch | SweetCrumb</title>
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
            width: 380px;
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

        input {
            width: 93%;
            padding: 10px 14px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 10px;
            font-size: 15px;
            transition: border 0.2s;
        }

        input:focus {
            border: 1px solid #c47a3f;
            outline: none;
        }

        button {
            width: 102%;
            padding: 12px;
            background: #c47a3f;
            color: white;
            font-weight: 600;
            border: none;
            border-radius: 10px;
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

        #stockHint {
            font-size: 13px;
            color: #777;
            margin-bottom: 10px;
            display: block;
        }

        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 999;
            left: 0; top: 0;
            width: 100%; height: 100%;
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

        .success { color: #2e8b57; font-weight: 600; }
        .error { color: #cc0000; font-weight: 600; }

        @keyframes fadeIn {
            from {opacity: 0; transform: translateY(20px);}
            to {opacity: 1; transform: translateY(0);}
        }

        @keyframes pop {
            0% {transform: scale(0.9); opacity: 0;}
            100% {transform: scale(1); opacity: 1;}
        }
    </style>
</head>
<body>
    <div class="form-box">
        <button type="button" class="back-btn" onclick="window.location.href='production.php'">← Back</button>
        <h2>🍞 Add New Batch</h2>
        <form id="batchForm" method="POST">
            <label>Product Name</label>
            <input type="text" name="product_name" required value="<?php echo $product_name_prefill; ?>">

            <label>Quantity</label>
            <input type="number" name="quantity" min="1" required value="<?php echo $quantity_prefill; ?>">
            <span id="stockHint"></span>

            <button type="submit" id="saveBtn">Save Batch</button>
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
            if (messageType === 'success') {
                document.getElementById('batchForm').reset();
            }
        }

        function closeModal() {
            document.getElementById('messageModal').style.display = 'none';
            window.location.href = "production.php";
        }

        // Stock checker
        const productInput = document.querySelector('input[name="product_name"]');
        const stockHint = document.getElementById('stockHint');

        productInput.addEventListener('input', function() {
            const productName = this.value.trim();
            if (!productName) {
                stockHint.innerText = '';
                return;
            }

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
</body>
</html>
