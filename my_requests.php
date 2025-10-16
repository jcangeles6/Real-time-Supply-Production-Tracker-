<?php
include 'backend/init.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

$user_id = $_SESSION['user_id'];
echo "Logged in user_id: " . $user_id;

// Handle cancel request (mark as denied)
if (isset($_POST['cancel_id'])) {
  $cancel_id = intval($_POST['cancel_id']);
  $stmt = $conn->prepare("UPDATE requests SET status = 'denied' WHERE id = ? AND status = 'pending'");
  $stmt->bind_param("i", $cancel_id);
  $stmt->execute();
  $stmt->close();
  header("Location: my_requests.php");
  exit();
}

// Handle approve request
if (isset($_POST['approve_id'])) {
  $approve_id = intval($_POST['approve_id']);
  $stmt = $conn->prepare("UPDATE requests SET status = 'approved' WHERE id = ? AND status = 'pending'");
  $stmt->bind_param("i", $approve_id);
  $stmt->execute();
  $stmt->close();
  header("Location: my_requests.php");
  exit();
}

// Fetch current stock + thresholds
$stockResult = $conn->query("
    SELECT i.id, i.item_name, i.quantity, st.threshold
    FROM inventory i
    LEFT JOIN stock_thresholds st ON i.id = st.item_id
") or die($conn->error);

// Admin: fetch all requests
$sql = "SELECT id, user_id, ingredient_name, quantity, notes, unit, status, requested_at 
        FROM requests 
        ORDER BY requested_at DESC";
$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

// Prepare summary counts
$total_requests = $pending = $approved = $denied = 0;
if ($result->num_rows > 0) {
  foreach ($result as $r) {
    $total_requests++;
    switch (strtolower($r['status'])) {
      case 'pending':
        $pending++;
        break;
      case 'approved':
        $approved++;
        break;
      case 'denied':
        $denied++;
        break;
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>🌸 BloomLux | All Requests</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/my_request.css">
  <link rel="stylesheet" href="css/my_request_form.css">
</head>

<body>
  <audio id="lowStockSound" src="alert.mp3" preload="auto"></audio>

  <div class="sidebar">
    <h2>🌸 BloomLux Requests 🌸</h2>
    <a href="admin_dashboard.php">🌸 Back to Dashboard 🌸</a>
    <a href="my_requests.php">📋 All Requests</a>
    <a href="backend/add_stock.php">📦 Add Stock</a>
    <a href="logout.php">🚪 Logout</a>
  </div>

  <div class="main">
    <div class="top-bar">
      <div class="title">📋 Materials Requests</div>
      <div id="live-time">⏰ Loading...</div>
    </div>

    <div class="card">
      <!-- Summary -->
      <div class="summary">
        Total Requests: <?= $total_requests ?> | Pending: <?= $pending ?> | Approved: <?= $approved ?> | Denied: <?= $denied ?>
      </div>
      <!-- Toggle approved -->
      <div class="toggle-approved" onclick="toggleApproved()">⬆️ Toggle Approved Requests</div>

      <?php if ($result->num_rows > 0): ?>
        <table id="requestsTable">
          <tr>
            <th>User ID</th>
            <th>Ingredient</th>
            <th>Quantity</th>
            <th>Unit</th>
            <th>Notes</th>
            <th>Status</th>
            <th>Date Requested</th>
            <th>Action</th>
          </tr>
          <?php foreach ($result as $row):
            $status = strtolower($row['status']);

            // Handle cancel request (mark as denied)
            if (isset($_POST['cancel_id'])) {
              $cancel_id = intval($_POST['cancel_id']);
              $stmt = $conn->prepare("UPDATE requests SET status = 'denied' WHERE id = ? AND status = 'pending'");
              $stmt->bind_param("i", $cancel_id);
              $stmt->execute();
              $stmt->close();
              header("Location: my_requests.php");
              exit();
            }


            $status_class = '';
            $status_label = '';

            if ($status == 'pending') {
              $status_label = 'Pending';
              $status_class = 'pending';
            } elseif ($status == 'approved') {
              $status_label = 'Approved';
              $status_class = 'approved';
            } elseif ($status == 'cancelled') {
              $status_label = 'Cancelled';
              $status_class = 'cancelled';
            } elseif ($status == 'denied') {
              $status_label = 'Denied';
              $status_class = 'denied';
            } else {
              $status_label = ucfirst($status);
              $status_class = $status;
            }
          ?>
            <tr class="<?= $status == 'approved' ? 'approved-row' : '' ?>">
              <td><?= htmlspecialchars($row['user_id']); ?></td>
              <td><?= htmlspecialchars($row['ingredient_name']); ?></td>
              <td><?= htmlspecialchars($row['quantity']); ?></td>
              <td><?= htmlspecialchars($row['unit']); ?></td>
              <td><?= htmlspecialchars($row['notes']); ?></td>
              <td><span class="status <?= $status_class; ?>"><?= $status_label; ?></span></td>
              <td><?= htmlspecialchars($row['requested_at']); ?></td>
              <td>
                <?php if ($status == 'pending'): ?>
                  <button class="btn-cancel" data-id="<?= $row['id']; ?>">Cancel</button>
                  <button class="btn-approve" data-id="<?= $row['id']; ?>">Approve</button>
                <?php else: ?>
                  -
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>

        </table>
      <?php else: ?>
        <p class="empty">No requests have been made yet.</p>
      <?php endif; ?>
    </div>
  </div>

  <script src="js/my-request-form.js"></script>

  <!-- Low Stock Modal -->
  <div id="lowStockModal" class="modal">
    <div class="modal-content">
      <h3>⚠️ Low Stock Alert 🌸</h3>
      <ul id="lowStockList"></ul>
      <button class="close-btn flash-btn" onclick="closeLowStockModal()">OK</button>
    </div>
  </div>


</body>

</html>