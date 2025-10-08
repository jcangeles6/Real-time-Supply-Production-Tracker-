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
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #fff5e1, #fce4d6);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .form-box {
            background: #fffaf0;
            padding: 40px 30px;
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(139, 69, 19, 0.2);
            width: 380px;
            text-align: center;
        }

        h2 {
            color: #8b4513;
            font-size: 24px;
            margin-bottom: 25px;
        }

        label {
            display: block;
            text-align: left;
            font-weight: 600;
            color: #5a2d0c;
            margin-bottom: 6px;
            margin-top: 10px;
        }

        input, select {
            width: 95%;
            padding: 12px;
            border: 1px solid #d2b48c;
            border-radius: 8px;
            margin-bottom: 15px;
            font-size: 15px;
            background-color: #fffdf8;
        }

        input:focus, select:focus {
            outline: none;
            border-color: #8b4513;
            box-shadow: 0 0 5px rgba(139, 69, 19, 0.4);
        }

        button {
            width: 102%;
            padding: 12px;
            background-color: #8b4513;
            color: white;
            font-weight: bold;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: 0.3s;
        }

        button:hover {
            background-color: #a0522d;
        }

        .back-btn {
            display: inline-block;
            margin-top: 12px;
            padding: 10px 15px;
            background-color: #d2b48c;
            color: #4b2e05;
            text-decoration: none;
            font-weight: bold;
            border-radius: 8px;
            transition: 0.3s;
        }

        .back-btn:hover {
            background-color: #b8860b;
            color: white;
        }

        .emoji {
            font-size: 35px;
            margin-bottom: 8px;
        }
    </style>
</head>
<body>
    <div class="form-box">
        <div class="emoji">🥖</div>
        <h2>Request Ingredient</h2>
        <form method="POST">
            <label>Ingredient Name</label>
            <input type="text" name="ingredient_name" placeholder="e.g. Flour" required>

            <label>Quantity</label>
            <input type="number" name="quantity" min="1" placeholder="e.g. 5" required>

            <label>Unit</label>
            <select name="unit" required>
                <option value="">Select Unit</option>
                <option value="kg">Kilograms (kg)</option>
                <option value="g">Grams (g)</option>
                <option value="L">Liters (L)</option>
                <option value="ml">Milliliters (ml)</option>
                <option value="pcs">Pieces</option>
            </select>

            <button type="submit">Submit Request</button>
        </form>

        <!-- Back to Home Button -->
        <a href="supply.php" class="back-btn">⬅ Back to Supply</a>
    </div>
</body>
</html>
