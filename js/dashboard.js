// home_notifications.js
document.addEventListener('DOMContentLoaded', () => {
    const notifDashboard = document.querySelector('#notifications-list'); // Dashboard for low stock
    const productionSchedule = document.querySelector('#production-schedule-list'); // Production schedule

    async function updateHomeNotifications() {
        try {
            // --- Fetch stock data ---
            const stockRes = await fetch('get_stock.php');
            const stockData = stockRes.ok ? await stockRes.json() : { items: [] };

            // Update notification dashboard (low stock)
            notifDashboard.innerHTML = '';
            const lowStockItems = Object.values(stockData.items || []).filter(i => i.quantity <= i.threshold);
            lowStockItems.forEach(item => {
                const li = document.createElement('li');
                li.textContent = `⚠️ ${item.name} stock is low! (Available: ${item.quantity})`;
                li.classList.add('low-stock');
                notifDashboard.appendChild(li);
            });

            // --- Fetch production data ---
            const prodRes = await fetch('get_production.php');
            const prodData = prodRes.ok ? await prodRes.json() : [];

            // Update production schedule
            productionSchedule.innerHTML = '';
            prodData.forEach(batch => {
                const li = document.createElement('li');
                const formattedTime = new Date(batch.timestamp).toLocaleString('en-US', {
                    month: 'short',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });

                let statusText = 'Started';
                let icon = '🛠️';
                if (batch.status.toLowerCase().includes('completed')) {
                    statusText = 'Completed';
                    icon = '✔️';
                }

                li.textContent = `${icon} ${batch.product_name} - ${statusText} (${formattedTime})`;
                productionSchedule.appendChild(li);
            });

        } catch (err) {
            console.error('Error updating home notifications:', err);
        }
    }

    updateHomeNotifications();
    setInterval(updateHomeNotifications, 5000); // Refresh every 5 seconds
});
