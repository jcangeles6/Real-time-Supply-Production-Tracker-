<?php
include 'db.php';
session_start();

if (!isset($_SESSION['reset_user_id'])) {
    echo "Unauthorized access.";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newpass = trim($_POST['newpass']);
    $confirmpass = trim($_POST['confirmpass']);

    // ❌ Block spaces & password strength regex
    if (preg_match('/\s/', $newpass)) {
        echo "<p style='color:red;'>❌ Spaces are not allowed in the password.</p>";
    } else {
        $pattern = "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/";

        if ($newpass !== $confirmpass) {
            echo "<p style='color:red;'>❌ Passwords do not match.</p>";
        } elseif (!preg_match($pattern, $newpass)) {
            echo "<p style='color:red;'>❌ Password must be at least 8 characters, include uppercase, lowercase, number, and special character.</p>";
        } else {
            $hashed = password_hash($newpass, PASSWORD_DEFAULT);
            $user_id = $_SESSION['reset_user_id'];

            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashed, $user_id);
            $stmt->execute();

            unset($_SESSION['reset_user_id']);
            echo "<p style='color:green;'>✅ Password updated successfully!</p>";
            echo "<a href='login.php'>Login Now</a>";
        }
    }
}
?>

<h2>Set New Password</h2>
<form method="POST">
    <label>New Password:</label><br>
    <input type="password" name="newpass" id="newpass" required placeholder="Enter your new password"><br><br>

    <label>Confirm Password:</label><br>
    <input type="password" name="confirmpass" id="confirmpass" required placeholder="Confirm your new password"><br><br>

    <button type="submit">Reset Password</button>
</form>

<script>
    const newpassInput = document.getElementById('newpass');
    const confirmInput = document.getElementById('confirmpass');

    [newpassInput, confirmInput].forEach(input => {

        // BLOCK pressing the space key
        input.addEventListener('keydown', (e) => {
            if (e.code === 'Space' || e.key === ' ') {
                e.preventDefault();
            }
        });

        // BLOCK pasting spaces
        input.addEventListener('paste', (e) => {
            let paste = (e.clipboardData || window.clipboardData).getData('text');
            if (paste.includes(' ')) {
                e.preventDefault(); // block the paste if it has spaces
            }
        });

        // Remove any spaces just in case (extra safety)
        input.addEventListener('input', () => {
            input.value = input.value.replace(/\s/g, '');
        });

    });
</script>