<?php
session_start();
include 'db.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle cancel request
if (isset($_POST['cancel_id'])) {
    $cancel_id = intval($_POST['cancel_id']);
    $stmt = $conn->prepare("UPDATE requests SET status = 'cancelled' WHERE id = ? AND status = 'pending'");
    $stmt->bind_param("i", $cancel_id);
    $stmt->execute();
    $stmt->close();
    header("Location: my_requests.php"); 
    exit();
}

// Fetch requests with ingredient name
$sql = "SELECT r.id, r.ingredient_name, r.quantity, r.status, r.requested_at
        FROM requests r
        ORDER BY r.requested_at DESC";

$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Requests - Bakery</title>
    <style>
        body { font-family: Arial, sans-serif; background: #fdf6f0; margin: 0; padding: 0; }
        .sidebar { width: 220px; background: #8b4513; color: #fff; height: 100vh; position: fixed; top: 0; left: 0; padding: 20px 0; }
        .sidebar h2 { text-align: center; margin-bottom: 30px; }
        .sidebar a { display: block; color: #fff; padding: 12px 20px; text-decoration: none; font-weight: bold; }
        .sidebar a:hover { background: #a0522d; }
        .main { margin-left: 240px; padding: 20px; }
        h1 { color: #8b4513; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; background: #fff8f0; border-radius: 10px; overflow: hidden; box-shadow: 0px 2px 5px rgba(0,0,0,0.1); }
        th, td { padding: 12px 15px; border-bottom: 1px solid #ddd; text-align: center; }
        th { background: #8b4513; color: white; }
        tr:hover { background: #f1e3d3; }
        .status { font-weight: bold; padding: 5px 10px; border-radius: 5px; }
        .pending { background: #ffeb99; color: #665c00; }
        .done { background: #c6f6c6; color: #006600; }
        .cancelled { background: #f8d7da; color: #721c24; }
        .btn-cancel { background: #b22222; color: white; padding: 6px 12px; border: none; border-radius: 5px; cursor: pointer; }
        .btn-cancel:hover { background: #800000; }
    </style>
</head>
<body>

<div class="sidebar">
        <h2>🍞 Bakery</h2>
        <a href="home.php" style="background:#5a2d0c; border-radius:6px; margin:0 10px 15px; text-align:center;">
            ⬅ Back to Dashboard</a>
        <a href="supply.php">Supply</a>
        <a href="production.php">Production</a>
        <a href="my_requests.php">My Requests</a>
        <a href="inventory.php">Inventory</a>
        <a href="logout.php">Logout</a>
    </div>

<div class="main">
    <h1>📋 My Ingredient Requests</h1>

    <?php if ($result->num_rows > 0): ?>
        <table>
            <tr>
                <th>Ingredient</th>
                <th>Quantity</th>
                <th>Status</th>
                <th>Date Requested</th>
                <th>Action</th>
            </tr>
            <?php while ($row = $result->fetch_assoc()): ?>
                <?php
                    $status = strtolower($row['status']);
                    $status_label = '';
                    $status_class = '';

                    if ($status == 'pending') {
                        $status_label = 'Pending';
                        $status_class = 'pending';
                    } elseif ($status == 'cancelled') {
                        $status_label = 'Cancelled';
                        $status_class = 'cancelled';
                    } elseif ($status == 'approved' || $status == 'completed') {
                        $status_label = 'Done';
                        $status_class = 'done';
                    } else {
                        $status_label = ucfirst($status);
                        $status_class = 'done';
                    }
                ?>
                <tr>
                    <td><?= htmlspecialchars($row['ingredient_name']); ?></td>
                    <td><?= htmlspecialchars($row['quantity']); ?></td>
                    <td>
                        <span class="status <?= $status_class; ?>">
                            <?= $status_label; ?>
                        </span>
                    </td>
                    <td><?= htmlspecialchars($row['requested_at']); ?></td>
                    <td>
                        <?php if ($status == 'pending'): ?>
                            <form method="POST" style="margin:0;" onsubmit="return confirmCancel();">
                                <input type="hidden" name="cancel_id" value="<?= $row['id']; ?>">
                                <button type="submit" class="btn-cancel">Cancel</button>
                            </form>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p style="color:#8b4513;">You have not made any requests yet.</p>
    <?php endif; ?>
</div>

<script>
function confirmCancel() {
    return confirm("⚠️ Are you sure you want to cancel this request?");
}
</script>

</body>
</html>
