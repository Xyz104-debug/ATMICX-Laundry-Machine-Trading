<?php
session_start();

// Prevent browser caching
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
header("Pragma: no-cache"); // HTTP 1.0.
header("Expires: 0"); // Proxies.

if (!isset($_SESSION['client_id']) || $_SESSION['role'] !== 'client') {
    header('Location: clientLOGIN.html');
    exit;
}

// Backward compatibility: If the name is not in the session, fetch it
if (!isset($_SESSION['name'])) {
    $host = 'localhost';
    $dbname = 'atmicxdb';
    $username_db = 'root';
    $password_db = '';

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username_db, $password_db);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $pdo->prepare("SELECT Name FROM client WHERE Client_ID = ?");
        $stmt->execute([$_SESSION['client_id']]);
        $client = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($client) {
            $_SESSION['name'] = $client['Name'];
        } else {
            // Handle case where client_id is invalid, though unlikely if they are logged in
            $_SESSION['name'] = 'Guest'; 
        }
    } catch (PDOException $e) {
        // If DB fails, use a default name to prevent fatal errors
        $_SESSION['name'] = 'Client';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Portal | ATMICX Trading</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        /* --- PREMIUM THEME VARIABLES (Secretary/Admin Theme) --- */
        :root {
            --navy-dark: #0f172a;       /* Deep Navy */
            --navy-light: #1e293b;
            --gold: #d4af37;            /* Metallic Gold - Primary Accent */ 
            --bg-body: #f8fafc;         /* Light Gray Body */
            --white: #ffffff;

            /* --- NEW COLORS FOR SECTION THEMES --- */
            --theme-shop-primary: #6c5ce7; /* Deep Purple for Business/Shop - Theme: Investment & Growth */
            --theme-shop-secondary: #a29bfe;
            --theme-repair-primary: #008080; /* Dark Teal for Repair/Service - Theme: Service & Solution */
            --theme-repair-secondary: #48d1cc; 
            
            /* --- CHINA BANK THEME VARIABLES (NEW) --- */
            --china-bank-green: #006841; /* Deep Green - Primary Brand Color */
            --china-bank-gold-accent: #FFC72C; /* Gold/Yellow - Accent Color */
            --china-bank-light-green: #E8F5E9; /* Lightest Green - Background for fields */
            
            --success-bg: #dcfce7; --success-text: #166534;
            --warning-bg: #fff7ed; --warning-text: #9a3412;
            --danger-bg: #fee2e2;  --danger-text: #991b1b;
            --info-bg: #e0f2fe;    --info-text: #075985;

            --text-main: #334155;
            --text-muted: #64748b;

            --shadow-card: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            --shadow-hover: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
            --radius-lg: 16px;
            --radius-md: 12px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }

        body {
            display: flex;
            background-color: var(--bg-body);
            height: 100vh;
            color: var(--text-main);
            overflow: hidden;
        }

        /* --- SIDEBAR --- */
        .sidebar {
            width: 280px;
            background: var(--navy-dark);
            color: var(--white);
            display: flex;
            flex-direction: column;
            padding: 24px;
            flex-shrink: 0;
            z-index: 20;
        }

        .brand-container { margin-bottom: 20px; }
        .brand { display: flex; align-items: center; gap: 12px; margin-bottom: 24px; }
        .brand i { color: var(--gold); font-size: 24px; }
        .brand h2 { font-size: 20px; font-weight: 700; letter-spacing: -0.02em; margin: 0; }
        .brand span { color: var(--gold); }

        .user-profile-box {
            background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: var(--radius-md); padding: 12px 16px; display: flex; align-items: center; gap: 12px; margin-bottom: -8px; transition: var(--transition); cursor: pointer; position: relative;
        }
        .user-profile-box:hover { background: rgba(255, 255, 255, 0.08); border-color: rgba(255, 255, 255, 0.2); transform: translateY(-1px); }
        .avatar { width: 38px; height: 38px; border-radius: 8px; background: linear-gradient(135deg, var(--gold), #fcd34d); color: var(--navy-dark); display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.2); }
        .user-info { flex: 1; }
        .user-info .name { font-size: 14px; font-weight: 600; color: white; line-height: 1.2; display: flex; align-items: center; gap: 6px; }
        .user-info .role { font-size: 11px; color: #94a3b8; font-weight: 500; margin-top: 2px; }
        .settings-icon { color: #94a3b8; font-size: 14px; transition: 0.2s; }
        .user-profile-box:hover .settings-icon { color: var(--gold); transform: rotate(90deg); }

        .nav-links { list-style: none; flex: 1; overflow-y: auto; padding-top: 10px;}
        .nav-item { margin-bottom: 6px; }
        
        .nav-btn { width: 100%; display: flex; align-items: center; gap: 14px; padding: 14px 16px; background: transparent; border: none; color: #94a3b8; font-size: 14px; font-weight: 500; cursor: pointer; border-radius: var(--radius-md); transition: var(--transition); }
        .nav-btn:hover { background: rgba(255, 255, 255, 0.05); color: var(--white); transform: translateX(4px); }
        .nav-btn.active { 
            background: linear-gradient(90deg, var(--gold), #b49226); 
            color: var(--navy-dark); 
            font-weight: 700;
            box-shadow: 0 4px 12px rgba(212, 175, 55, 0.2);
        }
        .nav-btn.active:hover { transform: none; }

        .sidebar-footer { margin-top: auto; padding-top: 20px; border-top: 1px solid rgba(255, 255, 255, 0.1); display: flex; justify-content: center; }
        .logout-btn { width: 100%; display: flex; align-items: center; justify-content: center; gap: 10px; padding: 12px; background: rgba(239, 68, 68, 0.15); color: #fca5a5; border: 1px solid rgba(239, 68, 68, 0.2); border-radius: var(--radius-md); cursor: pointer; font-weight: 600; transition: var(--transition); }
        .logout-btn:hover { background: #ef4444; color: white; border-color: #ef4444; }

        /* --- MAIN CONTENT --- */
        .main-content { flex: 1; display: flex; flex-direction: column; overflow: hidden; position: relative; }
        .header { height: 80px; background: var(--white); display: flex; justify-content: space-between; align-items: center; padding: 0 40px; flex-shrink: 0; border-bottom: 1px solid #e2e8f0; }
        .header h1 { font-size: 24px; font-weight: 800; color: var(--navy-dark); letter-spacing: -0.02em; }
        .header-actions { display: flex; gap: 16px; align-items: center; position: relative; }
        
        .notif-btn { position: relative; width: 40px; height: 40px; border-radius: 50%; background: var(--bg-body); border: 1px solid #e2e8f0; cursor: pointer; display: flex; justify-content: center; align-items: center; color: var(--navy-dark); transition: 0.2s; }
        .notif-btn:hover { background: #f1f5f9; }
        .notif-badge { position: absolute; top: -2px; right: -2px; width: 10px; height: 10px; background: #ef4444; border: 2px solid #fff; border-radius: 50%; }

        /* MODIFIED: Reduced padding for less whitespace */
        .dashboard-view { flex: 1; overflow-y: auto; padding: 24px 30px; scrollbar-width: thin; }
        .section { display: none; opacity: 0; transform: translateY(10px); transition: all 0.4s ease; }
        .section.active { display: block; opacity: 1; transform: translateY(0); }

        /* --- UTILITY COMPONENTS (FOR BALANCED UI) --- */
        .panel { background: var(--white); border-radius: var(--radius-lg); padding: 32px; box-shadow: var(--shadow-card); height: 655px; border: 1px solid #e2e8f0; display: flex; flex-direction: column; }
        .panel-title { font-size: 18px; font-weight: 700; color: var(--navy-dark); }
        .status-badge { padding: 6px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; display: inline-flex; align-items: center; gap: 6px; }
        .status-ok { background: var(--success-bg); color: var(--success-text); }
        .status-warn { background: var(--warning-bg); color: var(--warning-text); }
        .status-err { background: var(--danger-bg); color: var(--danger-text); }
        .status-info { background: var(--info-bg); color: var(--info-text); }
        .status-awaiting { background: #fff7ed; color: #9a3412; }
        .status-payment { background: #fef3c7; color: #92400e; }
        .status-verifying { background: #fef3c7; color: #b45309; }
        .status-verified { background: #d1fae5; color: #065f46; }
        .status-completed { background: #dcfce7; color: #166534; }
        .status-rejected { background: #fee2e2; color: #991b1b; }
        .status-declined { background: #f3f4f6; color: #6b7280; }

        .btn { padding: 10px 18px; border-radius: 8px; border: none; font-weight: 600; cursor: pointer; transition: 0.2s; font-size: 13px; display: inline-flex; align-items: center; justify-content: center; gap: 8px; }
        .btn-primary { background: var(--navy-dark); color: white; width: 100%; }
        .btn-primary:hover { background: var(--navy-light); transform: translateY(-2px); }
        .btn-danger { background: white; border: 1px solid var(--danger-text); color: var(--danger-text); width: 100%; }
        .btn-danger:hover { background: var(--danger-bg); }
        .btn-outline { background: white; border: 1px solid #cbd5e1; color: var(--text-main); width: auto; }
        .btn-outline:hover { background: #f8fafc; }
        .btn-gold { background: var(--gold); color: var(--navy-dark); font-weight: 700; }
        .btn-gold:hover { background: #b49226; color: white; transform: translateY(-2px); }
        .btn-success { background: #10b981; color: white; }
        .btn-success:hover { background: #059669; transform: translateY(-2px); }
        
        .tabs { display: flex; gap: 10px; margin-bottom: 30px; border-bottom: 2px solid #e2e8f0; }
        .tab { padding: 12px 24px; cursor: pointer; color: var(--text-muted); font-weight: 600; border-bottom: 2px solid transparent; margin-bottom: -2px; transition: 0.2s; }
        .tab:hover { color: var(--navy-dark); }
        .tab.active { color: var(--navy-dark); border-bottom-color: var(--gold); }
        
        /* Form styles */
        .form-group { margin-bottom: 15px; }
        .form-label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 8px; color: var(--text-main); }
        .form-control { width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px; background: #f8fafc; transition: 0.2s; }
        .form-control:focus { background: white; border-color: var(--gold); outline: none; }
        .radio-group { display: flex; flex-direction: column; gap: 10px; margin-top: 10px; }
        .radio-item { display: flex; align-items: center; gap: 10px; font-size: 14px; }

        /* Special Cards */
        .welcome-panel { 
            background: linear-gradient(135deg, var(--navy-dark) 0%, var(--navy-light) 100%); 
            color: white; 
            padding: 70px 50px; 
            border-radius: var(--radius-lg); 
            margin-bottom: 16px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.2);
            min-height: 380px;
        }
        .welcome-panel h1 { font-size: 48px; margin-bottom: 12px; }
        .welcome-panel p { opacity: 0.8; font-size: 18px; }
        
        /* Two-Column Grid for main action cards */
        .action-grid-2 { 
            display: grid; 
            grid-template-columns: 1fr 1fr; 
            gap: 16px; 
            margin-top: 16px; 
            margin-bottom: 24px; 
        }

        /* Style for the relocated Asset Summary card */
        .assets-summary-card { 
            background: var(--white); 
            border: 1px solid #e2e8f0; 
            padding: 20px; 
            border-radius: var(--radius-lg); 
            box-shadow: var(--shadow-card);
            display: flex; 
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        /* MODIFIED: Increased Size and Engagement */
        .action-card {
            background: var(--white); 
            padding: 30px; 
            border-radius: var(--radius-lg); 
            border: 1px solid #e2e8f0; 
            transition: var(--transition);
            display: flex; 
            flex-direction: column; 
            justify-content: space-between;
            box-shadow: var(--shadow-card);
            min-height: 300px; 
        }
        
        .action-card:hover { 
            transform: translateY(-5px); 
            box-shadow: 0 30px 40px -10px rgba(0, 0, 0, 0.15); 
            border-color: var(--gold); 
        }
        .action-card:nth-child(2):hover {
            border-color: var(--navy-dark);
        }

        /* Card 1: Shop - Gold Theme with Gradient (V2) */
        .action-card:nth-child(1), .card-shop-business {
            background: linear-gradient(135deg, var(--theme-shop-primary) 0%, var(--theme-shop-secondary) 100%);
            border: 2px solid var(--theme-shop-primary);
            border-left: 8px solid var(--theme-shop-primary); 
            padding-left: 25px;
            color: white;
        }
        .action-card:nth-child(1) h3, .card-shop-business h3 {
            color: white;
        }
        .action-card:nth-child(1) p, .card-shop-business p {
            color: rgba(255, 255, 255, 0.9);
        }
        .action-card:nth-child(1) .card-icon, .card-shop-business .card-icon {
            color: white;
        }
        .action-card:nth-child(1) .btn-primary, .card-shop-business .btn-primary {
            background: white;
            color: var(--theme-shop-primary);
            font-weight: 700;
        }
        .action-card:nth-child(1) .btn-primary:hover, .card-shop-business .btn-primary:hover {
            background: #f1f5f9;
            color: var(--theme-shop-primary);
            transform: translateY(-2px);
        }

        /* Card 2: Maintenance - Teal Gradient Theme (V2) */
        .action-card:nth-child(2), .card-maintenance {
            background: linear-gradient(135deg, var(--theme-repair-primary) 0%, var(--theme-repair-secondary) 100%);
            border: 2px solid var(--theme-repair-primary);
            border-left: 8px solid var(--theme-repair-primary); 
            padding-left: 25px;
            color: white;
        }
        .action-card:nth-child(2) h3, .card-maintenance h3 {
            color: white;
        }
        .action-card:nth-child(2) p, .card-maintenance p {
            color: rgba(255, 255, 255, 0.9);
        }
        .action-card:nth-child(2) .card-icon, .card-maintenance .card-icon {
            color: white;
        }
        .action-card:nth-child(2) .btn-outline, .card-maintenance .btn-outline {
            background: white;
            border-color: white;
            color: var(--theme-repair-primary);
            font-weight: 700;
        }
        .action-card:nth-child(2) .btn-outline:hover, .card-maintenance .btn-outline:hover {
            background: #f1f5f9;
            color: var(--theme-repair-primary);
            transform: translateY(-2px);
        }
        /* End Enhanced Engagement Styles */

        .card-icon { 
            font-size: 48px; 
            color: var(--gold); 
            margin-bottom: 20px; 
        }

        .action-card h3 { 
            font-size: 24px; 
            margin-bottom: 8px; 
            color: var(--navy-dark); 
        }

        .action-card p { 
            font-size: 14px; 
            color: var(--text-muted); 
            margin-bottom: 25px; 
        }
        
        /* MODIFIED: Dashboard Quotes Styling to be row-based */
        .recent-quotes-panel {
            background: var(--white);
            border-radius: var(--radius-lg);
            padding: 24px;
            box-shadow: var(--shadow-card);
            border: 1px solid #e2e8f0;
            margin-bottom: 24px;
        }
        .recent-quotes-list {
            display: block; /* Change from grid to block list */
            margin-top: 15px;
            border: 1px solid #e2e8f0;
            border-radius: var(--radius-md);
            overflow: hidden; /* Contains alternating colors */
        }
        
        /* New row styling */
        .dash-quote-item {
            padding: 15px;
            border: none;
            transition: background-color 0.2s;
            cursor: pointer;
            display: flex; 
            justify-content: space-between;
            align-items: center;
        }
        
        /* Alternating row colors (Dark/Light) */
        .dash-quote-item:nth-child(odd) {
            background-color: var(--white); /* Light row */
        }
        .dash-quote-item:nth-child(even) {
            background-color: #f8fafc; /* "Darker" row (very light gray accent) */
        }

        .dash-quote-item:hover {
            background: #f1f5f9; /* Consistent hover state */
        }
        .dash-quote-item:hover .dash-quote-amount {
            color: var(--navy-dark);
        }

        /* Internal layout for rows */
        .dash-quote-details {
            flex: 1; 
        }
        .dash-quote-item h5 {
            font-size: 14px;
            font-weight: 700;
            color: var(--navy-dark);
            margin-bottom: 4px;
        }
        .dash-quote-item .dash-quote-amount {
            font-size: 16px; 
            font-weight: 800;
            color: var(--gold); 
            margin: 0;
            flex-shrink: 0; 
            text-align: right;
        }
        .dash-quote-item .dash-quote-status {
            font-size: 11px;
            font-weight: 600;
            display: flex;
            gap: 10px;
            align-items: center;
        }


        /* Shop Styles */
        .logistics-box { background: #f8fafc; padding: 20px; border-radius: 10px; border: 1px solid #e2e8f0; margin-bottom: 20px; }
        
        /* Package selector for horizontal scrolling */
        .package-selector { 
            display: flex; 
            gap: 15px; 
            margin-bottom: 25px; 
            overflow-x: auto; 
            padding-bottom: 15px; 
            min-width: 100%;
        }
        /* pkg-option is now a label (V2 with Gradient Selected State) */
        .pkg-option {
            min-width: 200px; 
            flex-shrink: 0;
            border: 2px solid #e2e8f0; 
            padding: 20px; 
            border-radius: var(--radius-md); 
            cursor: pointer; 
            text-align: center; 
            transition: 0.2s;
            position: relative; /* Needed for absolute positioning of checkbox */
            background: white;
        }
        .pkg-option:hover { 
            border-color: var(--theme-shop-primary); 
            transform: translateY(-3px);
            box-shadow: 0 8px 16px rgba(108, 92, 231, 0.15);
        }
        .pkg-option.selected { 
            background: linear-gradient(135deg, var(--theme-shop-primary) 0%, var(--theme-shop-secondary) 100%);
            border-color: var(--theme-shop-primary);
            color: white;
        }
        .pkg-option.selected h4 {
            color: white;
        }
        .pkg-option.selected span {
            color: white;
        }
        .pkg-option.selected p {
            color: rgba(255, 255, 255, 0.9);
        }
        /* Custom styling for the hidden checkbox control */
        .pkg-checkbox, .pkg-checkbox-machine { 
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            cursor: pointer;
        }

        .pkg-option h4 { font-size: 16px; margin-bottom: 5px; color: var(--navy-dark); }
        .pkg-option span { font-size: 15px; font-weight: 700; color: var(--theme-shop-primary); }
        
        /* Overriding span size for machine-only display */
        .machine-selector .pkg-option span {
            font-size: 22px; 
        }

        .total-summary-card { background: var(--navy-dark); color: white; padding: 25px; border-radius: var(--radius-lg); text-align: center; margin-top: 20px; }
        .total-summary-card .label { font-size: 14px; color: #94a3b8; margin-bottom: 4px; }
        .total-summary-card .value { font-size: 38px; font-weight: 800; color: var(--theme-shop-primary); margin: 5px 0; }
        .total-summary-card .note { font-size: 12px; opacity: 0.8; }
        
        /* Maintenance Styles */
        .maintenance-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 25px;
        }
        .machine-list { border: 1px solid #e2e8f0; border-radius: var(--radius-md); overflow: hidden; }
        .machine-item { padding: 15px; border-bottom: 1px solid #e2e8f0; display: flex; align-items: flex-start; gap: 15px; background: var(--white); }
        .machine-item:last-child { border-bottom: none; }
        .machine-item input[type="checkbox"] { margin-top: 8px; transform: scale(1.0); } 
        .m-details { flex: 1; }
        .m-details h5 { font-size: 15px; margin-bottom: 2px; color: var(--navy-dark); }
        .m-details p { font-size: 12px; color: var(--text-muted); }
        .labor-est { font-weight: 700; color: #10b981; font-size: 13px; margin-top: 5px; }
        .alert-note { background: var(--warning-bg); padding: 10px; font-size: 12px; color: var(--warning-text); margin-top: 5px; border-radius: 6px; border: 1px solid #fcd34d;}

        /* Quotations Styles */
        .quotations-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .quotations-title {
            font-size: 24px;
            font-weight: 700;
            color: #1f2937;
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0;
        }
        
        .notification-badge {
            color: #ef4444;
            font-size: 20px;
            margin-left: 5px;
        }
        
        .quotations-section {
            background: #ffffff;
        }
        
        .section-subtitle {
            font-size: 16px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 20px;
            padding: 0;
        }
        
        .quotations-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        
        .quotation-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 24px;
            position: relative;
            transition: all 0.2s ease;
        }
        
        .quotation-card:hover {
            border-color: #d1d5db;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        
        .quotation-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
        }
        
        .quotation-title {
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
            margin: 0 0 4px 0;
        }
        
        .quotation-ref {
            font-size: 13px;
            color: #6b7280;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .quotation-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-awaiting {
            background-color: #fef3c7;
            color: #d97706;
        }
        
        .status-payment {
            background-color: #dbeafe;
            color: #2563eb;
        }
        
        .status-completed {
            background-color: #d1fae5;
            color: #059669;
        }
        
        .quotation-content {
            margin-bottom: 20px;
        }
        
        .quotation-meta {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 16px;
        }
        
        .quotation-details {
            flex: 1;
        }
        
        .quotation-date {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 4px;
        }
        
        .quotation-items {
            font-size: 14px;
            color: #374151;
            font-weight: 500;
        }
        
        .quotation-amount {
            font-size: 24px;
            font-weight: 800;
            color: #d97706;
            text-align: right;
        }
        
        .quotation-actions {
            display: flex;
            gap: 12px;
            align-items: center;
        }
        
        .btn-pay-now {
            flex: 1;
            background: linear-gradient(135deg, #d97706 0%, #f59e0b 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .btn-pay-now:hover {
            background: linear-gradient(135deg, #b45309 0%, #d97706 100%);
            transform: translateY(-1px);
        }
        
        .btn-decline {
            background: none;
            color: #ef4444;
            border: none;
            padding: 8px 16px;
            font-size: 14px;
            cursor: pointer;
            transition: color 0.2s ease;
        }
        
        .btn-decline:hover {
            color: #dc2626;
            text-decoration: underline;
        }
        /* Unequal grid for payments (1/3rd and 2/3rds split) */
        .payment-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 20px;
        }

        /* NOTE: quote-card margin-bottom is set to 0 to prevent double spacing with the grid gap */
        .quote-card { 
            background: var(--bg-body); 
            border: 1px solid #e2e8f0; 
            border-radius: var(--radius-md); 
            padding: 25px; 
            margin-bottom: 0px; 
            box-shadow: var(--shadow-card); 
            display: flex; 
            flex-direction: column; 
            justify-content: space-between;
            transition: var(--transition);
            cursor: pointer;
        }
        .quote-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
            border-color: var(--gold);
        }
        .quote-header { display: flex; justify-content: space-between; border-bottom: 1px solid #e2e8f0; padding-bottom: 15px; margin-bottom: 15px; }
        .quote-row { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 14px; color: var(--text-main); }
        .quote-total { border-top: 2px dashed #e2e8f0; padding-top: 15px; margin-top: 15px; display: flex; justify-content: space-between; font-weight: 800; font-size: 20px; color: var(--navy-dark); }
        .payment-terms { background: var(--info-bg); border: 1px solid #0ea5e9; padding: 15px; border-radius: 8px; margin: 20px 0; font-size: 13px; color: var(--info-text); }
        
        /* Payments Styles - China Bank Theme */
        .bank-details { 
            background: linear-gradient(135deg, var(--china-bank-green) 0%, #005030 100%); 
            color: white; 
            padding: 25px; 
            border-radius: var(--radius-md); 
            margin-bottom: 25px; 
            text-align: center; 
            border: 3px solid var(--china-bank-gold-accent);
            box-shadow: 0 8px 20px rgba(0, 104, 65, 0.3);
        }
        .bank-details h3 { 
            color: var(--china-bank-gold-accent); 
            margin-bottom: 15px;
            font-size: 20px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .bank-row { 
            display: flex; 
            justify-content: space-between; 
            margin-bottom: 8px; 
            font-size: 14px; 
            border-bottom: 1px solid rgba(255, 199, 44, 0.2); 
            padding-bottom: 5px;
        }
        .bank-row span:first-child {
            color: rgba(255, 255, 255, 0.8);
        }
        .bank-row span:last-child {
            font-weight: 700;
            color: var(--china-bank-gold-accent);
        }
        .bank-details p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 13px;
            margin-top: 10px;
        }

        /* Payment Action History Grid */
        .payment-action-history-grid {
            display: grid;
            grid-template-columns: 1fr 1.5fr;
            gap: 20px;
        }
        .payment-action-history-grid .left-col,
        .payment-action-history-grid .right-col {
            display: flex;
            flex-direction: column;
        }
        .table-panel {
            background: var(--white);
            border-radius: var(--radius-lg);
            padding: 25px;
            box-shadow: var(--shadow-card);
            border: 1px solid #e2e8f0;
        }
        .payment-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .payment-table thead {
            background: var(--navy-dark);
            color: white;
        }
        .payment-table th {
            padding: 12px;
            text-align: left;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
        }
        .payment-table tbody tr {
            background: #f8fafc;
            border-radius: 8px;
            transition: 0.2s;
        }
        .payment-table tbody tr:hover {
            background: #f1f5f9;
        }
        .payment-table td {
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 13px;
        }
        .payment-table td:first-child {
            border-top-left-radius: 8px;
            border-bottom-left-radius: 8px;
        }
        .payment-table td:last-child {
            border-top-right-radius: 8px;
            border-bottom-right-radius: 8px;
        }

        /* Payment History Table Styles */
        .payment-history-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            border-radius: var(--radius-md);
            overflow: hidden;
            box-shadow: var(--shadow-card);
        }
        .payment-history-table thead {
            background: var(--navy-dark);
            color: white;
        }
        .payment-history-table th {
            padding: 15px;
            text-align: left;
            font-weight: 700;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .payment-history-table td {
            padding: 15px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 14px;
            color: var(--text-main);
        }
        .payment-history-table tr:hover {
            background: #f8fafc;
        }
        .payment-history-table tr:last-child td {
            border-bottom: none;
        }

        /* --- SCHEDULE STYLES --- */
        .schedule-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .schedule-item {
            background: var(--white);
            border: 1px solid #e2e8f0;
            border-radius: var(--radius-md);
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: var(--transition);
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            cursor: pointer;
        }

        .schedule-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            border-color: var(--gold);
        }

        .schedule-date {
            flex-shrink: 0;
            width: 120px;
            text-align: center;
            border-right: 1px solid #e2e8f0;
            padding-right: 20px;
            margin-right: 20px;
        }
        .schedule-date .day {
            font-size: 32px;
            font-weight: 800;
            color: var(--navy-dark);
            line-height: 1;
        }
        .schedule-date .month {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
        }

        .schedule-details {
            flex: 1;
        }
        .schedule-details h4 {
            font-size: 16px;
            font-weight: 700;
            color: var(--navy-dark);
            margin-bottom: 5px;
        }
        .schedule-details p {
            font-size: 13px;
            color: var(--text-muted);
        }
        .schedule-time {
            font-weight: 700;
            color: #10b981;
        }
        .schedule-actions {
            flex-shrink: 0;
        }

        .modal-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: none; /* Default to hidden */
            justify-content: center;
            align-items: center;
            z-index: 1000;
            padding: 20px;
        }
        .modal-overlay.show {
            display: flex;
        }
        .modal-content {
            width: 100%;
            max-width: 500px;
            background: var(--white);
            border-radius: var(--radius-lg);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            animation: fadeInScale 0.3s ease-out;
            max-height: 90vh;
            overflow-y: auto;
        }
        /* Specific max-width for delete modal */
        .modal-content.delete-confirm {
            max-width: 400px;
            padding: 0;
            text-align: center;
        }
        @keyframes fadeInScale {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }
        .modal-header {
            padding: 20px 32px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-header .panel-title { margin: 0; }
        .modal-close-btn {
            background: none;
            border: none;
            font-size: 18px;
            color: var(--text-muted);
            cursor: pointer;
            transition: color 0.2s;
        }
        .modal-close-btn:hover { color: var(--danger-text); }
        .modal-close {
            background: none;
            border: none;
            font-size: 18px;
            color: var(--text-muted);
            cursor: pointer;
            transition: color 0.2s;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }
        .modal-close:hover { 
            color: var(--danger-text); 
            background: rgba(239, 68, 68, 0.1);
        }
        .modal-body {
            padding: 32px;
        }

        /* Toast Notification Styles */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 10px;
            pointer-events: none;
        }
        .toast {
            background: white;
            padding: 16px 20px;
            border-radius: var(--radius-md);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 300px;
            max-width: 400px;
            pointer-events: all;
            animation: slideInRight 0.3s ease-out;
            border-left: 4px solid #10b981;
        }
        @keyframes slideInRight {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        .toast.toast-success {
            border-left-color: #10b981;
        }
        .toast.toast-success .toast-icon {
            color: #10b981;
        }
        .toast.toast-error {
            border-left-color: #ef4444;
        }
        .toast.toast-error .toast-icon {
            color: #ef4444;
        }
        .toast.toast-warning {
            border-left-color: #f59e0b;
        }
        .toast.toast-warning .toast-icon {
            color: #f59e0b;
        }
        .toast.toast-info {
            border-left-color: #3b82f6;
        }
        .toast.toast-info .toast-icon {
            color: #3b82f6;
        }
        .toast-icon {
            font-size: 20px;
            flex-shrink: 0;
        }
        .toast-content {
            flex: 1;
        }
        .toast-title {
            font-weight: 700;
            font-size: 14px;
            color: var(--navy-dark);
            margin-bottom: 2px;
        }
        .toast-message {
            font-size: 13px;
            color: var(--text-muted);
        }

        /* Dashboard Home Specific Styles */
        .action-grid-2 {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 24px;
        }
        .dashboard-left-panel,
        .dashboard-right-panel {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }
        .gradient-action-card {
            background: var(--white);
            padding: 30px;
            border-radius: var(--radius-lg);
            border: 2px solid #e2e8f0;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .gradient-action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 30px 40px -10px rgba(0, 0, 0, 0.15);
        }
        .gradient-action-card .card-icon {
            font-size: 48px;
            color: white;
            margin-bottom: 10px;
        }
        .gradient-action-card h3 {
            color: white;
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        .gradient-action-card p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 14px;
        }
        .gradient-action-card.card-shop-business {
            background: linear-gradient(135deg, var(--theme-shop-primary) 0%, var(--theme-shop-secondary) 100%);
            border-color: var(--theme-shop-primary);
            border-left: 8px solid var(--theme-shop-primary);
        }
        .gradient-action-card.card-maintenance {
            background: linear-gradient(135deg, var(--theme-repair-primary) 0%, var(--theme-repair-secondary) 100%);
            border-color: var(--theme-repair-primary);
            border-left: 8px solid var(--theme-repair-primary);
        }

        /* Package options V2 styles */
        .pkg-option-v2 {
            min-width: 280px;
            max-width: 300px;
            flex-shrink: 0;
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: var(--radius-lg);
            padding: 25px;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            flex-direction: column;
            gap: 15px;
            position: relative;
        }
        .pkg-option-v2:hover {
            border-color: var(--theme-shop-primary);
            transform: translateY(-3px);
            box-shadow: 0 8px 16px rgba(108, 92, 231, 0.15);
        }
        .pkg-option-v2.selected {
            background: linear-gradient(135deg, var(--theme-shop-primary) 0%, var(--theme-shop-secondary) 100%);
            border-color: var(--theme-shop-primary);
            color: white;
        }
        .pkg-option-v2.selected h4,
        .pkg-option-v2.selected p,
        .pkg-option-v2.selected .price-large,
        .pkg-option-v2.selected .price-note {
            color: white !important;
        }
        .pkg-option-v2.selected .card-icon-box i {
            color: white;
        }
        .pkg-option-v2.selected .features-list li {
            color: rgba(255, 255, 255, 0.9);
        }
        .pkg-option-v2.selected .features-list i {
            color: white;
        }
        .pkg-radio {
            position: absolute;
            opacity: 0;
        }
        .card-icon-box {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--theme-shop-primary), var(--theme-shop-secondary));
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 5px;
        }
        .card-icon-box i {
            font-size: 24px;
            color: white;
        }
        .pkg-option-v2 h4 {
            font-size: 18px;
            font-weight: 700;
            color: var(--navy-dark);
            margin: 0;
        }
        .pkg-option-v2 .details {
            font-size: 13px;
            color: var(--text-muted);
            margin: 0;
        }
        .features-list {
            list-style: none;
            padding: 0;
            margin: 10px 0;
            flex-grow: 1;
        }
        .features-list li {
            font-size: 12px;
            color: var(--text-main);
            margin-bottom: 8px;
            display: flex;
            align-items: flex-start;
            gap: 8px;
        }
        .features-list i {
            color: var(--theme-shop-primary);
            font-size: 10px;
            margin-top: 3px;
            flex-shrink: 0;
        }
        .price-large {
            font-size: 24px;
            font-weight: 800;
            color: var(--theme-shop-primary);
            margin: 5px 0;
        }
        .price-note {
            font-size: 11px;
            color: var(--text-muted);
        }

        /* Detail panel styles */
        #package-details-panel {
            margin-top: 20px;
        }
        .detail-row {
            margin-bottom: 15px;
        }

        /* Quote details styles */
        .quote-details-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            padding-bottom: 8px;
            border-bottom: 1px dashed #e2e8f0;
            font-size: 14px;
        }
        .quote-details-row:last-child {
            border-bottom: none;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 2px solid #e2e8f0;
        }
        .quote-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        /* Maintenance grid and qty input */
        .qty-input {
            width: 80px;
            padding: 8px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            text-align: center;
            font-weight: 600;
        }

        /* Mobile responsive styles for quotations */
        @media (max-width: 768px) {
            .quotations-grid {
                grid-template-columns: 1fr !important;
                gap: 15px !important;
                padding: 10px !important;
            }
            .quotation-card {
                margin-bottom: 15px;
            }
            .quotation-header {
                flex-direction: column;
                align-items: flex-start !important;
                gap: 10px;
            }
            .quotation-title {
                font-size: 16px !important;
                margin-bottom: 5px;
            }
            .quotation-ref {
                font-size: 12px;
            }
            .quotation-meta {
                flex-direction: column !important;
                gap: 15px !important;
            }
            .quotation-amount {
                font-size: 20px !important;
                text-align: center;
                width: 100%;
                padding: 10px;
                background: #f8fafc;
                border-radius: 8px;
                border: 1px solid #e2e8f0;
            }
            .quotation-actions {
                flex-direction: column !important;
                gap: 10px !important;
            }
            .btn-pay-now, .btn-decline {
                width: 100% !important;
                justify-content: center;
                padding: 12px 20px !important;
                font-size: 14px !important;
            }
            .quotation-status {
                font-size: 11px !important;
                padding: 4px 10px !important;
            }
        }

        /* Small mobile devices */
        @media (max-width: 480px) {
            .section-header h2 {
                font-size: 20px;
            }
            .section-header {
                padding: 15px;
                margin-bottom: 15px;
            }
            .quotation-card {
                padding: 15px;
                margin-bottom: 10px;
            }
            .quotation-title {
                font-size: 15px !important;
            }
            .quotation-amount {
                font-size: 18px !important;
            }
        }
        
    </style>
</head>
<body>

    <div id="toast-container" class="toast-container">
    </div>

    <div class="sidebar">
        <div class="brand-container">
            <div class="brand">
               <img src="logo.png" alt="ATMICX Logo" style="height: 32px; width: auto; object-fit: contain;">
                <div><h2>ATMICX <span>Client</span></h2></div>
            </div>
            
            <div class="user-profile-box">
                <div class="avatar"><?php echo strtoupper(substr($_SESSION['name'], 0, 2)); ?></div>
                <div class="user-info">
                    <div class="name"><?php echo strtoupper($_SESSION['name']); ?></div>
                    <div class="role">Client ID: <?php echo $_SESSION['client_id']; ?></div>
                </div>
                <i class="fas fa-cog settings-icon"></i>
            </div>
        </div>

        <ul class="nav-links">
            <li class="nav-item">
                <button class="nav-btn active" onclick="showSection('home', this)" id="nav-home">
                    <i class="fas fa-home"></i> Home
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-btn" onclick="showSection('shop', this)" id="nav-shop">
                    <i class="fas fa-shopping-cart"></i> Shop & Invest
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-btn" onclick="showSection('maintenance', this)" id="nav-maintenance">
                    <i class="fas fa-tools"></i> Maintenance
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-btn" onclick="showSection('quotes', this)" id="nav-quotes">
                    <i class="fas fa-file-invoice-dollar"></i> My Quotations
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-btn" onclick="showSection('payments', this)" id="nav-payments">
                    <i class="fas fa-wallet"></i> Payments
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-btn" onclick="showSection('schedule', this)" id="nav-schedule">
                    <i class="fas fa-calendar-alt"></i> My Schedule
                </button>
            </li>
        </ul>

        <div class="sidebar-footer">
            <button class="logout-btn" onclick="openLogoutModal()"><i class="fas fa-sign-out-alt"></i> Log Out</button>
        </div>
    </div>

    <main class="main-content">
        <header class="header">
            <h1 id="current-page-title">Home</h1>
            <div class="header-actions">
                <button class="notif-btn">
                    <i class="fas fa-bell"></i>
                    <span class="notif-badge"></span>
                </button>
            </div>
        </header>

        <div class="dashboard-view">
            <div id="home" class="section active">

                <div class="action-grid-2">
                    <div class="dashboard-left-panel">
                        <div class="welcome-panel">
                            <div>
                                <h1>Welcome, <?php echo $_SESSION['name']; ?>!</h1>
                                <p>Manage your investments, machines, and service requests all in one portal.</p>
                            </div>
                            <div style="text-align: right;">
                                <i class="fas fa-chart-line" style="font-size: 80px; color: rgba(255, 255, 255, 0.3);"></i>
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
                            <div class="gradient-action-card card-shop-business" onclick="showSection('shop', getNavButtonBySectionId('shop'))">
                                <div class="card-icon">
                                    <i class="fas fa-cash-register"></i>
                                </div>
                                <div>
                                    <h3>Start a Laundry Business</h3>
                                    <p>Browse packages machines to expand your business.</p>
                                </div>
                            </div>
                            <div class="gradient-action-card card-maintenance" onclick="showSection('maintenance', getNavButtonBySectionId('maintenance'))">
                                <div class="card-icon">
                                    <i class="fas fa-cogs"></i>
                                </div>
                                <div>
                                    <h3>Request Repair</h3>
                                    <p>Schedule a repair or PMS for your current assets to ensure continuous operation.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="dashboard-right-panel" id="asset-history-panel">
                        <div class="panel">
                            <h3 class="panel-title" style="color: var(--navy-dark);">Owned Machine</h3>
                            <div class="schedule-list" style="margin-top: 15px;" id="dashboard-asset-history">
                            </div>
                            <button class="btn btn-outline" style="width: 100%; margin-top: 20px;" onclick="showSection('payments', getNavButtonBySectionId('payments'))">
                                <i class="fas fa-file-invoice"></i> View more
                            </button>
                        </div>
                    </div>
                </div>

                <div class="recent-quotes-panel" style="margin-top: 24px;">
                    <h3 class="panel-title">Recent Quotations</h3>
                    <div class="recent-quotes-list" id="dashboard-quotes-list">
                    </div>
                </div>
            </div>
            
            <div id="shop" class="section">
                <h2 class="panel-title" style="margin-bottom: 25px;">Machine Packages & Investment</h2>

                <div id="tab-packages" class="tab-content active" style="display: block;">
                    <label class="form-label">Select Package (Choose only one for quotation)</label>
                    <div class="package-selector" id="package-options-container">
                    </div>

                    <div id="package-details-panel" class="panel" style="padding: 25px; height: auto; margin-bottom: 24px;">
                        <h3 class="panel-title" id="detail-package-name" style="color: var(--theme-shop-primary); margin-bottom: 15px;">Package 1: The Micro Start (Selected)</h3>
                        <div class="detail-row" style="display: flex; justify-content: space-between; margin-bottom: 10px; padding-bottom: 5px; border-bottom: 1px dashed #e2e8f0;">
                            <span style="font-weight: 600; color: var(--text-main);">Core Equipment (Washer/Dryer Sets):</span>
                            <span id="detail-core-equipment" style="font-weight: 500;">2 Sets (2 Washers + 2 Dryers)</span>
                        </div>

                        <div class="detail-row" style="margin-bottom: 15px;">
                            <span style="font-weight: 600; color: var(--text-main); display: block; margin-bottom: 5px;">Key Inclusions/Features:</span>
                            <p id="detail-inclusions" style="font-size: 14px; color: var(--text-main); background: #f8fafc; padding: 10px; border-radius: 6px;">Basic shop layout, minimal inventory, essential folding table, and weighing scale.</p>
                        </div>

                        <div class="detail-row">
                            <span style="font-weight: 600; color: var(--text-main); display: block; margin-bottom: 5px;">Ideal For:</span>
                            <p id="detail-ideal-for" style="font-size: 14px; color: var(--text-main); background: #f8fafc; padding: 10px; border-radius: 6px;">Home-based operations or a small, low-cost entry into the market.</p>
                        </div>
                    </div>

                    <div class="logistics-box">
                        <div class="form-group">
                            <label class="form-label" for="logistics-option">Logistics & Installation Fee (Est. 5% of Package Price)</label>
                            <select id="logistics-option" class="form-control" onchange="updateShopSummary()">
                                <option value="standard">Standard (5% Fee)</option>
                                <option value="self">Self-Arranged (0% Fee)</option>
                            </select>
                        </div>
                        <p style="font-size: 12px; color: var(--text-muted);">Choosing 'Standard' includes delivery, professional installation, and initial machine calibration.</p>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="site-address">Site Address for Installation</label>
                        <input type="text" id="site-address" class="form-control" placeholder="123 Main St, Manila, Philippines" required>
                    </div>

                    <div class="total-summary-card">
                        <div class="label">Total Estimated Investment</div>
                        <div class="value" id="shop-total-value">₱630,000.00</div>
                        <div class="note">This is an estimated total. Final quotation will be sent for approval.</div>
                    </div>
                    <button class="btn btn-gold" style="width: 100%; margin-top: 20px;" onclick="sendPackageDataToSecretary()"><i class="fas fa-paper-plane"></i> Send Investment Request</button>
                </div>
            </div>

            <div id="maintenance" class="section">
                <h2 class="panel-title" style="margin-bottom: 25px;">Maintenance & Service Request</h2>
                <form id="repair-request-form">
                    <div class="maintenance-grid">
                        <div class="left-col">
                            <div class="form-group">
                                <label class="form-label">Select Machine(s) and Quantity to Service</label>
                                <div class="machine-list" id="maintenance-machine-list">
                                </div>
                                <div class="alert-note"><i class="fas fa-info-circle"></i> Only machines owned under your account are displayed here. Enter the quantity that requires service.</div>
                            </div>

                            <div class="form-group" style="margin-top: 25px;">
                                <label class="form-label">Type of Service</label>
                                <select id="service-type" class="form-control" onchange="updateRepairEstimate()">
                                    <option value="repair">Repair Service (Parts & Labor)</option>
                                    <option value="pms">Preventative Maintenance (PMS)</option>
                                    <option value="diagnosis">System Diagnosis Only</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="issue-description">Describe the Issue (or PMS preference)</label>
                                <textarea id="issue-description" class="form-control" rows="4" placeholder="e.g., Dryer is making a loud noise. Or: Requesting quarterly PMS for all washers." required></textarea>
                            </div>
                        </div>

                        <div class="right-col">
                            <div class="panel" style="height: auto; padding: 25px;">
                                <h3 class="panel-title" style="border-bottom: 1px solid #e2e8f0; padding-bottom: 15px; margin-bottom: 15px;">Estimated Cost</h3>
                                <div style="text-align: center;">
                                    <p class="label" style="font-size: 14px; color: var(--text-muted);">Initial Labor & Service Fee</p>
                                    <div class="value" id="repair-estimate-value" style="font-size: 38px; font-weight: 800; color: var(--theme-repair-primary); margin: 5px 0;">₱0.00</div>
                                    <p class="note" style="font-size: 12px; opacity: 0.8; color: var(--text-muted);">*Does not include parts. Final quotation will be provided after diagnosis.</p>
                                </div>

                                <button type="submit" class="btn btn-success" style="width: 100%; margin-top: 30px;">
                                    <i class="fas fa-wrench"></i> Submit Service Request
                                </button>
                                <button type="button" class="btn btn-outline" style="width: 100%; margin-top: 10px;" onclick="showToast('Request Canceled.', 'info')">
                                    Cancel
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div id="quotes" class="section">
                <div class="quotations-header">
                    <h2 class="quotations-title">
                        <i class="fas fa-file-invoice-dollar"></i>
                        My Quotations
                        <span class="notification-badge">•</span>
                    </h2>
                </div>
                
                <div class="quotations-section">
                    <h3 class="section-subtitle">All My Quotations</h3>
                    <div class="quotations-list" id="quotations-grid">
                        <!-- Quotations will be loaded here -->
                    </div>
                </div>
            </div>

            <div id="payments" class="section">
                <h2 class="panel-title" style="margin-bottom: 25px;">Payments & Billing</h2>

                <div class="bank-details">
                    <h3><i class="fas fa-university"></i> Bank Transfer Details</h3>
                    <div class="bank-row">
                        <span>Bank Name:</span> <span>China Banking Corporation (China Bank)</span>
                    </div>
                    <div class="bank-row">
                        <span>Account Name:</span> <span>ATMICX TRADING CORP.</span>
                    </div>
                    <div class="bank-row">
                        <span>Account Number:</span> <span>456-789-0123</span>
                    </div>
                    <div class="bank-row" style="border-bottom: none;">
                        <span>Swift Code:</span> <span>CHBKPHMMXXX</span>
                    </div>
                    <p><i class="fas fa-info-circle"></i> Please use your Quote/Invoice Reference # in the payment description to ensure quick processing.</p>
                </div>

                <div class="payment-action-history-grid">
                    <div class="left-col">
                        <div class="panel payment-form" style="padding: 25px; height: auto;">
                            <h3 class="panel-title">Submit Proof of Payment</h3>
                            <form id="payment-proof-form">
                                <div class="form-group" style="margin-top: 15px;">
                                    <label class="form-label" for="quote-reference">Quote/Invoice Reference #</label>
                                    <input type="text" id="quote-reference" class="form-control" placeholder="e.g., SR-001 or QT-2025-88" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="amount-paid">Amount Paid</label>
                                    <input type="number" id="amount-paid" class="form-control" placeholder="0.00" step="0.01" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="proof-file">Upload Proof (e.g., deposit slip, screenshot)</label>
                                    <input type="file" id="proof-file" class="form-control" style="padding: 10px;" accept="image/*,.pdf" required>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-upload"></i> Submit Proof
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="right-col">
                        <div class="table-panel">
                            <h3 class="panel-title">Payment History</h3>
                            <table class="payment-table">
                                <thead>
                                    <tr>
                                        <th>Ref #</th>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="payment-history-table">
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <div id="schedule" class="section">
                <h2 class="panel-title" style="margin-bottom: 25px;">Upcoming Appointments</h2>
                <div class="schedule-list" id="schedule-list">
                </div>
            </div>

        </div>
    </div>

    <!-- Logout Confirmation Modal -->
    <div id="logout-modal-overlay" class="modal-overlay">
        <div class="modal-content delete-confirm">
            <div style="padding: 32px; display: flex; flex-direction: column; align-items: center; text-align: center;">
                <i class="fas fa-exclamation-triangle" style="font-size: 48px; color: var(--danger-text); margin-bottom: 20px;"></i>
                <h4 style="font-size: 18px; font-weight: 700; color: var(--navy-dark); margin-bottom: 10px;">Confirm Logout</h4>
                <p style="font-size: 14px; color: var(--text-main); margin-bottom: 30px;">Are you sure you want to end your current session?</p>
                <div style="display: flex; gap: 10px; width: 100%;">
                    <button type="button" class="btn btn-danger" style="flex: 1;" onclick="window.location.href='client_logout.php'"><i class="fas fa-sign-out-alt"></i> Logout</button>
                    <button type="button" class="btn btn-outline" style="flex: 1;" onclick="closeLogoutModal()">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Quotation Details Modal -->
    <div id="quotation-modal-overlay" class="modal-overlay">
        <div class="modal-content" style="max-width: 800px; width: 90%;">
            <div class="modal-header">
                <h3><i class="fas fa-file-alt"></i> Quotation Details</h3>
                <button class="modal-close" onclick="closeQuotationModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="quotation-modal-body" class="modal-body">
                <!-- Content will be populated by JavaScript -->
            </div>
        </div>
    </div>
    
    <!-- Transaction Details Modal -->
    <div id="transaction-modal-overlay" class="modal-overlay">
        <div class="modal-content" style="max-width: 600px; width: 90%;">
            <div class="modal-header">
                <h3><i class="fas fa-file-invoice-dollar"></i> Transaction Details</h3>
                <button class="modal-close" onclick="closeTransactionModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="transaction-modal-body" class="modal-body">
                <!-- Content will be populated by JavaScript -->
            </div>
        </div>
    </div>
    
    <script>
        // --- DUMMY DATA ---
        const PACKAGES = [
            { id: 1, name: "The Micro Start", sets: "2 Sets (2 Washers + 2 Dryers)", price: 600000, value: "P-1-MicroStart", inclusions: "Basic shop layout, minimal inventory, essential folding table, and weighing scale.", idealFor: "Home-based operations or a small, low-cost entry into the market." },
            { id: 2, name: "The Essential Start", sets: "3 Sets (3 Washers + 3 Dryers)", price: 900000, value: "P-2-EssentialStart", inclusions: "Standard front counter, basic signage, initial training on laundry processes, and basic POS system.", idealFor: "First-time entrepreneurs in high-density residential areas." },
            { id: 3, name: "The Standard Shop", sets: "4 Sets (4 Washers + 4 Dryers)", price: 1200000, value: "P-3-StandardShop", inclusions: "Upgraded, more efficient machines. Includes plumbing, electrical, and gas line installation.", idealFor: "Mid-sized neighborhood laundromat with steady foot traffic." },
            { id: 4, name: "The Growth Model", sets: "5 Sets (5 Washers + 5 Dryers)", price: 1500000, value: "P-4-GrowthModel", inclusions: "Includes at least one stacked unit (washer/dryer in one vertical frame to save space) , CCTV system, and staff uniform allowance.", idealFor: "Shops aiming for higher volume, needing a mix of equipment types." },
            { id: 5, name: "The Premium Corner", sets: "6 Sets (6 Washers + 6 Dryers)", price: 2000000, value: "P-5-PremiumCorner", inclusions: "High-end, energy-efficient (Inverter) machines. Dedicated customer waiting area setup (furniture, Wi-Fi).", idealFor: "Prime location shop targeting middle-class customers and a strong brand image." },
            { id: 6, name: "The Anchor Laundromat", sets: "8 Sets (8 Washers + 8 Dryers)", price: 2700000, value: "P-6-AnchorLaundromat", inclusions: "Includes one extra-large capacity machine (40kg+) for commercial clients or bulky items (duvets, curtains). Advanced water filtration system.", idealFor: "Becoming the community's primary, high-capacity laundry service." },
            { id: 7, name: "The Industrial Lite", sets: "10 Sets (10 Washers + 10 Dryers)", price: 3500000, value: "P-7-IndustrialLite", inclusions: "Automated detergent dispensing system, large commercial folding tables, and initial investment for a small delivery vehicle (e-bike/scooter).", idealFor: "Transitioning to targeting small commercial accounts (e.g., hair salons, small restaurants)."},
            { id: 8, name: "The Multi-Load Center", sets: "12 Sets (12 Washers + 12 Dryers)", price: 4500000, value: "P-8-MultiLoadCenter", inclusions: "Mix of standard and multi-load industrial machines. Includes full HR/staffing guidance for a larger team (3-4 employees).", idealFor: "High-volume operation near dormitories, apartments, or factory areas." },
            { id: 9, name: "The Technology Hub", sets: "15 Sets (15 Washers + 15 Dryers)", price: 6000000, value: "P-9-TechnologyHub", inclusions: "Coin-less/App-based payment system integration, comprehensive digital marketing and online presence setup.", idealFor: "Modern, streamlined operation focused on customer convenience and advanced tracking." },
            { id: 10, name: "The Flagship Enterprise", sets: "20 Sets (20 Washers + 20 Dryers)", price: 8500000, value: "P-10-FlagshipEnterprise", inclusions: "Full franchise license (if applicable), complete brand kit, backup industrial generator for uninterrupted service, and a long-term service maintenance contract.", idealFor: "Establishing a major regional laundry center or a multi-site operation (e.g., two 10-set shops)." }
        ];

        const OWNED_MACHINES = [
            { id: "W-001", type: "Washer", model: "W-18", purchased: "2024-01-15", status: "Active" },
            { id: "D-001", type: "Dryer", model: "D-25", purchased: "2024-01-15", status: "Active" },
            { id: "D-002", type: "Dryer", model: "D-25", purchased: "2024-06-20", status: "Active" },
            { id: "W-002", type: "Washer", model: "W-18", purchased: "2024-01-15", status: "Active" },
            { id: "W-003", type: "Washer", model: "W-25", purchased: "2024-10-01", status: "Active" },
        ];

        let QUOTATIONS = []; // Will be loaded from database
        
        // Function to fetch quotations from database
        async function fetchQuotations() {
            try {
                const response = await fetch('client_quotations_api.php?action=get_quotations');
                const result = await response.json();
                
                if (result.success) {
                    QUOTATIONS = result.quotations;
                    renderQuotations();
                    renderDashboardQuotes();
                } else {
                    console.error('Failed to fetch quotations:', result.message);
                    showToast('Failed to load quotations', 'error');
                }
            } catch (error) {
                console.error('Error fetching quotations:', error);
                showToast('Error loading quotations', 'error');
            }
        }

        let PAYMENT_HISTORY = []; // Will be loaded from database
        
        // Function to fetch payment history from database
        async function fetchPaymentHistory() {
            try {
                const response = await fetch('client_quotations_api.php?action=get_quotations');
                const result = await response.json();
                
                if (result.success) {
                    // Filter to show only paid/verified quotations as payment history
                    PAYMENT_HISTORY = result.quotations
                        .filter(q => ['Verified', 'Paid', 'Completed'].includes(q.Status))
                        .map(q => ({
                            ref: q.ref || `QT-${String(q.Quotation_ID).padStart(3, '0')}`,
                            quotation_id: q.Quotation_ID,
                            package: q.Package,
                            amount: parseFloat(q.Amount),
                            date: q.Date_Issued,
                            status: q.Status === 'Verified' ? 'Confirmed' : q.Status,
                            delivery_method: q.Delivery_Method,
                            handling_fee: q.Handling_Fee
                        }));
                    renderPaymentHistory();
                } else {
                    console.error('Failed to fetch payment history:', result.message);
                    // Fallback to sample data
                    PAYMENT_HISTORY = [
                        { ref: "QT-067", quotation_id: 67, package: "The Micro Start (2 Sets)", amount: 600000, date: "2026-01-11", status: "Confirmed", delivery_method: "Pick-up", handling_fee: 0 },
                        { ref: "QT-068", quotation_id: 68, package: "The Micro Start (2 Sets)", amount: 600000, date: "2026-01-11", status: "Pending", delivery_method: "Delivery", handling_fee: 5000 }
                    ];
                    renderPaymentHistory();
                }
            } catch (error) {
                console.error('Error fetching payment history:', error);
                showToast('Error loading payment history', 'error');
            }
        }

        let SCHEDULE_ITEMS = []; // Will be loaded from database
        
        // Function to fetch schedule appointments from database
        async function fetchSchedule() {
            try {
                const response = await fetch('client_quotations_api.php?action=get_schedule');
                const result = await response.json();
                
                if (result.success) {
                    SCHEDULE_ITEMS = result.appointments.map(apt => {
                        const date = new Date(apt.scheduled_date);
                        return {
                            day: date.getDate().toString().padStart(2, '0'),
                            month: date.toLocaleDateString('en-US', { month: 'short' }).toUpperCase(),
                            type: apt.type,
                            machine: apt.Package.replace(/^(The |Package|\(.*\))/g, '').trim(),
                            ref: apt.ref,
                            time: apt.time,
                            team: apt.technician_team,
                            status: apt.Status
                        };
                    });
                    renderScheduleList();
                } else {
                    console.error('Failed to fetch schedule:', result.message);
                    showToast('Failed to load appointments', 'error');
                }
            } catch (error) {
                console.error('Error fetching schedule:', error);
                showToast('Error loading appointments', 'error');
                // Fallback to sample data
                SCHEDULE_ITEMS = [
                    { day: "15", month: "JAN", type: "Installation", machine: "2-Set Package", ref: "QT-68", time: "9:00 AM - 12:00 PM", team: "Team Beta", status: "Scheduled" },
                    { day: "22", month: "JAN", type: "PMS Service", machine: "Dryer Maintenance", ref: "QT-67", time: "1:00 PM - 3:00 PM", team: "Team Alpha", status: "Scheduled" }
                ];
                renderScheduleList();
            }
        }

        // --- TRANSACTION DETAILS FUNCTIONS ---
        async function viewTransactionDetails(ref, quotationId) {
            const modal = document.getElementById('transaction-modal-overlay');
            const modalBody = document.getElementById('transaction-modal-body');
            
            if (!modal || !modalBody) {
                showToast('Modal not found', 'error');
                return;
            }
            
            // Show modal with loading state
            modalBody.innerHTML = `
                <div style="text-align: center; padding: 40px;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 32px; color: var(--navy-dark); margin-bottom: 15px;"></i>
                    <p>Loading transaction details...</p>
                </div>
            `;
            modal.style.display = 'flex';
            
            try {
                const response = await fetch(`client_quotations_api.php?action=get_quotation_details&quotation_id=${quotationId}`);
                const result = await response.json();
                
                if (result.success) {
                    displayTransactionDetails(result.quotation, ref);
                } else {
                    throw new Error(result.message || 'Failed to fetch transaction details');
                }
            } catch (error) {
                console.error('Error loading transaction details:', error);
                modalBody.innerHTML = `
                    <div style="text-align: center; padding: 40px; color: var(--danger-text);">
                        <i class="fas fa-exclamation-triangle" style="font-size: 32px; margin-bottom: 15px;"></i>
                        <p>Error loading transaction details</p>
                        <button class="btn btn-outline" onclick="closeTransactionModal()">Close</button>
                    </div>
                `;
                showToast('Failed to load transaction details', 'error');
            }
        }
        
        function displayTransactionDetails(transaction, ref) {
            const modalBody = document.getElementById('transaction-modal-body');
            
            const totalAmount = parseFloat(transaction.Amount) + parseFloat(transaction.Handling_Fee || 0);
            const statusClass = transaction.Status === 'Verified' || transaction.Status === 'Paid' ? 'status-ok' : 
                               transaction.Status === 'Completed' ? 'status-ok' : 'status-warn';
            
            modalBody.innerHTML = `
                <div style="padding: 0;">
                    <!-- Transaction Header -->
                    <div style="background: linear-gradient(135deg, var(--navy-dark), #475569); color: white; padding: 25px; margin: -20px -20px 25px -20px; border-radius: var(--radius-md) var(--radius-md) 0 0;">
                        <h4 style="margin: 0 0 8px 0; color: var(--gold); font-size: 18px;">${ref}</h4>
                        <p style="margin: 0; opacity: 0.9;">${transaction.Package}</p>
                        <div style="margin-top: 15px; display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-size: 24px; font-weight: 700;">${formatCurrency(totalAmount)}</span>
                            <span class="status-badge ${statusClass}">${transaction.Status}</span>
                        </div>
                    </div>
                    
                    <!-- Transaction Details Grid -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px;">
                        <div>
                            <h5 style="margin: 0 0 8px 0; color: var(--navy-dark); font-size: 14px;">📅 Transaction Date</h5>
                            <p style="margin: 0; font-size: 13px; color: var(--text-main);">${new Date(transaction.Date_Issued).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</p>
                        </div>
                        <div>
                            <h5 style="margin: 0 0 8px 0; color: var(--navy-dark); font-size: 14px;">🚚 Delivery Method</h5>
                            <p style="margin: 0; font-size: 13px; color: var(--text-main);">${transaction.Delivery_Method || 'Pick-up'}</p>
                        </div>
                    </div>
                    
                    <!-- Amount Breakdown -->
                    <div style="background: var(--bg-secondary); padding: 20px; border-radius: var(--radius-md); margin-bottom: 25px;">
                        <h5 style="margin: 0 0 15px 0; color: var(--navy-dark); font-size: 14px;">💰 Amount Breakdown</h5>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                            <span style="font-size: 13px;">Package Amount:</span>
                            <span style="font-size: 13px; font-weight: 600;">${formatCurrency(parseFloat(transaction.Amount))}</span>
                        </div>
                        ${transaction.Handling_Fee > 0 ? `
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                            <span style="font-size: 13px;">Handling Fee:</span>
                            <span style="font-size: 13px; font-weight: 600;">${formatCurrency(parseFloat(transaction.Handling_Fee))}</span>
                        </div>
                        ` : ''}
                        <hr style="margin: 12px 0; border: none; border-top: 1px solid var(--border-light);">
                        <div style="display: flex; justify-content: space-between; font-weight: 700; font-size: 14px;">
                            <span>Total Amount:</span>
                            <span style="color: var(--success-text);">${formatCurrency(totalAmount)}</span>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div style="text-align: center;">
                        <button class="btn btn-outline" onclick="closeTransactionModal()" style="margin-right: 10px;">
                            <i class="fas fa-times"></i> Close
                        </button>
                        <button class="btn btn-primary" onclick="printTransactionReceipt(transaction)">
                            <i class="fas fa-download"></i> Download Receipt
                        </button>
                    </div>
                </div>
            `;
        }
        
        function closeTransactionModal() {
            const modal = document.getElementById('transaction-modal-overlay');
            if (modal) {
                modal.style.display = 'none';
            }
        }
        
        function printTransactionReceipt(transaction) {
            const totalAmount = parseFloat(transaction.Amount) + parseFloat(transaction.Handling_Fee || 0);
            const currentDate = new Date().toLocaleString('en-US', {
                year: 'numeric',
                month: 'long', 
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
            
            const receiptContent = `
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Receipt - QT-${String(transaction.Quotation_ID).padStart(3, '0')}</title>
                    <style>
                        * { margin: 0; padding: 0; box-sizing: border-box; }
                        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: white; color: #333; }
                        .receipt { max-width: 400px; margin: 20px auto; padding: 20px; border: 2px solid #2c3e50; }
                        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #2c3e50; padding-bottom: 15px; }
                        .company-name { font-size: 18px; font-weight: bold; color: #2c3e50; margin-bottom: 5px; }
                        .company-info { font-size: 12px; color: #666; }
                        .receipt-title { font-size: 16px; font-weight: bold; margin: 15px 0 10px 0; text-align: center; }
                        .receipt-id { font-size: 14px; text-align: center; color: #666; margin-bottom: 15px; }
                        .section { margin: 15px 0; }
                        .section-title { font-size: 12px; font-weight: bold; color: #2c3e50; margin-bottom: 8px; text-transform: uppercase; border-bottom: 1px solid #ddd; padding-bottom: 3px; }
                        .info-row { display: flex; justify-content: space-between; margin: 5px 0; font-size: 12px; }
                        .info-label { color: #666; }
                        .info-value { font-weight: 600; }
                        .amount-section { border: 1px solid #ddd; padding: 10px; background: #f8f9fa; }
                        .amount-row { display: flex; justify-content: space-between; margin: 3px 0; font-size: 12px; }
                        .total-row { border-top: 1px solid #333; margin-top: 8px; padding-top: 5px; font-weight: bold; font-size: 14px; }
                        .footer { text-align: center; margin-top: 20px; padding-top: 15px; border-top: 2px solid #2c3e50; }
                        .footer-text { font-size: 10px; color: #666; line-height: 1.4; }
                        .signature { margin-top: 15px; text-align: right; }
                        .signature-line { border-top: 1px solid #333; width: 150px; margin-left: auto; padding-top: 5px; font-size: 10px; text-align: center; }
                        @media print {
                            body { margin: 0; }
                            .receipt { margin: 0; border: none; max-width: none; }
                        }
                    </style>
                </head>
                <body>
                    <div class="receipt">
                        <div class="header">
                            <div class="company-name">ATMICX Laundry Machine Trading</div>
                            <div class="company-info">Professional Laundry Solutions<br>Email: info@atmicx.com | Phone: (034) 123-4567</div>
                        </div>
                        
                        <div class="receipt-title">TRANSACTION RECEIPT</div>
                        <div class="receipt-id">QT-${String(transaction.Quotation_ID).padStart(3, '0')}</div>
                        
                        <div class="section">
                            <div class="section-title">Transaction Details</div>
                            <div class="info-row">
                                <span class="info-label">Date Issued:</span>
                                <span class="info-value">${new Date(transaction.Date_Issued).toLocaleDateString('en-US')}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Delivery Method:</span>
                                <span class="info-value">${transaction.Delivery_Method || 'Pick-up'}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Status:</span>
                                <span class="info-value">${transaction.Status}</span>
                            </div>
                        </div>
                        
                        <div class="section">
                            <div class="section-title">Package Details</div>
                            <div class="info-row">
                                <span class="info-label">Package:</span>
                                <span class="info-value">${transaction.Package}</span>
                            </div>
                        </div>
                        
                        <div class="section">
                            <div class="section-title">Amount Breakdown</div>
                            <div class="amount-section">
                                <div class="amount-row">
                                    <span>Package Amount:</span>
                                    <span>₱${parseFloat(transaction.Amount).toLocaleString('en-US', { minimumFractionDigits: 2 })}</span>
                                </div>
                                ${transaction.Handling_Fee > 0 ? `
                                <div class="amount-row">
                                    <span>Handling Fee:</span>
                                    <span>₱${parseFloat(transaction.Handling_Fee).toLocaleString('en-US', { minimumFractionDigits: 2 })}</span>
                                </div>
                                ` : ''}
                                <div class="amount-row total-row">
                                    <span>TOTAL AMOUNT:</span>
                                    <span>₱${totalAmount.toLocaleString('en-US', { minimumFractionDigits: 2 })}</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="footer">
                            <div class="footer-text">
                                Thank you for choosing ATMICX Laundry Machine Trading!<br>
                                For inquiries, please contact us at the above information.<br>
                                <strong>This is a computer-generated receipt.</strong>
                            </div>
                            
                            <div class="signature">
                                <div class="signature-line">
                                    Client Copy
                                </div>
                            </div>
                        </div>
                        
                        <div style="text-align: center; font-size: 10px; color: #666; margin-top: 10px;">
                            Downloaded on: ${currentDate}
                        </div>
                    </div>
                </body>
                </html>
            `;
            
            // Create or update print container
            let printContainer = document.getElementById('print-client-receipt-container');
            if (!printContainer) {
                printContainer = document.createElement('div');
                printContainer.id = 'print-client-receipt-container';
                printContainer.style.display = 'none';
                document.body.appendChild(printContainer);
            }
            
            // Set the receipt content
            printContainer.innerHTML = receiptContent;
            
            // Add print-specific styles
            let printStyles = document.getElementById('print-client-receipt-styles');
            if (!printStyles) {
                printStyles = document.createElement('style');
                printStyles.id = 'print-client-receipt-styles';
                printStyles.innerHTML = `
                    @media print {
                        body * { visibility: hidden !important; }
                        #print-client-receipt-container, #print-client-receipt-container * { visibility: visible !important; }
                        #print-client-receipt-container {
                            position: absolute !important;
                            left: 0 !important;
                            top: 0 !important;
                            width: 100% !important;
                            height: 100% !important;
                            display: block !important;
                            background: white !important;
                        }
                        #print-client-receipt-container .receipt {
                            margin: 0 !important;
                            padding: 20px !important;
                            border: 1px solid #2c3e50 !important;
                            max-width: none !important;
                            width: 100% !important;
                        }
                    }
                `;
                document.head.appendChild(printStyles);
            }
            
            // Trigger print directly
            window.print();
            
            showToast('✅ Print dialog opened!', 'success');
        }
        
        // --- UTILITY FUNCTIONS ---
        function formatCurrency(amount) {
            return `₱${amount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
        }

        function getNavButtonBySectionId(id) {
            return document.getElementById(`nav-${id}`);
        }

        // --- TOAST NOTIFICATION FUNCTION ---
        function showToast(message, type = 'info') {
            const container = document.getElementById('toast-container');
            if (!container) return;

            const iconMap = {
                success: 'fas fa-check-circle',
                error: 'fas fa-times-circle',
                info: 'fas fa-info-circle',
                warning: 'fas fa-exclamation-triangle'
            };

            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            toast.innerHTML = `<i class="${iconMap[type] || iconMap['info']}"></i> <div>${message}</div>`;
            container.appendChild(toast);

            void toast.offsetWidth;
            toast.classList.add('show');

            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => {
                    if (container.contains(toast)) {
                        container.removeChild(toast);
                    }
                }, 400);
            }, 5000);
        }

        // --- LOGOUT MODAL FUNCTIONS ---
        function openLogoutModal() {
            document.getElementById('logout-modal-overlay').classList.add('show');
        }

        function closeLogoutModal() {
            document.getElementById('logout-modal-overlay').classList.remove('show');
        }

        // --- QUOTATION MODAL FUNCTIONS ---
        function showQuotationDetailsModal(quotation) {
            const modalBody = document.getElementById('quotation-modal-body');
            
            // Format the date
            const formattedDate = new Date(quotation.Date_Issued).toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            });
            
            const modalContent = `
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px;">
                    <div>
                        <h4 style="margin-bottom: 10px; color: var(--navy-dark);">Quotation Information</h4>
                        <div class="quote-details-row">
                            <span>Quotation ID:</span> <span>#${quotation.Quotation_ID}</span>
                        </div>
                        <div class="quote-details-row">
                            <span>Date Issued:</span> <span>${formattedDate}</span>
                        </div>
                        <div class="quote-details-row">
                            <span>Status:</span> <span><span class="status-badge">${quotation.Status}</span></span>
                        </div>
                        <div class="quote-details-row">
                            <span>Processed by:</span> <span>${quotation.User_Name || 'System'}</span>
                        </div>
                    </div>
                    <div>
                        <h4 style="margin-bottom: 10px; color: var(--navy-dark);">Client Information</h4>
                        <div class="quote-details-row">
                            <span>Name:</span> <span>${quotation.Client_Name}</span>
                        </div>
                        <div class="quote-details-row">
                            <span>Contact:</span> <span>${quotation.Contact_Num || 'N/A'}</span>
                        </div>
                        <div class="quote-details-row">
                            <span>Address:</span> <span>${quotation.Address || 'N/A'}</span>
                        </div>
                    </div>
                </div>
                
                <div style="background: var(--bg-body); padding: 20px; border-radius: 8px; margin-bottom: 25px;">
                    <h4 style="margin-bottom: 15px; color: var(--navy-dark);">Package Details</h4>
                    <div class="quote-details-row" style="font-size: 16px;">
                        <span style="font-weight: 600;">Package:</span> 
                        <span style="color: var(--gold); font-weight: 700;">${quotation.Package}</span>
                    </div>
                    <div class="quote-details-row">
                        <span>Delivery Method:</span> <span>${quotation.Delivery_Method || 'Standard'}</span>
                    </div>
                    <div class="quote-details-row">
                        <span>Handling Fee:</span> <span>${formatCurrency(parseFloat(quotation.Handling_Fee) || 0)}</span>
                    </div>
                </div>
                
                <div style="border-top: 2px solid var(--gold); padding-top: 20px;">
                    <div style="display: flex; justify-content: space-between; font-size: 20px; font-weight: 800;">
                        <span style="color: var(--navy-dark);">Total Amount:</span>
                        <span style="color: var(--gold);">${formatCurrency(parseFloat(quotation.Amount))}</span>
                    </div>
                </div>
            `;
            
            modalBody.innerHTML = modalContent;
            document.getElementById('quotation-modal-overlay').classList.add('show');
        }
        
        function closeQuotationModal() {
            document.getElementById('quotation-modal-overlay').classList.remove('show');
        }

        function showSection(sectionId, navButton, data = {}) {
            const titleMap = {
                'home': 'Home',
                'shop': 'Shop & Invest',
                'maintenance': 'Maintenance',
                'quotes': 'My Quotations',
                'payments': 'Payments',
                'schedule': 'My Schedule'
            };
            document.getElementById('current-page-title').textContent = titleMap[sectionId] || 'Portal';
            
            document.querySelectorAll('.section').forEach(section => {
                section.classList.remove('active');
            });

            const activeSection = document.getElementById(sectionId);
            if (activeSection) {
                activeSection.classList.add('active');
            }

            document.querySelectorAll('.nav-btn').forEach(btn => {
                btn.classList.remove('active');
            });

            if (navButton) {
                navButton.classList.add('active');
            }

            if (sectionId === 'payments' && data.quoteRef) {
                const invoiceRefInput = document.getElementById('invoice-ref');
                if (invoiceRefInput) {
                    invoiceRefInput.value = data.quoteRef;
                    invoiceRefInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        }

        // --- SHOP SECTION JS ---
        function findPackageById(id) {
            return PACKAGES.find(pkg => pkg.id === id);
        }

        function getCardFeatures(inclusionString) {
            const parts = inclusionString.split(/,\s*|\.\s*/).filter(p => p.trim() !== '');
            return parts.slice(0, 4);
        }

        function selectPackage(packageId) {
            const radio = document.querySelector(`.pkg-option-v2[data-id="${packageId}"] .pkg-radio`);
            if (radio) {
                radio.checked = true;
            }

            document.querySelectorAll('#tab-packages .pkg-option-v2').forEach(option => {
                option.classList.remove('selected');
            });
            document.querySelector(`.pkg-option-v2[data-id="${packageId}"]`).classList.add('selected');

            const pkg = findPackageById(packageId);
            if (pkg) {
                document.getElementById('detail-package-name').textContent = `Package ${pkg.id}: ${pkg.name} (Selected)`;
                document.getElementById('detail-core-equipment').textContent = pkg.sets;
                document.getElementById('detail-inclusions').textContent = pkg.inclusions;
                document.getElementById('detail-ideal-for').textContent = pkg.idealFor;
            }

            updateShopSummary();
        }

        function updateShopSummary() {
            let totalCost = 0;
            let logFeePercent = 0.05;

            const checkedPackage = document.querySelector('.pkg-radio:checked');
            if (checkedPackage) {
                totalCost = parseInt(checkedPackage.dataset.price);
            }

            const logisticsOption = document.getElementById('logistics-option').value;
            if (logisticsOption === 'self') {
                logFeePercent = 0;
            }
            const finalTotal = totalCost * (1 + logFeePercent);
            document.getElementById('shop-total-value').textContent = formatCurrency(finalTotal);
        }

        function renderPackageOptions() {
            const container = document.getElementById('package-options-container');
            if (!container) return;
            container.innerHTML = '';

            PACKAGES.forEach((pkg, index) => {
                const isSelected = index === 0;
                const cardFeatures = getCardFeatures(pkg.inclusions);
                const featuresHtml = cardFeatures.map(feature =>
                    `<li><i class="fas fa-check-circle"></i> ${feature}</li>`
                ).join('');

                const html = `
                    <label class="pkg-option pkg-option-v2 ${isSelected ? 'selected' : ''}" data-id="${pkg.id}" onclick="selectPackage(${pkg.id})">
                        <input type="radio" class="pkg-radio" name="package_option" value="${pkg.value}" data-price="${pkg.price}" style="display: none;" ${isSelected ? 'checked' : ''}>
                        <div class="card-icon-box">
                            <i class="fas fa-box-open"></i>
                        </div>
                        <div>
                            <h4>${pkg.name}</h4>
                            <p class="details">${pkg.sets}</p>
                        </div>
                        <ul class="features-list">${featuresHtml}</ul>
                        <div>
                            <div class="price-large">${formatCurrency(pkg.price)}</div>
                            <div class="price-note">Est. Price (Excl. logistics/fees)</div>
                        </div>
                    </label>
                `;
                container.insertAdjacentHTML('beforeend', html);
            });
        }

        // --- MAINTENANCE SECTION JS ---
        function renderMaintenanceMachineList() {
            const listContainer = document.getElementById('maintenance-machine-list');
            if (!listContainer) return;
            listContainer.innerHTML = '';

            const groupedMachines = OWNED_MACHINES.reduce((acc, machine) => {
                const key = machine.type;
                if (key === 'Washer' || key === 'Dryer') {
                    if (!acc[key]) {
                        acc[key] = {
                            type: machine.type,
                            count: 0
                        };
                    }
                    acc[key].count++;
                }
                return acc;
            }, {});

            const machineArray = Object.values(groupedMachines);

            if (machineArray.length === 0) {
                listContainer.innerHTML = '<div style="padding: 20px; text-align: center; color: var(--text-muted);">No machines found on your account.</div>';
                return;
            }

            machineArray.forEach((machine, index) => {
                const machineName = machine.type;
                const baseLaborCost = 1500;

                const html = `
                    <div class="machine-item">
                        <div class="m-details">
                            <label for="qty-${machineName}">
                                <h5>${machineName}</h5>
                                <p>Owned: ${machine.count} unit(s)</p>
                            </label>
                        </div>
                        <span class="labor-est">Base Labor: ${formatCurrency(baseLaborCost)}/unit</span>
                        <input type="number"
                               id="qty-${machineName}"
                               class="qty-input"
                               name="service_qty[]"
                               value="0"
                               min="0"
                               max="${machine.count}"
                               data-base-labor="${baseLaborCost}"
                               onchange="updateRepairEstimate()">
                    </div>
                `;
                listContainer.insertAdjacentHTML('beforeend', html);
            });
        }

        function updateRepairEstimate() {
            const estimatedCost = calculateTotalEstimate();
            const estimateValue = document.getElementById('repair-estimate-value');
            estimateValue.textContent = formatCurrency(estimatedCost);
        }

        function submitRepairRequest(e) {
            e.preventDefault();
            
            // Collect machine data
            const qtyInputs = document.querySelectorAll('#maintenance-machine-list .qty-input');
            const machines = [];
            
            qtyInputs.forEach(input => {
                const quantity = parseInt(input.value) || 0;
                if (quantity > 0) {
                    const machineRow = input.closest('.machine-item');
                    const machineName = machineRow.querySelector('h5').textContent;
                    machines.push({
                        name: machineName,
                        quantity: quantity
                    });
                }
            });
            
            if (machines.length === 0) {
                showToast('Please select at least one machine (quantity > 0) to service.', 'error');
                return;
            }
            
            const serviceType = document.getElementById('service-type').value;
            const issueDescription = document.getElementById('issue-description').value.trim();
            const estimatedCost = calculateTotalEstimate();
            
            if (!issueDescription) {
                showToast('Please describe the issue or service needed.', 'error');
                return;
            }
            
            // Show loading state
            const submitBtn = document.querySelector('#repair-request-form button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
            submitBtn.disabled = true;
            
            // Prepare form data
            const formData = new FormData();
            formData.append('action', 'submit');
            formData.append('client_name', '<?php echo addslashes($_SESSION['name']); ?>');
            formData.append('problem_description', issueDescription);
            formData.append('location', 'Not specified'); // Default for now
            formData.append('priority', 'medium'); // Default priority
            formData.append('estimated_cost', estimatedCost);
            
            // Additional data for reference
            formData.append('service_type', serviceType);
            formData.append('machines', JSON.stringify(machines));
            
            // Submit to API
            fetch('service_request_api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(`Service Request Submitted Successfully! Reference ID: ${data.service_id}`, 'success');
                    
                    // Clear form
                    document.getElementById('repair-request-form').reset();
                    updateRepairEstimate();
                    
                    // Show follow-up message
                    setTimeout(() => {
                        showToast('A quotation will be sent to you within 24 hours.', 'info');
                    }, 2000);
                    
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Service request error:', error);
                showToast('Failed to submit service request. Please try again.', 'error');
            })
            .finally(() => {
                // Restore button
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        }
        
        function calculateTotalEstimate() {
            const qtyInputs = document.querySelectorAll('#maintenance-machine-list .qty-input');
            const serviceType = document.getElementById('service-type').value;
            let totalMachinesToService = 0;
            
            qtyInputs.forEach(input => {
                const quantity = parseInt(input.value) || 0;
                totalMachinesToService += quantity;
            });
            
            if (totalMachinesToService > 0) {
                if (serviceType === 'diagnosis') {
                    return 1000;
                } else if (serviceType === 'pms') {
                    return totalMachinesToService * 800;
                } else if (serviceType === 'repair') {
                    return totalMachinesToService * 1500;
                }
            }
            return 0;
        }

        // Test session function for debugging
        async function testSession() {
            try {
                const response = await fetch('client_debug_session.php');
                const result = await response.json();
                console.log('Session Test Result:', result);
                
                if (result.is_logged_in) {
                    showToast(`✅ Session OK - Client ID: ${result.debug_info.session_data.client_id}`, 'success');
                } else {
                    showToast('❌ Session Invalid - Not logged in', 'error');
                    console.log('Session data:', result.debug_info.session_data);
                }
            } catch (error) {
                console.error('Session test failed:', error);
                showToast('Session test failed', 'error');
            }
        }

        // --- QUOTATIONS SECTION JS ---
        function renderQuotations() {
            const gridContainer = document.getElementById('quotations-grid');
            if (!gridContainer) return;
            
            // Load quotations from API
            fetch('client_quotations_enhanced_api.php')
            .then(response => response.json())
            .then(data => {
                console.log('API Response:', data); // Debug logging
                gridContainer.innerHTML = '';
                
                if (!data.success) {
                    console.error('API Error:', data.message);
                    if (data.message && data.message.includes('logged in')) {
                        // Session expired, redirect to login
                        showToast('Session expired. Please log in again.', 'error');
                        setTimeout(() => {
                            window.location.href = 'clientLOGIN.html';
                        }, 2000);
                        return;
                    }
                    gridContainer.innerHTML = '<div style="text-align: center; padding: 60px 20px; color: #ef4444;"><i class="fas fa-exclamation-triangle" style="font-size: 48px; margin-bottom: 15px; opacity: 0.3;"></i><h3>Error Loading Quotations</h3><p>' + (data.message || 'Unknown error occurred') + '</p></div>';
                    return;
                }
                
                if (data.quotations.length === 0) {
                    gridContainer.innerHTML = '<div style="text-align: center; padding: 60px 20px; color: #9ca3af;"><i class="fas fa-file-alt" style="font-size: 48px; margin-bottom: 15px; opacity: 0.3;"></i><h3>No Quotations Found</h3><p>You haven\'t received any quotations yet.</p></div>';
                    return;
                }

                data.quotations.forEach(quote => {
                    // Determine quotation type and status
                    let quotationType = 'New Installation';
                    let statusText = 'Awaiting Approval';
                    let statusClass = 'status-awaiting';
                    
                    if (quote.is_service_request) {
                        quotationType = quote.package.includes('PMS') ? 'PMS Service' : 'Repair Service';
                    }
                    
                    // Map status to display format - WORKFLOW INTEGRATION
                    const lowerStatus = quote.status.toLowerCase();
                    if (lowerStatus === 'pending') {
                        statusText = 'Review & Accept';
                        statusClass = 'status-awaiting';
                    } else if (lowerStatus === 'accepted') {
                        statusText = 'Awaiting Payment';
                        statusClass = 'status-payment';
                    } else if (lowerStatus === 'payment submitted' || lowerStatus === 'awaiting verification') {
                        statusText = 'Verifying Payment';
                        statusClass = 'status-verifying';
                    } else if (lowerStatus === 'verified' || lowerStatus === 'paid') {
                        statusText = 'Payment Verified';
                        statusClass = 'status-verified';
                    } else if (lowerStatus === 'completed') {
                        statusText = 'Completed';
                        statusClass = 'status-completed';
                    } else if (lowerStatus === 'payment rejected') {
                        statusText = 'Payment Rejected';
                        statusClass = 'status-rejected';
                    } else if (lowerStatus === 'declined') {
                        statusText = 'Declined';
                        statusClass = 'status-declined';
                    } else {
                        statusText = quote.status;
                        statusClass = 'status-awaiting';
                    }
                    
                    // Format date
                    const formattedDate = new Date(quote.date_issued).toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: '2-digit',
                        day: '2-digit'
                    });
                    
                    // Determine items description
                    let itemsText = quote.delivery_method || '1-Set Package, Logistics';
                    if (quote.is_service_request) {
                        itemsText = quote.package;
                    }
                    
                    // Show action buttons based on status - WORKFLOW INTEGRATION
                    let actionButtons = '';
                    if (lowerStatus === 'pending') {
                        // Step 1: Client needs to accept or decline
                        actionButtons = `
                            <div class="quotation-actions">
                                <button class="btn-pay-now" onclick="acceptQuotation(${quote.id}, ${quote.amount}, '${quote.reference}')">
                                    <i class="fas fa-check"></i> Accept Quotation
                                </button>
                                <button class="btn-decline" onclick="declineQuotation(${quote.id})">
                                    <i class="fas fa-times"></i> Decline
                                </button>
                            </div>
                        `;
                    } else if (lowerStatus === 'accepted') {
                        // Step 2: Client needs to submit payment
                        actionButtons = `
                            <div class="quotation-actions">
                                <button class="btn-pay-now" onclick="openPaymentForm('${quote.reference}', ${quote.amount})">
                                    <i class="fas fa-money-bill-wave"></i> Pay Now
                                </button>
                            </div>
                        `;
                    } else if (lowerStatus === 'payment submitted' || lowerStatus === 'awaiting verification') {
                        // Step 3: Waiting for manager verification
                        actionButtons = `
                            <div class="quotation-actions">
                                <button class="btn-pay-now" style="background: #6b7280; cursor: not-allowed;" disabled>
                                    <i class="fas fa-clock"></i> Awaiting Verification
                                </button>
                            </div>
                        `;
                    } else if (lowerStatus === 'payment rejected') {
                        // Payment rejected, can resubmit
                        actionButtons = `
                            <div class="quotation-actions">
                                <button class="btn-pay-now" onclick="openPaymentForm('${quote.reference}', ${quote.amount})">
                                    <i class="fas fa-redo"></i> Resubmit Payment
                                </button>
                            </div>
                        `;
                    } else if (lowerStatus === 'verified' || lowerStatus === 'paid' || lowerStatus === 'completed') {
                        // Completed workflow
                        actionButtons = `
                            <div class="quotation-actions">
                                <button class="btn-pay-now" style="background: #10b981; cursor: default;" disabled>
                                    <i class="fas fa-check-circle"></i> Completed
                                </button>
                            </div>
                        `;
                    }
                    
                    const cardHTML = `
                        <div class="quotation-card" data-quote-id="${quote.id}">
                            <div class="quotation-header">
                                <div>
                                    <h3 class="quotation-title">${quotationType}</h3>
                                    <div class="quotation-ref">Ref: ${quote.reference}</div>
                                </div>
                                <span class="quotation-status ${statusClass}">${statusText}</span>
                            </div>
                            
                            <div class="quotation-content">
                                <div class="quotation-meta">
                                    <div class="quotation-details">
                                        <div class="quotation-date">Date Issued: ${formattedDate}</div>
                                        <div class="quotation-items">Items: ${itemsText}</div>
                                    </div>
                                    <div class="quotation-amount">₱${parseFloat(quote.amount).toLocaleString('en-US', { minimumFractionDigits: 2 })}</div>
                                </div>
                            </div>
                            
                            ${actionButtons}
                        </div>
                    `;
                    
                    gridContainer.insertAdjacentHTML('beforeend', cardHTML);
                });
            })
            .catch(error => {
                console.error('Error loading quotations:', error);
                gridContainer.innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;"><i class="fas fa-exclamation-triangle"></i><h3>Error Loading Quotations</h3><p>Please try again later.</p></div>';
            });
        }
        
        // Open payment form with pre-filled quotation reference
        function openPaymentForm(reference, amount) {
            // Switch to payments tab
            showSection('payments', document.getElementById('nav-payments'));
            
            // Pre-fill the payment form
            setTimeout(() => {
                document.getElementById('quote-reference').value = reference;
                document.getElementById('amount-paid').value = amount;
                
                // Scroll to payment form
                document.querySelector('#payments .payment-form').scrollIntoView({ behavior: 'smooth' });
                
                showToast('Quotation details pre-filled in payment form', 'info');
            }, 100); // Small delay to ensure section is loaded
        }

        // Function to view quotation details
        async function viewQuotationDetails(quotationId) {
            try {
                const response = await fetch(`client_quotations_api.php?action=get_quotation_details&id=${quotationId}`);
                const result = await response.json();
                
                if (result.success) {
                    showQuotationDetailsModal(result.quotation);
                } else {
                    showToast('Failed to load quotation details', 'error');
                }
            } catch (error) {
                console.error('Error fetching quotation details:', error);
                showToast('Error loading quotation details', 'error');
            }
        }
        
        // Function to accept quotation
        async function acceptQuotation(quotationId) {
            try {
                const formData = new FormData();
                formData.append('action', 'accept_quotation');
                formData.append('id', quotationId);
                
                const response = await fetch('client_quotations_api.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showToast('Quotation accepted successfully!', 'success');
                    fetchQuotations(); // Refresh the list
                } else {
                    showToast(result.message || 'Failed to accept quotation', 'error');
                }
            } catch (error) {
                console.error('Error accepting quotation:', error);
                showToast('Error accepting quotation', 'error');
            }
        }
        
        // Function to accept quotation
        async function acceptQuotation(quotationId, amount, reference) {
            if (!confirm(`Accept this quotation of ${formatCurrency(amount)}?`)) {
                return;
            }
            
            try {
                const formData = new FormData();
                formData.append('action', 'accept_quotation');
                formData.append('id', quotationId);
                
                const response = await fetch('client_quotations_api.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showToast('Quotation accepted! Please proceed with payment.', 'success');
                    fetchQuotations(); // Refresh the list
                    
                    // Optionally show payment form immediately
                    setTimeout(() => {
                        if (confirm('Would you like to submit payment now?')) {
                            openPaymentForm(reference, amount);
                        }
                    }, 1000);
                } else {
                    showToast(result.message || 'Failed to accept quotation', 'error');
                }
            } catch (error) {
                console.error('Error accepting quotation:', error);
                showToast('Error accepting quotation', 'error');
            }
        }
        
        // Function to open payment form and switch to payments section
        function openPaymentForm(quoteReference, amount) {
            // Switch to payments section
            showSection('payments', getNavButtonBySectionId('payments'));
            
            // Fill in the form
            document.getElementById('quote-reference').value = quoteReference;
            document.getElementById('amount-paid').value = parseFloat(amount).toFixed(2);
            
            // Scroll to form
            setTimeout(() => {
                document.getElementById('quote-reference').scrollIntoView({ 
                    behavior: 'smooth', 
                    block: 'center' 
                });
                
                // Highlight the form briefly
                const form = document.getElementById('payment-proof-form').parentElement;
                form.style.border = '3px solid #d97706';
                form.style.transition = 'border 0.3s ease';
                
                setTimeout(() => {
                    form.style.border = '';
                }, 2000);
            }, 300);
        }
        
        // Function to decline quotation
        async function declineQuotation(quotationId) {
            if (!confirm('Are you sure you want to decline this quotation?')) {
                return;
            }
            
            try {
                const formData = new FormData();
                formData.append('action', 'decline_quotation');
                formData.append('id', quotationId);
                
                const response = await fetch('client_quotations_api.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showToast('Quotation declined', 'info');
                    fetchQuotations(); // Refresh the list
                } else {
                    showToast(result.message || 'Failed to decline quotation', 'error');
                }
            } catch (error) {
                console.error('Error declining quotation:', error);
                showToast('Error declining quotation', 'error');
            }
        }

        // --- PAYMENTS SECTION JS ---
        function renderPaymentHistory() {
            const tableBody = document.getElementById('payment-history-table');
            if (!tableBody) return;
            tableBody.innerHTML = '';
            
            if (PAYMENT_HISTORY.length === 0) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 40px 20px; color: var(--text-muted);">
                            <i class="fas fa-receipt" style="font-size: 48px; margin-bottom: 15px; opacity: 0.3; display: block;"></i>
                            <strong>No Payment History</strong><br>
                            <span style="font-size: 14px;">Your payment transactions will appear here</span>
                        </td>
                    </tr>
                `;
                return;
            }

            PAYMENT_HISTORY.forEach(payment => {
                const statusClass = payment.status === 'Confirmed' || payment.status === 'Verified' ? 'status-ok' : 
                                   payment.status === 'Paid' || payment.status === 'Completed' ? 'status-ok' : 'status-warn';
                const html = `
                    <tr>
                        <td><strong>${payment.ref}</strong></td>
                        <td>${payment.date}</td>
                        <td style="font-weight: 700;">${formatCurrency(payment.amount)}</td>
                        <td><span class="status-badge ${statusClass}">${payment.status}</span></td>
                        <td>
                            <button class="btn btn-outline btn-sm" onclick="viewTransactionDetails('${payment.ref}', ${payment.quotation_id})">
                                <i class="fas fa-eye"></i> Details
                            </button>
                        </td>
                    </tr>
                `;
                tableBody.insertAdjacentHTML('beforeend', html);
            });
        }

        // --- SCHEDULE SECTION JS ---
        function renderScheduleList() {
            const listContainer = document.getElementById('schedule-list');
            if (!listContainer) return;
            listContainer.innerHTML = '';
            
            if (SCHEDULE_ITEMS.length === 0) {
                listContainer.innerHTML = `
                    <div style="padding: 40px 20px; text-align: center; color: var(--text-muted);">
                        <i class="fas fa-calendar-times" style="font-size: 48px; margin-bottom: 15px; opacity: 0.3;"></i>
                        <h4 style="margin: 0 0 8px 0; font-size: 16px;">No Upcoming Appointments</h4>
                        <p style="margin: 0; font-size: 14px;">Your scheduled appointments will appear here</p>
                    </div>
                `;
                return;
            }

            SCHEDULE_ITEMS.forEach(item => {
                const statusClass = item.status === 'Scheduled' ? 'status-ok' : 
                                  item.status === 'Verified' ? 'status-info' : 'status-warn';
                                  
                const teamColor = item.team === 'Team Alpha' ? '#3498db' :
                                item.team === 'Team Beta' ? '#e74c3c' : '#f39c12';
                
                const html = `
                    <div class="schedule-item" onclick="showToast('${item.type} appointment on ${item.day} ${item.month} with ${item.team}', 'info')">
                        <div class="schedule-date">
                            <div class="day">${item.day}</div>
                            <div class="month">${item.month}</div>
                        </div>
                        <div class="schedule-details">
                            <h4>${item.type} - ${item.machine}</h4>
                            <p>Ref: ${item.ref} | <span class="badge ${statusClass}">${item.status}</span></p>
                            <p style="margin-top: 4px; font-size: 12px; color: ${teamColor};">👥 ${item.team || 'Team Alpha'}</p>
                        </div>
                        <span class="schedule-time"><i class="fas fa-clock"></i> ${item.time}</span>
                    </div>
                `;
                listContainer.insertAdjacentHTML('beforeend', html);
            });
        }

        // --- DASHBOARD RENDERING JS ---
        function renderDashboardOwnedMachines() {
            const listContainer = document.getElementById('dashboard-asset-history');
            if (!listContainer) return;
            listContainer.innerHTML = '';

            if (OWNED_MACHINES.length === 0) {
                listContainer.innerHTML = '<div style="padding: 20px; text-align: center; color: var(--text-muted); font-size: 13px;">No active machines found.</div>';
                return;
            }

            OWNED_MACHINES.slice(0, 3).forEach(machine => {
                const html = `
                    <div class="schedule-item" style="cursor: default;">
                        <div class="schedule-date" style="width: 70px; margin-right: 15px; padding-right: 15px;">
                            <div class="day" style="font-size: 16px;">${machine.model}</div>
                            <div class="month">${machine.type}</div>
                        </div>
                        <div class="schedule-details">
                            <h4 style="margin-bottom: 2px;">Asset ID: ${machine.id}</h4>
                            <p>Purchased: ${machine.purchased}</p>
                        </div>
                    </div>
                `;
                listContainer.insertAdjacentHTML('beforeend', html);
            });
        }

        function renderDashboardQuotes() {
            const listContainer = document.getElementById('dashboard-quotes-list');
            if (!listContainer) return;
            listContainer.innerHTML = '';

            QUOTATIONS.slice(0, 3).forEach(quote => {
                const html = `
                    <div class="dash-quote-item" onclick="showSection('quotes', getNavButtonBySectionId('quotes'))">
                        <div class="dash-quote-details">
                            <h5>Quote Ref: ${quote.ref} (${quote.type})</h5>
                            <span class="dash-quote-status">${quote.items} <span class="status-badge ${quote.badgeClass}">${quote.status}</span></span>
                        </div>
                        <span class="dash-quote-amount">${formatCurrency(quote.total) === '₱0.00' ? '₱0.00 (Under Contract)' : formatCurrency(quote.total)}</span>
                    </div>
                `;
                listContainer.insertAdjacentHTML('beforeend', html);
            });
        }

        // Function to send package data to secretary
        async function sendPackageDataToSecretary() {
            const checkedPackage = document.querySelector('.pkg-radio:checked');
            const logisticsOption = document.getElementById('logistics-option').value;
            
            if (!checkedPackage) {
                showToast('Please select a package first.', 'warning');
                return;
            }
            
            // Show loading state
            const button = event.target;
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending Request...';
            button.disabled = true;
            
            try {
                const formData = new FormData();
                formData.append('action', 'send_package_request');
                formData.append('package', checkedPackage.value);
                formData.append('logistics', logisticsOption);
                
                const response = await fetch('sales_inquiry_api.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showToast('Investment request sent to secretary successfully!', 'success');
                    
                    // Show additional details
                    setTimeout(() => {
                        showToast(`Quotation ID: ${result.quotation_id}`, 'info');
                    }, 1000);
                    
                    setTimeout(() => {
                        showToast(`Total Investment: ₱${result.total_amount.toLocaleString('en-US', { minimumFractionDigits: 2 })}`, 'info');
                    }, 2000);
                    
                    // Reset form or disable further submissions
                    button.innerHTML = '<i class="fas fa-check-circle"></i> Request Sent';
                    button.style.backgroundColor = '#10b981';
                    
                } else {
                    showToast(`Failed to send request: ${result.message}`, 'error');
                    button.innerHTML = originalText;
                    button.disabled = false;
                }
            } catch (error) {
                console.error('Error sending package data:', error);
                showToast('Error sending investment request. Please try again.', 'error');
                button.innerHTML = originalText;
                button.disabled = false;
            }
        }

        // Initialize the view
        document.addEventListener('DOMContentLoaded', () => {
            renderPackageOptions();
            renderMaintenanceMachineList();
            fetchQuotations(); // Fetch real quotations from database
            fetchPaymentHistory(); // Load real payment data
            fetchSchedule(); // Load real appointment data
            renderDashboardOwnedMachines();

            showSection('home', document.querySelector('.nav-btn.active'));

            selectPackage(PACKAGES[0].id);

            const repairForm = document.getElementById('repair-request-form');
            if (repairForm) {
                repairForm.addEventListener('submit', submitRepairRequest);
            }

            updateShopSummary();
            updateRepairEstimate();
        });

        function showToast(message, type = 'info', duration = 5000) {
            // Remove existing toasts
            const existingToasts = document.querySelectorAll('.toast-notification');
            existingToasts.forEach(toast => toast.remove());
            
            // Create toast element
            const toast = document.createElement('div');
            toast.className = 'toast-notification';
            
            // Determine icon and color based on type
            let icon, bgColor, textColor;
            switch(type) {
                case 'success':
                    icon = 'fa-check-circle';
                    bgColor = '#d4edda';
                    textColor = '#155724';
                    break;
                case 'error':
                    icon = 'fa-exclamation-circle';
                    bgColor = '#f8d7da';
                    textColor = '#721c24';
                    break;
                case 'warning':
                    icon = 'fa-exclamation-triangle';
                    bgColor = '#fff3cd';
                    textColor = '#856404';
                    break;
                default:
                    icon = 'fa-info-circle';
                    bgColor = '#d1ecf1';
                    textColor = '#0c5460';
            }
            
            toast.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background-color: ${bgColor};
                color: ${textColor};
                padding: 15px 20px;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                z-index: 10000;
                max-width: 400px;
                display: flex;
                align-items: center;
                gap: 10px;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                font-size: 14px;
                transform: translateX(100%);
                transition: all 0.3s ease;
            `;
            
            toast.innerHTML = `
                <i class="fas ${icon}" style="font-size: 16px;"></i>
                <span>${message}</span>
                <button onclick="this.parentElement.remove()" style="
                    background: none; 
                    border: none; 
                    color: ${textColor}; 
                    font-size: 18px; 
                    cursor: pointer;
                    padding: 0;
                    margin-left: auto;
                ">×</button>
            `;
            
            document.body.appendChild(toast);
            
            // Animate in
            setTimeout(() => {
                toast.style.transform = 'translateX(0)';
            }, 100);
            
            // Auto remove
            setTimeout(() => {
                toast.style.transform = 'translateX(100%)';
                setTimeout(() => {
                    if (toast.parentElement) {
                        toast.remove();
                    }
                }, 300);
            }, duration);
        }
        document.getElementById('payment-proof-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData();
            formData.append('action', 'submit_payment_proof');
            formData.append('quote_reference', document.getElementById('quote-reference').value);
            formData.append('amount_paid', document.getElementById('amount-paid').value);
            formData.append('proof_file', document.getElementById('proof-file').files[0]);
            
            const submitBtn = e.target.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
            submitBtn.disabled = true;
            
            fetch('payment_verification_api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Payment proof submitted successfully! Awaiting verification.', 'success');
                    e.target.reset();
                    renderQuotations(); // Refresh quotations to update status
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Payment submission error:', error);
                showToast('Failed to submit payment proof. Please try again.', 'error');
            })
            .finally(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        });

        // Load quotations when page loads
        document.addEventListener('DOMContentLoaded', function() {
            renderQuotations();
        });
    </script>
</body>
</html>
