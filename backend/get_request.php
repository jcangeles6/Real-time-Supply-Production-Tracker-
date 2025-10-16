    <?php
    session_start();    
    include '../db.php'; // Database connection

    // Only allow logged-in users
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Not logged in']);
        exit();
    }

    // Fetch all requests
    $sql = "SELECT id, user_id, ingredient_name, quantity, notes, unit, status, requested_at 
            FROM requests 
            ORDER BY requested_at DESC";
    $result = $conn->query($sql);

    $requests = [];
    $total = $pending = $approved = $denied = 0;

    while ($row = $result->fetch_assoc()) {
        $requests[] = $row;
        switch (strtolower($row['status'])) {
            case 'pending': $pending++; break;
            case 'approved': $approved++; break;
            case 'denied': $denied++; break;
        }
    }

    $total = count($requests);

    echo json_encode([
        'success' => true,
        'requests' => $requests,
        'summary' => [
            'total' => $total,
            'pending' => $pending,
            'approved' => $approved,
            'denied' => $denied
        ]
    ]);
