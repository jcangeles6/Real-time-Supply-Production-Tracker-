<?php
include '../db.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['action'], $input['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$id = intval($input['id']);
$action = $input['action'];

if ($action === 'approve') {
    $stmt = $conn->prepare("UPDATE requests SET status = 'approved' WHERE id = ? AND status = 'pending'");
} elseif ($action === 'cancel') {
    $stmt = $conn->prepare("UPDATE requests SET status = 'denied' WHERE id = ? AND status = 'pending'");
} else {
    echo json_encode(['success' => false, 'message' => 'Unknown action']);
    exit;
}

$stmt->bind_param("i", $id);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Request not found or already processed']);
}

$stmt->close();
$conn->close();
