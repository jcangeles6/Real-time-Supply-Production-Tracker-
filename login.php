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
            if ($failed_attempts >= 3) {
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
            background: url('BG.jpg');
            border-image: fill 0 linear-gradient(#0001, #000);
            font-family: 'Poppins', sans-serif;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            color: var(--text);
        }

        .login-container {
            background: linear-gradient(180deg, #ffffff34, #ffffff27);
            backdrop-filter: blur(7px);
            border-radius: 20px;
            border: 1px solid #ffffff83;
            box-shadow: 0 8px 32px #0000008a;
            width: 400px;
            padding: 40px 35px;
            text-align: center;
        }

        .login-container h2 {
            color: var(--bg);
            font-family: 'Times New Roman', Times, serif;
            font-style: italic;
            font-weight: bold;
            font-size: 50px;
            margin-bottom: 10px;
        }

        .login-container p {
            color: var(--text);
            font-style: italic;
            font-size: 15px;
            margin-bottom: 25px;
        }

        label {
            display: block;
            text-align: left;
            font-family: 'Times New Roman', Times, serif;
            font-weight: bold;
            font-style: italic;
            font-size: 20px;
            color: var(--text);
            margin-bottom: 6px;
        }

        input[type="text"],
        input[type="password"] {
            width: 90%;
            padding: 20px;
            margin-bottom: 18px;
            border: 1px solid #000000ff;
            border-radius: 20px;
            font-size: 16px;
            font-family: 'Arial';
            font-style: italic;
            background: linear-gradient(135deg, #ffffffff, #ffffffff, #ffb3ecff);
            transition: 0.3s;
        }

        input[type="text"]:focus,
        input[type="password"]:focus {
            border-color: var(--text);
            box-shadow: 0 0 6px rgba(46, 26, 46, 0.3);
            outline: none;
        }

        /* From Uiverse.io by cssbuttons-io */ 
        button {
        position: relative;
        display: inline-block;
        cursor: pointer;
        outline: none;
        border: 0;
        vertical-align: middle;
        text-decoration: none;
        background: transparent;
        padding: 0;
        font-size: inherit;
        font-family: inherit;
        }

        button.learn-more {
        width: 12rem;
        height: auto;
        }

        button.learn-more .circle {
        transition: all 0.45s cubic-bezier(0.65, 0, 0.076, 1);
        position: relative;
        display: block;
        margin: 0;
        width: 3rem;
        height: 3rem;
        background: #f9a8d4;
        border-radius: 1.625rem;
        }

        button.learn-more .circle .icon {
        transition: all 0.45s cubic-bezier(0.65, 0, 0.076, 1);
        position: absolute;
        top: 0;
        bottom: 0;
        margin: auto;
        background: #fff;
        }

        button.learn-more .circle .icon.arrow {
        transition: all 0.45s cubic-bezier(0.65, 0, 0.076, 1);
        left: 0.625rem;
        width: 1.125rem;
        height: 0.125rem;
        background: none;
        }

        button.learn-more .circle .icon.arrow::before {
        position: absolute;
        content: "";
        top: -0.29rem;
        right: 0.0625rem;
        width: 0.625rem;
        height: 0.625rem;
        border-top: 0.125rem solid #fff;
        border-right: 0.125rem solid #fff;
        transform: rotate(45deg);
        }

        button.learn-more .button-text {
        transition: all 0.45s cubic-bezier(0.65, 0, 0.076, 1);
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        padding: 0.75rem 0;
        margin: 0 0 0 1.85rem;
        color: #f9a8d4;
        font-weight: 700;
        line-height: 1.6;
        text-align: center;
        font-family: 'Times New Roman', Times, serif;
        text-transform: uppercase;
        }

        button:hover .circle {
        width: 100%;
        }

        button:hover .circle .icon.arrow {
        background: #fff;
        transform: translate(1rem, 0);
        }

        button:hover .button-text {
        color: #fff;
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
            color: #ffe6eb;
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
        /* From Uiverse.io by Sourcesketch */ 
        .flower-loader {
        overflow: hidden;
        position: relative;
        text-indent: -9999px;
        display: inline-block;
        width: 16px;
        height: 16px;
        background: rgba(252, 99, 239, 1);
        border-radius: 100%;
        -moz-box-shadow: white 0 0 15px 0, #2e1a2eff -12px -12px 0 4px,
            #2e1a2eff 12px -12px 0 4px, #2e1a2eff 12px 12px 0 4px, #2e1a2eff -12px 12px 0 4px;
        -webkit-box-shadow: white 0 0 15px 0, #2e1a2eff -12px -12px 0 4px,
            #2e1a2eff 12px -12px 0 4px, #2e1a2eff 12px 12px 0 4px, #2e1a2eff -12px 12px 0 4px;
        box-shadow: white 0 0 15px 0, #2e1a2eff -12px -12px 0 4px, #2e1a2eff 12px -12px 0 4px,
            #2e1a2eff 12px 12px 0 4px, #2e1a2eff -12px 12px 0 4px;
        -moz-animation: flower-loader 5s infinite ease-in-out;
        -webkit-animation: flower-loader 5s infinite ease-in-out;
        animation: flower-loader 5s infinite ease-in-out;
        -moz-transform-origin: 50% 50%;
        -ms-transform-origin: 50% 50%;
        -webkit-transform-origin: 50% 50%;
        transform-origin: 50% 50%;
        }

        @-moz-keyframes flower-loader {
        0% {
            -moz-transform: rotate(0deg);
            transform: rotate(0deg);
            -moz-box-shadow: white 0 0 15px 0, #2e1a2eff -12px -12px 0 4px,
            #2e1a2eff 12px -12px 0 4px, #2e1a2eff 12px 12px 0 4px, #2e1a2eff -12px 12px 0 4px;
            box-shadow: white 0 0 15px 0, #2e1a2eff -12px -12px 0 4px, #2e1a2eff 12px -12px 0 4px,
            #2e1a2eff 12px 12px 0 4px, #2e1a2eff -12px 12px 0 4px;
        }
        50% {
            -moz-transform: rotate(1080deg);
            transform: rotate(1080deg);
            -moz-box-shadow: white 0 0 15px 0, #2e1a2eff 12px 12px 0 4px,
            #2e1a2eff -12px 12px 0 4px, #2e1a2eff -12px -12px 0 4px, #2e1a2eff 12px -12px 0 4px;
            box-shadow: white 0 0 15px 0, #2e1a2eff 12px 12px 0 4px, #2e1a2eff -12px 12px 0 4px,
            #2e1a2eff -12px -12px 0 4px, #2e1a2eff 12px -12px 0 4px;
        }
        }
        @-webkit-keyframes flower-loader {
        0% {
            -webkit-transform: rotate(0deg);
            transform: rotate(0deg);
            -webkit-box-shadow: white 0 0 15px 0, #2e1a2eff -12px -12px 0 4px,
            #2e1a2eff 12px -12px 0 4px, #2e1a2eff 12px 12px 0 4px, #2e1a2eff -12px 12px 0 4px;
            box-shadow: white 0 0 15px 0, #2e1a2eff -12px -12px 0 4px, #2e1a2eff 12px -12px 0 4px,
            #2e1a2eff 12px 12px 0 4px, #2e1a2eff -12px 12px 0 4px;
        }
        50% {
            -webkit-transform: rotate(1080deg);
            transform: rotate(1080deg);
            -webkit-box-shadow: white 0 0 15px 0, #2e1a2eff 12px 12px 0 4px,
            #2e1a2eff -12px 12px 0 4px, #2e1a2eff -12px -12px 0 4px, #2e1a2eff 12px -12px 0 4px;
            box-shadow: white 0 0 15px 0, #2e1a2eff 12px 12px 0 4px, #2e1a2eff -12px 12px 0 4px,
            #2e1a2eff -12px -12px 0 4px, #2e1a2eff 12px -12px 0 4px;
        }
        }
        @keyframes flower-loader {
        0% {
            -moz-transform: rotate(0deg);
            -ms-transform: rotate(0deg);
            -webkit-transform: rotate(0deg);
            transform: rotate(0deg);
            -moz-box-shadow: white 0 0 15px 0, #2e1a2eff -12px -12px 0 4px,
            #2e1a2eff 12px -12px 0 4px, #2e1a2eff 12px 12px 0 4px, #2e1a2eff -12px 12px 0 4px;
            -webkit-box-shadow: white 0 0 15px 0, #2e1a2eff -12px -12px 0 4px,
            #2e1a2eff 12px -12px 0 4px, #2e1a2eff 12px 12px 0 4px, #2e1a2eff -12px 12px 0 4px;
            box-shadow: white 0 0 15px 0, #2e1a2eff -12px -12px 0 4px, #2e1a2eff 12px -12px 0 4px,
            #2e1a2eff 12px 12px 0 4px, #2e1a2eff -12px 12px 0 4px;
        }
        50% {
            -moz-transform: rotate(1080deg);
            -ms-transform: rotate(1080deg);
            -webkit-transform: rotate(1080deg);
            transform: rotate(1080deg);
            -moz-box-shadow: white 0 0 15px 0, #ffb3ecff 12px 12px 0 4px,
            #ffb3ecff -12px 12px 0 4px, #ffb3ecff -12px -12px 0 4px, #ffb3ecff 12px -12px 0 4px;
            -webkit-box-shadow: white 0 0 15px 0, #ffb3ecff 12px 12px 0 4px,
            #ffb3ecff -12px 12px 0 4px, #ffb3ecff -12px -12px 0 4px, #ffb3ecff 12px -12px 0 4px;
            box-shadow: white 0 0 15px 0, #ffb3ecff 12px 12px 0 4px, #ffb3ecff -12px 12px 0 4px,
            #ffb3ecff -12px -12px 0 4px, #ffb3ecff 12px -12px 0 4px;
        }
        }
    </style>
</head>

<body>
    <div class="login-container">
        <h2> BloomLux Login </h2>
        <!-- From Uiverse.io by Sourcesketch --> 
        <div class="cell">
            <div class="card">
                <span class="flower-loader">Loading…</span>
            </div>
        </div>

        <?php if ($error_message): ?>
            <div class="error-message"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <label for="username">Username</label>
            <input type="text" name="username" required placeholder="Enter your username">

            <label for="password">Password</label>
            <input type="password" name="password" required placeholder="Enter your password">

            <!-- From Uiverse.io by cssbuttons-io --> 
            <button class="learn-more">
                <span class="circle" aria-hidden="true">
                <span class="icon arrow"></span>
                </span>
                <span class="button-text">Login</span>
            </button>
        </form>

        <p><a href="forgot_password.php">Forgot password?</a></p>
        <p>Don’t have an account? <a href="register.php">Create one</a></p>
    </div>
</body>

</html>
