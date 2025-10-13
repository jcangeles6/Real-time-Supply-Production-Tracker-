<?php
include 'backend/init.php';

// Redirect if not logged in or not admin
if (!isset($_SESSION['user_id']) || $_SESSION['is_admin'] != 1) {
  header("Location: login.php");
  exit();
}

// Fetch all users for the admin table
$result = $conn->query("SELECT id, username, email, reset_requested FROM users");
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>🌸 BloomLux | Admin Dashboard</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/admin_dashboard.css">
</head>

<body>

  <!-- Sidebar -->
  <div class="sidebar">
    <h2>🌸 Admin Dashboard 🌸</h2>
    <a href="my_requests.php">📋 My Requests</a>
    <a href="backend/add_stock.php">📦 Add Stock</a>
    <a href="logout.php">🚪 Logout</a>
  </div>
  </div>

  <!-- Main Content -->
  <div class="main">
    <h1>👨‍🍳 Welcome, Admin <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
    <p>Manage users and password reset requests here.</p>

    <div class="card">
      <table>
        <tr>
          <th>ID</th>
          <th>Username</th>
          <th>Email</th>
          <th>Reset Requested</th>
          <th>Action</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
          <tr>
            <td><?php echo $row['id']; ?></td>
            <td><?php echo htmlspecialchars($row['username']); ?></td>
            <td><?php echo htmlspecialchars($row['email']); ?></td>
            <td><?php echo $row['reset_requested'] ? '<span style="color:red;font-weight:bold;">Yes</span>' : 'No'; ?></td>
            <td>
              <form action="admin_reset_user.php" method="POST" style="margin:0;">
                <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                <input type="submit" name="reset" value="Reset Password">
              </form>
            </td>
          </tr>
        <?php endwhile; ?>
      </table>
    </div>
  </div>

</body>

</html>