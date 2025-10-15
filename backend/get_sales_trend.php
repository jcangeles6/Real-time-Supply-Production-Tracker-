<?php
date_default_timezone_set('Asia/Manila'); // Set timezone to Manila
include '../db.php';
header('Content-Type: application/json');

$labels = [];
$values = [];

// Use today in Manila as the end date
$endDate = date('Y-m-d'); 

// Last 30 days including today
for ($i = 29; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("$endDate -$i days"));
    $labels[] = date('M d', strtotime($date));

    $stmt = $conn->prepare("
        SELECT COUNT(*) as total_completed 
        FROM batches 
        WHERE completed_at IS NOT NULL
          AND status = 'completed'
          AND completed_at BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY)
    ");
    $stmt->bind_param("ss", $date, $date);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $values[] = (int)$row['total_completed'];
}

echo json_encode([
    'labels' => $labels,
    'values' => $values
]);
