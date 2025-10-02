<?php
include 'backend/init.php'; // includes db.php, session_start(), security settings
redirect_if_logged_in(); // send logged-in users to home.php

$step = 1; // Step 1: email -> 2: question -> 3: reset password -> 4: confirmation
$message = "";

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['step'])) {
    switch ($_POST['step']) {
        case 1:
            // Step 1: Verify email
            $email = $_POST['email'];
            $stmt = $conn->prepare("SELECT id, security_question, security_answer FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();
            $stmt->bind_result($user_id, $security_question, $hashed_answer);

            if ($stmt->fetch()) {
                // Store in temporary session variables for forgot password
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
            // Step 2: Verify security answer
            $answer = $_POST['answer'];
            if (password_verify($answer, $_SESSION['security_answer'])) {
                $step = 3;
            } else {
                $message = "❌ Security answer is incorrect. Please try again.";
                $step = 2; // Stay on step 2
            }
            break;

        case 3:
            case 3:
    // Step 3: Reset password
    $new_password = $_POST['new_password'];

    // Password strength regex
    $pattern = "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/";

    if (!preg_match($pattern, $new_password)) {
        $message = "❌ Password must be at least 8 characters, include uppercase, lowercase, number, and special character.";
        $step = 3; // stay on Step 3
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        if (isset($_SESSION['fp_user_id'])) {
            $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update_stmt->bind_param("si", $hashed_password, $_SESSION['fp_user_id']);
            $update_stmt->execute();

            $message = "✅ Your password has been successfully reset. You can now log in with your new password.";
            $step = 4;

            // Clear forgot-password session variables
            unset($_SESSION['fp_user_id']);
            unset($_SESSION['security_question']);
            unset($_SESSION['security_answer']);
            unset($_SESSION['fp_email']);
        } else {
            $message = "❌ Session expired. Please start the process again.";
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
    <title>Forgot Password</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f1f1f1;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .forgot-password-container {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.2);
            padding: 40px;
            width: 100%;
            max-width: 380px;
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
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 14px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
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
        }
        button:hover {
            background-color: #0056b3;
        }
        .message {
            text-align: center;
            font-size: 16px;
            margin-bottom: 15px;
        }
        .message.success {
            color: #28a745;
        }
        .message.error {
            color: #dc3545;
        }
        .back-login {
            display: block;
            text-align: center;
            margin-top: 15px;
            font-size: 14px;
            color: #007BFF;
            text-decoration: underline;
        }
        .back-login:hover {
            color: #0056b3;
        }
        p {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
        }
        @media (max-width: 500px) {
            .forgot-password-container {
                padding: 25px;
                width: 90%;
            }
            h2 {
                font-size: 24px;
            }
            input[type="text"],
            input[type="email"],
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
<div class="forgot-password-container">
    <h2>Forgot Password</h2>

    <?php if ($message): ?>
        <div class="message <?php echo strpos($message,'✅') !== false ? 'success' : 'error'; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <?php if ($step === 1): ?>
        <p>Enter your registered email to start the reset process.</p>
        <form method="POST">
            <label>Email:</label>
            <input type="email" name="email" required placeholder="Enter your email">
            <input type="hidden" name="step" value="1">
            <button type="submit">Continue</button>
        </form>
        <a href="login.php?from_forgot=1" class="back-login">Back to Login</a>

    <?php elseif ($step === 2): ?>
        <p>Answer your security question to verify your identity.</p>
        <p><strong>Security Question:</strong> <?php echo htmlspecialchars($_SESSION['security_question']); ?></p>
        <form method="POST">
            <label>Answer:</label>
            <input type="text" name="answer" required placeholder="Enter your answer">
            <input type="hidden" name="step" value="2">
            <button type="submit">Submit</button>
        </form>
        <a href="login.php?from_forgot=1" class="back-login">Back to Login</a>

    <?php elseif ($step === 3): ?>
        <p>Enter your new password below.</p>
        <form method="POST">
            <label>New Password:</label>
            <input type="password" name="new_password" required placeholder="Enter new password">
            <input type="hidden" name="step" value="3">
            <button type="submit">Confirm New Password</button>
        </form>
        <a href="login.php?from_forgot=1" class="back-login">Back to Login</a>

    <?php elseif ($step === 4): ?>
        <a href="login.php?from_forgot=1" class="back-login">Back to Login</a>
    <?php endif; ?>
</div>
</body>
</html>
