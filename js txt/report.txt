// Update clock every second
function updateClock() {
    const now = new Date();
    document.getElementById('clock').innerText =
        "📅 " + now.toLocaleDateString() + " | ⏰ " + now.toLocaleTimeString();
}
setInterval(updateClock, 1000);
window.onload = updateClock;

// Print report
function printReport() {
    window.print();
}

// Export table as CSV
function exportCSV() {
    const table = document.querySelector("table");
    let csv = [];

    for (const row of table.rows) {
        const cols = Array.from(row.cells).map(cell => {
            let text = cell.innerText.replace(/"/g, '""');
            return `"${text}"`;
        });
        csv.push(cols.join(","));
    }

    const blob = new Blob([csv.join("\n")], { type: "text/csv" });
    const link = document.createElement("a");
    link.href = URL.createObjectURL(blob);
    link.download = "daily_report.csv";
    link.click();
}

async function fetchDailyReport() {
    try {
        const res = await fetch('backend/get_daily_report.php');
        const data = await res.json();

        // Update summary cards
        document.querySelector('.summary .card:nth-child(1) h2').innerText = data.total_completed;
        document.querySelector('.summary .card:nth-child(2) h2').innerText = data.total_quantity;

        // Update table body only
        const tbody = document.querySelector("table tbody");
        let html = "";

        if (data.batches.length > 0) {
            data.batches.forEach(row => {
                html += `
                    <tr>
                        <td>${row.id}</td>
                        <td>${row.product_name}</td>
                        <td>${row.quantity}</td>
                        <td style="text-align: left;">${row.materials}</td>
                        <td>${new Date(row.completed_at).toLocaleString()}</td>
                    </tr>
                `;
            });
        } else {
            html = `
                <tr>
                    <td colspan="5">No batches completed today.</td>
                </tr>
            `;
        }

        tbody.innerHTML = html;

    } catch (err) {
        console.error('Failed to fetch daily report:', err);
    }
}

// Initial fetch
fetchDailyReport();

// Auto refresh every 30 seconds
setInterval(fetchDailyReport, 30000);
