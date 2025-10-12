<?php
include __DIR__ . '/backend/init.php'; // Adjust path if needed

date_default_timezone_set('Asia/Manila');

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("
        SELECT id, password, is_admin, failed_attempts, locked_until 
        FROM users 
        WHERE username = ?
    ");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($user_id, $hash, $is_admin, $failed_attempts, $locked_until);

    if ($stmt->fetch()) {
        $current_page = basename($_SERVER['PHP_SELF']);

        if (!in_array($current_page, ['forgot_password.php', 'reset_password.php'])) {
            if ($locked_until && strtotime($locked_until) > time()) {
                $error_message = "⛔ Account is locked. Try again after " . $locked_until;
            }
        }

        if (!$error_message && password_verify($password, $hash)) {
            $reset_stmt = $conn->prepare("
                UPDATE users 
                SET failed_attempts = 0, locked_until = NULL 
                WHERE id = ?
            ");
            $reset_stmt->bind_param("i", $user_id);
            $reset_stmt->execute();

            session_regenerate_id(true);
            $_SESSION['user_id']   = $user_id;
            $_SESSION['username']  = $username;
            $_SESSION['is_admin']  = $is_admin;

            if ($is_admin == 1) {
                header("Location: admin_dashboard.php");
            } else {
                header("Location: home.php");
            }
            exit();
        } else {
            $failed_attempts++;
            if ($failed_attempts >= 5) {
                $lock_time = date("Y-m-d H:i:s", strtotime("+5 minutes"));
                $update_stmt = $conn->prepare("
                    UPDATE users 
                    SET failed_attempts = ?, locked_until = ? 
                    WHERE id = ?
                ");
                $update_stmt->bind_param("isi", $failed_attempts, $lock_time, $user_id);
                $update_stmt->execute();

                $formatted_lock_time = date("M d, Y, h:i:s A", strtotime($lock_time));
                $error_message = "⛔ Too many failed attempts. Account locked until $formatted_lock_time.";
            } else {
                $update_stmt = $conn->prepare("
                    UPDATE users 
                    SET failed_attempts = ? 
                    WHERE id = ?
                ");
                $update_stmt->bind_param("ii", $failed_attempts, $user_id);
                $update_stmt->execute();

                $error_message = "Invalid username or password. Attempts left: " . (5 - $failed_attempts);
            }
        }
    } else {
        $error_message = 'Invalid username or password.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>🌸 BloomLux | Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #ffb3ecff;
            --card: #f5f0fa;
            --primary: #2e1a2eff;
            --text: #000000ff;
            --highlight: #000000ff;
            --shadow: 0 3px 10px rgaba(0,0,0,0.08);
        }

        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            background: var(--bg);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            color: var(--text);
        }

        .login-container {
            background: var(--card);
            border-radius: 20px;
            box-shadow: var(--shadow);
            width: 400px;
            padding: 40px 35px;
            text-align: center;
        }

        .login-container h2 {
            color: var(--primary);
            font-weight: 700;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .login-container p {
            color: var(--text);
            font-size: 15px;
            margin-bottom: 25px;
        }

        label {
            display: block;
            text-align: left;
            font-weight: 500;
            color: var(--primary);
            margin-bottom: 6px;
        }

        input[type="text"],
        input[type="password"] {
            width: 94%;
            padding: 12px;
            margin-bottom: 18px;
            border: 1px solid #ddd;
            border-radius: 10px;
            font-size: 15px;
            background: #fff;
            transition: 0.3s;
        }

        input[type="text"]:focus,
        input[type="password"]:focus {
            border-color: var(--primary);
            box-shadow: 0 0 6px rgba(46, 26, 46, 0.3);
            outline: none;
        }

        button {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 10px;
            background: var(--primary);
            color: white;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: 0.3s;
        }

        button:hover {
            background: var(--highlight);
        }

        .error-message {
            background: #ffe6eb;
            color: #b71c1c;
            padding: 10px;
            border-radius: 10px;
            margin-bottom: 15px;
            font-size: 14px;
        }

        a {
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
            transition: 0.3s;
        }

        a:hover {
            color: var(--highlight);
            text-decoration: underline;
        }

        .emoji {
            font-size: 45px;
            margin-bottom: 10px;
        }

        footer {
            margin-top: 20px;
            font-size: 13px;
            color: #333;
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="emoji">🌸</div>
        <h2>🌸 BloomLux Login 🌸</h2>

        <?php if ($error_message): ?>
            <div class="error-message"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <label for="username">Username</label>
            <input type="text" name="username" required placeholder="Enter your username">

            <label for="password">Password</label>
            <input type="password" name="password" required placeholder="Enter your password">

            <button type="submit">Login</button>
        </form>

        <p><a href="forgot_password.php">Forgot password?</a></p>
        <p>Don’t have an account? <a href="register.php">Create one</a></p>
    </div>
</body>

</html>
