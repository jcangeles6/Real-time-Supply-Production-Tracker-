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
    <title>🍞 SweetCrumb Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">

    <style>
        :root {
            --brown: #8b4513;
            --light-brown: #c3814a;
            --cream: #fdf6f0;
            --white: #ffffff;
            --shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--cream);
            color: #5a2d0c;
            margin: 0;
            display: flex;
        }

        /* Sidebar */
        .sidebar {
            width: 240px;
            background: linear-gradient(180deg, var(--brown), #a0522d);
            color: var(--white);
            height: 100vh;
            position: fixed;
            padding: 25px 20px;
            display: flex;
            flex-direction: column;
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
            margin: 6px 0;
            text-decoration: none;
            border-radius: 10px;
            transition: 0.3s;
            text-align: left;
        }

        .sidebar a:hover {
            background: var(--light-brown);
            transform: translateX(4px);
        }

        .logout {
            margin-top: auto;
        }

        .logout a {
            display: block;
            background: #b22222;
            text-align: center;
            padding: 10px 18px;
            border-radius: 10px;
            text-decoration: none;
            color: white;
            font-weight: 500;
        }

        .logout a:hover {
            background: #a52a2a;
        }

        /* Main content */
        .main {
            flex: 1;
            margin-left: 260px;
            padding: 30px;
        }

        h1 {
            color: var(--brown);
            text-align: center;
            font-weight: 600;
            margin-bottom: 5px;
        }

        p {
            text-align: center;
            color: #7b4b22;
            margin-bottom: 25px;
        }

        /* Card container */
        .card {
            background: var(--white);
            border-radius: 18px;
            box-shadow: var(--shadow);
            padding: 25px;
            margin-bottom: 25px;
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
            background: var(--brown);
            color: var(--white);
            font-weight: 500;
        }

        tr:hover {
            background: #fff5ea;
        }

        /* Buttons */
        input[type="submit"] {
            background: var(--brown);
            color: white;
            border: none;
            padding: 8px 14px;
            border-radius: 20px;
            cursor: pointer;
            font-weight: 500;
            transition: 0.3s;
        }

        input[type="submit"]:hover {
            background: var(--light-brown);
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <h2>🍞 SweetCrumb Admin</h2>
        <a href="admin_dashboard.php" style="background: var(--light-brown);">📋 Dashboard</a>
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
