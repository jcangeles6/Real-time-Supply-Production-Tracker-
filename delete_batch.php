<?php
include 'db.php';
session_start();
$user_id = $_SESSION['user_id'] ?? null;
if (!isset($_GET['id'])) die("⚠️ Missing batch ID.");

$batch_id = intval($_GET['id']);

try {
    $conn->begin_transaction();

    // Fetch batch info
    $stmt = $conn->prepare("SELECT id, status FROM batches WHERE id = ?");
    $stmt->bind_param("i", $batch_id);
    $stmt->execute();
    $batch = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$batch) throw new Exception("❌ Batch not found.");
    if ($batch['status'] === 'completed') throw new Exception("⚠️ Completed batches cannot be deleted.");

    // ✅ Refund all material usage (if started)
    if ($batch['status'] === 'in_progress') {
        $stmt = $conn->prepare("
            SELECT stock_id, inventory_batch_id, quantity_used
            FROM batch_material_usage
            WHERE batch_id = ?
            FOR UPDATE
        ");
        $stmt->bind_param("i", $batch_id);
        $stmt->execute();
        $usages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        foreach ($usages as $use) {
            // Refund per inventory batch
            $stmt = $conn->prepare("UPDATE inventory_batches SET quantity = quantity + ? WHERE id = ?");
            $stmt->bind_param("di", $use['quantity_used'], $use['inventory_batch_id']);
            $stmt->execute();
            $stmt->close();

            // Refund main inventory
            $stmt = $conn->prepare("UPDATE inventory SET quantity = quantity + ? WHERE id = ?");
            $stmt->bind_param("di", $use['quantity_used'], $use['stock_id']);
            $stmt->execute();
            $stmt->close();
        }

        // Clear usage records
        $stmt = $conn->prepare("DELETE FROM batch_material_usage WHERE batch_id = ?");
        $stmt->bind_param("i", $batch_id);
        $stmt->execute();
        $stmt->close();
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

    // Mark notifications canceled
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
