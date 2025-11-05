<?php
header('Content-Type: application/json; charset=utf-8');
include '../init.php';

$batch_id = intval($_GET['batch_id'] ?? 0);
if (!$batch_id) {
    echo json_encode([]);
    exit;
}

// Fetch usage details with inventory batch info
$stmt = $conn->prepare("
    SELECT 
        i.item_name AS name,
        ib.id AS inventory_batch_id,
        ib.expiration_date,
        SUM(bmu.quantity_used) AS quantity_used
    FROM batch_material_usage bmu
    JOIN inventory_batches ib 
        ON bmu.inventory_batch_id = ib.id
    JOIN inventory i 
        ON ib.inventory_id = i.id
    WHERE bmu.batch_id = ?
    GROUP BY i.item_name, ib.id, ib.expiration_date
    ORDER BY i.item_name, ib.expiration_date ASC
");
$stmt->bind_param("i", $batch_id);
$stmt->execute();
$result = $stmt->get_result();

$breakdown = [];
$today = new DateTime();

while ($row = $result->fetch_assoc()) {
    if (empty($row['quantity_used']) || $row['quantity_used'] <= 0) {
        continue; // skip unused batches
    }

    // Determine freshness status and color
    $status = 'Non-perishable';
    $color = 'gray';

    if (!empty($row['expiration_date'])) {
        $exp_date = new DateTime($row['expiration_date']);
        $diff_days = (int)$today->diff($exp_date)->format("%r%a");

        if ($diff_days < 0) {
            $status = 'Expired';
            $color = 'red';
        } elseif ($diff_days <= 3) {
            $status = 'Near Expired';
            $color = 'orange';
        } else {
            $status = 'Fresh';
            $color = 'green';
        }
    }

    // Format quantity: show as integer if possible
    $quantity_used = (intval($row['quantity_used']) == $row['quantity_used'])
        ? intval($row['quantity_used'])
        : $row['quantity_used'];

    $breakdown[] = [
        'name' => $row['name'],
        'inventory_batch_id' => $row['inventory_batch_id'],
        'expiration_date' => $row['expiration_date'],
        'quantity_used' => $quantity_used,
        'status' => $status,
        'color' => $color
    ];
}

$stmt->close();
echo json_encode($breakdown);
exit;
