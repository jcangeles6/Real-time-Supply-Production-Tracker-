<?php
header('Content-Type: application/json');
session_start();
include '../db.php';

$user_id = $_SESSION['user_id'] ?? 0;
if (!$user_id) {
    echo json_encode(['notifications' => []]);
    exit;
}

// Fetch the latest 20 unique notifications for this user
$stmt = $conn->prepare("
    SELECT n.id, n.type, n.message, n.created_at, un.is_read
    FROM notifications n
    INNER JOIN user_notifications un 
        ON n.id = un.notification_id
    WHERE un.user_id = ?
    GROUP BY n.id  -- ✅ Ensure uniqueness by notification ID
    ORDER BY n.created_at DESC
    LIMIT 20
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
while ($row = $result->fetch_assoc()) {
    // Treat "New product" notifications as 'new-stock'
    if (strpos($row['message'], '📦 New product') !== false) {
        $row['type'] = 'new-stock';
    }
    $row['is_read'] = (int)$row['is_read'];
    $notifications[] = $row;
}

echo json_encode(['notifications' => $notifications]);
