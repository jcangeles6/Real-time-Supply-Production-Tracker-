<?php

//AUTO REFRESH BODY FOR PRODUCTION TABLE


include '../init.php'; // adjust path if needed

$status_filter = $_GET['status_filter'] ?? 'all';
$where = ($status_filter != 'all') ? "AND status='$status_filter'" : '';

$batches = $conn->query("SELECT * FROM batches WHERE is_deleted = 0 $where ORDER BY scheduled_at DESC");
if (!$batches) die("SQL Error: " . $conn->error);

while ($row = $batches->fetch_assoc()):
    $batch_id = $row['id'];

    $materials_res = $conn->query("
        SELECT i.id AS stock_id, i.item_name, i.quantity AS current_stock,
               bm.quantity_used, bm.quantity_reserved
        FROM batch_materials bm
        JOIN inventory i ON bm.stock_id = i.id
        WHERE bm.batch_id = $batch_id
    ");

    $materials = [];
    $startDisabled = false;

    while ($mat = $materials_res->fetch_assoc()) {
        $needed_total = $mat['quantity_used'];
        $after = $mat['current_stock'] - max($needed_total - $mat['quantity_reserved'], 0);

        if ($after < 0) $startDisabled = true;

        $materials[] = [
            'stock_id' => $mat['stock_id'],
            'name' => $mat['item_name'],
            'current' => $mat['current_stock'],
            'needed' => $needed_total,
            'reserved' => $mat['quantity_reserved'],
            'after' => $after
        ];
    }
?>
<tr>
    <td><?= $row['id'] ?></td>
    <td>
        <?= htmlspecialchars($row['product_name']) ?>
        <div class="quantity-info">Quantity: <b><?= $row['quantity'] ?></b></div>
    </td>
    <td>
        <?php
        if (!empty($materials)) {
            foreach ($materials as $m) {
                echo "{$m['name']}: <b style='color:blue'>{$m['current']}</b>";
                if ($row['status'] === 'scheduled') {
                    echo " | Needed: <b>{$m['needed']}</b>";
                }
                echo "<br>";
            }
        } else {
            echo $row['status'] === 'completed'
                ? "<span style='color:gray;'>None</span>"
                : "<span style='color:red;'>No materials linked</span>";
        }
        ?>
    </td>
    <td>
        <?php
        if (!empty($materials)) {
            foreach ($materials as $m) {
                if ($row['status'] === 'scheduled') {
                    echo "<span style='font-family:Poppins,sans-serif;'>{$m['name']}: Reserved (<b style='color:orange'>{$m['needed']}</b>)</span><br>";
                } elseif ($row['status'] === 'in_progress') {
                    echo "<span style='font-family:Poppins,sans-serif;'>{$m['name']}: Used (<b style='color:green'>{$m['needed']}</b>)</span><br>";
                } else {
                    echo "<span style='font-family:Poppins,sans-serif;'>—</span>";
                }
            }
        } else {
            echo "<span style='font-family:Poppins,sans-serif;'>—</span>";
        }
        ?>
    </td>
    <td class="status-<?= $row['status'] ?>"><?= ucfirst($row['status']) ?></td>
    <td><?= date("M d, Y, h:i A", strtotime($row['scheduled_at'])) ?></td>
    <td><?= $row['completed_at'] ? date("M d, Y, h:i A", strtotime($row['completed_at'])) : '—' ?></td>
    <td class="actions">
        <?php if ($row['status'] === 'scheduled'): ?>
            <?php if ($startDisabled): ?>
                <a href="#" onclick="showStockAlert()" class="btn">▶ Start</a>
            <?php else: ?>
                <a href="update_batch.php?id=<?= $row['id'] ?>&status=in_progress" class="btn">▶ Start</a>
            <?php endif; ?>
        <?php elseif ($row['status'] === 'in_progress'): ?>
            <a href="update_batch.php?id=<?= $row['id'] ?>&status=completed" class="btn" style="background:#6aa84f;">✔ Complete</a>
        <?php else: ?>
            <span style="color:gray;">✔ Done</span>
        <?php endif; ?>
        <?php if ($row['status'] !== 'completed'): ?>
            <a href="#" onclick="showDeleteModal(<?= $row['id'] ?>)" class="btn" style="background:#b22222;">🗑</a>
            <a href="add_batch.php?batch_id=<?= $row['id'] ?>" class="btn" style="background:#228b22;">📄</a>
        <?php endif; ?>
    </td>
</tr>
<?php endwhile; ?>
