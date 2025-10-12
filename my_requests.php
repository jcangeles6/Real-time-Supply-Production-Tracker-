<?php
include 'backend/init.php';

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
  <title>🌸 BloomLux | My Requests</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg: #ffb3ecff;          /* soft pink background */
      --card: #f5f0fa;          /* lavender card */
      --primary: #2e1a2eff;     /* dark lavender text/button */
      --accent: #ff7dd8;        /* bright pink accent */
      --text: #000000ff;        /* standard text */
      --shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
    }

    body {
      font-family: 'Poppins', sans-serif;
      background: var(--bg);
      margin: 0;
      color: var(--text);
      display: flex;
    }

    /* Sidebar */
    .sidebar {
      width: 240px;
      background: var(--primary);
      color: #fff;
      height: 100vh;
      position: fixed;
      display: flex;
      flex-direction: column;
      padding: 30px 20px;
      box-shadow: var(--shadow);
    }

    .sidebar h2 {
      text-align: center;
      font-weight: 700;
      font-size: 24px;
      margin-bottom: 40px;
    }

    .sidebar a {
      display: block;
      color: #fff;
      padding: 14px 18px;
      margin: 8px 0;
      text-decoration: none;
      border-radius: 30px;
      transition: 0.3s;
    }

    .sidebar a:hover {
      background: var(--accent);
      transform: translateX(5px);
    }

    /* Main area */
    .main {
      margin-left: 260px;
      flex-grow: 1;
      padding: 35px 45px;
    }

    .top-bar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 25px;
    }

    .title {
      font-size: 1.6rem;
      font-weight: 700;
      color: var(--primary);
    }

    #live-time {
      color: var(--accent);
      font-weight: 600;
    }

    /* Card container */
    .card {
      background: var(--card);
      padding: 25px;
      border-radius: 20px;
      box-shadow: var(--shadow);
      animation: fadeIn 0.6s ease-in-out;
    }

    .card h3 {
      color: var(--primary);
      margin-bottom: 20px;
      font-weight: 600;
    }

    /* Table styling */
    table {
      width: 100%;
      border-collapse: collapse;
      background: white;
      border-radius: 16px;
      overflow: hidden;
      box-shadow: var(--shadow);
    }

    th, td {
      padding: 12px 15px;
      border-bottom: 1px solid #eee;
      text-align: center;
    }

    th {
      background: var(--accent);
      color: white;
      font-weight: 600;
    }

    tr:hover {
      background: #ffe9f6;
      transition: 0.2s;
    }

    /* Status badges */
    .status {
      font-weight: 600;
      padding: 6px 12px;
      border-radius: 10px;
      display: inline-block;
    }

    .pending { background: #fff3cd; color: #8a6d3b; }
    .done { background: #d4edda; color: #155724; }
    .cancelled { background: #f8d7da; color: #721c24; }

    /* Cancel button */
    .btn-cancel {
      background: var(--primary);
      color: white;
      padding: 6px 14px;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      transition: 0.3s;
    }

    .btn-cancel:hover {
      background: var(--accent);
      transform: scale(1.05);
      box-shadow: var(--shadow);
    }

    .empty {
      color: var(--primary);
      text-align: center;
      padding: 20px 0;
      font-weight: 500;
    }

    /* Animation */
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }

    /* Responsive */
    @media (max-width: 992px) {
      .main {
        margin-left: 0;
        padding: 20px;
      }
      .sidebar {
        display: none;
      }
    }
  </style>
</head>
<body>

<div class="sidebar">
    <h2>🌸 BloomLux Requests 🌸</h2>
    <a href="admin_dashboard.php">🌸 Back to Dashboard 🌸</a>
    <a href="my_requests.php">📋 My Requests</a>
    <a href="backend/add_stock.php">📦 Add Stock</a>
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
