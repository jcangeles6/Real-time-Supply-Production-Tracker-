document.addEventListener('DOMContentLoaded', () => {
    const notifDashboard = document.querySelector('#notifications-list');
    const productionSchedule = document.querySelector('#production-schedule-list');
    const viewProductionBtn = document.querySelector('#viewProductionBtn');
    let lastStatuses = {}; // Keep previous item statuses and timestamps

    if (viewProductionBtn) {
        viewProductionBtn.addEventListener('click', () => {
            window.location.href = 'production.php';
        });
    }

    async function updateHomeNotifications() {
        try {
            const stockRes = await fetch('get_stock.php');
            const stockData = stockRes.ok ? await stockRes.json() : { items: [] };

            const newStatuses = {}; // Temporary tracker for this update

            Object.values(stockData.items || []).forEach(item => {
                let status = '';
                if (item.quantity === 0) {
                    status = 'out';
                } else if (item.quantity <= item.threshold) {
                    status = 'low';
                } else {
                    status = 'ok';
                }

                // If status changed, update timestamp
                if (!lastStatuses[item.name] || lastStatuses[item.name].status !== status) {
                    lastStatuses[item.name] = {
                        status,
                        time: new Date().toLocaleTimeString([], {
                            hour: '2-digit',
                            minute: '2-digit'
                        })
                    };
                }

                newStatuses[item.name] = lastStatuses[item.name]; // Keep current
            });

           // --- Update notification dashboard using DB timestamps ---
            notifDashboard.innerHTML = '';
            Object.values(stockData.items || []).forEach(item => {
                let li = document.createElement('li');
                const formattedTime = new Date(item.updated_at).toLocaleTimeString([], {
                    hour: '2-digit',
                    minute: '2-digit'
                });

            // Show only one status — out of stock OR low stock
            if (item.quantity === 0) {
                li.innerHTML = `❌ ${item.name} is out of stock! <span class="notif-time">(${formattedTime})</span>`;
                li.classList.add('out-of-stock');
            } else if (item.quantity <= item.threshold) {
                li.innerHTML = `⚠️ ${item.name} stock is low! (Available: ${item.quantity}) <span class="notif-time">(${formattedTime})</span>`;
                li.classList.add('low-stock');
            } else {
                return; // Skip normal stock
            }

            notifDashboard.appendChild(li);
            });


            lastStatuses = newStatuses; // Keep latest state

            // --- Production schedule update ---
            const prodRes = await fetch('get_production.php');
            const prodData = prodRes.ok ? await prodRes.json() : [];

            productionSchedule.innerHTML = '';
            prodData.forEach(batch => {
                const li = document.createElement('li');
                const formattedTime = new Date(batch.scheduled_at).toLocaleString('en-US', {
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });

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

                // Use innerHTML to keep the <span> for timestamp
                li.innerHTML = `${icon} ${batch.product_name} - ${statusText} <span class="prod-time">(${formattedTime})</span>`;

                productionSchedule.appendChild(li);
                            });

        } catch (err) {
            console.error('Error updating home notifications:', err);
        }
    }

    updateHomeNotifications();
    setInterval(updateHomeNotifications, 5000);
});
