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
  <style>
    :root {
      --bg: #ffb3ecff;          /* soft pink background */
      --card: #f5f0fa;          /* lavender card */
      --primary: #2e1a2eff;     /* deep lavender */
      --accent: #2e1a2eff;        /* pink highlight */
      --white: #ffffff;
      --shadow: 0 4px 12px rgba(0,0,0,0.1);
    }

    body {
      font-family: 'Poppins', sans-serif;
      background: var(--bg);
      color: var(--primary);
      margin: 0;
      display: flex;
    }

    /* Sidebar */
    .sidebar {
      width: 240px;
      background: linear-gradient(180deg, var(--primary), #7b4b9b);
      color: var(--white);
      height: 100vh;
      position: fixed;
      padding: 30px 20px;
      display: flex;
      flex-direction: column;
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
      color: var(--white);
      padding: 12px 18px;
      margin: 6px 0;
      text-decoration: none;
      border-radius: 25px;
      transition: 0.3s;
      text-align: left;
      font-weight: 500;
    }

    .sidebar a:hover {
      background: var(--accent);
      transform: translateX(5px);
    }

    .logout {
      margin-top: auto;
    }

    .logout a {
      display: block;
      background: var(--accent);
      text-align: center;
      padding: 10px 18px;
      border-radius: 25px;
      text-decoration: none;
      color: white;
      font-weight: 500;
      transition: 0.3s;
    }

    .logout a:hover {
      background: var(--primary);
      transform: scale(1.05);
    }

    /* Main content */
    .main {
      flex: 1;
      margin-left: 260px;
      padding: 40px;
      animation: fadeIn 0.6s ease-in-out;
    }

    h1 {
      color: var(--primary);
      text-align: center;
      font-weight: 700;
      margin-bottom: 8px;
    }

    p {
      text-align: center;
      color: var(--accent);
      margin-bottom: 30px;
      font-weight: 500;
    }

    /* Card container */
    .card {
      background: var(--card);
      border-radius: 20px;
      box-shadow: var(--shadow);
      padding: 25px;
      margin-bottom: 25px;
      transition: transform 0.3s ease;
    }

    .card:hover {
      transform: translateY(-4px);
    }

    /* Table */
    table {
      width: 100%;
      border-collapse: collapse;
      background: var(--white);
      border-radius: 16px;
      overflow: hidden;
      box-shadow: var(--shadow);
    }

    th, td {
      padding: 12px 14px;
      text-align: center;
      border-bottom: 1px solid #eee;
    }

    th {
      background: var(--accent);
      color: var(--white);
      font-weight: 600;
    }

    tr:hover {
      background: #ffe9f6;
      transition: 0.2s;
    }

    /* Buttons */
    input[type="submit"] {
      background: var(--accent);
      color: white;
      border: none;
      padding: 8px 16px;
      border-radius: 25px;
      cursor: pointer;
      font-weight: 600;
      transition: all 0.3s ease;
    }

    input[type="submit"]:hover {
      background: var(--primary);
      transform: scale(1.05);
      box-shadow: var(--shadow);
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
