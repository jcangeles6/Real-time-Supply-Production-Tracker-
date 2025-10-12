<?php
include 'backend/init.php';

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
    <title>🌸 BloomLux Request Material 🌸</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg: #ffb3ecff;
            --card: #f5f0fa;
            --primary: #2e1a2eff;
            --text: #000000ff;
            --highlight: #000000ff;
            --shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--bg);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            color: var(--text);
        }

        .form-box {
            background: var(--card);
            padding: 40px 35px;
            border-radius: 20px;
            box-shadow: var(--shadow);
            width: 100%;
            max-width: 400px;
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .form-box:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(46, 26, 46, 0.15);
        }

        .emoji {
            font-size: 40px;
            margin-bottom: 10px;
        }

        h2 {
            color: var(--primary);
            font-size: 26px;
            font-weight: 700;
            margin-bottom: 20px;
        }

        label {
            display: block;
            text-align: left;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 6px;
            margin-top: 10px;
        }

        input, select {
            width: 94%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 10px;
            margin-bottom: 18px;
            font-size: 15px;
            background-color: #fff;
            transition: 0.3s;
        }

        input:focus, select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 6px rgba(46, 26, 46, 0.3);
        }

        button {
            width: 100%;
            padding: 12px;
            background-color: var(--primary);
            color: white;
            font-weight: 600;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s ease, transform 0.2s ease;
        }

        button:hover {
            background-color: var(--highlight);
            transform: scale(1.03);
        }

        .back-btn {
            display: inline-block;
            margin-top: 15px;
            padding: 10px 15px;
            background-color: #e7d4f9;
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            border-radius: 10px;
            transition: 0.3s;
        }

        .back-btn:hover {
            background-color: var(--primary);
            color: #fff;
        }

        @media (max-width: 500px) {
            .form-box {
                width: 90%;
                padding: 30px;
            }

            h2 {
                font-size: 22px;
            }
        }
    </style>
</head>
<body>
    <div class="form-box">
        <div class="emoji">🌸</div>
        <h2>Request Ingredient</h2>
        <form method="POST">
            <label>Material Name</label>
            <input type="text" name="ingredient_name" placeholder="e.g. Paper" required>

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
