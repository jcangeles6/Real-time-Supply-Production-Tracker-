<?php

//PARA SA UNREAD BADGE NG NOTIFICATION ICON


header('Content-Type: application/json');
include __DIR__ . '/../init.php';

$user_id = $_SESSION['user_id'] ?? 0;
if (!$user_id) {
    echo json_encode(['success' => false, 'unread_count' => 0]);
    exit;
}

$stmt = $conn->prepare("SELECT COUNT(*) as unread_count FROM user_notifications WHERE user_id = ? AND is_read = 0");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

echo json_encode([
    'success' => true,
    'unread_count' => (int)$result['unread_count']
]);
