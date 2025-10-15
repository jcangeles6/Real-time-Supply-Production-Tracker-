document.addEventListener('DOMContentLoaded', async () => {
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
                indexAxis: 'y', // Horizontal bars
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
});



get_inventory_overview.php