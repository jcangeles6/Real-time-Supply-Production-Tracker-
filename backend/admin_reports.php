<?php
session_start();
include '../db.php'; // Database connection

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Check if user is admin
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT is_admin FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($is_admin);
$stmt->fetch();
$stmt->close();

if (!$is_admin) {
    echo "Access denied. Admins only.";
    exit;
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Admin Production Reports</title>
    <link rel="stylesheet" href="../css/admin_report.css">
</head>

<body>
    <div class="sidebar">
        <h2>🌸 BloomLux Admin 🌸</h2>
        <a href="../admin_dashboard.php">🔙 Back to Dashboard </a>
        <a href="../my_requests.php">📋 All Requests</a>
        <a href="add_stock.php">📦 Add Stock</a>
        <a href="../logout.php">🚪 Logout</a>
    </div>

    <div class="main">
        <h1>Production Reports 📊</h1>

        <!-- Summary Cards Row -->
        <div class="summary">
            <div class="card">
                <h2 id="totalCompleted">2</h2>
                <p>Total Completed</p>
            </div>
            <div class="card">
                <h2 id="totalQuantity">2</h2>
                <p>Total Quantity</p>
            </div>
        </div>

        <!-- Report Controls Row (now below summary cards) -->
        <div class="report-controls">
            <div class="controls">
                <label for="reportType">Select Report:</label>
                <select id="reportType">
                    <option value="daily">Daily</option>
                    <option value="weekly">Weekly</option>
                    <option value="monthly">Monthly</option>
                </select>
            </div>

            <div class="buttons">
                <button class="btn" id="printBtn">🖨 Print</button>
                <button class="btn" id="exportCsvBtn">📁 Export</button>
            </div>
        </div>

        <!-- Batches Table -->
        <table>
            <thead>
                <tr>
                    <th>Product Name</th>
                    <th>Quantity</th>
                    <th>Materials Used</th>
                    <th>Completed At</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>

    <script>
        const reportType = document.getElementById('reportType');
        const totalCompletedEl = document.getElementById('totalCompleted');
        const totalQuantityEl = document.getElementById('totalQuantity');
        const tbody = document.querySelector('table tbody');
        const printBtn = document.getElementById('printBtn');

        // --- Fetch and render report ---
        async function fetchReport(type) {
            try {
                const res = await fetch(`get_${type}_report.php`);
                const data = await res.json();

                let dataArray = [];
                if (Array.isArray(data)) dataArray = data;
                else if (data.batches && Array.isArray(data.batches)) dataArray = data.batches;
                else dataArray = Object.values(data);

                tbody.innerHTML = '';
                let totalCompleted = 0;
                let totalQuantity = 0;

                dataArray.forEach(batch => {
                    totalCompleted++;
                    totalQuantity += parseInt(batch.quantity || 0);
                    const row = document.createElement('tr');
                    row.innerHTML = `
                <td>${batch.product_name || 'N/A'}</td>
                <td>${batch.quantity || 0}</td>
                <td>${batch.materials || 'None'}</td>
                <td>${batch.completed_at || ''}</td>
            `;
                    tbody.appendChild(row);
                });

                totalCompletedEl.textContent = totalCompleted;
                totalQuantityEl.textContent = totalQuantity;

            } catch (err) {
                console.error('Error fetching report:', err);
                tbody.innerHTML = '<tr><td colspan="4">Failed to load report.</td></tr>';
                totalCompletedEl.textContent = '0';
                totalQuantityEl.textContent = '0';
            }
        }

        fetchReport(reportType.value);
        reportType.addEventListener('change', () => fetchReport(reportType.value));

        // --- Print Function ---
        printBtn.addEventListener('click', () => {
            const printContents = document.querySelector('.main').innerHTML;
            const originalContents = document.body.innerHTML;

            document.body.innerHTML = printContents;
            window.print();
            document.body.innerHTML = originalContents;
            window.location.reload();
        });

        const exportCsvBtn = document.getElementById('exportCsvBtn');

        exportCsvBtn.addEventListener('click', () => {
            const table = document.querySelector("table");
            let csv = [];

            // Loop through each table row
            for (const row of table.rows) {
                const cols = Array.from(row.cells).map((cell, index) => {
                    let text = cell.innerText;

                    // Materials Used column (adjust index if your table has extra columns)
                    if (cell.getAttribute('data-column') === 'materials' || index === 3) {
                        // Replace commas or existing line breaks with Excel line breaks
                        text = text.replace(/\r?\n|\r/g, "\n"); // normalize existing line breaks
                        text = text.replace(/, /g, "\n"); // split materials with line breaks
                    }

                    // Escape quotes
                    text = text.replace(/"/g, '""');

                    // Wrap in quotes
                    return `"${text}"`;
                });

                csv.push(cols.join(","));
            }

            // Create and download CSV
            const blob = new Blob([csv.join("\r\n")], {
                type: "text/csv;charset=utf-8;"
            });
            const link = document.createElement("a");
            link.href = URL.createObjectURL(blob);
            link.download = `${document.getElementById('reportType').value}_report.csv`;
            link.click();
        });
    </script>
</body>

</html>