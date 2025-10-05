<?php
session_start();
include '../db.php'; // Database connection

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Inventory - Bakery</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #fdf6f0;
            margin: 0;
            padding: 0;
        }

        .main {
            padding: 20px 40px;
            max-width: 1400px;
            margin: 0 auto;
        }

        h1 {
            color: #8b4513;
            margin-bottom: 20px;
            text-align: center;
        }

        .back-btn {
            display: inline-block;
            margin-bottom: 20px;
            padding: 6px 12px;
            background: #8b4513;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            font-size: 14px;
        }

        .back-btn:hover {
            background: #5a2d0c;
        }

        .add-btn-container {
            text-align: left;
            margin-bottom: 15px;
        }

        .search-container {
            text-align: right;
            margin-bottom: 10px;
        }

        .form-box {
            display: none;
            background: white;
            padding: 25px 30px;
            border-radius: 12px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
            margin: 0 auto 30px;
            width: 100%;
            max-width: 600px;
            box-sizing: border-box;
        }

        label {
            display: block;
            margin: 10px 0 5px;
            font-weight: bold;
        }

        input,
        select,
        button {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 8px;
            box-sizing: border-box;
        }

        button {
            background: #8b4513;
            color: white;
            font-weight: bold;
            border: none;
            cursor: pointer;
        }

        button:hover {
            background: #5a2d0c;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff8f0;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            margin-bottom: 50px;
        }

        th,
        td {
            padding: 12px 15px;
            border-bottom: 1px solid #ddd;
            text-align: center;
            white-space: normal;
        }

        th {
            background: #8b4513;
            color: white;
        }

        tr:hover {
            background: #f1e3d3;
        }

        .status-available {
            color: green;
            font-weight: bold;
        }

        .status-low {
            color: orange;
            font-weight: bold;
        }

        .status-out {
            color: red;
            font-weight: bold;
        }

        .success-msg {
            color: green;
            font-weight: bold;
            text-align: center;
            margin-bottom: 15px;
        }

        .error-msg {
            color: red;
            font-weight: bold;
            text-align: center;
            margin-bottom: 15px;
        }

        .btn,
        .btn-delete {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            border: none;
        }

        .btn {
            background: #8b4513;
            color: white;
        }

        .btn:hover {
            background: #5a2d0c;
        }

        .btn-delete {
            background: #b22222;
            color: white;
        }

        .btn-delete:hover {
            background: #8b0000;
        }

        .pagination-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            border: none;
            background: #8b4513;
            color: white;
            font-weight: bold;
            cursor: pointer;
            margin: 0 3px;
            transition: 0.2s;
            font-size: 14px;
        }

        .pagination-btn:hover {
            background: #5a2d0c;
        }

        #pagination span {
            font-size: 14px;
            margin: 0 5px;
        }

        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .modal {
            background: #fff;
            padding: 20px 30px;
            border-radius: 12px;
            width: 90%;
            max-width: 400px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        .modal h3 {
            margin-bottom: 20px;
        }

        .modal button {
            width: 45%;
            margin: 5px;
        }
    </style>
</head>

<body>
    <div class="main">
        <a href="../admin_dashboard.php" class="back-btn">⬅ Back to Admin Dashboard</a>
        <h1>📦 Inventory</h1>

        <div class="add-btn-container">
            <button class="btn" onclick="toggleForm()">➕ Add Stock</button>
        </div>

        <!-- Search filters -->
        <div class="search-container">
            <select id="filterType" style="padding:6px 10px; border-radius:6px; border:1px solid #ccc; margin-right:8px;">
                <option value="item_name">Item Name</option>
                <option value="quantity">Quantity</option>
                <option value="status">Status</option>
            </select>

            <input type="text" id="searchInput" placeholder="Search..." style="padding:6px 12px; border-radius:6px; border:1px solid #ccc;">

            <select id="statusSearch" style="padding:6px 10px; border-radius:6px; border:1px solid #ccc; display:none; margin-left:5px;">
                <option value="">--Select Status--</option>
                <option value="available">Available</option>
                <option value="low">Low</option>
                <option value="out">Out of Stock</option>
            </select>
        </div>

        <!-- Add Stock Form -->
        <div id="addStockForm" class="form-box">
            <h2>➕ Add Stock Item</h2>
            <div id="formMsg"></div>
            <form id="stockForm">
                <label>Item Name</label>
                <input type="text" name="item_name" required>

                <label>Quantity</label>
                <input type="number" name="quantity" min="1" required>

                <label>Unit</label>
                <select name="unit" required>
                    <option value="kg">Kilograms (kg)</option>
                    <option value="g">Grams (g)</option>
                    <option value="L">Liters (L)</option>
                    <option value="ml">Milliliters (ml)</option>
                    <option value="pcs">Pieces</option>
                </select>

                <label>Status</label>
                <select name="status" required>
                    <option value="available">Available</option>
                    <option value="low">Low</option>
                    <option value="out">Out of Stock</option>
                </select>

                <button type="submit">Add Stock</button>
            </form>
        </div>

        <!-- Inventory Table -->
        <table id="inventoryTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Item Name</th>
                    <th>Quantity</th>
                    <th>Unit</th>
                    <th>Status</th>
                    <th>Created At</th>
                    <th>Updated At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>

        <div id="pagination" style="text-align:center; margin-bottom:30px;"></div>
    </div>

    <!-- Modal -->
    <div class="modal-overlay" id="modalOverlay">
        <div class="modal">
            <h3>⚠️ Are you sure you want to delete this stock?</h3>
            <button onclick="confirmDelete()" class="btn btn-delete">Yes, Delete</button>
            <button onclick="cancelDelete()" class="btn">Cancel</button>
        </div>
    </div>

    <script>
        const apiUrl = 'stock.php';
        let currentPage = 1;
        const limit = 5;
        let currentSearch = '';

        const searchInput = document.getElementById('searchInput');
        const filterType = document.getElementById('filterType');
        const statusSearch = document.getElementById('statusSearch');

        searchInput.addEventListener('input', () => {
            if (filterType.value === 'quantity') {
                searchInput.value = searchInput.value.replace(/\D/g, '');
            }
            handleSearch();
        });

        filterType.addEventListener('change', () => {
            if (filterType.value === 'quantity') {
                searchInput.style.display = 'inline-block';
                statusSearch.style.display = 'none';
                searchInput.type = 'number';
                searchInput.value = '';
                searchInput.placeholder = 'Search by Quantity';
            } else if (filterType.value === 'item_name') {
                searchInput.style.display = 'inline-block';
                statusSearch.style.display = 'none';
                searchInput.type = 'text';
                searchInput.value = '';
                searchInput.placeholder = 'Search by Item Name';
            } else if (filterType.value === 'status') {
                searchInput.style.display = 'none';
                statusSearch.style.display = 'inline-block';
                statusSearch.value = '';
            }
            handleSearch();
        });

        statusSearch.addEventListener('change', handleSearch);

        function handleSearch() {
            const type = filterType.value;
            let query = '';
            if (type === 'status') query = statusSearch.value;
            else query = searchInput.value.trim();

            currentSearch = query;
            currentPage = 1;
            fetchStocks(currentPage, query, type);
        }

        function toggleForm() {
            const formBox = document.getElementById('addStockForm');
            formBox.style.display = (formBox.style.display === 'block') ? 'none' : 'block';
        }

        function getStatusText(status) {
            const map = { available: 'Available', low: 'Low', out: 'Out of Stock' };
            return map[status] || status;
        }

        async function fetchStocks(page = 1, search = '', filter = 'item_name') {
            try {
                const res = await fetch(`${apiUrl}?page=${page}&limit=${limit}&search=${encodeURIComponent(search)}&filter=${filter}`);
                const data = await res.json();
                const tbody = document.querySelector('#inventoryTable tbody');
                tbody.innerHTML = '';

                if (!data.stocks || data.stocks.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="8" style="color:#8b4513;">No stock items found.</td></tr>`;
                    renderPagination(page, limit, 0);
                    return;
                }

                data.stocks.forEach(stock => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>${stock.id}</td>
                        <td>${stock.item_name}</td>
                        <td>${stock.quantity}</td>
                        <td>${stock.unit}</td>
                        <td class="status-${stock.status}">${getStatusText(stock.status)}</td>
                        <td>${formatTimestamp(stock.created_at)}</td>
                        <td>${formatTimestamp(stock.updated_at)}</td>
                        <td>
                            <button class="btn" onclick="window.location.href='update_stock.php?id=${stock.id}'">Edit</button>
                            <button class="btn btn-delete" onclick="showDeleteModal(${stock.id})">Delete</button>
                        </td>`;
                    tbody.appendChild(tr);
                });

                renderPagination(page, limit, data.total);
            } catch (err) {
                console.error('Error fetching stocks:', err);
            }
        }

        function formatTimestamp(ts) {
            const date = new Date(ts);
            return date.toLocaleString('en-US', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit', hour12: true });
        }

        function renderPagination(page, limit, total) {
            const div = document.getElementById('pagination');
            div.innerHTML = '';
            const totalPages = Math.ceil(total / limit);

            if (page > 1) {
                const prevBtn = document.createElement('button');
                prevBtn.className = 'pagination-btn';
                prevBtn.innerHTML = '⬅';
                prevBtn.onclick = () => { currentPage--; fetchStocks(currentPage, currentSearch, filterType.value); };
                div.appendChild(prevBtn);
            }

            if (page < totalPages) {
                const nextBtn = document.createElement('button');
                nextBtn.className = 'pagination-btn';
                nextBtn.innerHTML = '➡';
                nextBtn.onclick = () => { currentPage++; fetchStocks(currentPage, currentSearch, filterType.value); };
                div.appendChild(nextBtn);
            }

            const pageInfo = document.createElement('span');
            pageInfo.textContent = `Page ${page} of ${totalPages || 1}`;
            div.appendChild(pageInfo);
        }

        document.getElementById('stockForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = Object.fromEntries(new FormData(e.target).entries());
            const msgDiv = document.getElementById('formMsg');
            try {
                const res = await fetch(apiUrl, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(formData) });
                const result = await res.json();
                if (result.success) {
                    msgDiv.innerHTML = '<div class="success-msg">Stock added successfully! ✅</div>';
                    e.target.reset();
                    fetchStocks(currentPage, currentSearch, filterType.value);
                } else {
                    msgDiv.innerHTML = `<div class="error-msg">${result.error || 'Failed to add stock'}</div>`;
                }
            } catch {
                msgDiv.innerHTML = '<div class="error-msg">Error adding stock</div>';
            }
        });

        let deleteId = 0;
        function showDeleteModal(id) { deleteId = id; document.getElementById('modalOverlay').style.display = 'flex'; }
        function confirmDelete() { deleteStock(deleteId); cancelDelete(); }
        function cancelDelete() { document.getElementById('modalOverlay').style.display = 'none'; }
        async function deleteStock(id) {
            try {
                const res = await fetch(`${apiUrl}?id=${id}`, { method: 'DELETE' });
                const result = await res.json();
                if (result.success) fetchStocks(currentPage, currentSearch, filterType.value);
                else alert(result.error || 'Failed to delete stock');
            } catch {
                alert('Error deleting stock');
            }
        }

        fetchStocks(currentPage);
    </script>
</body>
</html>
