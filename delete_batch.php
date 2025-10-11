<?php
include 'db.php';
session_start();
$user_id = $_SESSION['user_id'] ?? null;

if (!isset($_GET['id'])) {
    die("⚠️ Missing batch ID.");
}

$batch_id = $_GET['id'];

try {
    $conn->begin_transaction();

    // Fetch batch info and status
    $batchQuery = $conn->prepare("SELECT id, status FROM batches WHERE id = ?");
    $batchQuery->bind_param("i", $batch_id);
    $batchQuery->execute();
    $batch = $batchQuery->get_result()->fetch_assoc();
    $batchQuery->close();

    if (!$batch) throw new Exception("❌ Batch not found.");

    // Prevent deleting completed batches
    if ($batch['status'] === 'completed') {
        throw new Exception("⚠️ Completed batches cannot be deleted.");
    }

    // Restore stock only if batch was in_progress
    if ($batch['status'] === 'in_progress') {
        $materialsQuery = $conn->prepare("
            SELECT bm.stock_id, bm.quantity_used, i.quantity AS current_stock
            FROM batch_materials bm
            JOIN inventory i ON bm.stock_id = i.id
            WHERE bm.batch_id = ?
        ");
        $materialsQuery->bind_param("i", $batch_id);
        $materialsQuery->execute();
        $materials = $materialsQuery->get_result()->fetch_all(MYSQLI_ASSOC);
        $materialsQuery->close();

        foreach ($materials as $mat) {
            $restored_stock = $mat['current_stock'] + $mat['quantity_used'];
            $updateStock = $conn->prepare("UPDATE inventory SET quantity = ? WHERE id = ?");
            $updateStock->bind_param("ii", $restored_stock, $mat['stock_id']);
            $updateStock->execute();
            $updateStock->close();
        }
    }

    // Mark batch as deleted
    $deleteStmt = $conn->prepare("UPDATE batches SET is_deleted = 1 WHERE id = ?");
    $deleteStmt->bind_param("i", $batch_id);
    $deleteStmt->execute();
    $deleteStmt->close();

    // Log deletion
    if ($user_id) {
        $log = $conn->prepare("INSERT INTO batch_log (batch_id, user_id, action, timestamp) VALUES (?, ?, 'Batch Deleted', NOW())");
        $log->bind_param("ii", $batch_id, $user_id);
        $log->execute();
        $log->close();
    }

    $conn->commit();
    header("Location: production.php");
    exit();

} catch (Exception $e) {
    $conn->rollback();
    echo $e->getMessage();
}
?>
