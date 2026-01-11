<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ATMICX - Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            color: white;
            margin-bottom: 40px;
        }

        .header h1 {
            font-size: 48px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .header p {
            font-size: 18px;
            opacity: 0.9;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
            margin-bottom: 30px;
        }

        .dashboard-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            text-align: center;
        }

        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 30px 80px rgba(0,0,0,0.15);
        }

        .dashboard-card .icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            color: white;
        }

        .manager-card .icon {
            background: linear-gradient(135deg, #4f46e5, #4338ca);
        }

        .secretary-card .icon {
            background: linear-gradient(135deg, #10b981, #059669);
        }

        .dashboard-card h2 {
            font-size: 24px;
            margin-bottom: 10px;
            color: #374151;
        }

        .dashboard-card p {
            color: #6b7280;
            margin-bottom: 25px;
            line-height: 1.6;
        }

        .dashboard-card .access-btn {
            display: inline-block;
            padding: 12px 25px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .manager-card .access-btn {
            background: #4f46e5;
            color: white;
        }

        .manager-card .access-btn:hover {
            background: #4338ca;
            transform: translateY(-1px);
        }

        .secretary-card .access-btn {
            background: #10b981;
            color: white;
        }

        .secretary-card .access-btn:hover {
            background: #059669;
            transform: translateY(-1px);
        }

        .quick-actions {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
        }

        .quick-actions h3 {
            color: #374151;
            margin-bottom: 20px;
            font-size: 20px;
        }

        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .action-btn {
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 15px;
            text-decoration: none;
            color: #374151;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .action-btn:hover {
            border-color: #4f46e5;
            background: #4f46e5;
            color: white;
        }

        .session-status {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }

        .status-item {
            background: #f8fafc;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
        }

        .status-item.active {
            background: #d1fae5;
            color: #065f46;
        }

        .status-item.inactive {
            background: #fee2e2;
            color: #991b1b;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>ATMICX</h1>
            <p>Laundry Machine Trading System - Unified Dashboard</p>
        </div>

        <div class="session-status">
            <h3 style="text-align: center; margin-bottom: 15px; color: #374151;">Session Status</h3>
            <div class="status-grid">
                <div class="status-item" id="manager-status">
                    <i class="fas fa-user-tie"></i> Manager Session: <span id="manager-text">Checking...</span>
                </div>
                <div class="status-item" id="secretary-status">
                    <i class="fas fa-user-edit"></i> Secretary Session: <span id="secretary-text">Checking...</span>
                </div>
            </div>
        </div>

        <div class="dashboard-grid">
            <div class="dashboard-card manager-card">
                <div class="icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h2>Manager Dashboard</h2>
                <p>Approve quotations, verify payments, manage transactions, and oversee the complete workflow from secretary submissions to client approvals.</p>
                <a href="atmicxMANAGER.php?auto_login=1" class="access-btn">
                    <i class="fas fa-sign-in-alt"></i> Access Manager
                </a>
            </div>

            <div class="dashboard-card secretary-card">
                <div class="icon">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <h2>Secretary Interface</h2>
                <p>Create quotations for clients, upload proof documents, submit requests to manager for approval, and track submission status.</p>
                <a href="armicxSECRETARY.php?auto_login=1" class="access-btn">
                    <i class="fas fa-sign-in-alt"></i> Access Secretary
                </a>
            </div>
        </div>

        <div class="quick-actions">
            <h3>Quick Actions</h3>
            <div class="actions-grid">
                <a href="unified_login.php" class="action-btn">
                    <i class="fas fa-sign-in-alt"></i>
                    Fresh Login
                </a>
                <a href="clientNEW.php" class="action-btn">
                    <i class="fas fa-user"></i>
                    Client Portal
                </a>
                <a href="manager_debug.php" class="action-btn">
                    <i class="fas fa-bug"></i>
                    Debug Tools
                </a>
                <a href="logout.php" class="action-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout All
                </a>
            </div>
        </div>
    </div>

    <script>
        // Check session status for both roles
        async function checkSessionStatus() {
            try {
                // Check manager session
                const managerResponse = await fetch('role_session_check.php?role=manager');
                const managerData = await managerResponse.json();
                
                const managerStatus = document.getElementById('manager-status');
                const managerText = document.getElementById('manager-text');
                
                if (managerData.active) {
                    managerStatus.className = 'status-item active';
                    managerText.textContent = 'Active';
                } else {
                    managerStatus.className = 'status-item inactive';
                    managerText.textContent = 'Inactive';
                }

                // Check secretary session
                const secretaryResponse = await fetch('role_session_check.php?role=secretary');
                const secretaryData = await secretaryResponse.json();
                
                const secretaryStatus = document.getElementById('secretary-status');
                const secretaryText = document.getElementById('secretary-text');
                
                if (secretaryData.active) {
                    secretaryStatus.className = 'status-item active';
                    secretaryText.textContent = 'Active';
                } else {
                    secretaryStatus.className = 'status-item inactive';
                    secretaryText.textContent = 'Inactive';
                }

            } catch (error) {
                console.error('Error checking session status:', error);
            }
        }

        // Check status when page loads
        window.onload = checkSessionStatus;
        
        // Refresh status every 30 seconds
        setInterval(checkSessionStatus, 30000);
    </script>
</body>
</html>