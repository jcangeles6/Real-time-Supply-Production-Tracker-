<?php
include 'db.php';
session_start();
$message = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']); // already trimmed
    $security_question = trim($_POST['security_question']);
    $security_answer = trim($_POST['security_answer']);

    // Basic validation
    if (empty($username) || empty($email) || empty($password) || empty($security_question) || empty($security_answer)) {
        $message = "❌ All fields are required.";
    } else {
        // Check password strength
        $uppercase    = preg_match('@[A-Z]@', $password);
        $number       = preg_match('@[0-9]@', $password);
        $specialChars = preg_match('@[^\w]@', $password);

        if (strlen($password) < 8 || !$uppercase || !$number || !$specialChars) {
            $message = "❌ Password must be at least 8 characters long and include at least 1 uppercase letter, 1 number, and 1 special character.";
        }
    }

    // Only insert if no errors
    if (empty($message)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $hashed_answer   = password_hash($security_answer, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO users (username, email, password, security_question, security_answer) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $username, $email, $hashed_password, $security_question, $hashed_answer);

        if ($stmt->execute()) {
            $message = "✅ Registration successful. You can now <a href='login.php'>log in</a>.";
        } else {
            $message = "❌ Error: This username or email might already be taken.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Register</title>
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

        .register-container {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
            padding: 40px;
            width: 100%;
            max-width: 380px;
            transition: transform 0.3s ease-in-out;
        }

        .register-container:hover {
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
        input[type="password"],
        select {
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
            .register-container {
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

<div class="register-container">
    <h2>User Registration</h2>

    <!-- Display messages (success or error) -->
    <?php if ($message): ?>
        <div class="message <?php echo strpos($message, '✅') !== false ? 'success' : 'error'; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <label for="username">Username:</label>
        <input type="text" name="username" required placeholder="Enter your username">

        <label for="email">Email:</label>
        <input type="email" name="email" required placeholder="Enter your email">

        <label for="password">Password:</label>
        <input type="password" name="password" id="password" required placeholder="Enter your password" style="margin-bottom: 0;">
        <small style="color: #555; font-size: 12px; display: block; margin-top: 2px; margin-bottom: 16px;">
            Password must be at least 8 characters and include 1 uppercase letter, 1 number, and 1 special character. Spaces are not allowed.
        </small>

        <label for="security_question">Security Question:</label>
        <select name="security_question" required>
            <option value="">-- Select a question --</option>
            <option value="What is your favorite color?">What is your favorite color?</option>
            <option value="What is your mother's maiden name?">What is your mother's maiden name?</option>
            <option value="What is your first pet's name?">What is your first pet's name?</option>
        </select>

        <label for="security_answer">Answer to Security Question:</label>
        <input type="text" name="security_answer" required placeholder="Enter the answer">

        <button type="submit">Register</button>
    </form>

    <p>Already have an account? <a href="login.php">Log in here</a>.</p>
</div>

<script>
    const passwordInput = document.getElementById('password');
    passwordInput.addEventListener('input', () => {
        // Remove spaces in real-time as the user types
        passwordInput.value = passwordInput.value.replace(/\s/g, '');
    });
</script>

</body>
</html>
