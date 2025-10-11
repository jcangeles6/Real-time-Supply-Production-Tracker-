<?php
include 'backend/init.php';

$message = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $security_question = trim($_POST['security_question']);
    $security_answer = trim($_POST['security_answer']);

    if (empty($username) || empty($email) || empty($password) || empty($security_question) || empty($security_answer)) {
        $message = "❌ All fields are required.";
    } else {
        $uppercase    = preg_match('@[A-Z]@', $password);
        $number       = preg_match('@[0-9]@', $password);
        $specialChars = preg_match('@[^\w]@', $password);

        if (strlen($password) < 8 || !$uppercase || !$number || !$specialChars) {
            $message = "❌ Password must be at least 8 characters long and include 1 uppercase letter, 1 number, and 1 special character.";
        }
    }

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
    <title>Register - Bakery System</title>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #fdf6f0;
            /* soft cream background */
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .register-container {
            background-color: #fff8f0;
            /* warm beige */
            border-radius: 15px;
            box-shadow: 0 6px 15px rgba(139, 69, 19, 0.2);
            padding: 40px;
            width: 100%;
            max-width: 400px;
            border: 2px solid #f0d9b5;
            transition: transform 0.3s ease;
        }

        .register-container:hover {
            transform: scale(1.03);
        }

        h2 {
            text-align: center;
            color: #8b4513;
            /* warm brown */
            margin-bottom: 25px;
            font-size: 28px;
        }

        label {
            display: block;
            font-weight: bold;
            margin-bottom: 8px;
            color: #5a3e2b;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"],
        select {
            width: 100%;
            padding: 12px;
            margin-bottom: 18px;
            border: 1px solid #d2b48c;
            border-radius: 8px;
            background-color: #fffaf5;
            font-size: 15px;
        }

        input:focus,
        select:focus {
            outline: none;
            border-color: #c68642;
            box-shadow: 0 0 5px rgba(198, 134, 66, 0.4);
        }

        button {
            width: 100%;
            padding: 12px;
            background-color: #8b4513;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 18px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s;
        }

        button:hover {
            background-color: #a0522d;
        }

        .message {
            text-align: center;
            font-size: 15px;
            margin-bottom: 15px;
            padding: 10px;
            border-radius: 6px;
        }

        .message.success {
            background-color: #dff0d8;
            color: #3c763d;
        }

        .message.error {
            background-color: #f8d7da;
            color: #a94442;
        }

        p {
            text-align: center;
            margin-top: 15px;
            font-size: 14px;
            color: #5a3e2b;
        }

        a {
            color: #c68642;
            text-decoration: none;
            font-weight: bold;
        }

        a:hover {
            text-decoration: underline;
        }

        small {
            color: #7b6651;
            font-size: 12px;
            display: block;
            margin-top: -10px;
            margin-bottom: 10px;
        }

        @media (max-width: 480px) {
            .register-container {
                width: 90%;
                padding: 25px;
            }

            h2 {
                font-size: 22px;
            }
        }
    </style>
</head>

<body>

    <div class="register-container">
        <h2>🍞 Bakery Registration</h2>

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
            <input type="password" name="password" id="password" required placeholder="Enter your password">
            <small>Password must be at least 8 characters with 1 uppercase, 1 number, and 1 special character.</small>

            <label for="security_question">Security Question:</label>
            <select name="security_question" required>
                <option value="">-- Select a question --</option>
                <option value="What is your favorite bread?">What is your favorite bread?</option>
                <option value="What is your first pet's name?">What is your first pet's name?</option>
                <option value="What city were you born in?">What city were you born in?</option>
            </select>

            <label for="security_answer">Answer:</label>
            <input type="text" name="security_answer" required placeholder="Enter your answer">

            <button type="submit">Register</button>
        </form>

        <p>Already have an account? <a href="login.php">Login here</a>.</p>
    </div>

    <script>
        const passwordInput = document.getElementById('password');
        passwordInput.addEventListener('input', () => {
            passwordInput.value = passwordInput.value.replace(/\s/g, '');
        });
    </script>

</body>

</html>