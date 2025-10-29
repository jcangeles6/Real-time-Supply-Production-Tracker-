<?php

//ADMIN ADD STOCK PAGE AND FORM

session_start();
include '../db.php'; // Database connection

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
  header("Location: ../login.php");
  exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>🌸 Inventory - BloomLux</title>
  <link rel="stylesheet" href="../css/add_stock.css">
</head>

<body>
  <div class="sidebar">
    <h2>🌸 BloomLux Requests 🌸</h2>
    <a href="../admin_dashboard.php">🔙 Back to Dashboard </a>
    <a href="../my_requests.php">📋 All Requests</a>
    <a href="add_stock.php">📦 Add Stock</a>
    <a href="../logout.php">🚪 Logout</a>
  </div>

  <div class="main">
    <h1>🌸 BloomLux Admin Inventory Dashboard 🌸</h1>

    <div class="add-btn-container">
      <button class="btn" onclick="toggleForm()">➕ Add Stock</button>
      <button class="btn" onclick="window.location.href='admin_reports.php'">📊 View Daily Report</button>
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
        <input type="number" name="quantity" id="quantityInput" min="0" required>

        <label>Threshold</label>
        <input type="number" name="threshold" id="thresholdInput" min="1" required>

        <label>Status</label>
        <span id="statusDisplay" class="status-display">Available</span>
        <input type="hidden" name="status" id="statusInput" value="available">

        <label>Unit</label>
        <select name="unit" required>
          <option value="kg">Kilograms (kg)</option>
          <option value="g">Grams (g)</option>
          <option value="L">Liters (L)</option>
          <option value="m">Meters (M)</option>
          <option value="pcs">Pieces</option>
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
      if (filterType.value === 'quantity') searchInput.value = searchInput.value.replace(/\D/g, '');
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
      let query = (type === 'status') ? statusSearch.value : searchInput.value.trim();
      currentSearch = query;
      currentPage = 1;
      fetchStocks(currentPage, query, type);
    }

    function toggleForm() {
      const formBox = document.getElementById('addStockForm');
      formBox.style.display = (formBox.style.display === 'block') ? 'none' : 'block';
    }

    function getStatusText(status) {
      const map = {
        available: 'Available',
        low: 'Low',
        out: 'Out of Stock'
      };
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
      return date.toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        hour12: true
      });
    }

    function renderPagination(page, limit, total) {
      const div = document.getElementById('pagination');
      div.innerHTML = '';
      const totalPages = Math.ceil(total / limit);
      if (page > 1) {
        const prevBtn = document.createElement('button');
        prevBtn.className = 'pagination-btn';
        prevBtn.innerHTML = '⬅';
        prevBtn.onclick = () => {
          currentPage--;
          fetchStocks(currentPage, currentSearch, filterType.value);
        };
        div.appendChild(prevBtn);
      }
      if (page < totalPages) {
        const nextBtn = document.createElement('button');
        nextBtn.className = 'pagination-btn';
        nextBtn.innerHTML = '➡';
        nextBtn.onclick = () => {
          currentPage++;
          fetchStocks(currentPage, currentSearch, filterType.value);
        };
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
        const res = await fetch(apiUrl, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(formData)
        });
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

    function showDeleteModal(id) {
      deleteId = id;
      document.getElementById('modalOverlay').style.display = 'flex';
    }

    function confirmDelete() {
      deleteStock(deleteId);
      cancelDelete();
    }

    function cancelDelete() {
      document.getElementById('modalOverlay').style.display = 'none';
    }

    async function deleteStock(id) {
      try {
        const res = await fetch(`${apiUrl}?id=${id}`, {
          method: 'DELETE'
        });
        const result = await res.json();
        if (result.success) fetchStocks(currentPage, currentSearch, filterType.value);
        else alert(result.error || 'Failed to delete stock');
      } catch {
        alert('Error deleting stock');
      }
    }

    fetchStocks(currentPage);

    // ====== Status Update Fix ======
    const quantityInput = document.getElementById('quantityInput');
    const thresholdInput = document.getElementById('thresholdInput');
    const statusInput = document.getElementById('statusInput');
    const statusDisplay = document.getElementById('statusDisplay');

    function updateStatus() {
      const qty = parseInt(quantityInput.value) || 0;
      const threshold = parseInt(thresholdInput.value) || 0;
      let status = '';
      if (qty === 0) {
        status = 'out';
        statusDisplay.textContent = 'Out of Stock';
        statusDisplay.className = 'status-display status-out';
      } else if (qty <= threshold) {
        status = 'low';
        statusDisplay.textContent = 'Low';
        statusDisplay.className = 'status-display status-low';
      } else {
        status = 'available';
        statusDisplay.textContent = 'Available';
        statusDisplay.className = 'status-display status-available';
      }
      statusInput.value = status;
    }

    quantityInput.addEventListener('input', updateStatus);
    thresholdInput.addEventListener('input', updateStatus);
    updateStatus(); // initial call

    async function refreshInventory() {
      try {
        // call your fetch file (the one you pasted)
        const res = await fetch('inventory_page/fetch_inventory.php');
        const data = await res.json();

        if (!data.success) return;

        const tbody = document.querySelector('#inventoryTable tbody');
        tbody.innerHTML = '';

        data.items.forEach(item => {
          const tr = document.createElement('tr');
          tr.innerHTML = `
        <td>${item.id}</td>
        <td>${item.item_name}</td>
        <td>${item.available_quantity}</td>
        <td>${item.unit}</td>
        <td class="status-${item.status.toLowerCase()}">${item.status}</td>
        <td>${item.created_at || '—'}</td>
        <td>${item.updated_at || '—'}</td>
        <td>
          <button class="btn" onclick="window.location.href='update_stock.php?id=${item.id}'">Edit</button>
          <button class="btn btn-delete" onclick="showDeleteModal(${item.id})">Delete</button>
        </td>
      `;
          tbody.appendChild(tr);
        });
      } catch (err) {
        console.error('Error refreshing inventory:', err);
      }
    }

    // 🔁 Auto refresh every 5 seconds
    setInterval(refreshInventory, 5000);

    // Load immediately on page load
    refreshInventory();
  </script>
</body>

</html>