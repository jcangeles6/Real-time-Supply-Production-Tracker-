<?php
include 'backend/init.php';

if (!isset($_SESSION['user_id'])) {
    exit("Not logged in");
}
$user_id = $_SESSION['user_id'];

$sql = "SELECT r.id, r.quantity, r.notes, r.status, r.created_at, i.name AS ingredient_name
        FROM requests r
        JOIN ingredients i ON r.ingredient_id = i.id
        WHERE r.user_id = ?
        ORDER BY r.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

if ($result->num_rows > 0): ?>
    <table>
        <tr>
            <th>Ingredient</th>
            <th>Quantity</th>
            <th>Notes</th>
            <th>Status</th>
            <th>Date Requested</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['ingredient_name']); ?></td>
                <td><?= htmlspecialchars($row['quantity']); ?></td>
                <td><?= htmlspecialchars($row['notes']); ?></td>
                <td>
                    <span class="status <?= strtolower($row['status']); ?>">
                        <?= htmlspecialchars($row['status']); ?>
                    </span>
                </td>
                <td><?= htmlspecialchars($row['created_at']); ?></td>
            </tr>
        <?php endwhile; ?>
    </table>
<?php else: ?>
    <p style="color:#8b4513;">You have not made any requests yet.</p>
<?php endif; ?>
