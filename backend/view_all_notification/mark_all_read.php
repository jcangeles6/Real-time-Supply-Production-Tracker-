<?php

//PARA SA NOTIFICATION PAGE

session_start();
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING); // Ignore notices/warnings
ini_set('display_errors', 0); // Ensure no HTML error output
header('Content-Type: application/json');

include __DIR__ . '/../init.php';

$user_id = $_SESSION['user_id'] ?? 0;

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

try {
    $stmt = $conn->prepare("
        UPDATE user_notifications
        SET is_read = 1, read_at = NOW()
        WHERE user_id = ? AND is_read = 0
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    echo json_encode([
        'success' => true,
        'updated' => $stmt->affected_rows,
        'message' => $stmt->affected_rows > 0
            ? 'All notifications marked as read'
            : 'No unread notifications found'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error updating notifications',
        'error' => $e->getMessage()
    ]);
}
