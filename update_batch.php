<?php

//NOTIFICATION PARA SA BATCHES PRODUCTION (STARTED, COMPLETED)

include 'backend/init.php';
session_start();
$user_id = $_SESSION['user_id'] ?? null;

if (!isset($_GET['id'], $_GET['status'])) {
    $_SESSION['batch_error'] = "⚠️ Missing parameters.";
    header("Location: production.php");
    exit();
}

$id = intval($_GET['id']);
$status = $_GET['status'];
$allowed = ['scheduled', 'in_progress', 'completed'];

if (!in_array($status, $allowed)) {
    $_SESSION['batch_error'] = "❌ Invalid status value.";
    header("Location: production.php");
    exit();
}

try {
    $conn->begin_transaction();

    // Fetch batch
    $stmt = $conn->prepare("SELECT id, status FROM batches WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $batch = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$batch) throw new Exception("Batch not found.");

    // --- Start batch → deduct stock ---
    if ($status === 'in_progress' && $batch['status'] !== 'in_progress') {
        $stmt = $conn->prepare("
            SELECT bm.stock_id, bm.quantity_used, i.quantity AS current_stock, i.item_name
            FROM batch_materials bm
            JOIN inventory i ON bm.stock_id = i.id
            WHERE bm.batch_id = ?
            FOR UPDATE
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $materials = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        foreach ($materials as $mat) {
            $new_stock = $mat['current_stock'] - $mat['quantity_used'];
            if ($new_stock < 0) {
                throw new Exception("⚠️ Not enough stock for '{$mat['item_name']}'. Needed: {$mat['quantity_used']}, Available: {$mat['current_stock']}");
            }
            // Deduct actual inventory
            $stmt = $conn->prepare("UPDATE inventory SET quantity = ? WHERE id = ?");
            $stmt->bind_param("ii", $new_stock, $mat['stock_id']);
            $stmt->execute();
            $stmt->close();

            // Clear reserved quantities
            $stmt = $conn->prepare("UPDATE batch_materials SET quantity_reserved = 0 WHERE batch_id = ? AND stock_id = ?");
            $stmt->bind_param("ii", $id, $mat['stock_id']);
            $stmt->execute();
            $stmt->close();
        }
    }

    // --- Complete batch → clear reserved quantities ---
    if ($status === 'completed') {
        $stmt = $conn->prepare("UPDATE batch_materials SET quantity_reserved = 0 WHERE batch_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

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
            $stmt = $conn->prepare("INSERT INTO batch_log (batch_id, user_id, action, timestamp) VALUES (?, ?, ?, NOW())");
            $stmt->bind_param("iis", $id, $user_id, $action);
            $stmt->execute();
            $stmt->close();
        }
    }

   // --- Notifications for batch start/completion ---
// Fetch product_name securely
$stmt = $conn->prepare("SELECT product_name FROM batches WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$batch_info = $stmt->get_result()->fetch_assoc();
$stmt->close();

$product_name = $batch_info['product_name'] ?? '';

if ($status === 'in_progress' || $status === 'completed') {
    $notif_type = 'batch';
    $notif_message = $status === 'in_progress'
        ? "🛠️ $product_name - Batch Started"
        : "✔️ $product_name - Batch Completed";

    // Check if a notification exists for this batch
    $stmt = $conn->prepare("SELECT id FROM notifications WHERE batch_id = ? AND type = ? LIMIT 1");
    $stmt->bind_param("is", $id, $notif_type);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($existing) {
        // Update the notification message and timestamp
        $stmt = $conn->prepare("UPDATE notifications SET message = ?, created_at = NOW() WHERE id = ?");
        $stmt->bind_param("si", $notif_message, $existing['id']);
        $stmt->execute();
        $stmt->close();

        $notification_id = $existing['id'];
    } else {
        // Insert new notification if none exists
        $stmt = $conn->prepare("INSERT INTO notifications (batch_id, type, message, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iss", $id, $notif_type, $notif_message);
        $stmt->execute();
        $notification_id = $stmt->insert_id;
        $stmt->close();
    }

    // Reset user_notifications to unread for all users
    $stmt = $conn->prepare("
        INSERT INTO user_notifications (user_id, notification_id, is_read)
        SELECT id, ?, 0 FROM users
        ON DUPLICATE KEY UPDATE is_read = 0
    ");
    $stmt->bind_param("i", $notification_id);
    $stmt->execute();
    $stmt->close();
}




    $conn->commit();
    header("Location: production.php");
    exit();
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['batch_error'] = "❌ Error: " . $e->getMessage();
    header("Location: production.php");
    exit();
}
