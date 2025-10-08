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

// Fetch requests
$sql = "SELECT id, ingredient_name, quantity, status, requested_at FROM requests ORDER BY requested_at DESC";
$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>🍞 SweetCrumb | My Requests</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --brown: #8b4513;
            --light-brown: #c3814a;
            --cream: #fdf6f0;
            --white: #ffffff;
            --shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--cream);
            margin: 0;
            color: #333;
            display: flex;
        }

        /* Sidebar */
        .sidebar {
            width: 240px;
            background: linear-gradient(180deg, var(--brown), #a0522d);
            color: var(--white);
            height: 100vh;
            position: fixed;
            display: flex;
            flex-direction: column;
            padding: 25px 20px;
            box-shadow: var(--shadow);
        }

        .sidebar h2 {
            text-align: center;
            font-weight: 600;
            font-size: 22px;
            margin-bottom: 40px;
        }

        .sidebar a {
            display: block;
            color: var(--white);
            padding: 12px 18px;
            margin: 8px 0;
            text-decoration: none;
            border-radius: 10px;
            transition: 0.3s;
        }

        .sidebar a:hover {
            background: var(--light-brown);
            transform: translateX(4px);
        }

        /* Main area */
        .main {
            margin-left: 260px;
            flex-grow: 1;
            padding: 30px 40px;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--brown);
        }

        #live-time {
            color: #6d3f1a;
            font-weight: 500;
        }

        /* Card container */
        .card {
            background: var(--white);
            padding: 25px;
            border-radius: 16px;
            box-shadow: var(--shadow);
        }

        .card h3 {
            color: var(--brown);
            margin-bottom: 20px;
            font-weight: 600;
        }

        /* Table styling */
        table {
            width: 100%;
            border-collapse: collapse;
            background: var(--white);
            border-radius: 12px;
            overflow: hidden;
        }

        th, td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            text-align: center;
        }

        th {
            background: var(--brown);
            color: white;
            font-weight: 600;
        }

        tr:hover {
            background: #fff5e9;
        }

        /* Status badges */
        .status {
            font-weight: 600;
            padding: 6px 10px;
            border-radius: 8px;
        }

        .pending { background: #ffeb99; color: #665c00; }
        .done { background: #c6f6c6; color: #006600; }
        .cancelled { background: #f8d7da; color: #721c24; }

        /* Cancel button */
        .btn-cancel {
            background: #b22222;
            color: white;
            padding: 6px 14px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: 0.3s;
        }

        .btn-cancel:hover {
            background: #800000;
        }

        .empty {
            color: var(--brown);
            text-align: center;
            padding: 20px 0;
        }
    </style>
</head>
<body>

<div class="sidebar">
    <h2>🍞 SweetCrumb</h2>
    <a href="home.php">🏠 Dashboard</a>
    <a href="supply.php">📦 Supply</a>
    <a href="production.php">🧁 Production</a>
    <a href="my_requests.php" style="background: var(--light-brown);">📋 My Requests</a>
    <a href="inventory.php">📊 Inventory</a>
    <a href="logout.php">🚪 Logout</a>
</div>

<div class="main">
    <div class="top-bar">
        <div class="title">📋 My Ingredient Requests</div>
        <div id="live-time">⏰ Loading...</div>
    </div>

    <div class="card">
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
                        <td><span class="status <?= $status_class; ?>"><?= $status_label; ?></span></td>
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
            <p class="empty">You have not made any requests yet.</p>
        <?php endif; ?>
    </div>
</div>

<script>
function updateTime() {
    const now = new Date();
    const options = { hour: '2-digit', minute: '2-digit', second: '2-digit',
                      weekday: 'short', month: 'short', day: 'numeric' };
    document.getElementById("live-time").innerHTML = "⏰ " + now.toLocaleString('en-US', options);
}
setInterval(updateTime, 1000);
updateTime();

function confirmCancel() {
    return confirm("⚠️ Are you sure you want to cancel this request?");
}
</script>

</body>
</html>
