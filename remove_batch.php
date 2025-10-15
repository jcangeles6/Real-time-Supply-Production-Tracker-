<?php
include 'backend/init.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_GET['id']) || !isset($_SESSION['user_id'])) die("Unauthorized or missing batch ID.");

$batch_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

try {
    $conn->begin_transaction();

    // 1️⃣ Get batch info
    $batchQuery = $conn->prepare("SELECT stock_id, quantity, status FROM batches WHERE id = ?");
    $batchQuery->bind_param("i", $batch_id);
    $batchQuery->execute();
    $batch = $batchQuery->get_result()->fetch_assoc();
    $batchQuery->close();
    if (!$batch) throw new Exception("Batch not found.");

    // 2️⃣ Restore stock if in_progress
    if ($batch['status'] === 'in_progress' && $batch['stock_id']) {
        $stockQuery = $conn->prepare("SELECT quantity FROM inventory WHERE id = ?");
        $stockQuery->bind_param("i", $batch['stock_id']);
        $stockQuery->execute();
        $stockResult = $stockQuery->get_result()->fetch_assoc();
        $stockQuery->close();

        if ($stockResult) {
            $newQty = $stockResult['quantity'] + $batch['quantity'];
            $updateStock = $conn->prepare("UPDATE inventory SET quantity = ? WHERE id = ?");
            $updateStock->bind_param("ii", $newQty, $batch['stock_id']);
            $updateStock->execute();
            $updateStock->close();
        }
    }

    // 3️⃣ Soft delete batch
    $update = $conn->prepare("UPDATE batches SET is_deleted = 1 WHERE id = ?");
    $update->bind_param("i", $batch_id);
    $update->execute();
    $update->close();

    // 4️⃣ Log deletion
    $action = "Batch Deleted";
    $log = $conn->prepare("INSERT INTO batch_log (batch_id, user_id, action, timestamp) VALUES (?, ?, ?, NOW())");
    $log->bind_param("iis", $batch_id, $user_id, $action);
    $log->execute();
    $log->close();

    // 5️⃣ Hide related notifications using batch_id
    $updateNotif = $conn->prepare("
        UPDATE notifications
        SET type = 'deleted', message = CONCAT(message, ' (Canceled)')
        WHERE batch_id = ?
    ");
    $updateNotif->bind_param("i", $batch_id);
    $updateNotif->execute();
    $updateNotif->close();

    $conn->commit();
    header("Location: production.php");
    exit();
} catch (Exception $e) {
    $conn->rollback();
    echo "Error: " . $e->getMessage();
}
