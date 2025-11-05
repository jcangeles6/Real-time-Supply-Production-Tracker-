<?php
header('Content-Type: application/json');
include __DIR__ . '/../init.php';

// Number of days before expiration to warn
$daysBeforeExpire = 3;

// ✅ Updated query — removed `b.is_deleted`
$query = $conn->prepare("
    SELECT 
        i.item_name,
        b.id AS batch_id,
        b.expiration_date,
        b.quantity
    FROM inventory_batches b
    JOIN inventory i ON b.inventory_id = i.id
    WHERE b.expiration_date IS NOT NULL
      AND b.expiration_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
");
$query->bind_param('i', $daysBeforeExpire);
$query->execute();
$result = $query->get_result();

$count = 0;
while ($row = $result->fetch_assoc()) {
    $item = $row['item_name'];
    $batchId = $row['batch_id'];
    $expDate = $row['expiration_date'];
    $quantity = $row['quantity'];

    $type = (strtotime($expDate) < time()) ? 'expired' : 'expiring';
    $message = ($type === 'expired')
        ? "💀 {$item} (Batch #{$batchId}) has expired!"
        : "⏳ {$item} (Batch #{$batchId}) will expire on {$expDate}!";

    // Avoid duplicates
    $exists = $conn->prepare("SELECT id FROM notifications WHERE batch_id = ? AND type = ?");
    $exists->bind_param('is', $batchId, $type);
    $exists->execute();
    $exists->store_result();

    if ($exists->num_rows === 0) {
        $insertNotif = $conn->prepare("
            INSERT INTO notifications (batch_id, type, message, created_at)
            VALUES (?, ?, ?, NOW())
        ");
        $insertNotif->bind_param('iss', $batchId, $type, $message);
        $insertNotif->execute();
        $insertNotif->close();

        // Assign to all users (if user_notifications table exists)
        $notifId = $conn->insert_id;
        $conn->query("
            INSERT INTO user_notifications (user_id, notification_id, is_read)
            SELECT id, {$notifId}, 0 FROM users
        ");

        $count++;
    }

    $exists->close();
}

echo json_encode([
    'success' => true,
    'message' => "Checked items; {$count} new notifications added."
]);
