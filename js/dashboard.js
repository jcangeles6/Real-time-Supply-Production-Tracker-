// home_notifications.js
document.addEventListener('DOMContentLoaded', () => {
    const notifDashboard = document.querySelector('#notifications-list'); // Dashboard for stock
    const productionSchedule = document.querySelector('#production-schedule-list'); // Production schedule

    // Add click event to redirect to production.php
    if (viewProductionBtn) {
        viewProductionBtn.addEventListener('click', () => {
            window.location.href = 'production.php';
        });
    }

    async function updateHomeNotifications() {
        // ... your existing code
    }

    async function updateHomeNotifications() {
        try {
            // --- Fetch stock data ---
            const stockRes = await fetch('get_stock.php');
            const stockData = stockRes.ok ? await stockRes.json() : { items: [] };

            // --- Update notification dashboard ---
            notifDashboard.innerHTML = '';
            Object.values(stockData.items || []).forEach(item => {
                // Low stock
                if (item.quantity <= item.threshold) {
                    const li = document.createElement('li');
                    li.textContent = `⚠️ ${item.name} stock is low! (Available: ${item.quantity})`;
                    li.classList.add('low-stock');
                    notifDashboard.appendChild(li);
                }

              // Replenished stock
            if (item.status && item.status.toLowerCase() === 'replenished') {
                const li = document.createElement('li');
                li.textContent = `📦 ${item.name} stock has been replenished! (Available: ${item.quantity})`;
                li.classList.add('replenished');
                notifDashboard.appendChild(li);
            }

            });

            // --- Fetch production data ---
            const prodRes = await fetch('get_production.php'); 
            const prodData = prodRes.ok ? await prodRes.json() : [];

            // --- Update production schedule ---
            productionSchedule.innerHTML = '';
            prodData.forEach(batch => {
                const li = document.createElement('li');
                const formattedTime = new Date(batch.scheduled_at).toLocaleString('en-US', {
                    month: 'short',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });

                // Normalize status
                const normalizedStatus = batch.status.toLowerCase();
                let statusText = '';
                let icon = '';

                if (normalizedStatus === 'scheduled') {
                    statusText = 'Scheduled';
                    icon = '🗓️';
                } else if (normalizedStatus === 'in_progress' || normalizedStatus === 'started') {
                    statusText = 'Started';
                    icon = '🛠️';
                } else if (normalizedStatus === 'completed') {
                    statusText = 'Completed';
                    icon = '✔️';
                } else {
                    statusText = batch.status;
                    icon = 'ℹ️';
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
