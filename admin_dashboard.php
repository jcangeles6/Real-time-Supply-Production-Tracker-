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
    <title>Admin Dashboard - Bakery</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #fdf9f5;
            color: #5a2d0c;
            margin: 0;
            display: flex;
        }

        /* Sidebar */
        .sidebar {
            width: 220px;
            background: linear-gradient(180deg, #8b4513, #a0522d);
            color: #fff;
            height: 100vh;
            padding: 25px 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }

        .sidebar h2 {
            margin-bottom: 40px;
            font-size: 22px;
            font-weight: bold;
            letter-spacing: 1px;
        }

        .sidebar a {
            color: white;
            text-decoration: none;
            padding: 12px 20px;
            width: 80%;
            text-align: center;
            border-radius: 8px;
            margin-bottom: 15px;
            font-weight: 600;
            background-color: transparent;
            transition: background-color 0.3s;
        }

        .sidebar a:hover {
            background-color: #c67d45;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            padding: 30px;
        }

        .main-content h2 {
            text-align: center;
            color: #8b4513;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .main-content p {
            text-align: center;
            color: #7b4b22;
            margin-bottom: 30px;
        }

        /* Table */
        table {
            width: 100%;
            border-collapse: collapse;
            background-color: #fffaf0;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            border-radius: 12px;
            overflow: hidden;
        }

        th, td {
            padding: 12px 15px;
            text-align: center;
            border-bottom: 1px solid #e0c9a6;
        }

        th {
            background-color: #8b4513;
            color: white;
        }

        tr:hover {
            background-color: #f3e9e2;
        }

        td {
            color: #5a2d0c;
        }

        /* Buttons */
        input[type="submit"] {
            background-color: #a0522d;
            color: white;
            border: none;
            padding: 8px 14px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
        }

        input[type="submit"]:hover {
            background-color: #8b4513;
        }

        /* Logout Button */
        .logout {
            margin-top: auto;
        }

        .logout a {
            background: #8b0000;
            color: #fff;
            padding: 10px 25px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            display: block;
            text-align: center;
            width: 80%;
        }

        .logout a:hover {
            background: #a52a2a;
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <h2>🍞 Bakery Admin</h2>
    <a href="admin_dashboard.php">Dashboard</a>
    <a href="backend/add_stock.php">Add Stock</a>
    <a href="logout.php">Logout</a>
</div>

<!-- Main Content -->
<div class="main-content">
    <h2>Welcome, Admin <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
    <p>Manage users and password reset requests below:</p>

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
                <td><?php echo $row['reset_requested'] ? 'Yes' : 'No'; ?></td>
                <td>
                    <form action="admin_reset_user.php" method="POST">
                        <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                        <input type="submit" name="reset" value="Reset Password">
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
</div>

</body>
</html>
