<!DOCTYPE html>
<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'secretary') {
    header('Location: atmicxLOGIN.html');
    exit;
}
?>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secretary Dashboard - ATMICX</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* --- THEME VARIABLES --- */
        :root {
            --bg-sidebar: #0B1623;
            --bg-body: #F3F4F6;
            --card-bg: #FFFFFF;
            --accent-gold: #FBBF24;
            --accent-gold-hover: #d97706;
            --text-navy: #152238;
            --text-light: #6b7280;
            --danger: #ef4444;
            --success: #10b981;
            --warning: #f59e0b;
            --info: #3b82f6;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }

        body { display: flex; background-color: var(--bg-body); height: 100vh; color: var(--text-navy); overflow: hidden; }

        /* --- SIDEBAR --- */
        .sidebar { width: 280px; background: var(--bg-sidebar); color: white; display: flex; flex-direction: column; padding: 20px; box-shadow: 4px 0 15px rgba(0,0,0,0.1); flex-shrink: 0; z-index: 10; }
        .brand { display: flex; align-items: center; gap: 15px; padding-bottom: 25px; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 25px; }
        .brand-icon { width: 45px; height: 45px; border: 2px solid var(--accent-gold); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: var(--accent-gold); font-size: 20px; box-shadow: 0 0 15px rgba(251, 191, 36, 0.2); flex-shrink: 0; }
        .brand-text h2 { font-size: 15px; font-weight: 700; line-height: 1.2; text-transform: uppercase; letter-spacing: 0.5px; margin: 0; }
        .text-white { color: #ffffff; }
        .text-gold { color: var(--accent-gold); }

        .user-profile { display: flex; align-items: center; gap: 12px; padding: 15px; background: rgba(255,255,255,0.05); border-radius: 12px; margin-bottom: 25px; border: 1px solid rgba(255,255,255,0.05); }
        .user-avatar { width: 40px; height: 40px; background: var(--accent-gold); color: var(--bg-sidebar); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 16px; }
        .user-info h4 { font-size: 14px; font-weight: 600; color: white; }
        .user-info p { font-size: 11px; color: #9ca3af; text-transform: uppercase; }

        .nav-links { list-style: none; flex: 1; overflow-y: auto; }
        .nav-links li { margin-bottom: 8px; }
        .nav-btn { width: 100%; border: none; background: none; color: #9ca3af; padding: 12px 15px; text-align: left; cursor: pointer; border-radius: 10px; font-size: 14px; display: flex; align-items: center; gap: 12px; transition: all 0.3s ease; font-weight: 500; }
        .nav-btn:hover { background: rgba(255,255,255,0.05); color: white; }
        .nav-btn.active { background: var(--accent-gold); color: var(--bg-sidebar); font-weight: 700; box-shadow: 0 4px 15px rgba(251, 191, 36, 0.3); }
        .logout-btn { margin-top: auto; color: #ef4444; }
        .logout-btn:hover { background: rgba(239, 68, 68, 0.15); color: #ef4444; }

        /* --- MAIN CONTENT --- */
        .main-content { flex: 1; padding: 30px; overflow-y: auto; position: relative; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .header h1 { font-size: 26px; font-weight: 700; color: var(--bg-sidebar); }
        .date-display { color: var(--text-light); font-size: 14px; font-weight: 500; }

        /* --- CARDS --- */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .card { background: var(--card-bg); padding: 25px; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.03); display: flex; justify-content: space-between; align-items: center; border: 1px solid rgba(0,0,0,0.04); transition: transform 0.2s; position: relative; overflow: hidden; }
        .card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.08); }
        .card-info { z-index: 2; }
        .card-info h3 { font-size: 32px; font-weight: 700; margin-bottom: 0px; line-height: 1.2; color: var(--text-navy); }
        .card-info p { font-size: 13px; font-weight: 600; opacity: 0.8; color: var(--text-light); }
        
        .card-gold { background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border: 1px solid #fde68a; }
        .card-blue { background: linear-gradient(135deg, #e0f2fe 0%, #bae6fd 100%); border: 1px solid #bae6fd; }
        .card-green { background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%); border: 1px solid #bbf7d0; }
        .card-icon { width: 55px; height: 55px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 24px; backdrop-filter: blur(5px); z-index: 2; color: var(--text-navy); }

        /* --- TABLES --- */
        .table-container { background: var(--card-bg); padding: 25px; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.03); margin-bottom: 30px; border: 1px solid rgba(0,0,0,0.04); display: flex; flex-direction: column; }
        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .section-title { font-size: 18px; font-weight: 700; color: var(--bg-sidebar); }
        table { width: 100%; border-collapse: separate; border-spacing: 0; min-width: 700px; }
        th { text-align: left; padding: 15px; color: var(--text-light); font-size: 12px; font-weight: 600; text-transform: uppercase; border-bottom: 2px solid #f3f4f6; }
        td { padding: 15px; border-bottom: 1px solid #f9fafb; font-size: 14px; color: var(--text-navy); }
        tbody tr:nth-child(even) { background-color: #f8fafc; }
        tbody tr:hover { background-color: #f1f5f9; }

        .badge { padding: 5px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .bg-pending { background: #fef3c7; color: #b45309; }
        .bg-success { background: #d1fae5; color: #065f46; }
        .bg-danger { background: #fee2e2; color: #b91c1c; }
        .bg-info { background: #e0f2fe; color: #075985; }

        .btn { padding: 10px 20px; border: none; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; transition: 0.2s; display: inline-flex; align-items: center; gap: 8px; }
        .btn-primary { background: var(--bg-sidebar); color: white; }
        .btn-accent { background: var(--accent-gold); color: var(--bg-sidebar); }
        .btn-success { background: var(--success); color: white; }
        .btn-danger { background: var(--danger); color: white; }
        .btn-sm { padding: 6px 12px; font-size: 12px; }

        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-size: 12px; margin-bottom: 5px; color: var(--text-light); }
        .form-control { width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 8px; background: #f9fafb; outline: none; }
        .search-bar { width: 100%; max-width: 300px; padding: 10px; border-radius: 20px; border: 1px solid #e5e7eb; outline:none; }

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
            <div class="user-avatar">J</div>
            <div class="user-info">
                <h4>Jane Doe</h4>
                <p>Secretary</p>
            </div>
        </div>

        <ul class="nav-links">
            <li><button class="nav-btn active" onclick="switchTab('dashboard', this)"><i class="fas fa-th-large"></i> Dashboard</button></li>
            <li><button class="nav-btn" onclick="switchTab('customers', this)"><i class="fas fa-users"></i> Customer Records</button></li>
            <li><button class="nav-btn" onclick="switchTab('requests', this)"><i class="fas fa-concierge-bell"></i> Service Requests</button></li>
            <li><button class="nav-btn" onclick="switchTab('quotes', this)"><i class="fas fa-file-invoice-dollar"></i> Quotations</button></li>
            <li><button class="nav-btn" onclick="switchTab('appointments', this)"><i class="fas fa-calendar-alt"></i> Appointments</button></li>
            <li><button class="nav-btn" onclick="switchTab('payments', this)"><i class="fas fa-wallet"></i> Payments</button></li>
            <li><button class="nav-btn logout-btn" onclick="if(confirm('Log out?')) window.location.href='logout.php'"><i class="fas fa-sign-out-alt"></i> Logout</button></li>
        </ul>
    </nav>

    <main class="main-content">
        <header class="header">
            <h1 id="page-title">Secretary Dashboard</h1>
            <span class="date-display">Today: <strong id="current-date"></strong></span>
        </header>

        <div id="dashboard" class="section-view active">
            <div class="stats-grid">
                <div class="card card-blue">
                    <div class="card-info"><h3 id="stat-appt">0</h3><p>Today's Appointments</p></div>
                    <div class="card-icon"><i class="fas fa-calendar-day"></i></div>
                </div>
                <div class="card card-gold">
                    <div class="card-info"><h3 id="stat-quotes">0</h3><p>Pending Quotes</p></div>
                    <div class="card-icon"><i class="fas fa-file-invoice"></i></div>
                </div>
                <div class="card card-green">
                    <div class="card-info"><h3 id="stat-req">0</h3><p>Pending Requests</p></div>
                    <div class="card-icon"><i class="fas fa-clipboard-list"></i></div>
                </div>
            </div>

            <div class="table-container">
                <div class="section-header">
                    <div class="section-title">Latest Service Requests</div>
                </div>
                <table>
                    <thead><tr><th>Client</th><th>Service</th><th>Date</th><th>Status</th></tr></thead>
                    <tbody id="dashboard-requests-body"></tbody>
                </table>
            </div>
        </div>

        <div id="customers" class="section-view">
            <div class="table-container">
                <div class="section-header">
                    <div class="section-title">Customer Database (Read-Only)</div>
                    <input type="text" id="cust-search" class="search-bar" placeholder="Search customer..." onkeyup="filterCustomers()">
                </div>
                <table>
                    <thead><tr><th>ID</th><th>Name</th><th>Contact</th><th>Email</th><th>Action</th></tr></thead>
                    <tbody id="customers-table-body"></tbody>
                </table>
            </div>
        </div>

        <div id="requests" class="section-view">
            <div class="table-container">
                <div class="section-header">
                    <div class="section-title">Incoming Requests</div>
                </div>
                <table>
                    <thead><tr><th>Req ID</th><th>Client</th><th>Service Issue</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody id="requests-table-body"></tbody>
                </table>
            </div>
        </div>

        <div id="quotes" class="section-view">
            <div class="table-container">
                <div class="section-header">
                    <div class="section-title">Create Quotation</div>
                </div>
                <form onsubmit="handleQuoteSubmit(event)">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Client Name</label>
                            <input type="text" id="quote-client" class="form-control" placeholder="Search Client" required>
                        </div>
                        <div class="form-group">
                            <label>Service Type</label>
                            <select id="quote-service" class="form-control">
                                <option>AC Repair</option>
                                <option>Installation</option>
                                <option>Cleaning</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Estimated Amount (₱)</label>
                            <input type="number" id="quote-amount" class="form-control" placeholder="0.00" required>
                        </div>
                        <div class="form-group">
                            <label>Notes</label>
                            <input type="text" class="form-control" placeholder="Additional details">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-accent">Generate & Send Quote</button>
                </form>
            </div>
            
            <div class="table-container">
                <div class="section-title">Recent Quotations</div>
                <br>
                <table>
                    <thead><tr><th>Quote ID</th><th>Client</th><th>Amount</th><th>Status</th></tr></thead>
                    <tbody id="quotes-table-body"></tbody>
                </table>
            </div>
        </div>

        <div id="appointments" class="section-view">
            <div class="table-container">
                <div class="section-header">
                    <div class="section-title">Schedule Management</div>
                </div>
                <table>
                    <thead><tr><th>Date & Time</th><th>Client</th><th>Service</th><th>Technician</th><th>Status</th><th>Action</th></tr></thead>
                    <tbody id="appointments-table-body"></tbody>
                </table>
            </div>
        </div>

        <div id="payments" class="section-view">
            <div class="table-container">
                <div class="section-header">
                    <div class="section-title">Record Payment</div>
                </div>
                <form onsubmit="handlePaymentSubmit(event)">
                    <div class="form-grid">
                        <div class="form-group"><label>Reference #</label><input type="text" id="pay-ref" class="form-control" required placeholder="PAY-00X"></div>
                        <div class="form-group"><label>Client Name</label><input type="text" id="pay-client" class="form-control" required placeholder="Full Name"></div>
                    </div>
                    <div class="form-grid">
                        <div class="form-group"><label>Amount Received (₱)</label><input type="number" id="pay-amount" class="form-control" required placeholder="0.00"></div>
                        <div class="form-group"><label>Method</label>
                            <select id="pay-method" class="form-control"><option>Cash</option><option>GCash</option><option>Bank Transfer</option></select>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success">Record Transaction</button>
                </form>
            </div>

            <div class="table-container">
                <div class="section-header">
                    <div class="section-title">Payment History / Confirmation</div>
                </div>
                <table>
                    <thead><tr><th>Ref #</th><th>Client</th><th>Amount</th><th>Method</th><th>Date</th><th>Status</th></tr></thead>
                    <tbody id="payments-table-body"></tbody>
                </table>
            </div>
        </div>

    </main>

    <script>
        // --- 1. DATA SIMULATION (Persistent in LocalStorage) ---
        const defaultData = {
            customers: [
                {id: 'CL-001', name: 'Maria Cruz', contact: '0912 345 6789', email: 'maria@email.com'},
                {id: 'CL-002', name: 'Juan Dela Cruz', contact: '0998 765 4321', email: 'juan@email.com'},
                {id: 'CL-003', name: 'Ben Santos', contact: '0917 111 2222', email: 'ben@email.com'}
            ],
            requests: [
                {id: 'REQ-101', client: 'Maria Cruz', service: 'AC Leaking', status: 'Pending', date: 'Dec 12'},
                {id: 'REQ-102', client: 'Ben Santos', service: 'Installation', status: 'Approved', date: 'Dec 13'},
                {id: 'REQ-103', client: 'John Doe', service: 'Cleaning', status: 'Pending', date: 'Dec 14'}
            ],
            quotes: [
                {id: 'QT-501', client: 'Maria Cruz', amount: 2500, status: 'Pending'},
                {id: 'QT-502', client: 'Juan Dela Cruz', amount: 5000, status: 'Accepted'}
            ],
            appointments: [
                {id: 1, date: 'Dec 12 - 9:00 AM', client: 'Maria Cruz', service: 'AC Repair', tech: 'Unassigned', status: 'Pending'},
                {id: 2, date: 'Dec 12 - 1:00 PM', client: 'Juan Dela Cruz', service: 'Installation', tech: 'Tech Mike', status: 'Scheduled'}
            ],
            payments: [
                {ref: 'PAY-001', client: 'Juan Dela Cruz', amount: 5000, method: 'Cash', date: 'Dec 10', status: 'Completed'},
                {ref: 'PAY-002', client: 'Ben Santos', amount: 1500, method: 'GCash', date: 'Dec 11', status: 'Verifying'}
            ]
        };

        let db = JSON.parse(localStorage.getItem('sec_db')) || defaultData;

        function saveDb() {
            localStorage.setItem('sec_db', JSON.stringify(db));
            renderAll();
        }

        // --- 2. RENDER FUNCTIONS ---
        function renderAll() {
            renderStats();
            renderCustomers();
            renderRequests();
            renderQuotes();
            renderAppointments();
            renderPayments(); // New Render function
        }

        function renderStats() {
            document.getElementById('stat-appt').innerText = db.appointments.length;
            document.getElementById('stat-quotes').innerText = db.quotes.filter(q => q.status === 'Pending').length;
            document.getElementById('stat-req').innerText = db.requests.filter(r => r.status === 'Pending').length;
            
            const tbody = document.getElementById('dashboard-requests-body');
            tbody.innerHTML = '';
            db.requests.slice(0, 3).forEach(r => {
                let badge = r.status === 'Pending' ? 'bg-pending' : 'bg-success';
                tbody.innerHTML += `<tr><td>${r.client}</td><td>${r.service}</td><td>${r.date}</td><td><span class="badge ${badge}">${r.status}</span></td></tr>`;
            });
        }

        function renderCustomers() {
            const tbody = document.getElementById('customers-table-body');
            tbody.innerHTML = '';
            db.customers.forEach(c => {
                tbody.innerHTML += `<tr><td>${c.id}</td><td>${c.name}</td><td>${c.contact}</td><td>${c.email}</td><td><button class="btn btn-sm btn-primary" onclick="alert('Viewing Profile: ${c.name}')">View</button></td></tr>`;
            });
        }

        function renderRequests() {
            const tbody = document.getElementById('requests-table-body');
            tbody.innerHTML = '';
            db.requests.forEach((r, index) => {
                let badge = r.status === 'Pending' ? 'bg-pending' : (r.status === 'Approved' ? 'bg-success' : 'bg-danger');
                let actions = r.status === 'Pending' 
                    ? `<button class="btn btn-sm btn-success" onclick="updateReqStatus(${index}, 'Approved')">Approve</button> 
                       <button class="btn btn-sm btn-danger" onclick="updateReqStatus(${index}, 'Declined')">Decline</button>`
                    : `<span style="color:#aaa; font-size:12px;">Processed</span>`;
                
                tbody.innerHTML += `<tr><td>${r.id}</td><td>${r.client}</td><td>${r.service}</td><td><span class="badge ${badge}">${r.status}</span></td><td>${actions}</td></tr>`;
            });
        }

        function renderQuotes() {
            const tbody = document.getElementById('quotes-table-body');
            tbody.innerHTML = '';
            db.quotes.forEach(q => {
                let badge = q.status === 'Pending' ? 'bg-pending' : 'bg-success';
                tbody.innerHTML += `<tr><td>${q.id}</td><td>${q.client}</td><td>₱ ${q.amount.toLocaleString()}</td><td><span class="badge ${badge}">${q.status}</span></td></tr>`;
            });
        }

        function renderAppointments() {
            const tbody = document.getElementById('appointments-table-body');
            tbody.innerHTML = '';
            db.appointments.forEach((a, index) => {
                let techDisplay = a.tech === 'Unassigned' ? `<span style="color:red">Unassigned</span>` : `<span>${a.tech}</span>`;
                let action = a.tech === 'Unassigned' 
                    ? `<button class="btn btn-sm btn-accent" onclick="assignTech(${index})">Assign Tech</button>` 
                    : `<span class="badge bg-info">Scheduled</span>`;
                
                tbody.innerHTML += `<tr><td>${a.date}</td><td>${a.client}</td><td>${a.service}</td><td>${techDisplay}</td><td><span class="badge bg-pending">${a.status}</span></td><td>${action}</td></tr>`;
            });
        }

        function renderPayments() {
            const tbody = document.getElementById('payments-table-body');
            tbody.innerHTML = '';
            db.payments.forEach(p => {
                let badge = p.status === 'Completed' ? 'bg-success' : 'bg-pending';
                tbody.innerHTML += `
                    <tr>
                        <td>${p.ref}</td>
                        <td>${p.client}</td>
                        <td>₱ ${p.amount.toLocaleString()}</td>
                        <td>${p.method}</td>
                        <td>${p.date}</td>
                        <td><span class="badge ${badge}">${p.status}</span></td>
                    </tr>`;
            });
        }

        // --- 3. INTERACTION HANDLERS ---
        function updateReqStatus(index, status) {
            db.requests[index].status = status;
            if(status === 'Approved') {
                db.appointments.push({
                    id: Date.now(),
                    date: 'TBD',
                    client: db.requests[index].client,
                    service: db.requests[index].service,
                    tech: 'Unassigned',
                    status: 'Pending'
                });
                alert(`Request Approved! Moved to Appointments.`);
            }
            saveDb();
        }

        function handleQuoteSubmit(e) {
            e.preventDefault();
            const client = document.getElementById('quote-client').value;
            const amount = document.getElementById('quote-amount').value;
            
            db.quotes.unshift({
                id: '#QT-'+Math.floor(Math.random()*1000),
                client: client,
                amount: parseFloat(amount),
                status: 'Pending'
            });
            alert('Quote Generated and Sent Successfully!');
            saveDb();
            e.target.reset();
        }

        function assignTech(index) {
            const techName = prompt("Enter Technician Name (e.g., Tech Mike):");
            if(techName) {
                db.appointments[index].tech = techName;
                db.appointments[index].status = 'Scheduled';
                saveDb();
            }
        }

        function handlePaymentSubmit(e) {
            e.preventDefault();
            
            const newPayment = {
                ref: document.getElementById('pay-ref').value,
                client: document.getElementById('pay-client').value,
                amount: parseFloat(document.getElementById('pay-amount').value),
                method: document.getElementById('pay-method').value,
                date: new Date().toLocaleDateString('en-US', {month:'short', day:'numeric'}),
                status: 'Completed'
            };

            db.payments.unshift(newPayment); // Add to top of list
            alert("Payment Recorded Successfully!");
            saveDb(); // Saves and re-renders table
            e.target.reset();
        }

        function filterCustomers() {
            const input = document.getElementById('cust-search').value.toLowerCase();
            const rows = document.getElementById('customers-table-body').getElementsByTagName('tr');
            for (let i = 0; i < rows.length; i++) {
                const name = rows[i].getElementsByTagName('td')[1].textContent.toLowerCase();
                rows[i].style.display = name.indexOf(input) > -1 ? "" : "none";
            }
        }

        // --- 4. UI LOGIC ---
        const dateOptions = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        document.getElementById('current-date').innerText = new Date().toLocaleDateString('en-US', dateOptions);

        function switchTab(tabId, btn) {
            document.querySelectorAll('.section-view').forEach(el => el.classList.remove('active'));
            document.getElementById(tabId).classList.add('active');
            
            document.querySelectorAll('.nav-btn').forEach(el => el.classList.remove('active'));
            btn.classList.add('active');

            const titles = {
                'dashboard': 'Secretary Dashboard', 'customers': 'Customer Records',
                'requests': 'Service Requests', 'quotes': 'Quotation System',
                'appointments': 'Appointments', 'payments': 'Payment Processing'
            };
            document.getElementById('page-title').innerText = titles[tabId];
        }

        // Init
        renderAll();
    </script>
</body>
</html>
