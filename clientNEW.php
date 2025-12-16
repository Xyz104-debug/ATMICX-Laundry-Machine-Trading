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
        .quotations-panel {
            background: var(--white);
            border-radius: var(--radius-lg);
            padding: 30px; /* Panel padding */
            box-shadow: var(--shadow-card);
            border: 1px solid #e2e8f0;
        }

        .quotations-grid {
            display: flex;
            flex-direction: column;
            gap: 20px; /* Space between the cards */
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
                    <button class="btn btn-gold" style="width: 100%; margin-top: 20px;" onclick="showToast('Quotation Request Sent! You will be notified when it is ready.', 'success')"><i class="fas fa-paper-plane"></i> Request Final Quotation</button>
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
                <h2 class="panel-title" style="margin-bottom: 25px;">All My Quotations</h2>
                <div class="quotations-grid" id="quotations-grid">
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
                        <div class="panel" style="padding: 25px; height: auto;">
                            <h3 class="panel-title">Submit Proof of Payment</h3>
                            <div class="form-group" style="margin-top: 15px;">
                                <label class="form-label" for="invoice-ref">Quote/Invoice Reference #</label>
                                <input type="text" id="invoice-ref" class="form-control" placeholder="e.g., QT-2025-88" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="amount-paid">Amount Paid</label>
                                <input type="number" id="amount-paid" class="form-control" placeholder="0.00" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="proof-file">Upload Proof (e.g., deposit slip, screenshot)</label>
                                <input type="file" id="proof-file" class="form-control" style="padding: 10px;" required>
                            </div>
                            <button class="btn btn-primary" onclick="showToast('Payment Proof Submitted! Please wait for confirmation.', 'success')">
                                <i class="fas fa-upload"></i> Submit Proof
                            </button>
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

        const QUOTATIONS = [
            { ref: "QT-2025-88", type: "New Installation", date: "2025-09-01", total: 465000, status: "Awaiting Approval", items: "1-Set Package, Logistics", badgeClass: "status-warn" },
            { ref: "SVC-2025-99", type: "Repair Service", date: "2025-08-28", total: 4800, status: "Awaiting Payment", items: "Washer & Dryer Repair", badgeClass: "status-info" },
            { ref: "SVC-2025-70", type: "PMS Service", date: "2025-07-10", total: 0, status: "Completed", items: "Industrial Dryer PMS", badgeClass: "status-ok" },
            { ref: "QT-2024-05", type: "New Installation", date: "2024-01-10", total: 735000, status: "Approved/Paid", items: "2-Set Package, Logistics", badgeClass: "status-ok" },
        ];

        const PAYMENT_HISTORY = [
            { ref: "INV-2024-05", date: "2024-01-12", amount: 735000, status: "Confirmed" },
            { ref: "INV-2025-99", date: "2025-08-30", amount: 4800, status: "Pending Confirmation" },
        ];

        const SCHEDULE_ITEMS = [
            { day: "10", month: "DEC", type: "Installation", machine: "2-Set Package", ref: "QT-2025-88", time: "9:00 AM - 12:00 PM" },
            { day: "20", month: "JAN", type: "PMS Service", machine: "Dryer (D-002)", ref: "SVC-2025-70", time: "1:00 PM - 3:00 PM" },
        ];

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
            const qtyInputs = document.querySelectorAll('#maintenance-machine-list .qty-input');
            const serviceType = document.getElementById('service-type').value;
            const estimateValue = document.getElementById('repair-estimate-value');
            let totalEstimate = 0;
            let totalMachinesToService = 0;

            qtyInputs.forEach(input => {
                const quantity = parseInt(input.value) || 0;
                totalMachinesToService += quantity;
            });

            if (totalMachinesToService > 0) {
                if (serviceType === 'diagnosis') {
                    totalEstimate = 1000;
                } else if (serviceType === 'pms') {
                    totalEstimate = totalMachinesToService * 800;
                } else if (serviceType === 'repair') {
                    totalEstimate = totalMachinesToService * 1500;
                }
            } else {
                totalEstimate = 0;
            }

            estimateValue.textContent = formatCurrency(totalEstimate);
        }

        function submitRepairRequest(e) {
            e.preventDefault();
            const totalMachinesToService = Array.from(document.querySelectorAll('#maintenance-machine-list .qty-input'))
                .reduce((sum, input) => sum + (parseInt(input.value) || 0), 0);
            if (totalMachinesToService === 0) {
                showToast('Please select at least one machine (quantity > 0) to service.', 'error');
                return;
            }
            showToast(`Service Request for ${totalMachinesToService} machine(s) Submitted! A quotation will be sent to you shortly.`, 'success');
        }

        // --- QUOTATIONS SECTION JS ---
        function renderQuotations() {
            const gridContainer = document.getElementById('quotations-grid');
            if (!gridContainer) return;
            gridContainer.innerHTML = '';

            QUOTATIONS.forEach(quote => {
                const isPaid = quote.status === 'Approved/Paid';
                const buttonHtml = isPaid
                    ? `<button class="btn btn-outline" style="flex: 1;"><i class="fas fa-eye"></i> View Invoice</button>`
                    : `<button class="btn btn-gold" style="flex: 1;" onclick="showSection('payments', getNavButtonBySectionId('payments'), { quoteRef: '${quote.ref}' })"><i class="fas fa-file-upload"></i> Pay Now</button>
                        <button class="btn btn-danger" style="flex: 1; border: none;" onclick="showToast('Quotation ${quote.ref} declined.', 'info')"><i class="fas fa-times"></i> Decline</button>`;
                
                const html = `
                    <div class="quote-card" onclick="if(!event.target.closest('button')) showSection('quotes', getNavButtonBySectionId('quotes'))">
                        <div class="quote-header">
                            <h4>${quote.type} <span class="status-badge ${quote.badgeClass}">${quote.status}</span></h4>
                            <p style="font-size: 12px; color: var(--text-muted);">Ref: ${quote.ref}</p>
                        </div>
                        <div class="quote-details">
                            <div class="quote-details-row">
                                <span>Date Issued:</span> <span>${quote.date}</span>
                            </div>
                            <div class="quote-details-row">
                                <span>Items:</span> <span>${quote.items}</span>
                            </div>
                            <div class="quote-details-row" style="margin-top: 15px;">
                                <span style="font-size: 16px; font-weight: 700; color: var(--navy-dark);">Total Amount:</span>
                                <span style="font-size: 16px; font-weight: 800; color: var(--gold);">${formatCurrency(quote.total)}</span>
                            </div>
                        </div>
                        <div class="quote-actions">
                            ${buttonHtml}
                        </div>
                    </div>
                `;
                gridContainer.insertAdjacentHTML('beforeend', html);
            });
        }

        // --- PAYMENTS SECTION JS ---
        function renderPaymentHistory() {
            const tableBody = document.getElementById('payment-history-table');
            if (!tableBody) return;
            tableBody.innerHTML = '';

            PAYMENT_HISTORY.forEach(payment => {
                const statusClass = payment.status === 'Confirmed' ? 'status-ok' : 'status-warn';
                const html = `
                    <tr>
                        <td>${payment.ref}</td>
                        <td>${payment.date}</td>
                        <td style="font-weight: 700;">${formatCurrency(payment.amount)}</td>
                        <td><span class="status-badge ${statusClass}">${payment.status}</span></td>
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

            SCHEDULE_ITEMS.forEach(item => {
                const html = `
                    <div class="schedule-item" onclick="showToast('Viewing details for ${item.type} on ${item.day} ${item.month}', 'info')">
                        <div class="schedule-date">
                            <div class="day">${item.day}</div>
                            <div class="month">${item.month}</div>
                        </div>
                        <div class="schedule-details">
                            <h4>${item.type} - ${item.machine}</h4>
                            <p>Ref: ${item.ref} | Status: Confirmed</p>
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

        // Initialize the view
        document.addEventListener('DOMContentLoaded', () => {
            renderPackageOptions();
            renderMaintenanceMachineList();
            renderQuotations();
            renderPaymentHistory();
            renderScheduleList();
            renderDashboardOwnedMachines();
            renderDashboardQuotes();

            showSection('home', document.querySelector('.nav-btn.active'));

            selectPackage(PACKAGES[0].id);

            const repairForm = document.getElementById('repair-request-form');
            if (repairForm) {
                repairForm.addEventListener('submit', submitRepairRequest);
            }

            updateShopSummary();
            updateRepairEstimate();
        });

    </script>
</body>
</html>
