<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ATMICX Workflow Demo - Complete Flow</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        h1 {
            text-align: center;
            color: white;
            font-size: 36px;
            margin-bottom: 40px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }
        .workflow-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 40px;
        }
        .workflow-card {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            position: relative;
            transition: transform 0.3s ease;
        }
        .workflow-card:hover {
            transform: translateY(-5px);
        }
        .workflow-card::after {
            content: '→';
            position: absolute;
            right: -30px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 40px;
            color: white;
            font-weight: bold;
        }
        .workflow-card:last-child::after {
            display: none;
        }
        .step-number {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 20px;
        }
        .step-title {
            font-size: 20px;
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 10px;
        }
        .step-role {
            font-size: 14px;
            color: #718096;
            margin-bottom: 15px;
        }
        .step-action {
            background: #f7fafc;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
        }
        .step-action h4 {
            font-size: 14px;
            color: #2d3748;
            margin-bottom: 8px;
        }
        .step-action p {
            font-size: 13px;
            color: #718096;
            line-height: 1.5;
        }
        .status-flow {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            margin-bottom: 40px;
        }
        .status-flow h2 {
            font-size: 24px;
            color: #1a202c;
            margin-bottom: 20px;
        }
        .status-list {
            display: flex;
            align-items: center;
            gap: 15px;
            overflow-x: auto;
            padding: 20px 0;
        }
        .status-item {
            background: #f7fafc;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 15px 25px;
            min-width: 180px;
            text-align: center;
            font-size: 14px;
            font-weight: 600;
            color: #2d3748;
            position: relative;
        }
        .status-item::after {
            content: '▶';
            position: absolute;
            right: -22px;
            top: 50%;
            transform: translateY(-50%);
            color: #cbd5e1;
            font-size: 20px;
        }
        .status-item:last-child::after {
            display: none;
        }
        .status-item.pending { background: #fff7ed; border-color: #fb923c; color: #c2410c; }
        .status-item.accepted { background: #dbeafe; border-color: #60a5fa; color: #1e40af; }
        .status-item.payment { background: #fef3c7; border-color: #fbbf24; color: #92400e; }
        .status-item.verified { background: #d1fae5; border-color: #34d399; color: #065f46; }
        .status-item.completed { background: #dcfce7; border-color: #22c55e; color: #166534; }
        
        .demo-section {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }
        .demo-section h2 {
            font-size: 24px;
            color: #1a202c;
            margin-bottom: 20px;
        }
        .btn {
            padding: 12px 24px;
            border-radius: 8px;
            border: none;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .demo-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-top: 20px;
        }
        .demo-card {
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
        }
        .demo-card h3 {
            font-size: 16px;
            color: #1a202c;
            margin-bottom: 15px;
        }
        
        @media (max-width: 1200px) {
            .workflow-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .workflow-card::after {
                display: none;
            }
        }
        
        @media (max-width: 768px) {
            .workflow-grid {
                grid-template-columns: 1fr;
            }
            .demo-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔄 ATMICX Complete Workflow</h1>
        
        <!-- Workflow Steps -->
        <div class="workflow-grid">
            <!-- Step 1: Client Shop -->
            <div class="workflow-card">
                <div class="step-number">1</div>
                <div class="step-title">Client Shop & Request</div>
                <div class="step-role">👤 CLIENT</div>
                <div class="step-action">
                    <h4>Actions:</h4>
                    <p>• Browse products/services<br>
                    • Submit service request<br>
                    • Provide requirements<br>
                    • Wait for quotation</p>
                </div>
                <div class="step-action" style="margin-top: 10px; background: #fff7ed;">
                    <h4>Status: Pending</h4>
                    <p>Request sent to Secretary</p>
                </div>
            </div>
            
            <!-- Step 2: Secretary Process -->
            <div class="workflow-card">
                <div class="step-number">2</div>
                <div class="step-title">Create Quotation</div>
                <div class="step-role">📋 SECRETARY</div>
                <div class="step-action">
                    <h4>Actions:</h4>
                    <p>• Review client request<br>
                    • Calculate pricing<br>
                    • Create quotation<br>
                    • Send to client</p>
                </div>
                <div class="step-action" style="margin-top: 10px; background: #dbeafe;">
                    <h4>Status: Awaiting Client</h4>
                    <p>Quotation sent to client</p>
                </div>
            </div>
            
            <!-- Step 3: Client Payment -->
            <div class="workflow-card">
                <div class="step-number">3</div>
                <div class="step-title">Review & Pay</div>
                <div class="step-role">💳 CLIENT</div>
                <div class="step-action">
                    <h4>Actions:</h4>
                    <p>• Review quotation<br>
                    • Accept/Decline<br>
                    • Upload payment proof<br>
                    • Submit for verification</p>
                </div>
                <div class="step-action" style="margin-top: 10px; background: #fef3c7;">
                    <h4>Status: Payment Submitted</h4>
                    <p>Waiting for verification</p>
                </div>
            </div>
            
            <!-- Step 4: Manager Verify -->
            <div class="workflow-card">
                <div class="step-number">4</div>
                <div class="step-title">Verify Payment</div>
                <div class="step-role">✅ MANAGER</div>
                <div class="step-action">
                    <h4>Actions:</h4>
                    <p>• Review payment proof<br>
                    • Verify amount<br>
                    • Approve/Reject<br>
                    • Complete order</p>
                </div>
                <div class="step-action" style="margin-top: 10px; background: #dcfce7;">
                    <h4>Status: Verified/Completed</h4>
                    <p>Order processed</p>
                </div>
            </div>
        </div>
        
        <!-- Status Flow -->
        <div class="status-flow">
            <h2>📊 Quotation Status Flow</h2>
            <div class="status-list">
                <div class="status-item pending">Pending</div>
                <div class="status-item accepted">Accepted</div>
                <div class="status-item payment">Payment Submitted</div>
                <div class="status-item verified">Verified</div>
                <div class="status-item completed">Completed</div>
            </div>
        </div>
        
        <!-- Demo Actions -->
        <div class="demo-section">
            <h2>🎯 Test Workflow</h2>
            <div class="demo-grid">
                <div class="demo-card">
                    <h3>Client Portal</h3>
                    <p style="color: #718096; font-size: 14px; margin: 15px 0;">Submit requests and pay for quotations</p>
                    <button class="btn btn-primary" onclick="window.location.href='clientLOGIN.html'">
                        Login as Client
                    </button>
                </div>
                
                <div class="demo-card">
                    <h3>Secretary Dashboard</h3>
                    <p style="color: #718096; font-size: 14px; margin: 15px 0;">Process requests and create quotations</p>
                    <button class="btn btn-primary" onclick="window.location.href='atmicxLOGIN.html'">
                        Login as Secretary
                    </button>
                </div>
                
                <div class="demo-card">
                    <h3>Manager Dashboard</h3>
                    <p style="color: #718096; font-size: 14px; margin: 15px 0;">Verify payments and approve orders</p>
                    <button class="btn btn-primary" onclick="window.location.href='atmicxLOGIN.html'">
                        Login as Manager
                    </button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
