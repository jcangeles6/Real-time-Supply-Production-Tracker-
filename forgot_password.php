<?php
include 'backend/init.php'; 
redirect_if_logged_in(); 

$step = 1; 
$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['step'])) {
    switch ($_POST['step']) {
        case 1:
            $email = $_POST['email'];
            $stmt = $conn->prepare("SELECT id, security_question, security_answer FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();
            $stmt->bind_result($user_id, $security_question, $hashed_answer);

            if ($stmt->fetch()) {
                $_SESSION['fp_user_id'] = $user_id;
                $_SESSION['security_question'] = $security_question;
                $_SESSION['security_answer'] = $hashed_answer;
                $_SESSION['fp_email'] = $email;
                $step = 2;
            } else {
                $message = "❌ Email not found. Please verify and try again.";
            }
            break;

        case 2:
            $answer = $_POST['answer'];
            if (password_verify($answer, $_SESSION['security_answer'])) {
                $step = 3;
            } else {
                $message = "❌ Security answer is incorrect. Please try again.";
                $step = 2;
            }
            break;

        case 3:
            $new_password = $_POST['new_password'];
            $pattern = "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/";

            if (!preg_match($pattern, $new_password)) {
                $message = "❌ Password must include uppercase, lowercase, number, and special character.";
                $step = 3;
            } else {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                if (isset($_SESSION['fp_user_id'])) {
                    $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $update_stmt->bind_param("si", $hashed_password, $_SESSION['fp_user_id']);
                    $update_stmt->execute();

                    $message = "✅ Password successfully reset. You can now log in.";
                    $step = 4;

                    unset($_SESSION['fp_user_id'], $_SESSION['security_question'], $_SESSION['security_answer'], $_SESSION['fp_email']);
                } else {
                    $message = "❌ Session expired. Please start again.";
                    $step = 1;
                }
            }
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>🌸 BloomLux | Forgot Password</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #ffb3ecff;
            --card: #f5f0fa;
            --primary: #2e1a2eff;
            --text: #000000ff;
            --highlight: #000000ff;
            --shadow: 0 3px 10px rgba(0,0,0,0.08);
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--bg);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            color: var(--text);
        }

        .forgot-container {
            background: var(--card);
            border-radius: 20px;
            box-shadow: var(--shadow);
            padding: 40px 35px;
            width: 100%;
            max-width: 420px;
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .forgot-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(46, 26, 46, 0.15);
        }

        h2 {
            color: var(--primary);
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 15px;
        }

        p {
            color: var(--text);
            font-size: 15px;
            margin-bottom: 25px;
        }

        label {
            display: block;
            text-align: left;
            color: var(--primary);
            font-weight: 600;
            margin-bottom: 6px;
        }

        input[type="email"],
        input[type="text"],
        input[type="password"] {
            width: 94%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 10px;
            margin-bottom: 18px;
            font-size: 15px;
            background: #fff;
            transition: 0.3s;
        }

        input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 6px rgba(46, 26, 46, 0.3);
        }

        button {
            width: 100%;
            padding: 12px;
            background-color: var(--primary);
            border: none;
            color: #fff;
            font-size: 16px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.3s ease, transform 0.2s ease;
        }

        button:hover {
            background-color: var(--highlight);
            transform: scale(1.03);
        }

        .message {
            margin-bottom: 15px;
            font-weight: 500;
            padding: 10px;
            border-radius: 10px;
            text-align: center;
        }

        .success {
            background-color: #e6ffed;
            color: #137333;
        }

        .error {
            background-color: #ffe6e9;
            color: #b71c1c;
        }

        .back-login {
            display: inline-block;
            margin-top: 15px;
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            transition: 0.3s;
        }

        .back-login:hover {
            color: var(--highlight);
            text-decoration: underline;
        }

        /* BloomLux loading animation */
        .loader {
            display: inline-block;
            border: 3px solid #f3d8f6;
            border-top: 3px solid var(--primary);
            border-radius: 50%;
            width: 22px;
            height: 22px;
            animation: spin 0.9s linear infinite;
            margin-left: 8px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @media (max-width: 500px) {
            .forgot-container {
                width: 90%;
                padding: 30px;
            }

            h2 {
                font-size: 22px;
            }
        }
    </style>
</head>
<body>

<div class="forgot-container">
    <h2>🌸 Forgot Password 🌸</h2>

    <?php if ($message): ?>
        <div class="message <?php echo strpos($message, '✅') !== false ? 'success' : 'error'; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <?php if ($step === 1): ?>
        <p>Enter your registered email to begin the reset.</p>
        <form method="POST">
            <label>Email</label>
            <input type="email" name="email" required placeholder="Enter your email">
            <input type="hidden" name="step" value="1">
            <button type="submit">Continue</button>
        </form>
        <a href="login.php" class="back-login">← Back to Login</a>

    <?php elseif ($step === 2): ?>
        <p>Answer your security question to continue.</p>
        <p><strong><?php echo htmlspecialchars($_SESSION['security_question']); ?></strong></p>
        <form method="POST">
            <label>Answer</label>
            <input type="text" name="answer" required placeholder="Your answer">
            <input type="hidden" name="step" value="2">
            <button type="submit">Submit</button>
        </form>
        <a href="login.php" class="back-login">← Back to Login</a>

    <?php elseif ($step === 3): ?>
        <p>Enter your new password below.</p>
        <form method="POST">
            <label>New Password</label>
            <input type="password" name="new_password" required placeholder="Enter new password">
            <input type="hidden" name="step" value="3">
            <button type="submit">Reset Password</button>
        </form>
        <a href="login.php" class="back-login">← Back to Login</a>

    <?php elseif ($step === 4): ?>
        <p>Password successfully reset! 🎉</p>
        <a href="login.php" class="back-login">Go to Login</a>
    <?php endif; ?>
</div>

</body>
</html>
