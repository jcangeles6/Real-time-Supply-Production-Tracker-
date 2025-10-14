<?php
header('Content-Type: application/json');
session_start();
include '../db.php';

$user_id = $_SESSION['user_id'] ?? 0;
if (!$user_id) {
    echo json_encode(['success' => false]);
    exit;
}

// Get IDs from POST
$input = json_decode(file_get_contents('php://input'), true);
$ids = $input['notification_ids'] ?? [];

if (!empty($ids)) {
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids) + 1);

    $stmt = $conn->prepare("
        UPDATE user_notifications 
        SET is_read = 1, read_at = NOW() 
        WHERE user_id = ? AND notification_id IN ($placeholders)
    ");
    $stmt->bind_param($types, $user_id, ...$ids);
    $stmt->execute();
    $stmt->close();
}

echo json_encode(['success' => true]);
