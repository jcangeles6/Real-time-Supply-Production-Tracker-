<?php
// VIEW STOCK BUTTON PAGE IN ADD STOCK PAGE

session_start();
include '../db.php'; // Database connection

include 'update_batch_status.php'; // Update batch statuses

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Redirect if no ID
if (!isset($_GET['id'])) {
    header("Location: add_stock.php");
    exit();
}

$id = intval($_GET['id']);

// Fetch inventory item
$stmt = $conn->prepare("SELECT * FROM inventory WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$inventory = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$inventory) {
    echo "❌ Inventory item not found!";
    exit();
}

// Fetch batches for this inventory
$batch_stmt = $conn->prepare("
    SELECT * FROM inventory_batches
    WHERE inventory_id = ?
    ORDER BY created_at DESC
");
$batch_stmt->bind_param("i", $id);
$batch_stmt->execute();
$batches = $batch_stmt->get_result();
$batch_stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>🌸 View Stock - <?= htmlspecialchars($inventory['item_name']); ?></title>
    <link rel="stylesheet" href="../css/add_stock.css">
    <style>
        /* 🌸 BloomLux Glassmorphic Table Theme */
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

        /* 🌺 Background Layers */
        body::before {
            content: "";
            position: absolute;
            inset: 0;
            background: url('https://thumbs.dreamstime.com/b/beautiful-colorful-meadow-wild-flowers-floral-background-landscape-purple-pink-sunset-blurred-soft-pastel-magical-332027305.jpg')
                no-repeat center/cover;
            filter: blur(8px) brightness(0.7);
            transform: scale(1.05);
            z-index: 0;
        }

        body::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(120deg, rgba(255, 179, 236, 0.25), rgba(211, 164, 255, 0.25));
            z-index: 1;
            animation: hueShift 12s infinite alternate ease-in-out;
        }

        /* 🌼 Main Glass Container */
        .main {
            position: relative;
            z-index: 2;
            width: 90%;
            max-width: 1100px;
            margin: 60px auto;
            padding: 40px 50px;
            background: rgba(255, 255, 255, 0.12);
            border-radius: 24px;
            backdrop-filter: blur(18px) saturate(180%);
            -webkit-backdrop-filter: blur(18px) saturate(180%);
            box-shadow: 0 10px 30px rgba(0,0,0,0.3),
                        0 0 20px rgba(255, 179, 236, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.25);
            animation: fadeIn 0.7s ease;
        }

        /* 🌷 Title */
        h1 {
            text-align: center;
            color: #fff;
            font-weight: 700;
            margin-bottom: 30px;
            text-shadow: 0 0 15px rgba(255, 179, 236, 0.6);
        }

        /* 🩷 Back Button */
        .back-btn {
            display: inline-block;
            background: linear-gradient(120deg, #ffb3ec, #d3a4ff);
            color: #2e1a2e;
            padding: 10px 20px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 600;
            box-shadow: 0 0 15px rgba(255, 179, 236, 0.3);
            transition: 0.3s ease;
            margin-bottom: 20px;
        }
        .back-btn:hover {
            background: linear-gradient(120deg, #ffc7f5, #e3baff);
            transform: scale(1.05);
            box-shadow: 0 0 25px rgba(255, 179, 236, 0.6);
        }

        /* 🌸 Table */
        table {
            border-collapse: collapse;
            width: 100%;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
            animation: fadeIn 0.5s ease-in-out;
        }

        table thead th {
            background: linear-gradient(120deg, #ffb3ec, #d3a4ff);
            color: #2e1a2e;
            padding: 14px 16px;
            font-size: 1rem;
            font-weight: bold;
            text-align: center;
        }

        table tbody td {
            background: rgba(255, 255, 255, 0.1);
            text-align: center;
            padding: 14px 12px;
            color: #fff;
            font-size: 0.95rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.15);
        }

        table tbody tr:nth-child(even) td {
            background: rgba(255, 255, 255, 0.08);
        }

        table tbody tr:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: scale(1.01);
            transition: 0.25s ease-in-out;
            box-shadow: 0 0 20px rgba(255, 179, 236, 0.4);
        }

        /* 🌼 Table Links */
        table a {
            color: #ffb3ec;
            font-weight: 600;
            text-decoration: none;
            transition: 0.2s ease;
        }
        table a:hover {
            text-decoration: underline;
            color: #ffd6fa;
        }

        /* 🌺 Status Colors */
        .status-expired { color: #ff7b7b; font-weight: bold; }
        .status-near { color: #ffc85f; font-weight: bold; }
        .status-fresh { color: #b0ffb0; font-weight: bold; }
        .status-none { color: #cfcfcf; font-weight: bold; }

        /* ✨ Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes hueShift {
            0% { filter: hue-rotate(0deg); }
            100% { filter: hue-rotate(40deg); }
        }

        /* 📱 Responsive Table */
        @media (max-width: 768px) {
            .main {
                width: 95%;
                padding: 25px;
            }

            table {
                width: 100%;
                font-size: 0.85rem;
            }
            table thead {
                display: none;
            }
            table tbody tr {
                display: block;
                margin-bottom: 15px;
                background: rgba(255, 255, 255, 0.12);
                border-radius: 12px;
                padding: 10px;
            }
            table tbody td {
                display: flex;
                justify-content: space-between;
                text-align: right;
                padding: 8px 10px;
                border: none;
            }
            table tbody td::before {
                content: attr(data-label);
                font-weight: bold;
                color: #ffb3ec;
                text-align: left;
            }
        }

    </style>
</head>
<body>
<div class="main">
    <a href="add_stock.php" class="back-btn">⬅ Back to Inventory</a>
    <h1>📦 View Stock for "<?= htmlspecialchars($inventory['item_name']); ?>"</h1>

    <?php if ($batches->num_rows === 0): ?>
        <p>No batches found for this item.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Batch ID</th>
                    <th>Quantity</th>
                    <th>Expiration Date</th>
                    <th>Status</th>
                    <th>Created At</th>
                    <th>Updated At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($batch = $batches->fetch_assoc()): ?>
                    <?php
                    // Freshness calculation
                    $today = new DateTime();
                    $today->setTime(0, 0, 0);
                    $near_expiry_days = isset($inventory['near_expiry_days']) ? (int)$inventory['near_expiry_days'] : 7;

                    if ($batch['expiration_date'] && $batch['expiration_date'] != '0000-00-00') {
                        $exp_date = new DateTime($batch['expiration_date']);
                        $exp_date->setTime(0, 0, 0);
                        $interval = (int)$today->diff($exp_date)->format('%r%a');

                        if ($interval < 0) $freshness = 'Expired';
                        elseif ($interval <= $near_expiry_days) $freshness = 'Near Expired';
                        else $freshness = 'Fresh';
                    } else {
                        $freshness = 'No Expiry';
                    }

                    // Assign class for color
                    $class = match($freshness) {
                        'Expired' => 'status-expired',
                        'Near Expired' => 'status-near',
                        'Fresh' => 'status-fresh',
                        default => 'status-none'
                    };
                    ?>
                    <tr>
                        <td><?= $batch['id'] ?></td>
                        <td><?= $batch['quantity'] ?></td>
                        <td><?= ($batch['expiration_date'] && $batch['expiration_date'] != '0000-00-00') ? $batch['expiration_date'] : '-' ?></td>
                        <td><span class="<?= $class ?>"><?= $freshness ?></span></td>
                        <td><?= date("M d, Y, h:i A", strtotime($batch['created_at'])) ?></td>
                        <td><?= date("M d, Y, h:i A", strtotime($batch['updated_at'])) ?></td>
                        <td>
                            <a href="edit_batch.php?id=<?= $batch['id'] ?>&inventory_id=<?= $id ?>">✏️ Edit</a> |
                            <a href="delete_batch.php?id=<?= $batch['id'] ?>&inventory_id=<?= $id ?>" onclick="return confirm('Are you sure?');">🗑️ Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
</body>
</html>
