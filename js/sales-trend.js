document.addEventListener('DOMContentLoaded', async () => {
    const ctx = document.getElementById('salesTrendChart');

    try {
        const res = await fetch('backend/get_sales_trend.php');
        const data = await res.json();

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.labels,
                datasets: [{
                    label: 'Completed Batches',
                    data: data.values,
                    backgroundColor: data.values.map(v => v > 5 ? '#36a2eb' : '#ff6384'), // Highlight busy days
                    borderRadius: 10,
                    barPercentage: 0.6,
                    categoryPercentage: 0.7
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `${context.raw} batch${context.raw !== 1 ? 'es' : ''} completed`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });
    } catch (err) {
        console.error('Failed to load sales trend:', err);
    }
});
