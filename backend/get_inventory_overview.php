<?php
include '../db.php';
header('Content-Type: application/json');

$labels = [];
$values = [];

// Top 10 products based on completed batches
$stmt = $conn->prepare("
    SELECT product_name, COUNT(*) AS total_sold
    FROM batches
    WHERE status = 'completed'
    GROUP BY product_name
    ORDER BY total_sold DESC
    LIMIT 10
");
$stmt->execute();
$result = $stmt->get_result();

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $labels[] = $row['product_name'];
        $values[] = (int)$row['total_sold'];
    }
}

// Generate colors
$colors = [];
foreach ($labels as $label) {
    $colors[] = '#' . substr(md5($label), 0, 6);
}

echo json_encode([
    'labels' => $labels,
    'values' => $values,
    'colors' => $colors
]);
