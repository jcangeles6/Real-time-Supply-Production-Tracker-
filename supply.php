<?php
include 'backend/init.php';

// Get username
$user_id = $_SESSION['user_id'];
$result = $conn->query("SELECT username FROM users WHERE id = $user_id");
$user = $result->fetch_assoc();
$username = $user['username'];

// Fetch ingredient requests
$result_requests = $conn->query("SELECT * FROM requests ORDER BY requested_at DESC");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>🌸 BloomLux Supply 🌸</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/supply.css">
</head>

<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <h2>🌸 BloomLux Supply 🌸</h2>
        <a href="home.php">🌸 Back to Dashboard 🌸</a>
        <a href="supply.php">📦 Supply</a>
        <a href="production.php">🧁 Production</a>
        <a href="inventory.php">📊 Inventory</a>
        <a href="logout.php">🚪 Logout</a>
    </div>


    <!-- Main -->
    <div class="main">
        <div class="top-bar">
            <div class="welcome">📦 Supply Management</div>
            <div class="top-right">
                <div id="live-time">⏰ Loading...</div>
                <div class="search-bar">
                    <input type="text" placeholder="Search items...">
                </div>
                <div class="notif" id="notif-icon">
                    🔔
                    <span id="notif-badge" style="background:red;color:white;font-size:0.75rem;border-radius:50%;padding:2px 6px;position:absolute;top:-5px;right:-5px;display:none;">0</span>
                </div>
                <div id="notif-dropdown">
                    <ul id="notif-feed"></ul>
                </div>
            </div>
        </div>

        <div class="card">
            <h3>🥖 Available Ingredients</h3>
            <table>
                <tr>
                    <th>Ingredient</th>
                    <th>Price</th>
                    <th>Supplier</th>
                    <th>Action</th>
                </tr>
                <tr>
                    <td>Design</td>
                    <td>₱1000 / 100 PCS</td>
                    <td>ABC Mills</td>
                    <td><a href="request_form.php?ingredient=Flour" class="btn">Request</a></td>
                </tr>
                <tr>
                    <td>Paper</td>
                    <td>₱200 / 250 PCS</td>
                    <td>Sweet Co.</td>
                    <td><a href="request_form.php?ingredient=Sugar" class="btn">Request</a></td>
                </tr>
                <tr>
                    <td>Ribbon</td>
                    <td>₱1500 / 100 PCS</td>
                    <td>Dairy Best</td>
                    <td><a href="request_form.php?ingredient=Butter" class="btn">Request</a></td>
                </tr>
                <tr>
                    <td>Rose</td>
                    <td>₱1000 / 160 PCS</td>
                    <td>BakePro</td>
                    <td><a href="request_form.php?ingredient=Yeast" class="btn">Request</a></td>
                </tr>
                <tr>
                    <td>Base</td>
                    <td>₱1300 / 50 PCS</td>
                    <td>ABC Mills</td>
                    <td><a href="request_form.php?ingredient=Flour" class="btn">Request</a></td>
                </tr>
            </table>
        </div>

        <div class="card">
            <h3>📋 Ingredient Requests</h3>
            <table>
                <tr>
                    <th>ID</th>
                    <th>Ingredient</th>
                    <th>Quantity</th>
                    <th>Unit</th>
                    <th>Status</th>
                    <th>Requested At</th>
                </tr>
                <?php if ($result_requests->num_rows > 0): ?>
                    <?php while ($row = $result_requests->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['id']; ?></td>
                            <td><?= htmlspecialchars($row['ingredient_name']); ?></td>
                            <td><?= htmlspecialchars($row['quantity']); ?></td>
                            <td><?= htmlspecialchars($row['unit']); ?></td>
                            <td>
                                <span class="badge <?= strtolower($row['status']); ?>">
                                    <?= ucfirst($row['status']); ?>
                                </span>
                            </td>
                            <td><?= $row['requested_at']; ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="color:#8b4513;">No ingredient requests yet.</td>
                    </tr>
                <?php endif; ?>
            </table>

            <div class="pagination">
                <a href="#" class="active">1</a>
                <a href="#">2</a>
                <a href="#">3</a>
            </div>
        </div>
    </div>
    <script src="js/time.js"></script>
    <script src="js/notification.js"></script>
</body>

</html>