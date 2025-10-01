<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_name = $_POST['product_name'];
    $quantity = $_POST['quantity'];

    $stmt = $conn->prepare("INSERT INTO batches (product_name, quantity, status) VALUES (?, ?, 'scheduled')");
    $stmt->bind_param("si", $product_name, $quantity);
    $stmt->execute();

    header("Location: production.php"); // go back to dashboard
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Batch</title>
    <style>
        body { font-family: Arial, sans-serif; background: #fdf9f5; padding: 40px; }
        .form-box {
            background: white; padding: 20px; border-radius: 12px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1); max-width: 400px; margin: auto;
        }
        h2 { color: #5a2d0c; text-align: center; }
        label { display: block; margin: 10px 0 5px; font-weight: bold; }
        input, button {
            width: 100%; padding: 10px; margin-bottom: 15px;
            border: 1px solid #ccc; border-radius: 8px;
        }
        button {
            background: #8b4513; color: white; font-weight: bold;
            border: none; cursor: pointer;
        }
        button:hover { background: #5a2d0c; }
    </style>
</head>
<body>
    <div class="form-box">
        <h2>➕ Add New Batch</h2>
        <form method="POST">
            <label>Product Name</label>
            <input type="text" name="product_name" required>

            <label>Quantity</label>
            <input type="number" name="quantity" min="1" required>

            <button type="submit">Save Batch</button>
        </form>
    </div>
</body>
</html>
