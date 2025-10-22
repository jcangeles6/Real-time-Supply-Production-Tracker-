<?php

//PARA SA NOTIFICATION PAGE

header('Content-Type: application/json');
include __DIR__ . '/../init.php';

$user_id = $_SESSION['user_id'] ?? 0;
$notif_id = $_POST['user_notification_id'] ?? 0;

if ($user_id && $notif_id) {
    $now = date('Y-m-d H:i:s');
    $stmt = $conn->prepare("UPDATE user_notifications SET is_read = 1, read_at = ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param("sii", $now, $notif_id, $user_id);
    $stmt->execute();
    echo json_encode(['success' => true, 'read_at' => $now]);
} else {
    echo json_encode(['success' => false]);
}
?>
