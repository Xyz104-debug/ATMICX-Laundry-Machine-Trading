<!DOCTYPE html>
<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'manager') {
    header('Location: atmicxLOGIN.html');
    exit;
}
?>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Dashboard - ATMICX Laundry</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        /* --- THEME VARIABLES (Dark Navy & Gold) --- */
        :root {
            --bg-sidebar: #0B1623;      /* Deep Navy */
            --bg-body: #F3F4F6;         /* Light Grey */
            --card-bg: #FFFFFF;         /* White */
            --accent-gold: #FBBF24;     /* Vibrant Gold */
            --accent-gold-hover: #d97706;
            --text-navy: #152238;       /* Dark Text */
            --text-light: #6b7280;      /* Grey Text */
            --danger: #ef4444;
            --success: #10b981;
            --warning: #f59e0b;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }

        body {
            display: flex; background-color: var(--bg-body); height: 100vh;
            color: var(--text-navy); overflow: hidden;
        }

        /* --- SIDEBAR --- */
        .sidebar {
            width: 280px; background: var(--bg-sidebar); color: white; display: flex;
            flex-direction: column; padding: 20px; box-shadow: 4px 0 15px rgba(0,0,0,0.1);
            flex-shrink: 0; z-index: 10;
        }

        .brand {
            display: flex; align-items: center; gap: 15px; padding-bottom: 25px;
            border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 25px;
        }
        
        .brand-icon {
            width: 45px; height: 45px; border: 2px solid var(--accent-gold); border-radius: 10px;
            display: flex; align-items: center; justify-content: center; color: var(--accent-gold);
            font-size: 20px; box-shadow: 0 0 15px rgba(251, 191, 36, 0.2); flex-shrink: 0;
        }

        /* UPDATED BRAND TEXT STYLING */
        .brand-text {
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .brand-text h2 { 
            font-size: 15px;       /* Same size for all */
            font-weight: 700;      /* Bold for all */
            line-height: 1.2; 
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0;
        }
        
        .text-white { color: #ffffff; }
        .text-gold { color: var(--accent-gold); }

        .user-profile {
            display: flex; align-items: center; gap: 12px; padding: 15px;
            background: rgba(255,255,255,0.05); border-radius: 12px; margin-bottom: 25px;
            border: 1px solid rgba(255,255,255,0.05);
        }
        .user-avatar {
            width: 40px; height: 40px; background: var(--accent-gold); color: var(--bg-sidebar);
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 16px;
        }
        .user-info h4 { font-size: 14px; font-weight: 600; color: white; }
        .user-info p { font-size: 11px; color: #9ca3af; text-transform: uppercase; }

        .nav-links { list-style: none; flex: 1; overflow-y: auto; }
        .nav-links li { margin-bottom: 8px; }

        .nav-btn {
            width: 100%; border: none; background: none; color: #9ca3af; padding: 12px 15px;
            text-align: left; cursor: pointer; border-radius: 10px; font-size: 14px;
            display: flex; align-items: center; gap: 12px; transition: all 0.3s ease; font-weight: 500;
        }
        .nav-btn:hover { background: rgba(255,255,255,0.05); color: white; }
        .nav-btn.active { background: var(--accent-gold); color: var(--bg-sidebar); font-weight: 700; box-shadow: 0 4px 15px rgba(251, 191, 36, 0.3); }

        .logout-btn { margin-top: auto; color: #ef4444; }
        .logout-btn:hover { background: rgba(239, 68, 68, 0.15); color: #ef4444; }

        /* --- MAIN CONTENT --- */
        .main-content { flex: 1; padding: 30px; overflow-y: auto; position: relative; }

        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .header h1 { font-size: 26px; font-weight: 700; color: var(--bg-sidebar); }
        .date-display { color: var(--text-light); font-size: 14px; font-weight: 500; }

        /* --- CARDS (COLORFUL STYLES) --- */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px; }
        
        .card {
            background: var(--card-bg); padding: 25px; border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.03); display: flex; justify-content: space-between;
            align-items: center; border: 1px solid rgba(0,0,0,0.04); transition: transform 0.2s;
            position: relative; overflow: hidden;
        }
        .card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.08); }
        
        .card-info { z-index: 2; }
        .card-info h3 { font-size: 32px; font-weight: 700; margin-bottom: 0px; line-height: 1.2; }
        .card-info p { font-size: 13px; font-weight: 600; opacity: 0.8; }
        
        /* Colorful Card Variants */
        .card-blue { background: linear-gradient(135deg, #e0f2fe 0%, #bae6fd 100%); border: 1px solid #bae6fd; }
        .card-blue h3, .card-blue p { color: #0c4a6e; }
        .card-blue .card-icon { background: rgba(12, 74, 110, 0.1); color: #0c4a6e; }

        .card-green { background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%); border: 1px solid #bbf7d0; }
        .card-green h3, .card-green p { color: #14532d; }
        .card-green .card-icon { background: rgba(20, 83, 45, 0.1); color: #14532d; }

        .card-red { background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%); border: 1px solid #fecaca; }
        .card-red h3, .card-red p { color: #7f1d1d; }
        .card-red .card-icon { background: rgba(127, 29, 29, 0.1); color: #7f1d1d; }

        .card-gold { background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border: 1px solid #fde68a; }
        .card-gold h3, .card-gold p { color: #78350f; }
        .card-gold .card-icon { background: rgba(120, 53, 15, 0.1); color: #78350f; }

        .card-icon { 
            width: 55px; height: 55px; border-radius: 14px; 
            display: flex; align-items: center; justify-content: center; font-size: 24px;
            backdrop-filter: blur(5px); z-index: 2;
        }

        /* --- TABLES & CONTAINERS --- */
        .table-container {
            background: var(--card-bg); padding: 25px; border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.03); margin-bottom: 30px; border: 1px solid rgba(0,0,0,0.04);
            display: flex; flex-direction: column;
        }
        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .section-title { font-size: 18px; font-weight: 700; color: var(--bg-sidebar); }

        table { width: 100%; border-collapse: separate; border-spacing: 0; min-width: 700px; }
        
        th { 
            text-align: left; padding: 15px; color: var(--text-light); 
            font-size: 12px; font-weight: 600; text-transform: uppercase; 
            border-bottom: 2px solid #f3f4f6; 
        }
        
        td { 
            padding: 15px; border-bottom: 1px solid #f9fafb; 
            font-size: 14px; color: var(--text-navy); 
        }

        /* Alternating Rows */
        tbody tr:nth-child(even) { background-color: #f8fafc; }
        tbody tr:hover { background-color: #f1f5f9; }

        .badge { padding: 5px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .bg-low { background: #fee2e2; color: #b91c1c; }
        .bg-ok { background: #d1fae5; color: #065f46; }
        .bg-warn { background: #fef3c7; color: #b45309; }
        .bg-navy { background: #e0f2fe; color: #075985; }

        /* --- BUTTONS --- */
        .btn { padding: 10px 20px; border: none; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; transition: 0.2s; display: inline-flex; align-items: center; gap: 8px; }
        .btn-primary { background: var(--bg-sidebar); color: white; }
        .btn-primary:hover { background: #1f2937; }
        .btn-accent { background: var(--accent-gold); color: var(--bg-sidebar); }
        .btn-accent:hover { background: var(--accent-gold-hover); }
        .btn-danger { background: var(--danger); color: white; }
        .btn-sm { padding: 6px 12px; font-size: 12px; }

        /* --- FILTERS --- */
        .filter-select {
            padding: 8px 12px; border: 1px solid #e5e7eb; border-radius: 8px;
            background: white; color: var(--text-navy); font-size: 13px; outline: none; cursor: pointer;
        }

        /* --- CHARTS SPECIFIC --- */
        .charts-row { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; }
        .chart-wrapper { position: relative; height: 300px; width: 100%; }

        /* --- MODALS --- */
        .modal {
            display: none; position: fixed; z-index: 100; left: 0; top: 0; width: 100%; height: 100%;
            background-color: rgba(11, 22, 35, 0.7); backdrop-filter: blur(2px);
            align-items: center; justify-content: center;
        }
        .modal.active { display: flex; }
        .modal-content {
            background-color: white; padding: 30px; border-radius: 16px; width: 450px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.2); position: relative; animation: slideUp 0.3s ease;
        }
        @keyframes slideUp { from {transform: translateY(20px); opacity: 0;} to {transform: translateY(0); opacity: 1;} }
        
        .modal-header { font-size: 18px; font-weight: 700; color: var(--bg-sidebar); margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-size: 12px; margin-bottom: 5px; color: var(--text-light); }
        .form-group input, .form-group select { 
            width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 8px; 
            background: #f9fafb; outline: none; 
        }
        .modal-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; }

        /* --- SECTIONS --- */
        .section-view { display: none; }
        .section-view.active { display: block; animation: fadeIn 0.3s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

    </style>
</head>
<body>

    <nav class="sidebar">
        <div class="brand">
            <div class="brand-icon"><i class="fas fa-layer-group"></i></div>
           <div class="brand-text">
    <h2>
        <span class="text-white">ATMICX</span> 
        <span class="text-gold">LAUNDRY</span>
    </h2>
    <h2 class="text-gold">MACHINE TRADING</h2>
</div>
        </div>

        <div class="user-profile">
            <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?></div>
            <div class="user-info">
                <h4><?php echo $_SESSION['username']; ?></h4>
                <p><?php echo ucfirst($_SESSION['role']); ?></p>
            </div>
        </div>

        <ul class="nav-links">
            <li><button class="nav-btn active" onclick="switchTab('dashboard', this)"><i class="fas fa-th-large"></i> Dashboard</button></li>
            <li><button class="nav-btn" onclick="switchTab('inventory', this)"><i class="fas fa-boxes"></i> Inventory Mgmt</button></li>
            <li><button class="nav-btn" onclick="switchTab('deliveries', this)"><i class="fas fa-truck-loading"></i> Delivery Records</button></li>
            <li><button class="nav-btn" onclick="switchTab('activity', this)"><i class="fas fa-clipboard-list"></i> Service Reports</button></li>
            <li><button class="nav-btn" onclick="switchTab('sales', this)"><i class="fas fa-chart-line"></i> Sales Analysis</button></li>
            <li><button class="nav-btn" onclick="switchTab('users', this)"><i class="fas fa-users-cog"></i> User Mgmt</button></li>
            <li><button class="nav-btn logout-btn" onclick="if(confirm('Are you sure you want to logout?')) window.location.href='logout.php'"><i class="fas fa-sign-out-alt"></i> Logout</button></li>
        </ul>
    </nav>

    <main class="main-content">
        <header class="header">
            <h1 id="page-title">Dashboard Overview</h1>
            <span class="date-display">Today: <strong id="current-date"></strong></span>
        </header>

        <div id="dashboard" class="section-view active">
            <div class="stats-grid">
                <div class="card card-blue">
                    <div class="card-info"><h3>12</h3><p>Appointments Today</p></div>
                    <div class="card-icon"><i class="fas fa-calendar-check"></i></div>
                </div>
                <div class="card card-green">
                    <div class="card-info"><h3>8</h3><p>Services Completed</p></div>
                    <div class="card-icon"><i class="fas fa-check-circle"></i></div>
                </div>
                <div class="card card-red">
                    <div class="card-info"><h3 id="low-stock-count">0</h3><p>Low Stock Alerts</p></div>
                    <div class="card-icon"><i class="fas fa-exclamation-triangle"></i></div>
                </div>
                <div class="card card-gold">
                    <div class="card-info"><h3>₱ 24k</h3><p>Sales Today</p></div>
                    <div class="card-icon"><i class="fas fa-coins"></i></div>
                </div>
            </div>

            <div class="table-container">
                <div class="section-header">
                    <div class="section-title">Recent System Activity</div>
                </div>
                <table>
                    <thead><tr><th>Time</th><th>User</th><th>Action</th><th>Details</th></tr></thead>
                    <tbody id="activity-log-body"></tbody>
                </table>
            </div>
        </div>

        <div id="inventory" class="section-view">
            <div class="table-container">
                <div class="section-header">
                    <div class="section-title">Inventory Master List</div>
                    <div><button class="btn btn-accent" onclick="openModal('inventoryModal')"><i class="fas fa-plus"></i> Add Item</button></div>
                </div>
                <table>
                    <thead><tr><th>Item Name</th><th>Category</th><th>Supplier</th><th>Stock</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody id="inventory-table-body"></tbody>
                </table>
            </div>
        </div>

        <div id="deliveries" class="section-view">
            <div class="table-container">
                <div class="section-header">
                    <div class="section-title">Incoming Supply Deliveries</div>
                    <button class="btn btn-accent" onclick="openModal('deliveryModal')"><i class="fas fa-truck"></i> Record New Delivery</button>
                </div>
                <table>
                    <thead><tr><th>Del ID</th><th>Date</th><th>Supplier</th><th>Items</th><th>Remarks</th></tr></thead>
                    <tbody id="delivery-table-body"></tbody>
                </table>
            </div>
        </div>

        <div id="activity" class="section-view">
            <div class="table-container">
                <div class="section-header">
                    <div class="section-title">Service Transaction History</div>
                    <button class="btn btn-primary" onclick="alert('Exporting to Excel...')"><i class="fas fa-file-export"></i> Export Report</button>
                </div>
                <table>
                    <thead><tr><th>Srv ID</th><th>Client</th><th>Service Type</th><th>Staff</th><th>Status</th><th>Amount</th></tr></thead>
                    <tbody>
                        <tr><td>#SRV-101</td><td>John Doe</td><td>AC Repair</td><td>Tech Mike</td><td><span class="badge bg-ok">Completed</span></td><td>₱ 2,500</td></tr>
                        <tr><td>#SRV-102</td><td>Jane Smith</td><td>Installation</td><td>Tech Alex</td><td><span class="badge bg-low">Cancelled</span></td><td>₱ 0</td></tr>
                        <tr><td>#SRV-103</td><td>Maria Cruz</td><td>Cleaning</td><td>Tech Mike</td><td><span class="badge bg-ok">Completed</span></td><td>₱ 1,500</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="sales" class="section-view">
            <div class="stats-grid">
                <div class="card card-gold"><div class="card-info"><h3>₱ 54,200</h3><p>Revenue (Selected Period)</p></div><div class="card-icon"><i class="fas fa-chart-line"></i></div></div>
                <div class="card card-green"><div class="card-info"><h3>+12.5%</h3><p>Growth Rate</p></div><div class="card-icon"><i class="fas fa-arrow-up"></i></div></div>
                <div class="card card-blue"><div class="card-info"><h3>AC Repair</h3><p>Top Performing Service</p></div><div class="card-icon"><i class="fas fa-trophy"></i></div></div>
            </div>

            <div class="table-container">
                <div class="section-header">
                    <div class="section-title">Revenue Trends</div>
                    <select id="sales-period" class="filter-select" onchange="updateSalesCharts()">
                        <option value="weekly">This Week</option>
                        <option value="monthly" selected>Monthly (Last 6 Months)</option>
                        <option value="yearly">Yearly</option>
                    </select>
                </div>
                <div class="chart-wrapper">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>

            <div class="charts-row">
                <div class="table-container">
                    <div class="section-title">Service Type Mix</div>
                    <div class="chart-wrapper" style="height: 250px;">
                        <canvas id="serviceChart"></canvas>
                    </div>
                </div>
                <div class="table-container">
                    <div class="section-title">Top Services (Revenue)</div>
                    <table style="min-width: 100%;">
                        <thead><tr><th>Service</th><th>Trans.</th><th>Total</th></tr></thead>
                        <tbody>
                            <tr><td>AC Repair</td><td>45</td><td style="font-weight:bold;">₱ 112k</td></tr>
                            <tr><td>Installation</td><td>20</td><td style="font-weight:bold;">₱ 50k</td></tr>
                            <tr><td>Cleaning</td><td>25</td><td style="font-weight:bold;">₱ 37k</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="users" class="section-view">
            <div class="table-container">
                <div class="section-header">
                    <div class="section-title">System Users</div>
                    <button class="btn btn-accent" onclick="openModal('userModal')"><i class="fas fa-user-plus"></i> Add Secretary</button>
                </div>
                <table>
                    <thead><tr><th>User ID</th><th>Name</th><th>Role</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody id="users-table-body"></tbody>
                </table>
            </div>
        </div>

    <div id="logout-modal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Are you logging out?</h2>
                <p>You can always log back in at any time.</p>
            </div>
            <div class="modal-body">
                <button class="btn btn-secondary" onclick="closeLogoutModal()">Cancel</button>
                <button class="btn btn-danger" onclick="window.location.href='logout.php'">Log out</button>
            </div>
        </div>
    </div>

    </main>

    <div id="inventoryModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">Add/Edit Inventory Item</div>
            <form onsubmit="handleInventorySubmit(event)">
                <div class="form-group"><label>Item Name</label><input type="text" id="inv-name" required></div>
                <div class="form-group"><label>Category</label><select id="inv-cat"><option>Parts</option><option>Machines</option><option>Consumables</option></select></div>
                <div class="form-group"><label>Supplier</label><input type="text" id="inv-supp" required></div>
                <div class="form-group"><label>Stock Quantity</label><input type="number" id="inv-stock" required></div>
                <div class="modal-actions"><button type="button" class="btn btn-danger" onclick="closeModal('inventoryModal')">Cancel</button><button type="submit" class="btn btn-primary">Save Item</button></div>
            </form>
        </div>
    </div>

    <div id="deliveryModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">Record Delivery</div>
            <form onsubmit="handleDeliverySubmit(event)">
                <div class="form-group"><label>Supplier</label><input type="text" id="del-supp" required></div>
                <div class="form-group"><label>Items Description</label><input type="text" id="del-items" required placeholder="e.g., 50x Valves"></div>
                <div class="form-group"><label>Remarks</label><input type="text" id="del-remarks" placeholder="Optional"></div>
                <div class="modal-actions"><button type="button" class="btn btn-danger" onclick="closeModal('deliveryModal')">Cancel</button><button type="submit" class="btn btn-primary">Record</button></div>
            </form>
        </div>
    </div>

    <div id="userModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">Add Secretary Account</div>
            <form onsubmit="handleUserSubmit(event)">
                <div class="form-group"><label>Full Name</label><input type="text" id="usr-name" required></div>
                <div class="form-group"><label>Username</label><input type="text" id="usr-user" required></div>
                <div class="form-group"><label>Default Password</label><input type="text" value="Welcome123" readonly style="background:#eee"></div>
                <div class="modal-actions"><button type="button" class="btn btn-danger" onclick="closeModal('userModal')">Cancel</button><button type="submit" class="btn btn-primary">Create Account</button></div>
            </form>
        </div>
    </div>

    <script>
        // --- DATA STORE ---
        const defaultData = {
            inventory: [
                { id: 1, name: 'Whirlpool Motor 2.0', category: 'Machines', supplier: 'Global Parts', stock: 12, min: 5 },
                { id: 2, name: 'LG Capacitor 50uF', category: 'Parts', supplier: 'ElectroWorld', stock: 2, min: 5 },
                { id: 3, name: 'Cleaning Solution', category: 'Consumables', supplier: 'ChemSupply', stock: 45, min: 10 }
            ],
            deliveries: [
                { id: 'DEL-001', date: 'Oct 24, 2025', supplier: 'Global Parts', items: '50x Inlet Valves', remarks: 'Complete' }
            ],
            users: [
                { id: 'USR-001', name: 'Admin User', role: 'Manager', status: 'Active' },
                { id: 'USR-002', name: 'Jane Doe', role: 'Secretary', status: 'Active' }
            ],
            logs: [
                { time: '10:30 AM', user: 'System', action: 'Login', details: 'Manager Access' }
            ]
        };

        let db = JSON.parse(localStorage.getItem('atmicx_db')) || defaultData;

        function saveDb() {
            localStorage.setItem('atmicx_db', JSON.stringify(db));
            renderAll();
        }

        const dateOptions = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        document.getElementById('current-date').innerText = new Date().toLocaleDateString('en-US', dateOptions);

        function renderAll() {
            renderInventory();
            renderDeliveries();
            renderUsers();
            renderLogs();
            updateLowStockCount();
        }

        function renderInventory() {
            const tbody = document.getElementById('inventory-table-body');
            tbody.innerHTML = '';
            db.inventory.forEach((item, index) => {
                let badge = item.stock <= item.min ? '<span class="badge bg-low">Low Stock</span>' : '<span class="badge bg-ok">In Stock</span>';
                tbody.innerHTML += `<tr><td>${item.name}</td><td>${item.category}</td><td>${item.supplier}</td><td style="font-weight:bold">${item.stock}</td><td>${badge}</td><td><button class="btn btn-sm btn-danger" onclick="deleteItem(${index})">Delete</button></td></tr>`;
            });
        }

        function renderDeliveries() {
            const tbody = document.getElementById('delivery-table-body');
            tbody.innerHTML = '';
            db.deliveries.forEach(d => { tbody.innerHTML += `<tr><td>${d.id}</td><td>${d.date}</td><td>${d.supplier}</td><td>${d.items}</td><td>${d.remarks}</td></tr>`; });
        }

        function renderUsers() {
            const tbody = document.getElementById('users-table-body');
            tbody.innerHTML = '';
            db.users.forEach((u, index) => {
                const btn = u.role === 'Manager' ? `<button class="btn btn-sm btn-primary" disabled>Owner</button>` : `<button class="btn btn-sm btn-danger" onclick="deleteUser(${index})">Disable</button>`;
                tbody.innerHTML += `<tr><td>${u.id}</td><td>${u.name}</td><td>${u.role}</td><td><span class="badge bg-ok">${u.status}</span></td><td>${btn}</td></tr>`;
            });
        }

        function renderLogs() {
            const tbody = document.getElementById('activity-log-body');
            tbody.innerHTML = '';
            db.logs.slice(-5).reverse().forEach(log => { tbody.innerHTML += `<tr><td>${log.time}</td><td>${log.user}</td><td>${log.action}</td><td>${log.details}</td></tr>`; });
        }

        function updateLowStockCount() {
            document.getElementById('low-stock-count').innerText = db.inventory.filter(i => i.stock <= i.min).length;
        }

        // --- HANDLERS ---
        function handleInventorySubmit(e) { e.preventDefault(); db.inventory.push({ id: Date.now(), name: document.getElementById('inv-name').value, category: document.getElementById('inv-cat').value, supplier: document.getElementById('inv-supp').value, stock: parseInt(document.getElementById('inv-stock').value), min: 5 }); addLog('Manager', 'Add Item', 'Added Inventory'); saveDb(); closeModal('inventoryModal'); e.target.reset(); }
        function deleteItem(i) { if(confirm('Delete?')) { db.inventory.splice(i, 1); saveDb(); } }
        function handleDeliverySubmit(e) { e.preventDefault(); db.deliveries.push({ id: '#DEL-'+Math.floor(Math.random()*1000), date: new Date().toLocaleDateString(), supplier: document.getElementById('del-supp').value, items: document.getElementById('del-items').value, remarks: document.getElementById('del-remarks').value || 'Received' }); addLog('Manager', 'Delivery', 'Recorded Delivery'); saveDb(); closeModal('deliveryModal'); e.target.reset(); }
        function handleUserSubmit(e) { e.preventDefault(); db.users.push({ id: '#USR-'+Math.floor(Math.random()*1000), name: document.getElementById('usr-name').value, role: 'Secretary', status: 'Active' }); addLog('Manager', 'User Mgmt', 'Added Secretary'); saveDb(); closeModal('userModal'); e.target.reset(); }
        function deleteUser(i) { if(confirm('Disable user?')) { db.users[i].status = 'Disabled'; saveDb(); } }
        function addLog(u,a,d) { db.logs.push({ time: new Date().toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'}), user: u, action: a, details: d }); }

        // --- UI ---
        function switchTab(tabId, btn) {
            document.querySelectorAll('.section-view').forEach(el => el.classList.remove('active'));
            document.getElementById(tabId).classList.add('active');
            document.querySelectorAll('.nav-btn').forEach(el => el.classList.remove('active'));
            btn.classList.add('active');
            const titles = { 'dashboard': 'Dashboard Overview', 'inventory': 'Inventory Mgmt', 'deliveries': 'Deliveries', 'activity': 'Service Reports', 'sales': 'Sales Analysis', 'users': 'User Mgmt' };
            document.getElementById('page-title').innerText = titles[tabId];
            if(tabId === 'sales') {
                setTimeout(updateSalesCharts, 100); // Small delay to ensure container is visible
            }
        }
        function openModal(id) { document.getElementById(id).classList.add('active'); }
        function closeModal(id) { document.getElementById(id).classList.remove('active'); }

        // --- SALES CHARTS LOGIC (FIXED) ---
        let revenueChartInstance = null;
        let serviceChartInstance = null;

        function updateSalesCharts() {
            const period = document.getElementById('sales-period').value;
            const ctxRev = document.getElementById('revenueChart').getContext('2d');
            const ctxSrv = document.getElementById('serviceChart').getContext('2d');

            // 1. Destroy old charts if they exist
            if (revenueChartInstance) revenueChartInstance.destroy();
            if (serviceChartInstance) serviceChartInstance.destroy();

            // 2. Define Data based on Filter
            let revLabels, revData;

            if (period === 'weekly') {
                revLabels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
                revData = [5000, 7000, 4500, 8000, 12000, 15000, 2700];
            } else if (period === 'monthly') {
                revLabels = ['Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov'];
                revData = [120000, 150000, 180000, 160000, 210000, 156000];
            } else {
                revLabels = ['2020', '2021', '2022', '2023', '2024', '2025'];
                revData = [1.2, 1.5, 2.1, 2.8, 3.2, 3.5]; // In Millions
            }

            // 3. Create Revenue Line Chart
            revenueChartInstance = new Chart(ctxRev, {
                type: 'line',
                data: {
                    labels: revLabels,
                    datasets: [{
                        label: 'Revenue (₱)',
                        data: revData,
                        borderColor: '#0B1623',
                        backgroundColor: 'rgba(11, 22, 35, 0.1)',
                        fill: true,
                        tension: 0.4,
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } }
                }
            });

            // 4. Create Service Doughnut Chart (Static Mix)
            serviceChartInstance = new Chart(ctxSrv, {
                type: 'doughnut',
                data: {
                    labels: ['Repair', 'Install', 'Cleaning', 'Parts'],
                    datasets: [{
                        data: [45, 20, 25, 10],
                        backgroundColor: ['#0B1623', '#FBBF24', '#10b981', '#9ca3af'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'right' } }
                }
            });
        }

        // Initialize
        renderAll();
    </script>
</body>
</html>
