<?php
// update_batch_status.php
include '../db.php'; // adjust path if needed

// Today
$today = new DateTime();

// Fetch all batches that have expiration dates
$stmt = $conn->prepare("
    SELECT 
        b.id AS batch_id,
        b.inventory_id,
        b.expiration_date,
        i.near_expiry_days
    FROM inventory_batches b
    JOIN inventory i ON b.inventory_id = i.id
    WHERE b.expiration_date IS NOT NULL
");
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {

    $batchId = $row['batch_id'];
    $expDate = new DateTime($row['expiration_date']);
    $nearDays = intval($row['near_expiry_days']);

    // Calculate difference in days
    $diff = intval($today->diff($expDate)->format('%r%a'));

    // Determine status
    if ($diff < 0) {
        $status = "Expired";
    } elseif ($diff <= $nearDays) {
        $status = "Near Expired";
    } else {
        $status = "Fresh";
    }

    // Update batch
    $update = $conn->prepare("
        UPDATE inventory_batches 
        SET status = ?, updated_at = NOW() 
        WHERE id = ?
    ");
    $update->bind_param("si", $status, $batchId);
    $update->execute();
    $update->close();
}

$stmt->close();

// echo "✅ Batch statuses updated.";
?>
