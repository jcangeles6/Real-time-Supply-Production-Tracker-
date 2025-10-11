<?php
include 'backend/init.php';

if (isset($_GET['id']) && isset($_GET['status'])) {
    $id = $_GET['id'];
    $status = $_GET['status'];
    session_start();
    $user_id = $_SESSION['user_id'] ?? null;

    $allowed = ['scheduled', 'in_progress', 'completed'];
    if (!in_array($status, $allowed)) die("❌ Invalid status value.");

    try {
        $conn->begin_transaction();

        // Fetch batch
        $batchQuery = $conn->prepare("SELECT id, status FROM batches WHERE id = ?");
        $batchQuery->bind_param("i", $id);
        $batchQuery->execute();
        $batch = $batchQuery->get_result()->fetch_assoc();
        $batchQuery->close();

        if (!$batch) throw new Exception("❌ Batch not found.");

        // Only adjust stock if moving to in_progress
        if ($status === 'in_progress' && $batch['status'] !== 'in_progress') {

            // Fetch all materials for this batch, including previously reserved qty
            $materialsQuery = $conn->prepare("
                SELECT bm.stock_id, bm.quantity_used, bm.quantity_reserved, i.quantity AS current_stock, i.item_name
                FROM batch_materials bm
                JOIN inventory i ON bm.stock_id = i.id
                WHERE bm.batch_id = ?
            ");
            $materialsQuery->bind_param("i", $id);
            $materialsQuery->execute();
            $materials = $materialsQuery->get_result()->fetch_all(MYSQLI_ASSOC);
            $materialsQuery->close();

            // Adjust stock dynamically
            foreach ($materials as $mat) {
                $prev_used = $mat['quantity_reserved'] ?? 0;
                $diff = $mat['quantity_used'] - $prev_used; // new - old
                $new_stock = $mat['current_stock'] - $diff;

                if ($new_stock < 0) {
                    throw new Exception("⚠️ Not enough stock for '{$mat['item_name']}'. Needed adjustment: {$diff}, Available: {$mat['current_stock']}");
                }

                // Update inventory
                $updateStock = $conn->prepare("UPDATE inventory SET quantity = ? WHERE id = ?");
                $updateStock->bind_param("ii", $new_stock, $mat['stock_id']);
                $updateStock->execute();
                $updateStock->close();

                // Update reserved quantity
                $updateReserved = $conn->prepare("UPDATE batch_materials SET quantity_reserved = ? WHERE batch_id = ? AND stock_id = ?");
                $reserved = $mat['quantity_used'];
                $updateReserved->bind_param("iii", $reserved, $id, $mat['stock_id']);
                $updateReserved->execute();
                $updateReserved->close();
            }
        }

        // Update batch status
        if ($status === 'completed') {
            $stmt = $conn->prepare("UPDATE batches SET status = ?, completed_at = NOW() WHERE id = ?");
        } else {
            $stmt = $conn->prepare("UPDATE batches SET status = ? WHERE id = ?");
        }
        $stmt->bind_param("si", $status, $id);
        $stmt->execute();
        $stmt->close();

        // Log action
        if ($user_id) {
            $action = $status === 'in_progress' ? "Batch Started" : ($status === 'completed' ? "Batch Completed" : "");
            if ($action) {
                $log = $conn->prepare("INSERT INTO batch_log (batch_id, user_id, action, timestamp) VALUES (?, ?, ?, NOW())");
                $log->bind_param("iis", $id, $user_id, $action);
                $log->execute();
                $log->close();
            }
        }

        $conn->commit();
        header("Location: production.php");
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        echo $e->getMessage();
    }
} else {
    echo "⚠️ Missing parameters.";
}
