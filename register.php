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
        } else {
            // ✅ Check if username or email already exists
            $check = $conn->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
            $check->bind_param("ss", $username, $email);
            $check->execute();
            $result = $check->get_result();

            if ($result->num_rows > 0) {
                $message = "❌ Username or Email already exists. Please choose another.";
            } else {
                // Hash password and answer
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $hashed_answer   = password_hash($security_answer, PASSWORD_DEFAULT);

                // Insert new user
                $stmt = $conn->prepare("INSERT INTO users (username, email, password, security_question, security_answer) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssss", $username, $email, $hashed_password, $security_question, $hashed_answer);

                if ($stmt->execute()) {
                    $message = "✅ Registration successful. You can now <a href='login.php'>log in</a>.";
                } else {
                    $message = "❌ Error occurred during registration. Please try again.";
                }
                $stmt->close();
            }
            $check->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>🌸 BloomLux | Register</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        /* ---------------- VARIABLES ---------------- */
        :root {
            --bg: #ffb3ecff;
            --card: #f5f0fa;
            --primary: #2e1a2eff;
            --text: #000;
            --accent: #2e1a2eff;
            --shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
        }

        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            background: var(--bg);
            display: flex;
            color: var(--text);
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        /* Background with floral image + tint layers */
        body::before {
            content: "";
            position: fixed;
            inset: 0;
            background: url('https://thumbs.dreamstime.com/b/beautiful-colorful-meadow-wild-flowers-floral-background-landscape-purple-pink-sunset-blurred-soft-pastel-magical-332027305.jpg') no-repeat center/cover;
            z-index: -2;
        }
        body::after {
            content: "";
            position: fixed;
            inset: 0;
            background: linear-gradient(120deg, rgba(214, 122, 177, 0.4), rgba(184, 112, 209, 0.4));
            backdrop-filter: blur(15px);
            z-index: -1;
        }

        .register-container {
            background: var(--bg);
            border-radius: 20px;
            box-shadow: var(--shadow);
            padding: 40px 35px;
            width: 100%;
            max-width: 420px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .register-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(46, 26, 46, 0.15);
        }

        h2 {
            text-align: center;
            color: var(--primary);
            font-weight: 700;
            font-size: 28px;
            margin-bottom: 25px;
        }

        label {
            display: block;
            font-weight: 500;
            margin-bottom: 6px;
            color: var(--primary);
        }

        input[type="text"],
        input[type="email"],
        input[type="password"],
        select {
            width: 94%;
            padding: 12px;
            margin-bottom: 18px;
            border: 1px solid #ddd;
            border-radius: 10px;
            background: #fff;
            font-size: 15px;
            transition: all 0.3s ease;
        }

        input:focus,
        select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 6px rgba(46, 26, 46, 0.3);
            outline: none;
        }

        button {
            width: 100%;
            padding: 12px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 17px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.3s ease, transform 0.2s;
        }

        button:hover {
            background: var(--highlight);
            transform: scale(1.02);
        }

        .message {
            text-align: center;
            font-size: 15px;
            margin-bottom: 15px;
            padding: 10px;
            border-radius: 10px;
        }

        .message.success {
            background-color: #e6ffed;
            color: #137333;
        }

        .message.error {
            background-color: #ffe6e9;
            color: #b71c1c;
        }

        p {
            text-align: center;
            margin-top: 15px;
            font-size: 14px;
            color: var(--text);
        }

        a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            transition: 0.3s;
        }

        a:hover {
            color: var(--highlight);
            text-decoration: underline;
        }

        small {
            color: #6c4675;
            font-size: 12px;
            display: block;
            margin-top: -8px;
            margin-bottom: 10px;
        }

        @media (max-width: 480px) {
            .register-container {
                width: 90%;
                padding: 30px 25px;
            }

            h2 {
                font-size: 22px;
            }
        }
    </style>
</head>

<body>

    <div class="register-container">
        <h2>🌸 Bloomlux Register 🌸</h2>

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