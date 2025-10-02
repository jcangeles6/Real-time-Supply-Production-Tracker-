<?php
include 'backend/init.php';

// Restrict access to admin
if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'admin') {
    die("Access denied. Only admin can reset passwords.");
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if form fields are set
    if (!empty($_POST['id']) && !empty($_POST['newpass'])) {
        $id = intval($_POST['id']);
        $newpass = password_hash($_POST['newpass'], PASSWORD_DEFAULT);

        $stmt = $conn->prepare("UPDATE users SET password = ?, reset_requested = 0 WHERE id = ?");
        $stmt->bind_param("si", $newpass, $id);
        if ($stmt->execute()) {
            echo "<p style='color:green;'>✅ Password has been reset.</p><a href='admin_dashboard.php'>Back to Admin Dashboard</a>";
        } else {
            echo "<p style='color:red;'>❌ Database error: " . $conn->error . "</p>";
        }
    } else {
        echo "<p style='color:red;'>❌ Error: Missing user ID or new password.</p>";
    }
    exit();
}

// If not submitted yet, get ID from GET
if (!isset($_GET['id'])) {
    die("❌ No user ID provided.");
}

$id = intval($_GET['id']);
?>

<h2>Reset User Password</h2>
<form method="POST">
    <input type="hidden" name="id" value="<?php echo htmlspecialchars($id); ?>">
    <label>New Password:</label><br>
    <input type="password" name="newpass" required><br><br>
    <button type="submit">Reset Password</button>
</form>
<a href="admin_dashboard.php">Cancel</a>
