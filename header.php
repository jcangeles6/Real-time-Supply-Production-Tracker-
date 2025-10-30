<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'db.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get username
$user_id = $_SESSION['user_id'];
// Securely fetch username using a prepared statement
$stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();
$stmt->close();

$username = $user['username'] ?? 'User';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bakery Dashboard</title>
    <style>
        body { margin:0; font-family: Arial, sans-serif; background:#fdf6f0; }
        .sidebar {
            width:220px; background:#8b4513; color:#fff;
            height:100vh; position:fixed; top:0; left:0; padding:20px 0;
        }
        .sidebar h2 { text-align:center; margin-bottom:30px; }
        .sidebar a {
            display:block; color:#fff; padding:12px 20px;
            text-decoration:none; font-weight:bold;
        }
        .sidebar a:hover { background:#a0522d; }
        .main { margin-left:240px; padding:20px; }
        .top-bar { display:flex; justify-content:space-between; align-items:center; }
        .top-bar h1 { color:#8b4513; }
        .search-bar input {
            padding:8px; border:1px solid #ccc; border-radius:5px;
        }
        .notif { font-size:20px; cursor:pointer; margin-left:10px; }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <h2>🍞 Bakery</h2>
        <a href="home.php">Dashboard</a>
        <a href="supply.php">Supply</a>
        <a href="production.php">Production</a>
        <a href="requests.php">My Requests</a>
        <a href="inventory.php">Inventory</a>
        <a href="orders.php">Orders</a>
        <a href="logout.php">Logout</a>
    </div>

    <!-- Main -->
    <div class="main">
        <div class="top-bar">
            <h1>Welcome, <?php echo htmlspecialchars($username); ?> 👋</h1>
            <div class="search-bar">
                <input type="text" placeholder="Search...">
                <span class="notif">🔔</span>
            </div>
        </div>
