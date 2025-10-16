<?php
include '../db.php'; // Database connection
header('Content-Type: application/json');

$result = $conn->query("SELECT item_name, quantity, unit, status FROM inventory ORDER BY item_name ASC");

$items = [];
while($row = $result->fetch_assoc()) {
    $items[] = $row;
}

echo json_encode($items);
