<?php
include 'db.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Use a prepared statement for safety
    $stmt = $conn->prepare("DELETE FROM batches WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    // Redirect back to production dashboard
    header("Location: production.php");
    exit();
} else {
    echo "⚠️ No batch ID provided.";
}
