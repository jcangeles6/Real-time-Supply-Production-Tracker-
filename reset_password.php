<?php
include 'db.php';
session_start();

if (!isset($_SESSION['reset_user_id'])) {
    echo "Unauthorized access.";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newpass = $_POST['newpass'];
    $confirmpass = $_POST['confirmpass'];

    if ($newpass !== $confirmpass) {
        echo "<p style='color:red;'>❌ Passwords do not match.</p>";
    } else {
        $hashed = password_hash($newpass, PASSWORD_DEFAULT);
        $user_id = $_SESSION['reset_user_id'];

        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashed, $user_id);
        $stmt->execute();

        // Clean up session
        unset($_SESSION['reset_user_id']);
        echo "<p style='color:green;'>✅ Password updated successfully!</p>";
        echo "<a href='login.php'>Login Now</a>";
    }
}
?>

<h2>Set New Password</h2>
<form method="POST">
    <label>New Password:</label><br>
    <input type="password" name="newpass" required><br><br>
    
    <label>Confirm Password:</label><br>
    <input type="password" name="confirmpass" required><br><br>

    <button type="submit">Reset Password</button>
</form>
