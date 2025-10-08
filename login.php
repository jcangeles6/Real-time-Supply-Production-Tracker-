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
    <title>Bakery Login</title>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #fff5e1, #fce4d6);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .login-container {
            background-color: #fffaf0;
            border-radius: 15px;
            box-shadow: 0 4px 10px rgba(139, 69, 19, 0.2);
            padding: 40px;
            width: 350px;
            text-align: center;
        }

        h2 {
            color: #8b4513;
            margin-bottom: 25px;
            font-size: 26px;
        }

        label {
            display: block;
            text-align: left;
            font-weight: 600;
            color: #5a2d0c;
            margin-bottom: 8px;
        }

        input[type="text"],
        input[type="password"] {
            width: 93%;
            padding: 12px;
            margin-bottom: 20px;
            border: 1px solid #d2b48c;
            border-radius: 8px;
            font-size: 15px;
            background-color: #fffdf8;
        }

        button {
            width: 100%;
            padding: 12px;
            background-color: #8b4513;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: 0.3s;
        }

        button:hover {
            background-color: #a0522d;
        }

        .error-message {
            background-color: #fbeaea;
            color: #b22222;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-size: 14px;
        }

        a {
            color: #8b4513;
            text-decoration: none;
            font-weight: 600;
        }

        a:hover {
            text-decoration: underline;
        }

        p {
            margin-top: 15px;
            font-size: 14px;
        }

        .emoji {
            font-size: 40px;
            margin-bottom: 10px;
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="emoji">🍞</div>
        <h2>Bakery Login</h2>

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
