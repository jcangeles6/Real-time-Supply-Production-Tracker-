// js/notifications-feed.js
document.addEventListener('DOMContentLoaded', () => {
    const notifFeed = document.querySelector('#notif-feed');
    const notifBadge = document.querySelector('#notif-badge');
    const notifDropdown = document.querySelector('#notif-dropdown');
    const notifIcon = document.querySelector('#notif-icon');

    let readNotifications = JSON.parse(localStorage.getItem('readNotifications') || '[]');

    async function updateNotifications() {
        try {
            const allNotifs = [];
            const batchMap = new Map(); // Track latest batch status per product

            // --- Low stock ---
            const stockRes = await fetch('get_stock.php');
            const stockData = stockRes.ok ? await stockRes.json() : { items: [] };
            Object.values(stockData.items || []).filter(i => i.quantity <= i.threshold)
                .forEach(item => allNotifs.push({
                    text: `⚠️ ${item.name} stock is low! (Available: ${item.quantity})`,
                    timestamp: new Date().toISOString(),
                    type: 'low-stock'
                }));

            // --- Notifications from DB ---
            const notifRes = await fetch('backend/get_notification.php');
            const notifData = notifRes.ok ? await notifRes.json() : { notifications: [] };

            notifData.notifications.forEach(n => {
                const msg = n.message;
                const match = msg.match(/[\w\s]+ - Batch (Started|Completed)/i);

                if (match) {
                    const productName = msg.split(' - ')[0].replace(/[✔️🛠️]/g, '').trim();
                    const existing = batchMap.get(productName);

                    // Keep only the latest timestamped status
                    if (!existing || new Date(n.created_at) > new Date(existing.timestamp)) {
                        batchMap.set(productName, {
                            text: msg,
                            timestamp: n.created_at,
                            type: n.type,
                            id: n.id
                        });
                    }
                } else {
                    allNotifs.push({
                        text: msg,
                        timestamp: n.created_at,
                        type: n.type,
                        id: n.id
                    });
                }
            });

            // --- Add only latest batch notifications ---
            batchMap.forEach(v => allNotifs.push(v));

            // --- Sort and take latest 10 ---
            allNotifs.sort((a, b) => new Date(b.timestamp) - new Date(a.timestamp));
            const latestNotifs = allNotifs.slice(0, 10);

            // --- Build notification feed ---
            notifFeed.innerHTML = '';

            const todayHeader = document.createElement('li');
            todayHeader.classList.add('notif-header');
            todayHeader.dataset.date = 'Today';
            const headerSpan = document.createElement('span');
            headerSpan.textContent = 'TODAY';
            todayHeader.appendChild(headerSpan);

            const viewAllBtn = document.createElement('button');
            viewAllBtn.textContent = 'View All';
            viewAllBtn.classList.add('view-all-btn');
            viewAllBtn.style.cssText = `
                background: #ff4d4d; color: #fff; border: none;
                border-radius: 5px; padding: 2px 8px; cursor: pointer;
                font-size: 0.75rem; margin-left: 10px;
            `;
            viewAllBtn.addEventListener('click', () => window.location.href = 'notification.html');
            todayHeader.appendChild(viewAllBtn);
            notifFeed.appendChild(todayHeader);

            let lowStockDividerAdded = false;
            const productionDivider = document.createElement('li');
            productionDivider.classList.add('notif-divider', 'production-update');
            productionDivider.style.cssText = `
                text-align: center; color: #888; font-weight: 600;
                font-size: 0.8rem; padding: 5px 0;
            `;
            productionDivider.textContent = 'Production Update';

            latestNotifs.forEach(n => {
                const li = document.createElement('li');
                li.dataset.batchId = n.batchId || '';
                li.dataset.id = n.id || ''; // <-- ensures we have the ID for marking as read
                li.textContent = n.text;

                if (n.type === 'low-stock') li.classList.add('low-stock');
                else if (n.type === 'new-stock') li.classList.add('new-stock');
                else if (n.type === 'replenished') li.classList.add('replenished');
                else if (n.type === 'in_progress') li.classList.add('notif-in_progress');
                else if (n.type === 'completed') li.classList.add('notif-completed');

                if (!readNotifications.includes(n.text)) li.classList.add('new-notif');

                if (n.type === 'low-stock') {
                    notifFeed.appendChild(li);
                } else {
                    if (!lowStockDividerAdded) {
                        notifFeed.appendChild(productionDivider);
                        lowStockDividerAdded = true;
                    }
                    notifFeed.appendChild(li);
                }
            });

            // --- Update badge ---
            const totalUnread = notifFeed.querySelectorAll('li.new-notif').length;
            if (totalUnread > 0) {
                notifBadge.style.display = 'inline-block';
                notifBadge.textContent = totalUnread > 99 ? '99+' : totalUnread;
                notifBadge.classList.remove('pulse');
                void notifBadge.offsetWidth;
                notifBadge.classList.add('pulse');
            } else {
                notifBadge.style.display = 'none';
            }

        } catch (err) {
            console.error('Error updating notifications:', err);
        }
    }

    updateNotifications();
    setInterval(updateNotifications, 5000);

    notifIcon.addEventListener('click', () => {
        const isVisible = notifDropdown.style.display === 'block';
        notifDropdown.style.display = isVisible ? 'none' : 'block';

        if (!isVisible) {
            const visibleNotifs = Array.from(notifFeed.querySelectorAll('li.new-notif'));
            visibleNotifs.forEach(li => li.classList.remove('new-notif'));

            const notifIds = visibleNotifs.map(li => li.dataset.id).filter(id => id);

            // Update localStorage
            readNotifications = Array.from(new Set([
                ...readNotifications,
                ...visibleNotifs.map(li => li.textContent)
            ]));
            localStorage.setItem('readNotifications', JSON.stringify(readNotifications));
            notifBadge.style.display = 'none';

            // --- CALL PHP TO MARK AS READ ---
            if (notifIds.length > 0) {
                fetch('backend/mark_notification_read.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ notification_ids: notifIds })
                })
                .then(res => res.json())
                .then(data => {
                    if (!data.success) console.error('Failed to mark notifications as read');
                })
                .catch(err => console.error(err));
            }
        }
    });
});
