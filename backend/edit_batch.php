<?php
session_start();
include '../db.php';

//EDIT BUTTON SA VIEW STOCK PAGE



if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if (!isset($_GET['id'], $_GET['inventory_id'])) {
    header("Location: add_stock.php");
    exit();
}

$batch_id = intval($_GET['id']);
$inventory_id = intval($_GET['inventory_id']);

// Fetch batch
$stmt = $conn->prepare("SELECT * FROM inventory_batches WHERE id=?");
$stmt->bind_param("i", $batch_id);
$stmt->execute();
$batch = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$batch) {
    echo "❌ Batch not found!";
    exit();
}

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $quantity = floatval($_POST['quantity']);
    $expiration_date = $_POST['expiration_date'] ?? null;

    $batch_status = 'Fresh';
    if ($expiration_date && $expiration_date != '0000-00-00') {
        $today = date('Y-m-d');
        $batch_status = ($expiration_date < $today) ? 'Expired' : 'Fresh';
    }

    $update = $conn->prepare("
        UPDATE inventory_batches
        SET quantity=?, expiration_date=?, status=?, updated_at=NOW()
        WHERE id=?
    ");
    $update->bind_param("dssi", $quantity, $expiration_date, $batch_status, $batch_id);
    $update->execute();
    $update->close();

    // Recalculate inventory total and status
    $total_stmt = $conn->prepare("SELECT SUM(quantity) as total_qty FROM inventory_batches WHERE inventory_id=?");
    $total_stmt->bind_param("i", $inventory_id);
    $total_stmt->execute();
    $total = $total_stmt->get_result()->fetch_assoc()['total_qty'] ?? 0;
    $total_stmt->close();

    // Fetch threshold
    $th_stmt = $conn->prepare("SELECT COALESCE(threshold,10) as threshold FROM stock_thresholds WHERE item_id=?");
    $th_stmt->bind_param("i", $inventory_id);
    $th_stmt->execute();
    $threshold = $th_stmt->get_result()->fetch_assoc()['threshold'] ?? 10;
    $th_stmt->close();

    $status = ($total == 0) ? 'out' : (($total <= $threshold) ? 'low' : 'available');

    $inv_update = $conn->prepare("UPDATE inventory SET quantity=?, status=?, updated_at=NOW() WHERE id=?");
    $inv_update->bind_param("dsi", $total, $status, $inventory_id);
    $inv_update->execute();
    $inv_update->close();

    header("Location: view_stock.php?id=$inventory_id");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>✏️ Edit Batch</title>
    <style>
        /* 🌸 BloomLux Glassmorphic Dashboard Theme */
        body {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: #1e0e1e;
            color: #fff;
            overflow: hidden;
            position: relative;
        }

        /* 🌺 Background image + tint layers */
        body::before {
            content: "";
            position: absolute;
            inset: 0;
            background: url('https://thumbs.dreamstime.com/b/beautiful-colorful-meadow-wild-flowers-floral-background-landscape-purple-pink-sunset-blurred-soft-pastel-magical-332027305.jpg') no-repeat center/cover;
            z-index: 0;
            filter: blur(8px) brightness(0.7);
            transform: scale(1.05);
        }

        body::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(120deg, rgba(255, 179, 236, 0.25), rgba(211, 164, 255, 0.25));
            z-index: 1;
            animation: hueShift 12s infinite alternate ease-in-out;
        }

        /* 🌸 Main glass container */
        .main {
            position: relative;
            z-index: 2;
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(18px) saturate(180%);
            border-radius: 24px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3),
                        0 0 20px rgba(255, 179, 236, 0.2);
            padding: 40px 60px;
            text-align: center;
            width: 400px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            animation: fadeIn 0.7s ease;
        }

        /* 🌼 Text */
        h1 {
            font-size: 1.8em;
            color: #fff;
            margin-bottom: 25px;
            text-shadow: 0 0 10px rgba(255, 179, 236, 0.5);
        }

        /* 🩷 Labels + Inputs */
        label {
            display: block;
            text-align: left;
            margin-top: 15px;
            font-weight: 600;
            color: #fbeaff;
        }

        input {
            width: 100%;
            padding: 10px 12px;
            border: none;
            border-radius: 12px;
            margin-top: 8px;
            background: rgba(255, 255, 255, 0.2);
            color: #fff;
            font-size: 1em;
            backdrop-filter: blur(8px);
            outline: none;
            transition: 0.3s ease;
        }

        input:focus {
            background: rgba(255, 255, 255, 0.3);
            box-shadow: 0 0 8px rgba(255, 179, 236, 0.5);
        }

        /* 💾 Buttons */
        button {
            margin-top: 25px;
            background: linear-gradient(120deg, #ffb3ec, #d3a4ff);
            border: none;
            color: #2e1a2e;
            padding: 10px 25px;
            border-radius: 30px;
            cursor: pointer;
            font-weight: 600;
            font-size: 1em;
            box-shadow: 0 0 15px rgba(255, 179, 236, 0.4);
            transition: 0.3s ease;
        }

        button:hover {
            background: linear-gradient(120deg, #ffc7f5, #e3baff);
            transform: scale(1.05);
            box-shadow: 0 0 20px rgba(255, 179, 236, 0.6);
        }

        /* ⬅ Back button */
        .back-btn {
            display: inline-block;
            margin-bottom: 20px;
            background: linear-gradient(120deg, #ffb3ec, #d3a4ff);
            color: #2e1a2e;
            padding: 8px 18px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            box-shadow: 0 0 15px rgba(255, 179, 236, 0.3);
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            background: linear-gradient(120deg, #ffc7f5, #e3baff);
            transform: scale(1.05);
            box-shadow: 0 0 25px rgba(255, 179, 236, 0.6);
        }

        /* ✨ Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes hueShift {
            0% { filter: hue-rotate(0deg); }
            100% { filter: hue-rotate(40deg); }
        }

    </style>
</head>
<body>
<div class="main">
    <a href="view_stock.php?id=<?= $inventory_id ?>" class="back-btn">⬅ Back</a>
    <h1>✏️ Edit Batch #<?= $batch_id ?></h1>
    <form method="POST">
        <label>Quantity</label>
        <input type="number" step="any" min="0" name="quantity" value="<?= $batch['quantity'] ?>" required>
        <label>Expiration Date</label>
        <input type="date" name="expiration_date" value="<?= ($batch['expiration_date'] != '0000-00-00') ? $batch['expiration_date'] : '' ?>">
        <button type="submit">Update Batch</button>
    </form>
</div>
</body>
</html>
