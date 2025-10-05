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
    <title>Admin Dashboard - Hot Wheels</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        /* General Layout */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
            color: #333;
        }

        /* Navigation Bar */
        nav {
            background-color: #333;
            padding: 15px 0;
            margin-bottom: 30px;
        }

        nav ul {
            list-style: none;
            text-align: center;
            margin: 0;
            padding: 0;
        }

        nav ul li {
            display: inline;
            margin-right: 30px;
        }

        nav ul li a {
            color: white;
            text-decoration: none;
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        nav ul li a:hover {
            color: #ff9900;
        }

        /* Page Heading */
        h1 {
            text-align: center;
            color: #ff9900;
            font-size: 36px;
            margin-bottom: 20px;
        }

        p {
            text-align: center;
            font-size: 18px;
            margin-bottom: 40px;
        }

        /* Table Styling */
        table {
            width: 90%;
            margin: 0 auto;
            border-collapse: collapse;
            background-color: #fff;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            overflow: hidden;
        }

        th, td {
            padding: 12px 16px;
            text-align: center;
            border-bottom: 1px solid #ccc;
        }

        th {
            background-color: #ff9900;
            color: white;
        }

        tr:hover {
            background-color: #f1f1f1;
        }

        form {
            display: inline;
        }

        input[type="submit"] {
            background-color: #ff9900;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 5px;
            cursor: pointer;
        }

        input[type="submit"]:hover {
            background-color: #ff6600;
        }

        /* Logout button (top of page) */
        .logout-link {
            display: block;
            text-align: center;
            margin-top: 20px;
        }

        .logout-link a {
            background-color: #ff9900;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
        }

        .logout-link a:hover {
            background-color: #ff6600;
        }
    </style>
</head>
<body>

<!-- Navigation Bar -->
<nav>
    <ul>
        <li><a href="admin_dashboard.php">Dashboard</a></li>
        <li><a href="backend/add_stock.php">Add Stock</a></li>
        <li><a href="logout.php">Logout</a></li>
    </ul>
</nav>

<!-- Heading -->
<h1>Welcome, Admin <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
<p>Manage users and password reset requests below:</p>

<!-- User Table -->
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

<!-- Logout Button -->
<div class="logout-link">
    <a href="logout.php">Logout</a>
</div>

</body>
</html>
