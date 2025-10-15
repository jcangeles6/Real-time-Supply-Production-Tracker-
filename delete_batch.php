<?php
include 'db.php';
session_start();
$user_id = $_SESSION['user_id'] ?? null;
if (!isset($_GET['id'])) die("⚠️ Missing batch ID.");

$batch_id = intval($_GET['id']);

try {
    $conn->begin_transaction();

    // Fetch batch info and status
    $stmt = $conn->prepare("SELECT id, status FROM batches WHERE id = ?");
    $stmt->bind_param("i", $batch_id);
    $stmt->execute();
    $batch = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$batch) throw new Exception("❌ Batch not found.");
    if ($batch['status'] === 'completed') throw new Exception("⚠️ Completed batches cannot be deleted.");

    // Fetch batch materials
    $stmt = $conn->prepare("
        SELECT bm.stock_id, bm.quantity_used
        FROM batch_materials bm
        WHERE bm.batch_id = ? FOR UPDATE
    ");
    $stmt->bind_param("i", $batch_id);
    $stmt->execute();
    $materials = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Refund stock if in_progress
    foreach ($materials as $mat) {
        $refund_qty = ($batch['status'] === 'in_progress') ? $mat['quantity_used'] : 0;
        if ($refund_qty > 0) {
            $stmt = $conn->prepare("UPDATE inventory SET quantity = quantity + ? WHERE id = ?");
            $stmt->bind_param("ii", $refund_qty, $mat['stock_id']);
            $stmt->execute();
            $stmt->close();
        }
    }

    // Delete batch materials
    $stmt = $conn->prepare("DELETE FROM batch_materials WHERE batch_id = ?");
    $stmt->bind_param("i", $batch_id);
    $stmt->execute();
    $stmt->close();

    // Mark batch deleted
    $stmt = $conn->prepare("UPDATE batches SET is_deleted = 1 WHERE id = ?");
    $stmt->bind_param("i", $batch_id);
    $stmt->execute();

    // Log deletion
    if ($stmt->affected_rows > 0 && $user_id) {
        $log = $conn->prepare("INSERT INTO batch_log (batch_id, user_id, action, timestamp) VALUES (?, ?, 'Batch Deleted', NOW())");
        $log->bind_param("ii", $batch_id, $user_id);
        $log->execute();
        $log->close();
    }
    $stmt->close();

    // Hide related notifications using batch_id
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
    echo "❌ Error: " . $e->getMessage();
}
