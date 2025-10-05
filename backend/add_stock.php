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
body { font-family: Arial, sans-serif; background: #fdf6f0; margin:0; padding:0; }
.main { padding: 20px 40px; max-width: 1400px; margin: 0 auto; } 
h1 { color: #8b4513; margin-bottom: 20px; text-align: center; }

.back-btn { display:inline-block; margin-bottom:20px; padding:6px 12px; background:#8b4513; color:white; text-decoration:none; border-radius:6px; font-weight:bold; font-size:14px; }
.back-btn:hover { background:#5a2d0c; }

.add-btn-container { text-align:left; margin-bottom: 15px; }

.form-box { display: none; background: white; padding: 25px 30px; border-radius: 12px; box-shadow: 0 2px 6px rgba(0,0,0,0.1); margin: 0 auto 30px; width: 100%; max-width: 600px; box-sizing: border-box; }

label { display: block; margin: 10px 0 5px; font-weight: bold; }
input, select, button { width: 100%; padding: 12px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 8px; box-sizing: border-box; }
button { background: #8b4513; color: white; font-weight: bold; border: none; cursor: pointer; }
button:hover { background: #5a2d0c; }

table { width: 100%; border-collapse: collapse; background: #fff8f0; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-bottom: 50px; }
th, td { padding: 12px 15px; border-bottom: 1px solid #ddd; text-align: center; }
th { background: #8b4513; color: white; }
tr:hover { background: #f1e3d3; }

.status-available { color: green; font-weight: bold; }
.status-low { color: orange; font-weight: bold; }
.status-out { color: red; font-weight: bold; }

.success-msg { color: green; font-weight: bold; text-align: center; margin-bottom: 15px; }
.error-msg { color: red; font-weight: bold; text-align: center; margin-bottom: 15px; }

/* Buttons */
.btn, .btn-delete {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 5px;
    font-weight: bold;
    text-align: center;
    cursor: pointer;
    font-size: 14px;
    text-decoration: none;
    border: none;
}
.btn { background: #8b4513; color: white; }
.btn:hover { background: #5a2d0c; }
.btn-delete { background: #b22222; color: white; }
.btn-delete:hover { background: #8b0000; }

/* Modal Styles */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
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
    box-shadow: 0 2px 10px rgba(0,0,0,0.2);
}
.modal h3 { margin-bottom: 20px; }
.modal button { width: 45%; margin: 5px; }
</style>
</head>
<body>
<div class="main">
    <a href="../admin_dashboard.php" class="back-btn">⬅ Back to Admin Dashboard</a>
    <h1>📦 Inventory</h1>

    <div class="add-btn-container">
        <button class="btn" onclick="toggleForm()">➕ Add Stock</button>
    </div>

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

    <table id="inventoryTable">
        <thead>
            <tr>
                <th>ID</th>
                <th>Item Name</th>
                <th>Quantity</th>
                <th>Unit</th>
                <th>Status</th>
                <th>Updated At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
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
const apiUrl = 'stock.php'; // backend API

// Toggle Add Stock Form
function toggleForm() {
    const formBox = document.getElementById('addStockForm');
    formBox.style.display = (formBox.style.display === 'block') ? 'none' : 'block';
}

// Fetch all stocks and populate table
async function fetchStocks() {
    try {
        const res = await fetch(apiUrl);
        const data = await res.json();
        const tbody = document.querySelector('#inventoryTable tbody');
        tbody.innerHTML = '';
        if(data.length === 0){
            tbody.innerHTML = `<tr><td colspan="7" style="color:#8b4513;">No stock items found.</td></tr>`;
            return;
        }
        data.forEach(stock => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${stock.id}</td>
                <td>${stock.item_name}</td>
                <td>${stock.quantity}</td>
                <td>${stock.unit}</td>
                <td class="status-${stock.status}">${capitalize(stock.status)}</td>
                <td>${stock.updated_at}</td>
                <td>
                    <button class="btn" onclick="window.location.href='update_stock.php?id=${stock.id}'">Edit</button>
                    <button class="btn btn-delete" onclick="showDeleteModal(${stock.id})">Delete</button>
                </td>
            `;
            tbody.appendChild(tr);
        });
    } catch(err) {
        console.error('Error fetching stocks:', err);
    }
}

function capitalize(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}

// Add Stock Form Submission
document.getElementById('stockForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = Object.fromEntries(new FormData(e.target).entries());
    const msgDiv = document.getElementById('formMsg');

    try {
        const res = await fetch(apiUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(formData)
        });
        const result = await res.json();
        if(result.success){
            msgDiv.innerHTML = '<div class="success-msg">Stock added successfully! ✅</div>';
            e.target.reset();
            fetchStocks();
        } else {
            msgDiv.innerHTML = '<div class="error-msg">'+(result.error || 'Failed to add stock')+'</div>';
        }
    } catch(err){
        console.error(err);
        msgDiv.innerHTML = '<div class="error-msg">Error adding stock</div>';
    }
});

// Modal Delete
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

// Delete stock via API
async function deleteStock(id){
    try{
        const res = await fetch(`${apiUrl}?id=${id}`, { method: 'DELETE' });
        const result = await res.json();
        if(result.success){
            fetchStocks();
        } else {
            alert(result.error || 'Failed to delete stock');
        }
    } catch(err){
        console.error(err);
        alert('Error deleting stock');
    }
}

// Initial fetch
fetchStocks();
</script>
</body>
</html>
