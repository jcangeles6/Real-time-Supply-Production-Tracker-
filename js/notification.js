/* -------- NOTIFICATION DROPDOWN (PAG PININDOT YUNG NOTIF ICON) -------- */
document.addEventListener('DOMContentLoaded', () => {
    const notifFeed = document.querySelector('#notif-feed');
    const notifBadge = document.querySelector('#notif-badge');
    const notifDropdown = document.querySelector('#notif-dropdown');
    const notifIcon = document.querySelector('#notif-icon');

    let readNotifications = JSON.parse(localStorage.getItem('readNotifications') || '[]');
    let timeSpans = [];

    async function updateBadge(totalUnread = null) {
        try {
            let count = totalUnread;
            if (count === null) {
                const res = await fetch('backend/view_all_notification/get_unread_count.php');
                const data = await res.json();
                if (!data.success) return;
                count = data.unread_count;
            }
            notifBadge.style.display = count ? 'inline-block' : 'none';
            notifBadge.textContent = count > 99 ? '99+' : count;
        } catch (err) {
            console.error('Error updating unread badge:', err);
        }
    }

    async function updateNotifications() {
        try {
            const stockRes = await fetch('backend/production_page/get_inventory_low_stock.php');
            const stockData = stockRes.ok ? await stockRes.json() : { items: [] };
            const dbRes = await fetch('backend/get_notification.php');
            const dbData = dbRes.ok ? await dbRes.json() : { notifications: [] };

            const allNotifs = [];

stockData.items.forEach(item => {
    if (item.status === 'low') {
        allNotifs.push({
            id: `stock-${item.id}`,
            text: `⚠️ ${item.name} stock is low! (Available: ${item.quantity})`,
            timestamp: new Date().toISOString(),
            type: 'low-stock',
            isUnread: true
        });
    } else if (item.status === 'out') {
        allNotifs.push({
            id: `stock-${item.id}`,
            text: `❌ ${item.name} is out of stock!`,
            timestamp: new Date().toISOString(),
            type: 'out-stock',
            isUnread: true
        });
    }
});

dbData.notifications.forEach(n => {
    if (n.type === 'deleted') return;

    let text = n.message;
    if (n.type === 'expiring') {
        text = `${n.message}`; // expiring soon
    } else if (n.type === 'expired') {
        text = `${n.message}`; // expired
    }

    allNotifs.push({
        id: n.user_notification_id,
        text,
        timestamp: n.created_at,
        type: n.type,
        batchId: n.batch_id,
        isUnread: n.is_read == 0
    });
});

            allNotifs.sort((a, b) => new Date(b.timestamp) - new Date(a.timestamp));

            const unreadNotifs = allNotifs.filter(n => n.isUnread && !readNotifications.includes(n.id));
            const totalUnread = unreadNotifs.length;

            const latestNotifs = allNotifs.slice(0, 10);
            const extraUnread = unreadNotifs.filter(n => !latestNotifs.includes(n)).length;

            notifFeed.innerHTML = '';
            timeSpans = [];

            // --- TODAY header + View All button
            const todayHeader = document.createElement('li');
            todayHeader.classList.add('notif-header');
            todayHeader.innerHTML = `<span>TODAY</span>
                                     <button class="view-all-btn">View All</button>
                                     ${extraUnread > 0 ? `<div style="font-size:0.7rem;color:#888;">+${extraUnread} more notifications</div>` : ''}`;
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
                timeSpan.style.cssText = 'font-size:0.7rem;color:#888;float:right;';
                li.appendChild(timeSpan);
                timeSpans.push(timeSpan);

                if (n.isUnread && !readNotifications.includes(n.id)) li.classList.add('new-notif');

                notifFeed.appendChild(li);
            });

            updateBadge(totalUnread);

        } catch (err) {
            console.error('Error updating notifications:', err);
        }
    }

    updateNotifications();
    setInterval(updateNotifications, 3000);

    setInterval(() => {
        timeSpans.forEach(span => {
            span.textContent = getRelativeTime(new Date(span.dataset.timestamp));
        });
    }, 60000);

    notifIcon.addEventListener('click', async () => {
        const isVisible = notifDropdown.style.display === 'block';
        notifDropdown.style.display = isVisible ? 'none' : 'block';

        if (!isVisible) {
            const visibleNotifs = Array.from(notifFeed.querySelectorAll('li.new-notif'));
            const notifIds = visibleNotifs.map(li => li.dataset.id).filter(id => id);

            if (notifIds.length === 0) return;

            visibleNotifs.forEach(li => li.classList.remove('new-notif'));
            readNotifications = Array.from(new Set([...readNotifications, ...notifIds]));
            localStorage.setItem('readNotifications', JSON.stringify(readNotifications));

            const allUnread = Array.from(notifFeed.querySelectorAll('li.new-notif')).length;
            updateBadge(allUnread);

            try {
                const res = await fetch('backend/mark_notification_read.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ notification_ids: notifIds })
                });
                const data = await res.json();
                if (!data.success) console.error('Failed to mark notifications as read');
            } catch (err) {
                console.error(err);
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
