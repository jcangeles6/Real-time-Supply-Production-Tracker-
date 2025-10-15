<?php
header('Content-Type: application/json');
session_start();
include '../db.php';

// Get notification IDs from POST
$input = json_decode(file_get_contents('php://input'), true);
$ids = $input['notification_ids'] ?? [];

if (!empty($ids)) {
    // Prepare placeholders for dynamic binding
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids)); // only the IDs

    $stmt = $conn->prepare("
        UPDATE user_notifications
        SET is_read = 1, read_at = NOW()
        WHERE id IN ($placeholders)
    ");

    // Bind parameters dynamically
    $stmt->bind_param($types, ...$ids);
    $stmt->execute();
    $stmt->close();
}

echo json_encode(['success' => true]);
