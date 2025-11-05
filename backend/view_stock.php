<?php
// VIEW STOCK BUTTON PAGE IN ADD STOCK PAGE

session_start();
include '../db.php'; // Database connection

include 'update_batch_status.php'; // Update batch statuses

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Redirect if no ID
if (!isset($_GET['id'])) {
    header("Location: add_stock.php");
    exit();
}

$id = intval($_GET['id']);

// Fetch inventory item
$stmt = $conn->prepare("SELECT * FROM inventory WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$inventory = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$inventory) {
    echo "❌ Inventory item not found!";
    exit();
}

// Fetch batches for this inventory
$batch_stmt = $conn->prepare("
    SELECT * FROM inventory_batches
    WHERE inventory_id = ?
    ORDER BY created_at DESC
");
$batch_stmt->bind_param("i", $id);
$batch_stmt->execute();
$batches = $batch_stmt->get_result();
$batch_stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>🌸 View Stock - <?= htmlspecialchars($inventory['item_name']); ?></title>
    <link rel="stylesheet" href="../css/add_stock.css">
    <style>
        /* Color-coded status */
        .status-expired { color: red; font-weight: bold; }
        .status-near { color: orange; font-weight: bold; }
        .status-fresh { color: green; font-weight: bold; }
        .status-none { color: gray; font-weight: bold; }
    </style>
</head>
<body>
<div class="main">
    <a href="add_stock.php" class="back-btn">⬅ Back to Inventory</a>
    <h1>📦 View Stock for "<?= htmlspecialchars($inventory['item_name']); ?>"</h1>

    <?php if ($batches->num_rows === 0): ?>
        <p>No batches found for this item.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Batch ID</th>
                    <th>Quantity</th>
                    <th>Expiration Date</th>
                    <th>Status</th>
                    <th>Created At</th>
                    <th>Updated At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($batch = $batches->fetch_assoc()): ?>
                    <?php
                    // Freshness calculation
                    $today = new DateTime();
                    $today->setTime(0, 0, 0);
                    $near_expiry_days = isset($inventory['near_expiry_days']) ? (int)$inventory['near_expiry_days'] : 7;

                    if ($batch['expiration_date'] && $batch['expiration_date'] != '0000-00-00') {
                        $exp_date = new DateTime($batch['expiration_date']);
                        $exp_date->setTime(0, 0, 0);
                        $interval = (int)$today->diff($exp_date)->format('%r%a');

                        if ($interval < 0) $freshness = 'Expired';
                        elseif ($interval <= $near_expiry_days) $freshness = 'Near Expired';
                        else $freshness = 'Fresh';
                    } else {
                        $freshness = 'No Expiry';
                    }

                    // Assign class for color
                    $class = match($freshness) {
                        'Expired' => 'status-expired',
                        'Near Expired' => 'status-near',
                        'Fresh' => 'status-fresh',
                        default => 'status-none'
                    };
                    ?>
                    <tr>
                        <td><?= $batch['id'] ?></td>
                        <td><?= $batch['quantity'] ?></td>
                        <td><?= ($batch['expiration_date'] && $batch['expiration_date'] != '0000-00-00') ? $batch['expiration_date'] : '-' ?></td>
                        <td><span class="<?= $class ?>"><?= $freshness ?></span></td>
                        <td><?= date("M d, Y, h:i A", strtotime($batch['created_at'])) ?></td>
                        <td><?= date("M d, Y, h:i A", strtotime($batch['updated_at'])) ?></td>
                        <td>
                            <a href="edit_batch.php?id=<?= $batch['id'] ?>&inventory_id=<?= $id ?>">✏️ Edit</a> |
                            <a href="delete_batch.php?id=<?= $batch['id'] ?>&inventory_id=<?= $id ?>" onclick="return confirm('Are you sure?');">🗑️ Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
</body>
</html>
