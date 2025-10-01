<?php
include 'db.php';
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

$step = 1; // Step 1: email -> 2: question -> 3: reset password
$message = "";

// Step 1: Verify email
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['step']) && $_POST['step'] == 1) {
    $email = $_POST['email'];
    $stmt = $conn->prepare("SELECT id, security_question, security_answer FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($user_id, $security_question, $hashed_answer);

    if ($stmt->fetch()) {
        $_SESSION['user_id'] = $user_id;
        $_SESSION['security_question'] = $security_question;
        $_SESSION['security_answer'] = $hashed_answer;
        $_SESSION['email'] = $email;
        $step = 2;
    } else {
        $message = "❌ Email not found.";
    }
}

// Step 2: Verify security answer
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['step']) && $_POST['step'] == 2) {
    $answer = $_POST['answer'];
    if (password_verify($answer, $_SESSION['security_answer'])) {
        $message = "✅ Answer correct! Now enter a new password.";
        $step = 3;
    } else {
        $message = "❌ Incorrect answer. Please try again.";
        $step = 2;
    }
}

// Step 3: Reset password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['step']) && $_POST['step'] == 3) {
    $new_password = $_POST['new_password'];
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $update_stmt->bind_param("si", $hashed_password, $_SESSION['user_id']);
    $update_stmt->execute();
    $message = "✅ Password has been reset successfully.";
    session_unset(); // Clear session data
    $step = 1; // Go back to email step
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password</title>
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

        .forgot-password-container {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
            padding: 40px;
            width: 100%;
            max-width: 380px;
            transition: transform 0.3s ease-in-out;
        }

        .forgot-password-container:hover {
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
        input[type="email"],
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
            .forgot-password-container {
                padding: 25px;
                width: 90%;
            }

            h2 {
                font-size: 24px;
            }

            input[type="text"],
            input[type="password"],
            input[type="email"] {
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

        <!-- Display messages (success or error) -->
        <?php if ($message): ?>
            <div class="message <?php echo strpos($message, '✅') !== false ? 'success' : 'error'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Step 1: Email form -->
        <?php if ($step === 1) { ?>
            <form method="POST">
                <label for="email">Email:</label>
                <input type="email" name="email" required placeholder="Enter your email">
                <input type="hidden" name="step" value="1">
                <button type="submit">Next</button>
            </form>
        <?php } ?>

        <!-- Step 2: Security answer form -->
        <?php if ($step === 2) { ?>
            <p><strong>Security Question:</strong> <?php echo htmlspecialchars($_SESSION['security_question']); ?></p>
            <form method="POST">
                <label for="answer">Answer:</label>
                <input type="text" name="answer" required placeholder="Enter your answer">
                <input type="hidden" name="step" value="2">
                <button type="submit">Verify</button>
            </form>
        <?php } ?>

        <!-- Step 3: Reset password form -->
        <?php if ($step === 3) { ?>
            <form method="POST">
                <label for="new_password">New Password:</label>
                <input type="password" name="new_password" required placeholder="Enter your new password">
                <input type="hidden" name="step" value="3">
                <button type="submit">Reset Password</button>
            </form>
        <?php } ?>

        <!-- Link back to login page -->
        <p><a href="login.php">Back to Login</a></p>
    </div>

</body>
</html>
