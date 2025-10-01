<?php
session_start();
include 'db.php';

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user data
$stmt = $conn->prepare("SELECT username, email, security_question FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($username, $email, $security_question);
$stmt->fetch();
$stmt->close();

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['current_password'], $_POST['new_password'])) {
    $current = $_POST['current_password'];
    $new = password_hash($_POST['new_password'], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($hashed);
    $stmt->fetch();
    $stmt->close();

    if (password_verify($current, $hashed)) {
        $update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $update->bind_param("si", $new, $user_id);
        $update->execute();
        $password_msg = "<p style='color:green;'>✅ Password updated successfully.</p>";
    } else {
        $password_msg = "<p style='color:red;'>❌ Incorrect current password.</p>";
    }
}

// Handle security question update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_question'], $_POST['new_answer'])) {
    $new_question = $_POST['new_question'];
    $new_answer_hashed = password_hash($_POST['new_answer'], PASSWORD_DEFAULT);

    $update = $conn->prepare("UPDATE users SET security_question = ?, security_answer = ? WHERE id = ?");
    $update->bind_param("ssi", $new_question, $new_answer_hashed, $user_id);
    $update->execute();
    $security_msg = "<p style='color:green;'>✅ Security question updated successfully.</p>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Account Settings - Hot Wheels</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            color: #333;
            margin: 0;
            padding: 0;
        }

        nav {
            background-color: #333;
            padding: 15px 0;
            margin-bottom: 20px;
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
            font-weight: bold;
            font-size: 18px;
            text-transform: uppercase;
        }

        nav ul li a:hover {
            color: #ff9900;
        }

        h2 {
            text-align: center;
            color: #ff9900;
            margin-top: 20px;
        }

        .settings-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        .info-box,
        .form-box {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .info-box p {
            text-align: center;
            font-size: 18px;
        }

        label {
            display: block;
            margin-top: 10px;
            font-weight: bold;
        }

        input[type="text"],
        input[type="password"],
        input[type="email"] {
            width: 100%;
            padding: 12px 14px;
            margin-top: 6px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 16px;
            transition: border 0.2s ease-in-out, box-shadow 0.2s;
            box-sizing: border-box;
        }

        input[type="text"]:focus,
        input[type="password"]:focus,
        input[type="email"]:focus {
            border-color: #ff9900;
            box-shadow: 0 0 5px rgba(255, 153, 0, 0.5);
            outline: none;
        }

        button {
            margin-top: 15px;
            padding: 10px 15px;
            background-color: #ff9900;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        button:hover {
            background-color: #ff6600;
        }

        .back-button {
            text-align: center;
            margin-top: 30px;
        }

        .back-button a {
            background-color: #ff9900;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            font-size: 16px;
        }

        .back-button a:hover {
            background-color: #ff6600;
        }

        @media (max-width: 500px) {
            nav ul li {
                display: block;
                margin: 10px 0;
            }
        }
    </style>
</head>
<body>

<nav>
    <ul>
        <li><a href="home.php">Home</a></li>
        <li><a href="buy.php">Buy</a></li>
        <li><a href="sell.php">Sell</a></li>
        <li><a href="my_purchases.php">My Purchases</a></li>
        <li><a href="account_settings.php">Account Settings</a></li>
        <li><a href="logout.php">Logout</a></li>
    </ul>
</nav>

<h2>Account Settings</h2>

<div class="settings-container">
    <div class="info-box">
        <p><strong>Username:</strong> <?php echo htmlspecialchars($username); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($email); ?></p>
    </div>

    <!-- Change Password -->
    <div class="form-box">
        <form method="POST">
            <h3>Change Password</h3>
            <?php if (isset($password_msg)) echo $password_msg; ?>
            <label for="current_password">Current Password:</label>
            <input type="password" name="current_password" required>

            <label for="new_password">New Password:</label>
            <input type="password" name="new_password" required>

            <button type="submit">Update Password</button>
        </form>
    </div>

    <!-- Update Security Question -->
    <div class="form-box">
        <form method="POST">
            <h3>Update Security Question</h3>
            <?php if (isset($security_msg)) echo $security_msg; ?>
            <label for="new_question">New Security Question:</label>
            <input type="text" name="new_question" value="<?php echo htmlspecialchars($security_question); ?>" required>

            <label for="new_answer">Answer:</label>
            <input type="text" name="new_answer" required>

            <button type="submit">Update Question</button>
        </form>
    </div>

    <!-- Back Button -->
    <div class="back-button">
        <a href="home.php">Back to Home</a>
    </div>
</div>

</body>
</html>
