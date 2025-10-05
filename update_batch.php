<?php
include 'db.php';

if (isset($_GET['id']) && isset($_GET['status'])) {
    $id = $_GET['id'];
    $status = $_GET['status'];

    // Validate allowed statuses
    $allowed = ['scheduled', 'in_progress', 'completed'];
    if (!in_array($status, $allowed)) {
        die("❌ Invalid status value.");
    }

    // If completed, record the timestamp
    if ($status === 'completed') {
        $stmt = $conn->prepare("UPDATE batches SET status = ?, completed_at = NOW() WHERE id = ?");
    } else {
        $stmt = $conn->prepare("UPDATE batches SET status = ? WHERE id = ?");
    }

    $stmt->bind_param("si", $status, $id);
    $stmt->execute();
    $stmt->close();

    header("Location: production.php"); // return to dashboard
    exit();
} else {
    echo "⚠️ Missing parameters.";
}
?>
