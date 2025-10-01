<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ingredient = $_POST['ingredient_name'];
    $quantity = $_POST['quantity'];
    $unit = $_POST['unit'];

    $stmt = $conn->prepare("INSERT INTO requests (ingredient_name, quantity, unit, status) VALUES (?, ?, ?, 'pending')");
    $stmt->bind_param("sis", $ingredient, $quantity, $unit);
    $stmt->execute();

    header("Location: supply.php"); // after request, go to supply list
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Ingredient</title>
    <style>
        body { font-family: Arial, sans-serif; background: #fdf9f5; padding: 40px; }
        .form-box {
            background: white; padding: 20px; border-radius: 12px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1); max-width: 400px; margin: auto;
        }
        h2 { color: #5a2d0c; text-align: center; }
        label { display: block; margin: 10px 0 5px; font-weight: bold; }
        input, select, button {
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
        <h2>📝 Request Ingredient</h2>
        <form method="POST">
            <label>Ingredient Name</label>
            <input type="text" name="ingredient_name" required>

            <label>Quantity</label>
            <input type="number" name="quantity" min="1" required>

            <label>Unit</label>
            <select name="unit" required>
                <option value="kg">Kilograms (kg)</option>
                <option value="g">Grams (g)</option>
                <option value="L">Liters (L)</option>
                <option value="ml">Milliliters (ml)</option>
                <option value="pcs">Pieces</option>
            </select>

            <button type="submit">Submit Request</button>
        </form>
    </div>
</body>
</html>
