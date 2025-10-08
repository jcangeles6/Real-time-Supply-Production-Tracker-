<?php
include 'db.php';

if (isset($_GET['id']) && isset($_GET['status'])) {
    $id = $_GET['id'];
    $status = $_GET['status'];
    session_start();
    $user_id = $_SESSION['user_id'] ?? null;

    $allowed = ['scheduled', 'in_progress', 'completed'];
    if (!in_array($status, $allowed)) {
        die("❌ Invalid status value.");
    }

    try {
        // 🔹 Start a transaction
        $conn->begin_transaction();

        // Get batch info first
        $batchQuery = $conn->prepare("SELECT stock_id, quantity FROM batches WHERE id = ?");
        $batchQuery->bind_param("i", $id);
        $batchQuery->execute();
        $batch = $batchQuery->get_result()->fetch_assoc();
        $batchQuery->close();

        if (!$batch) {
            throw new Exception("❌ Batch not found.");
        }

        $stock_id = $batch['stock_id'];
        $batch_qty = $batch['quantity'];

        // 🔹 If starting batch, deduct stock
        if ($status === 'in_progress') {
            if (is_null($stock_id)) {
                throw new Exception("⚠️ Batch #$id does not have a stock item linked. Please set stock_id.");
            }

            // Get current stock from inventory table
            $stockQuery = $conn->prepare("SELECT quantity FROM inventory WHERE id = ?");
            $stockQuery->bind_param("i", $stock_id);
            $stockQuery->execute();
            $stockResult = $stockQuery->get_result()->fetch_assoc();
            $stockQuery->close();

            if (!$stockResult) {
                throw new Exception("❌ Stock item not found in inventory for stock_id $stock_id.");
            }

            $current_stock = $stockResult['quantity'];

            // Check stock
            if ($current_stock < $batch_qty) {
                throw new Exception("⚠️ Insufficient stock to start batch. Current stock: $current_stock, needed: $batch_qty");
            }

            // Deduct stock
            $new_stock = $current_stock - $batch_qty;
            $updateStock = $conn->prepare("UPDATE inventory SET quantity = ? WHERE id = ?");
            $updateStock->bind_param("ii", $new_stock, $stock_id);
            $updateStock->execute();
            $updateStock->close();
        }

        // 🔹 Update batch status
        if ($status === 'completed') {
            $stmt = $conn->prepare("UPDATE batches SET status = ?, completed_at = NOW() WHERE id = ?");
        } else {
            $stmt = $conn->prepare("UPDATE batches SET status = ? WHERE id = ?");
        }

        $stmt->bind_param("si", $status, $id);
        $stmt->execute();
        $stmt->close();

        // 🔹 Log the action
        if ($user_id) {
            $action = "";
            if ($status === 'in_progress') {
                $action = "Batch Started";
            } elseif ($status === 'completed') {
                $action = "Batch Completed";
            }

            if ($action) {
        $log = $conn->prepare("INSERT INTO batch_log (batch_id, user_id, action, timestamp) VALUES (?, ?, ?, NOW())");
        $log->bind_param("iis", $id, $user_id, $action);
        $log->execute();
        $log->close();
    }
}
        // 🔹 Commit changes
        $conn->commit();
        header("Location: production.php");
        exit();

    } catch (Exception $e) {
        // 🔹 Rollback if anything fails
        $conn->rollback();
        echo $e->getMessage();
    }

} else {
    echo "⚠️ Missing parameters.";
}
?>
