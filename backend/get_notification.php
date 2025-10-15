<?php
header('Content-Type: application/json');
session_start();
include '../db.php';

$user_id = $_SESSION['user_id'] ?? 0;
if (!$user_id) {
    echo json_encode(['notifications' => []]);
    exit;
}

// Fetch latest 20 notifications for this user
$stmt = $conn->prepare("
    SELECT 
        n.id AS notification_id,
        un.id AS user_notification_id,
        n.batch_id,
        n.type,
        n.message,
        n.created_at,
        un.is_read
    FROM user_notifications un
    JOIN notifications n ON un.notification_id = n.id
    WHERE un.user_id = ?
    ORDER BY n.created_at DESC
    LIMIT 20
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
while ($row = $result->fetch_assoc()) {
    $row['is_read'] = (int)$row['is_read']; // ensure consistency
    $notifications[] = $row;
}

echo json_encode(['notifications' => $notifications]);
