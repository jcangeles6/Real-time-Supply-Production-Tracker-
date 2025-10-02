<?php
include __DIR__ . '/backend/init.php'; // Adjust path if needed

$error_message = ''; // Variable to store error messages

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // 🔹 Modified query: fetch failed_attempts and locked_until
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
        $current_page = basename($_SERVER['PHP_SELF']); // Get current page name

        // ✅ Only enforce lockout if NOT on forgot/reset password pages
        if (!in_array($current_page, ['forgot_password.php', 'reset_password.php'])) {
            if ($locked_until && strtotime($locked_until) > time()) {
                $error_message = "⛔ Account is locked. Try again after " . $locked_until;
            }
        }

        // ✅ Continue with login if not locked
        if (!$error_message && password_verify($password, $hash)) {
            // Success → reset attempts + unlock
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
            // ❌ Wrong password → increment attempts
            $failed_attempts++;
            if ($failed_attempts >= 5) {
                // Lock for 10 minutes
                $lock_time = date("Y-m-d H:i:s", strtotime("+10 minutes"));
                $update_stmt = $conn->prepare("
                    UPDATE users 
                    SET failed_attempts = ?, locked_until = ? 
                    WHERE id = ?
                ");
                $update_stmt->bind_param("isi", $failed_attempts, $lock_time, $user_id);
                $update_stmt->execute();

                $error_message = "⛔ Too many failed attempts. Account locked until $lock_time.";
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
    <title>Login</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f1f1f1;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            padding: 0;
        }

        .login-container {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 8px 16px rgba(247, 165, 165, 0.2);
            padding: 40px;
            width: 100%;
            max-width: 380px;
            transition: transform 0.3s ease-in-out;
        }

        .login-container:hover {
            transform: scale(1.05);
        }

        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
            font-size: 28px;
            font-weight: bold;
        }

        label {
            display: block;
            font-weight: bold;
            margin-bottom: 8px;
            color: #333;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 14px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            box-sizing: border-box;
        }

        button {
            width: 100%;
            padding: 14px;
            background-color: #007BFF;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 18px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        button:hover {
            background-color: #0056b3;
        }

        .error-message {
            background-color: #ffcccc;
            color: #ff0000;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 6px;
            font-size: 14px;
            text-align: center;
        }

        p {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
        }

        a {
            color: #007BFF;
            text-decoration: none;
            font-weight: bold;
        }

        a:hover {
            text-decoration: underline;
        }

        @media (max-width: 500px) {
            .login-container {
                padding: 25px;
                width: 90%;
            }

            h2 {
                font-size: 24px;
            }

            input[type="text"],
            input[type="password"] {
                font-size: 14px;
            }

            button {
                font-size: 16px;
            }
        }
    </style>
</head>
<body>

    <div class="login-container">
        <h2>Login</h2>

        <!-- Display error message if login fails -->
        <?php if ($error_message): ?>
            <div class="error-message"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <label for="username">Username:</label>
            <input type="text" name="username" required placeholder="Enter your username">

            <label for="password">Password:</label>
            <input type="password" name="password" required placeholder="Enter your password">

            <button type="submit">Login</button>
        </form>

        <p><a href="forgot_password.php">Forgot password?</a></p>
        <p>Don't have an account? <a href="register.php">Register here</a></p>
    </div>

</body>
</html>
