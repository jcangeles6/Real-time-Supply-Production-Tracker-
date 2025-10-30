let alreadyAlerted = {}; // Track items that triggered alert

function updateTime() {
    const now = new Date();
    const options = { hour: '2-digit', minute: '2-digit', second: '2-digit', weekday: 'short', month: 'short', day: 'numeric' };
    document.getElementById("live-time").innerHTML = "⏰ " + now.toLocaleString('en-US', options);
}
setInterval(updateTime, 1000);
updateTime();

// Toggle approved rows
function toggleApproved() {
    document.querySelectorAll('.approved-row').forEach(row => {
        row.style.display = row.style.display === 'none' ? '' : 'none';
    });
}

// Low stock modal logic
function closeLowStockModal() {
    document.getElementById('lowStockModal').style.display = 'none';
}

async function checkLowStock() {
    try {
        const modal = document.getElementById('lowStockModal');
        const list = document.getElementById('lowStockList');

        // 🔒 If modal is already open, just skip everything — don't touch it
        if (modal.style.display === 'block') return;

        const res = await fetch('get_stock.php');
        const data = await res.json();
        if (!data.success) return;

        const lowStockItems = Object.values(data.items || []).filter(
            item => item.quantity <= item.threshold
        );

        // Only alert for new low-stock items not seen before
        const newAlerts = lowStockItems.filter(item => !alreadyAlerted[item.name]);
        if (newAlerts.length === 0) return;

        // ✅ Build the alert list (once per new set)
        list.innerHTML = '';
        newAlerts.forEach(item => alreadyAlerted[item.name] = true);

        lowStockItems.forEach(item => {
            const li = document.createElement('li');
            li.innerHTML = `<span class="item-name">${item.name}</span>
                            <span class="stock-info">
                                Available: <span class="qty">${item.quantity}</span> /
                                Threshold: <span class="threshold">${item.threshold}</span>
                            </span>`;
            list.appendChild(li);
        });

        // 🔔 Show modal — stays visible until user closes it
        modal.style.display = 'block';

        // 🔊 Play alert sound up to 3x
        const sound = document.getElementById('lowStockSound');
        let playCount = 0, volume = 0.2;
        sound.volume = volume;
        sound.currentTime = 0;

        const playInterval = setInterval(() => {
            sound.volume = Math.min(volume, 1);
            sound.play();
            volume += 0.2;
            playCount++;
            if (playCount >= 3) clearInterval(playInterval);
        }, 1200);
    } catch (err) {
        console.error('Error fetching stock:', err);
    }
}


async function updateRequests() {
    try {
        const res = await fetch('backend/get_request.php');
        const data = await res.json();
        if (!data.success) return;

        const requests = data.requests || [];
        const summary = data.summary || {
            total: requests.length,
            pending: requests.filter(r => r.status.toLowerCase() === 'pending').length,
            approved: requests.filter(r => r.status.toLowerCase() === 'approved').length,
            denied: requests.filter(r => r.status.toLowerCase() === 'denied').length
        };

        const summaryDiv = document.querySelector('.summary');
        summaryDiv.textContent = 'Total Requests: ${summary.total} | Pending: ${summary.pending} | Approved: ${summary.approved} | Denied: ${summary.denied}';

        const table = document.getElementById('requestsTable');
        if (!table) return;

        // Clear previous rows
        table.querySelectorAll('tr:not(:first-child)').forEach(tr => tr.remove());

        requests.forEach(row => {
            const status = row.status.toLowerCase();
            let status_class = '', status_label = '';

            if (status === 'pending') { status_label = 'Pending'; status_class = 'pending'; }
            else if (status === 'approved') { status_label = 'Approved'; status_class = 'approved'; }
            else if (status === 'cancelled') { status_label = 'Cancelled'; status_class = 'cancelled'; }
            else if (status === 'denied') { status_label = 'Denied'; status_class = 'denied'; }
            else { status_label = row.status; status_class = status; }

            const tr = document.createElement('tr');
            if (status === 'approved') tr.classList.add('approved-row');

            tr.innerHTML = `
                <td>${row.user_id}</td>
                <td>${row.ingredient_name}</td>
                <td>${row.quantity}</td>
                <td>${row.unit}</td>
                <td>${row.notes}</td>
                <td><span class="status ${status_class}">${status_label}</span></td>
                <td>${row.requested_at}</td>
                <td>
                    ${status === 'pending' ? `
                        <button class="btn-cancel" data-id="${row.id}">Cancel</button>
                        <button class="btn-approve" data-id="${row.id}">Approve</button>
                    ` : '-'}
                </td>
            `;
            table.appendChild(tr);
        });

    } catch (err) {
        console.error('Error updating requests:', err);
    }
}
// Handle approve/cancel clicks via AJAX
document.addEventListener('click', async (e) => {
    if (e.target.classList.contains('btn-cancel') || e.target.classList.contains('btn-approve')) {
        const id = parseInt(e.target.dataset.id, 10);
        const action = e.target.classList.contains('btn-cancel') ? 'cancel' : 'approve';
        const confirmMsg = action === 'cancel' ? '⚠️ Cancel this request?' : '✅ Approve this request?';
        if (!confirm(confirmMsg)) return;

        try {
            const res = await fetch('backend/handle_request.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action, id })
            });
            const data = await res.json();
            if (data.success) {
                updateRequests(); // refresh table via AJAX
            } else {
                alert('Error: ' + data.message);
            }
        } catch (err) {
            console.error(err);
        }
    }
});
// Polls
setInterval(updateRequests, 5000); // new requests every 5s
setInterval(checkLowStock, 10000); // low stock every 10s

// Initial calls
updateRequests();
checkLowStock();