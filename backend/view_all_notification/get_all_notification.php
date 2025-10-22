<?php
header('Content-Type: application/json');

include __DIR__ . '/../init.php';

$user_id = $_SESSION['user_id'] ?? 0;
if (!$user_id) {
    echo json_encode(['success' => false, 'notifications' => []]);
    exit;
}

// Fetch all notifications for this user (excluding deleted)
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
    WHERE un.user_id = ? AND n.type != 'deleted'
    ORDER BY n.created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
while ($row = $result->fetch_assoc()) {
    $row['is_read'] = (int)$row['is_read'];
    $notifications[] = $row;
}

echo json_encode(['success' => true, 'notifications' => $notifications]);
?>
