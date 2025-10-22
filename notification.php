<?php
include 'backend/init.php';

$user_id = $_SESSION['user_id'] ?? 0;
if (!$user_id) {
    echo "<p>Please log in to view notifications.</p>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>All Notifications</title>
    <link rel="stylesheet" href="css/notification_page.css">
    <style>
        .mark-all-btn {
            background: #2e1a2e;
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 10px 20px;
            font-weight: 500;
            cursor: pointer;
            transition: 0.3s;
            margin-left: 10px;
        }
        .mark-all-btn:hover {
            background: #4a2a4a;
        }
    </style>
</head>
<body>

<a href="#" class="back-btn" onclick="history.back(); return false;">← Back</a>
<h1>All Notifications</h1>

<div class="filter-search">
    <select id="filterType">
        <option value="all">View All</option>
        <option value="replenished">Replenished</option>
        <option value="in-progress">In Progress</option>
        <option value="completed">Completed</option>
    </select>
    <input type="text" id="searchInput" placeholder="Search notifications...">
    <button id="applyFilter">Filter</button>
    <button id="markAllRead" class="mark-all-btn">Mark All as Read</button>
</div>

<div id="notifications-container">Loading notifications...</div>

<script>
const container = document.getElementById('notifications-container');
const filterType = document.getElementById('filterType');
const searchInput = document.getElementById('searchInput');
const applyFilter = document.getElementById('applyFilter');
const markAllReadBtn = document.getElementById('markAllRead');

let allNotifications = [];

/* -------- Derive normalized category -------- */
function getCategory(n) {
    const t = (n.type || '').toLowerCase();
    const msg = (n.message || '').toLowerCase().replace(/[^\w\s]/g, ''); // remove symbols

    if (t.includes('replenish') || msg.includes('replenished')) return 'replenished';
    if (t.includes('batch') || msg.includes('batch')) {
        if (msg.includes('completed')) return 'completed';
        if (msg.includes('started') || msg.includes('in progress')) return 'in-progress';
        return 'scheduled';
    }
    if (t.includes('in_progress') || msg.includes('in progress')) return 'in-progress';
    if (t.includes('completed') || msg.includes('completed')) return 'completed';
    return 'other';
}

/* -------- Fetch DB notifications -------- */
async function fetchDBNotifications() {
    try {
        const res = await fetch('backend/view_all_notification/get_all_notification.php');
        const data = await res.json();
        if (!data.success) return [];

        return data.notifications.map(n => {
            n._category = getCategory(n);
            n.is_ephemeral = false;
            return n;
        });
    } catch (err) {
        console.error('Error fetching DB notifications:', err);
        return [];
    }
}

/* -------- Fetch and render notifications -------- */
async function fetchNotifications() {
    allNotifications = await fetchDBNotifications();
    renderNotifications(allNotifications);
}

/* -------- Render notifications (grouped) -------- */
function renderNotifications(notifs) {
    if (!notifs.length) {
        container.innerHTML = "<p>No notifications found.</p>";
        return;
    }

    const today = new Date().toISOString().slice(0, 10);
    const sections = { Today: [], Older: [] };

    notifs.forEach(n => {
        const notifDate = (n.created_at || new Date().toISOString()).slice(0, 10);
        if (notifDate === today) sections.Today.push(n);
        else sections.Older.push(n);
    });

    container.innerHTML = '';

    for (const [title, list] of Object.entries(sections)) {
        if (!list.length) continue;

        const sectionDiv = document.createElement('div');
        sectionDiv.className = 'notif-section ' + (title === 'Older' ? 'older' : '');
        sectionDiv.innerHTML = `<h2>📅 ${title}</h2><ul class="notif-list"></ul>`;
        const ul = sectionDiv.querySelector('ul');

        list.forEach(n => {
            let icon = '🔔';
            if (n._category === 'replenished') icon = '♻️';
            if (n._category === 'in-progress') icon = '🛠️';
            if (n._category === 'completed') icon = '✔️';

            const safeMsg = String(n.message).replace(/^[^\w\s]+/, '').replace(/</g, '&lt;').replace(/>/g, '&gt;');

            const li = document.createElement('li');
            li.className = 'cat-' + n._category + (n.is_read ? '' : ' new-notif');
            li.innerHTML = `
                <div style="display:flex;align-items:center;gap:8px;">
                    ${!n.is_read ? '<span class="unread-dot"></span>' : ''}
                    <span style="font-weight:${n.is_read ? 400 : 600}">${icon} ${safeMsg}</span>
                </div>
                <div style="display:flex;align-items:center;gap:10px;">
                    ${!n.is_read ? `<button class="mark-read-btn" onclick="markRead(${n.user_notification_id})">Mark Read</button>` : ''}
                    <span class="timestamp">${new Date(n.created_at || new Date()).toLocaleString([], { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })}</span>
                </div>
            `;
            ul.appendChild(li);
        });

        container.appendChild(sectionDiv);
    }
}

/* -------- Mark single notification -------- */
async function markRead(id) {
    try {
        const formData = new FormData();
        formData.append('user_notification_id', id);
        const res = await fetch('backend/view_all_notification/mark_read.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            // Update local state
            allNotifications = allNotifications.map(n =>
                n.user_notification_id === id ? { ...n, is_read: 1 } : n
            );
            applyCurrentFilter();
        } else {
            alert('Failed to mark notification as read.');
        }
    } catch (err) {
        console.error('Error marking as read:', err);
    }
}

/* -------- Mark all notifications as read -------- */
async function markAllRead() {
    try {
        const res = await fetch('backend/view_all_notification/mark_all_read.php', { method: 'POST' });
        const data = await res.json();
        if (data.success) {
            allNotifications = allNotifications.map(n => ({ ...n, is_read: 1 }));
            applyCurrentFilter();
            alert('All notifications marked as read.');
        } else {
            alert('Failed to mark all as read.');
        }
    } catch (err) {
        console.error('Error marking all as read:', err);
    }
}

/* -------- Apply search + filter -------- */
function applyCurrentFilter() {
    const type = filterType.value.toLowerCase();
    const keyword = searchInput.value.toLowerCase();

    const filtered = allNotifications.filter(n => {
        const cat = n._category || 'other';
        const matchType = type === 'all' || cat === type;
        const matchSearch = !keyword || n.message.toLowerCase().includes(keyword);
        return matchType && matchSearch;
    });

    renderNotifications(filtered);
}

applyFilter.addEventListener('click', applyCurrentFilter);
searchInput.addEventListener('keydown', e => {
    if (e.key === 'Enter') applyCurrentFilter();
});
markAllReadBtn.addEventListener('click', markAllRead);

/* -------- Initial Load -------- */
fetchNotifications();
</script>

</body>
</html>
