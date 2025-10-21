// js/add_batch_page/add_batch.js

document.addEventListener('DOMContentLoaded', () => {
    const materialsContainer = document.getElementById('materialsContainer');
    const addMaterialBtn = document.getElementById('addMaterialBtn');
    const messageModal = document.getElementById('messageModal');
    const modalMessage = document.getElementById('modalMessage');modalMessage.textContent = messageText; 
    const closeBtn = messageModal.querySelector('.close-btn');

    // Function to create a new material row
    function createMaterialRow(index) {
        const div = document.createElement('div');
        div.classList.add('material-row');
        div.innerHTML = `
            <label>Material</label>
            <select name="materials[${index}][id]" required>
                <option value="">-- Select Material --</option>
                ${inventoryItems.map(item => `
                    <option value="${item.id}">
                        ${item.name} (Available: ${Math.max(0, item.quantity)})
                    </option>
                `).join('')}
            </select>
            <label>Quantity</label>
            <input type="number" name="materials[${index}][quantity]" min="1" required>
            <button type="button" class="removeMaterialBtn" style="background:#b22222;color:white;border:none;border-radius:40px;padding:10px 10px;margin-top:5px;cursor:pointer;">Remove</button>
        `;
        div.querySelector('.removeMaterialBtn').addEventListener('click', () => div.remove());
        return div;
    }

    // Initialize prefilled materials
    if (materialsPrefill.length > 0) {
        materialsPrefill.forEach((mat, idx) => {
            const div = createMaterialRow(idx);
            const select = div.querySelector('select');
            select.value = mat.stock_id;
            const input = div.querySelector('input');
            input.value = mat.quantity_used;
            materialsContainer.appendChild(div);
        });
    }

    // Highlight all material rows on page load
    document.querySelectorAll('#materialsContainer .material-row').forEach(row => {
        row.classList.add('highlight');
        setTimeout(() => row.classList.remove('highlight'), 1500);
    });

    // Add new material row
    addMaterialBtn.addEventListener('click', () => {
        const row = createMaterialRow(materialIndex);
        materialsContainer.appendChild(row);
        materialIndex++;
    });

    // Attach remove event for existing rows
    materialsContainer.querySelectorAll('.removeMaterialBtn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.target.closest('.material-row').remove();
        });
    });

    // Stock updater (live)
    async function updateStockOptions() {
        try {
            const response = await fetch('get_stock.php');
            const data = await response.json();
            if (!data.success) return;
            const items = data.items;
            document.querySelectorAll('#materialsContainer select').forEach(select => {
                for (const option of select.options) {
                    const id = option.value;
                    if (items[id]) {
                        option.text = `${items[id].name} (Available: ${items[id].quantity})`;
                    }
                }
            });
        } catch (err) {
            console.error('Error fetching stock:', err);
        }
    }
    setInterval(updateStockOptions, 5000);
    updateStockOptions();

    // Modal logic
    function showModal() {
        modalMessage.textContent = messageText;
        modalMessage.className = messageType;
        messageModal.style.display = 'block';
    }

    closeBtn.addEventListener('click', () => {
        messageModal.style.display = 'none';
        if (messageType === 'success') window.location.href = 'production.php';
    });

    if (messageText) showModal();

    // Expose createMaterialRow globally if needed
    window.createMaterialRow = createMaterialRow;
});
