document.addEventListener('DOMContentLoaded', () => {
    const notifFeed = document.querySelector('#notif-feed');
    const notifBadge = document.querySelector('#notif-badge');
    const notifDropdown = document.querySelector('#notif-dropdown');
    const notifIcon = document.querySelector('#notif-icon');

    let readNotifications = JSON.parse(localStorage.getItem('readNotifications') || '[]');
    let displayedNotifIds = new Set(readNotifications);
    let timeSpans = [];

   async function updateNotifications() {
    try {
        const stockRes = await fetch('backend/production_page/get_inventory_low_stock.php');
        const stockData = stockRes.ok ? await stockRes.json() : { items: [] };

        const dbRes = await fetch('backend/get_notification.php');
        const dbData = dbRes.ok ? await dbRes.json() : { notifications: [] };

        const allNotifs = [];

        // --- Convert current low-stock items into notifications
        stockData.items.forEach(item => {
            allNotifs.push({
                id: `stock-${item.id}`,
                text: item.quantity > 0
                    ? `⚠️ ${item.name} stock is low! (Available: ${item.quantity})`
                    : `❌ ${item.name} is out of stock!`,
                timestamp: new Date().toISOString(),
                type: 'low-stock',
                isUnread: true
            });
        });

        // --- Add DB notifications (skip deleted)
        dbData.notifications.forEach(n => {
            if (n.type === 'deleted') return;
            allNotifs.push({
                id: n.user_notification_id,
                text: n.message,
                timestamp: n.created_at,
                type: n.type,
                batchId: n.batch_id,
                isUnread: n.is_read == 0
            });
        });

        // --- Sort latest 10
        allNotifs.sort((a, b) => new Date(b.timestamp) - new Date(a.timestamp));
        const latestNotifs = allNotifs.slice(0, 10);

        // --- Rebuild feed
        notifFeed.innerHTML = '';
        timeSpans = [];

        const todayHeader = document.createElement('li');
        todayHeader.classList.add('notif-header');
        todayHeader.innerHTML = `<span>TODAY</span><button class="view-all-btn">View All</button>`;
        todayHeader.querySelector('button').addEventListener('click', () => window.location.href = 'notification.php');
        notifFeed.appendChild(todayHeader);

        latestNotifs.forEach(n => {
            const li = document.createElement('li');
            li.dataset.id = n.id;
            if (n.batchId) li.dataset.batchId = n.batchId;

            li.textContent = n.text;
            if (n.type) li.classList.add(n.type.replace('_', '-'));

            const timeSpan = document.createElement('span');
            timeSpan.textContent = getRelativeTime(new Date(n.timestamp));
            timeSpan.dataset.timestamp = n.timestamp;
            timeSpan.style.cssText = 'font-size: 0.7rem; color: #888; float: right;';
            li.appendChild(timeSpan);
            timeSpans.push(timeSpan);

            if (n.isUnread && !readNotifications.includes(n.id)) li.classList.add('new-notif');

            notifFeed.appendChild(li);
        });

        // --- Update badge
        const totalUnread = notifFeed.querySelectorAll('li.new-notif').length;
        notifBadge.style.display = totalUnread ? 'inline-block' : 'none';
        notifBadge.textContent = totalUnread > 99 ? '99+' : totalUnread;

    } catch (err) {
        console.error('Error updating notifications:', err);
    }
}

    updateNotifications();
    setInterval(updateNotifications, 3000);

    // --- Auto-update timestamps
    setInterval(() => {
        timeSpans.forEach(span => {
            span.textContent = getRelativeTime(new Date(span.dataset.timestamp));
        });
    }, 60000);

    // --- Mark notifications read & update DB
    notifIcon.addEventListener('click', () => {
        const isVisible = notifDropdown.style.display === 'block';
        notifDropdown.style.display = isVisible ? 'none' : 'block';

        if (!isVisible) {
            const visibleNotifs = Array.from(notifFeed.querySelectorAll('li.new-notif'));
            const notifIds = visibleNotifs.map(li => li.dataset.id).filter(id => id);

            visibleNotifs.forEach(li => li.classList.remove('new-notif'));
            readNotifications = Array.from(new Set([...readNotifications, ...notifIds]));
            localStorage.setItem('readNotifications', JSON.stringify(readNotifications));

            notifBadge.style.display = 'none';

            if (notifIds.length > 0) {
                fetch('backend/mark_notification_read.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ notification_ids: notifIds })
                }).then(res => res.json())
                  .then(data => { if (!data.success) console.error('Failed to mark notifications as read'); })
                  .catch(err => console.error(err));
            }
        }
    });

    function getRelativeTime(date) {
        const now = new Date();
        const diff = Math.floor((now - date) / 1000);
        if (diff < 60) return 'Just now';
        if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
        if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`;
        return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }
});
