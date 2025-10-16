document.addEventListener('DOMContentLoaded', async () => {
    // 1️⃣ Top-Selling Products Chart
    const ctx = document.getElementById('topSellingChart');
    try {
        const res = await fetch('backend/get_inventory_overview.php');
        const data = await res.json();

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.labels,
                datasets: [{
                    label: 'Units Sold',
                    data: data.values,
                    backgroundColor: data.colors,
                    borderRadius: 5
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: ctx => `${ctx.label}: ${ctx.raw} sold`
                        }
                    }
                },
                scales: {
                    x: { beginAtZero: true }
                }
            }
        });
    } catch (err) {
        console.error('Failed to load top-selling products:', err);
    }

    // 2️⃣ Dashboard Stats Boxes
    async function updateInventoryOverview() {
        try {
            const res = await fetch('backend/get_home_stats.php');
            const data = await res.json();

            if (!data.success) {
                console.error('Failed to fetch home stats:', data.error || 'Unknown error');
                return;
            }

            // Update the dashboard numbers
            document.querySelectorAll('.stat-box')[0].querySelector('h3').textContent = data.materials;
            document.querySelectorAll('.stat-box')[1].querySelector('h3').textContent = data.inProduction;
            document.querySelectorAll('.stat-box')[2].querySelector('h3').textContent = data.completed;

        } catch (err) {
            console.error('Error fetching home stats:', err);
        }
    }

    // Initial load
    updateInventoryOverview();

    // Update every 5 seconds
    setInterval(updateInventoryOverview, 5000);
});
