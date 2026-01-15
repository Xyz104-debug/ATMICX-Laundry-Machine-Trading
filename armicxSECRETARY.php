<?php
require_once 'role_session_manager.php';

// Start secretary session
RoleSessionManager::start('secretary');

// Prevent browser caching
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
header("Pragma: no-cache"); // HTTP 1.0.
header("Expires: 0"); // Proxies.

// Secretary authentication
if (!RoleSessionManager::isAuthenticated() || RoleSessionManager::getRole() !== 'secretary') {
    // No session - create a default one or redirect to login
    if (isset($_GET['auto_login'])) {
        RoleSessionManager::login(2, 'Secretary User', 'secretary');
    } else {
        header('Location: atmicxLOGIN.html');
        exit;
    }
}
// Allow access for any user with a session
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secretary Dashboard | ATMICX Trading</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        /* --- PREMIUM THEME VARIABLES --- */
        :root {
            --navy-dark: #0f172a;       
            --navy-light: #1e293b;     
            --gold: #d4af37;           
            --bg-body: #f8fafc;        
            --white: #ffffff;
            
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

        .brand-container { margin-bottom: 30px; }
        .brand { display: flex; align-items: center; gap: 12px; margin-bottom: 24px; }
        .brand img { height: 35px; width: auto; object-fit: contain; }
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

        .nav-links { list-style: none; flex: 1; }
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
        .header { height: 80px; background: var(--bg-body); display: flex; justify-content: space-between; align-items: center; padding: 0 40px; flex-shrink: 0; border-bottom: 1px solid #e2e8f0; }
        .header h1 { font-size: 24px; font-weight: 800; color: var(--navy-dark); letter-spacing: -0.02em; }
        .header-actions { display: flex; gap: 16px; align-items: center; position: relative; }
        .search-box { background: var(--white); padding: 10px 20px; border-radius: 30px; display: flex; align-items: center; gap: 10px; width: 280px; border: 1px solid #e2e8f0; transition: var(--transition); }
        .search-box:focus-within { border-color: var(--gold); box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.1); }
        .search-box input { border: none; outline: none; width: 100%; font-size: 13px; color: var(--text-main); }

        .notif-btn { position: relative; width: 40px; height: 40px; border-radius: 50%; background: white; border: 1px solid #e2e8f0; cursor: pointer; display: flex; justify-content: center; align-items: center; color: var(--navy-dark); transition: 0.2s; }
        .notif-btn:hover { background: #f1f5f9; }
        .notif-badge { position: absolute; top: -2px; right: -2px; width: 10px; height: 10px; background: #ef4444; border: 2px solid #fff; border-radius: 50%; }
        .notif-dropdown { position: absolute; top: 50px; right: 0; width: 320px; background: white; border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.1); border: 1px solid #e2e8f0; z-index: 100; display: none; animation: slideDown 0.2s ease; }
        .notif-dropdown.show { display: block; }
        @keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        
        .notif-header { padding: 15px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; }
        .notif-header h4 { font-size: 14px; font-weight: 700; margin: 0; }
        .notif-header button { font-size: 11px; color: var(--text-muted); background: none; border: none; cursor: pointer; text-decoration: underline; }
        
        .notif-body { max-height: 300px; overflow-y: auto; }
        .notif-item { padding: 15px; border-bottom: 1px solid #f1f5f9; display: flex; gap: 12px; align-items: start; transition: 0.2s; }
        .notif-item:hover { background: #f8fafc; }
        .notif-icon { width: 32px; height: 32px; background: #e0f2fe; color: #0369a1; border-radius: 50%; display: flex; justify-content: center; align-items: center; flex-shrink: 0; font-size: 12px; }
        .notif-content p { font-size: 13px; margin: 0 0 4px 0; color: var(--text-main); font-weight: 500; }
        .notif-content span { font-size: 11px; color: var(--text-muted); }

        .dashboard-view { flex: 1; overflow-y: auto; padding: 32px 40px; scrollbar-width: thin; }
        .section { display: none; opacity: 0; transform: translateY(10px); transition: all 0.4s ease; }
        .section.active { display: block; opacity: 1; transform: translateY(0); }

        /* Ensure all dashboard content is visible */
        #sec-dashboard.active { 
            display: block !important; 
            opacity: 1 !important; 
            transform: translateY(0) !important;
        }
        #sec-dashboard .metrics-grid-4 {
            display: grid !important;
            grid-template-columns: repeat(4, 1fr) !important;
            gap: 24px !important;
        }

        /* --- METRICS & PANELS --- */
        .metrics-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-bottom: 32px; }
        .metrics-grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; margin-bottom: 32px; }
        /* NEW: 4-Column Grid for Dashboard Metrics */
        .metrics-grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px; margin-bottom: 32px; }

        .metric-card {
            border-radius: 20px;
            padding: 24px;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 170px;
            color: white;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15);
            /* This is the key for smooth animation */
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(255,255,255,0.1);
        }
        /* This rule creates the 'move' effect (lifting up and changing shadow) */
        .metric-card:hover { 
            transform: translateY(-8px); /* Lifts the card up by 8px */
            box-shadow: 0 20px 35px -5px rgba(0, 0, 0, 0.25); /* Increases the shadow */
        }
        .metric-card i.bg-icon { position: absolute; right: -15px; top: 10px; font-size: 110px; opacity: 0.15; transform: rotate(-15deg); transition: all 0.3s ease; }
        .metric-card:hover i.bg-icon { transform: rotate(0deg) scale(1.1); opacity: 0.2; }

        .metric-header { display: flex; align-items: center; gap: 10px; z-index: 2; margin-bottom: 5px; }
        .metric-icon-small { width: 32px; height: 32px; background: rgba(255,255,255,0.4); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 14px; backdrop-filter: blur(4px); }
        .metric-label { font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; opacity: 0.9; }
        .metric-value { font-size: 34px; font-weight: 800; z-index: 2; margin: 10px 0; letter-spacing: -1px; }
        .metric-footer { z-index: 2; display: flex; align-items: center; gap: 8px; font-size: 12px; font-weight: 500; opacity: 0.9; background: rgba(0,0,0,0.1); width: fit-content; padding: 4px 10px; border-radius: 20px; }

        .card-green { background: linear-gradient(135deg, #059669 0%, #10b981 100%); }
        .card-orange { background: linear-gradient(135deg, #d97706 0%, #f59e0b 100%); }
        .card-red { background: linear-gradient(135deg, #b91c1c 0%, #ef4444 100%); }
        .card-blue { background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%); }

        /* --- Custom: Sales Quote Summary Box (MODIFIED) --- */
        .quote-summary-box {
            /* Changed from var(--navy-light) to a lighter shade */
            background: var(--bg-body); 
            transition: none; /* Disable transition for quick change */
            color: var(--text-main); /* Set text to dark for contrast */
            border: 1px solid #e2e8f0; /* Add a subtle border */
        }
        
        /* Explicitly prevents any change on hover for the quote summary box */
        .quote-summary-box:hover {
            background: var(--bg-body); /* Explicitly keep the same background color */
            box-shadow: none; /* Remove any potential shadow */
            transform: none; /* Remove any potential lift effect */
            cursor: default;
            border-color: #e2e8f0; /* Keep border color */
        }

        .quote-summary-box h3 {
             color: var(--navy-dark); /* Ensure header is dark for contrast */
        }

        .quote-detail-row {
            display: flex; 
            justify-content: space-between; 
            padding: 12px 0;
            /* Changed border color from light-on-dark to dark-on-light */
            border-bottom: 1px dashed #e2e8f0; 
            color: var(--text-muted); /* Make the detail text muted/darker */
        }
        
        .quote-detail-row:last-of-type { border-bottom: none; }
        /* --- END Custom: Sales Quote Summary Box --- */

        /* --- UTILITY COMPONENTS (FOR BALANCED UI) --- */
        .panel { background: var(--white); border-radius: var(--radius-lg); padding: 32px; box-shadow: var(--shadow-card); height: 100%; border: 1px solid #e2e8f0; display: flex; flex-direction: column; }
        /* Adjusted Content Grid for Calendar/Assignment View */
        .content-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; min-height: 400px; } 
        .panel-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .panel-title { font-size: 18px; font-weight: 700; color: var(--navy-dark); }
        .status-badge { padding: 6px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; display: inline-flex; align-items: center; gap: 6px; }
        .status-ok { background: var(--success-bg); color: var(--success-text); }
        .status-warn { background: var(--warning-bg); color: var(--warning-text); }
        .status-err { background: var(--danger-bg); color: var(--danger-text); }

        .btn { padding: 10px 18px; border-radius: 8px; border: none; font-weight: 600; cursor: pointer; transition: 0.2s; font-size: 13px; display: inline-flex; align-items: center; justify-content: center; gap: 8px; }
        .btn-primary { background: var(--navy-dark); color: white; width: 100%; }
        .btn-primary:hover { background: var(--navy-light); transform: translateY(-2px); }
        .btn-danger { background: white; border: 1px solid var(--danger-text); color: var(--danger-text); width: 100%; }
        .btn-danger:hover { background: var(--danger-bg); }
        .btn-outline { background: white; border: 1px solid #cbd5e1; color: var(--text-main); width: auto; }
        .btn-outline:hover { background: #f8fafc; }
        .btn-gold { background: var(--gold); color: var(--navy-dark); font-weight: 700; width: 100%;}
        .btn-gold:hover { background: #b49226; color: white; transform: translateY(-2px); }
        .btn-ghost { background: transparent; color: var(--text-muted); font-size: 12px; text-decoration: underline; cursor: pointer; border: none; width: auto; padding: 0; }
        
        /* NEW STYLES FOR BUTTON BLOCK */
        .quote-actions-block {
            padding-top: 20px;
            border-top: 1px dashed #e2e8f0;
            margin-top: 30px; 
        }
        .quote-actions-block .btn {
            margin-top: 8px;
            padding: 12px 18px; /* Slightly larger buttons */
            font-size: 14px;
        }

        /* File input styling to match the general form aesthetic */
        .file-upload-wrapper {
            display: flex;
            align-items: center;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: #f8fafc;
            overflow: hidden;
            width: 100%;
            height: 44px; /* Matches form-control height */
        }

        .file-upload-wrapper input[type="file"] {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
            overflow: hidden;
        }

        .file-upload-wrapper label {
            /* This is the Choose File button */
            flex-shrink: 0;
            background: #cbd5e1;
            color: var(--navy-dark);
            padding: 10px 15px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
            height: 100%;
            display: flex;
            align-items: center;
        }

        .file-upload-wrapper label:hover {
            background: #94a3b8;
        }
        
        .file-upload-name {
            /* This is the 'No file chosen' text */
            padding-left: 15px;
            font-size: 14px;
            color: var(--text-muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .tabs { display: flex; gap: 10px; margin-bottom: 30px; border-bottom: 2px solid #e2e8f0; }
        .tab { padding: 12px 24px; cursor: pointer; color: var(--text-muted); font-weight: 600; border-bottom: 2px solid transparent; margin-bottom: -2px; transition: 0.2s; }
        .tab:hover { color: var(--navy-dark); }
        .tab.active { color: var(--navy-dark); border-bottom-color: var(--gold); }
        
        /* Toast */
        .toast { position: fixed; bottom: 30px; right: 30px; background: var(--navy-dark); color: white; padding: 16px 24px; border-radius: 12px; display: flex; align-items: center; gap: 12px; transform: translateY(100px); transition: 0.3s; opacity: 0; box-shadow: 0 10px 30px rgba(0,0,0,0.2); z-index: 1000; }
        .toast.show { transform: translateY(0); opacity: 1; }

        /* KPI Cards in Reports */
        .kpi-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 24px; }

        /* List Styles */
        .triage-list { display: flex; flex-direction: column; gap: 25px; } /* Increased gap for better separation */
        .triage-card { background: var(--white); border-radius: var(--radius-lg); padding: 24px; box-shadow: var(--shadow-card); border: 1px solid #e2e8f0; transition: var(--transition); }
        .triage-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-hover); border-color: var(--gold); }
        .triage-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .triage-type { font-size: 12px; font-weight: 700; text-transform: uppercase; background: #e0f2fe; color: #075985; padding: 4px 8px; border-radius: 4px; }
        .triage-info h4 { font-size: 16px; font-weight: 800; color: var(--navy-dark); margin: 0; }
        .triage-info p { font-size: 13px; color: var(--text-muted); margin-top: 5px; display: flex; align-items: center; gap: 8px; }
        .triage-footer { display: flex; justify-content: space-between; align-items: center; margin-top: 15px; padding-top: 15px; border-top: 1px dashed #e2e8f0; }
        .triage-btn { font-size: 12px; padding: 8px 15px; width: auto; justify-content: center; }

        .txn-list { display: flex; flex-direction: column; gap: 25px; } /* Increased gap for better separation */
        .txn-card { 
            background: var(--white); 
            border-radius: var(--radius-lg); 
            padding: 0; 
            box-shadow: var(--shadow-card); 
            border: 1px solid #e2e8f0; 
            display: grid; 
            grid-template-columns: 80px 1.8fr 2.2fr 1.2fr; 
            overflow: hidden; 
            transition: var(--transition); 
            align-items: stretch;
        }
        .txn-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-hover); border-color: var(--gold); }
        .txn-icon-col { background: #f8fafc; display: flex; align-items: center; justify-content: center; border-right: 1px solid #e2e8f0; font-size: 24px; color: var(--navy-light); }
        .txn-client-col { padding: 24px; border-right: 1px dashed #e2e8f0; display: flex; flex-direction: column; justify-content: center; }
        .txn-client-name { font-size: 14px; font-weight: 700; color: var(--navy-dark); margin-bottom: 4px; }
        .txn-client-loc { font-size: 12px; color: var(--text-muted); display: flex; align-items: center; gap: 6px; }
        .txn-ref { font-size: 10px; font-weight: 700; color: var(--info-text); margin-bottom: 6px; display: inline-block; background: var(--info-bg); padding: 3px 8px; border-radius: 4px; width: fit-content; }
        .txn-finance-col { padding: 24px; display: flex; flex-direction: column; justify-content: center; }
        .finance-row { display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 4px; color: var(--text-muted); }
        .finance-row.total { font-weight: 700; color: var(--navy-dark); border-top: 1px dashed #e2e8f0; padding-top: 6px; margin-top: 4px; font-size: 13px; }
        .txn-action-col { padding: 24px; background: #fafbfc; border-left: 1px solid #e2e8f0; display: flex; flex-direction: column; justify-content: center; gap: 8px; }
        .txn-action-col .btn { font-size: 12px; padding: 8px 12px; width: 100%; justify-content: center; }

        .form-group { margin-bottom: 15px; }
        .form-label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 8px; color: var(--text-main); }
        .form-control { width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px; background: #f8fafc; transition: 0.2s; }
        .form-control:focus { background: white; border-color: var(--gold); outline: none; }
        
        .total-summary-card { background: var(--navy-dark); color: white; padding: 20px; border-radius: var(--radius-md); margin-top: 20px; }
        .total-summary-card .label { font-size: 14px; color: #94a3b8; margin-bottom: 4px; }
        .total-summary-card .value { font-size: 32px; font-weight: 800; }
        
        table { width: 100%; border-collapse: separate; border-spacing: 0 15px; } 
        th { text-align: left; padding: 0 16px 8px; font-size: 12px; color: var(--text-muted); text-transform: uppercase; font-weight: 700; }
        td { padding: 16px; background: var(--white); font-size: 14px; border-top: 1px solid #f1f5f9; border-bottom: 1px solid #f1f5f9; }
        td:first-child { border-left: 1px solid #f1f5f9; border-top-left-radius: 12px; border-bottom-left-radius: 12px; }
        td:last-child { border-right: 1px solid #f1f5f9; border-top-right-radius: 12px; border-bottom-right-radius: 12px; }
        tbody tr:nth-child(odd) td { background-color: var(--white); }
        tbody tr:nth-child(even) td { background-color: #f8fafc; }

        /* New Styles for Scheduling Card */
        .schedule-card {
            background: var(--white); 
            border-radius: var(--radius-lg); 
            padding: 24px; 
            box-shadow: var(--shadow-card); 
            border: 1px solid #e2e8f0; 
            display: grid; 
            grid-template-columns: 1fr; /* Single column layout for internal details */
            gap: 15px; /* Adjusted gap for stacking */
            margin-bottom: 0px; /* Reset margin */
            transition: var(--transition);
        }
        .schedule-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-hover); border-color: var(--gold); }
        .job-detail { font-size: 14px; }
        .job-detail strong { display: block; font-size: 16px; color: var(--navy-dark); margin-top: 4px; }
        .job-detail .status { font-size: 11px; font-weight: 700; padding: 4px 8px; border-radius: 4px; display: inline-block; margin-top: 5px; }
        
        /* The .schedule-actions container is now a FORM for actionable cards */
        /* I will remove the general .schedule-actions styling and apply specific styling to the form elements */
        .schedule-actions { 
            display: flex; 
            flex-direction: row; 
            gap: 10px;          
            align-items: center; 
            padding-top: 10px; 
            border-top: 1px dashed #e2e8f0; 
            width: 100%; 
        }
        .schedule-actions .form-control {
            flex-grow: 1; 
            width: auto;
        }
        .schedule-actions .btn {
            width: auto; 
            flex-shrink: 0;
        }
        
        /* --- Grid container for side-by-side schedule cards (MODIFIED) --- */
        .schedule-grid {
            display: grid;
            grid-template-columns: 1fr; /* Now stacks cards vertically (1 card per row) */ 
            gap: 24px; 
        }
        /* --- END Schedule Grid CSS --- */

        /* --- NEW: Calendar Styles (For "Scheduling above Calendar" request) --- */
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 1px;
            border: 1px solid #e2e8f0;
            border-radius: var(--radius-md);
            overflow: hidden;
        }
        .calendar-header {
            background: var(--navy-dark);
            color: white;
            text-align: center;
            padding: 10px 0;
            font-weight: 600;
            font-size: 13px;
        }
        .calendar-day {
            background: var(--white);
            padding: 10px 5px;
            min-height: 80px;
            border: 1px solid #f1f5f9;
            font-size: 14px;
            position: relative;
            cursor: pointer;
            transition: background 0.1s;
        }
        .calendar-day:hover:not(.inactive) { background: #e0f2fe; }
        .calendar-day.today { border: 2px solid var(--gold); }
        .calendar-day.inactive { background: #f8fafc; color: var(--text-muted); opacity: 0.6; cursor: default; }
        .event-badge { background: var(--danger-bg); color: var(--danger-text); font-size: 10px; padding: 2px 4px; border-radius: 4px; display: block; margin-top: 5px; font-weight: 600; }
        /* --- END Calendar Styles --- */

        /* --- MODAL STYLES --- */
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
        
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="brand-container">
            <div class="brand">
                <img src="logo.png" alt="ATMICX Logo" style="height: 32px; width: auto; object-fit: contain;">
                <div><h2>ATMICX <span>Secretary</span></h2></div>
            </div>
            
            <div class="user-profile-box" onclick="toast('Opening Profile Settings...')">
                <div class="avatar" style="background: linear-gradient(135deg, #10b981, #059669); color: white;"><?php echo strtoupper(substr(RoleSessionManager::getUsername(), 0, 1)); ?></div>
                <div class="user-info">
                    <div class="name" style="color:#f1f5f9;"><?php echo RoleSessionManager::getUsername(); ?></div>
                    <div class="role"><?php echo ucfirst($_SESSION['role']); ?></div>
                </div>
                <i class="fas fa-cog settings-icon"></i>
            </div>
        </div>

        <ul class="nav-links">
            <li class="nav-item"><button class="nav-btn active" onclick="nav('sec-dashboard', this)"><i class="fas fa-tachometer-alt"></i> Dashboard</button></li>
            <li class="nav-item"><button class="nav-btn" onclick="nav('service-requests', this)"><i class="fas fa-headset"></i> Service Requests <span id="service-requests-badge" style="margin-left:auto; background:#ef4444; color:white; font-size:10px; padding:2px 6px; border-radius:4px; display:none;">0</span></button></li>
            <li class="nav-item"><button class="nav-btn" onclick="nav('sales-quotes', this)"><i class="fas fa-hand-holding-usd"></i> Sales Quotes</button></li>
            <li class="nav-item"><button class="nav-btn" onclick="nav('job-scheduling', this)"><i class="fas fa-calendar-alt"></i> Job Scheduling</button></li> 
            <li class="nav-item"><button class="nav-btn" onclick="nav('inventory-check', this)"><i class="fas fa-box-open"></i> Inventory Check</button></li>
            <li class="nav-item"><button class="nav-btn" onclick="nav('customer-records', this)"><i class="fas fa-address-book"></i> Customer Records</button></li>
        </ul>

        <div class="sidebar-footer">
            <button class="logout-btn" onclick="openLogoutModal()">
                <i class="fas fa-sign-out-alt"></i> Logout
            </button>
        </div>
    </aside>

    <main class="main-content">
        <header class="header">
            <h1 id="page-title">Operations Dashboard</h1>
            <div class="header-actions">
                <div class="search-box">
                    <i class="fas fa-search" style="color: #94a3b8;"></i>
                    <input type="text" placeholder="Search data..." id="global-search">
                </div>
                
                <button class="notif-btn" onclick="toggleNotif()">
                    <i class="fas fa-bell"></i>
                    <span class="notif-badge" id="notif-dot"></span>
                </button>

                <div class="notif-dropdown" id="notif-dropdown">
                    <div class="notif-header">
                        <h4>Notifications</h4>
                        <button onclick="clearNotif()">Clear All</button>
                    </div>
                    <div class="notif-body">
                        <div class="notif-item">
                            <div class="notif-icon" style="background:#fee2e2; color:#991b1b;"><i class="fas fa-wrench"></i></div>
                            <div class="notif-content">
                                <p>New Maintenance Request (URGENT)</p>
                                <span>1 min ago</span>
                            </div>
                        </div>
                        <div class="notif-item">
                            <div class="notif-icon" style="background:#fff7ed; color:#9a3412;"><i class="fas fa-dollar-sign"></i></div>
                            <div class="notif-content">
                                <p>Sales Inquiry: New Investor John</p>
                                <span>5 mins ago</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <div class="dashboard-view">

            <div id="sec-dashboard" class="section active">
                <div class="metrics-grid-4">
                    <div class="metric-card card-red triage-btn">
                        <i class="fas fa-exclamation-triangle bg-icon"></i>
                        <div class="metric-header">
                            <div class="metric-icon-small" style="background:rgba(255,255,255,0.4);"><i class="fas fa-wrench"></i></div>
                            <span class="metric-label">Urgent Maintenance</span>
                        </div>
                        <h3 class="metric-value">1 Request</h3>
                        <div class="metric-footer" style="background:rgba(255,255,255,0.15);">Triage Now <i class="fas fa-arrow-right"></i></div>
                    </div>
                    <div class="metric-card card-orange triage-btn">
                        <i class="fas fa-users bg-icon"></i>
                        <div class="metric-header">
                            <div class="metric-icon-small" style="background:rgba(255,255,255,0.4);"><i class="fas fa-dollar-sign"></i></div>
                            <span class="metric-label">New Sales Inquiries</span>
                        </div>
                        <h3 class="metric-value">2 Leads</h3>
                        <div class="metric-footer" style="background:rgba(255,255,255,0.15);">Triage Now <i class="fas fa-arrow-right"></i></div>
                    </div>
                    <div class="metric-card card-blue" style="min-height: 140px;" onclick="nav('job-scheduling', getNavButtonBySectionId('job-scheduling'))">
                        <i class="fas fa-check-circle bg-icon"></i>
                        <div class="metric-header">
                            <div class="metric-icon-small" style="background:rgba(255,255,255,0.4);"><i class="fas fa-file-invoice"></i></div>
                            <span class="metric-label">Jobs Awaiting Schedule</span>
                        </div>
                        <h3 class="metric-value">1 Job</h3>
                    </div>
                    <div class="metric-card card-green" style="min-height: 140px;">
                        <i class="fas fa-boxes bg-icon"></i>
                        <div class="metric-header">
                            <div class="metric-icon-small" style="background:rgba(255,255,255,0.4);"><i class="fas fa-box-open"></i></div>
                            <span class="metric-label">Low Stock Alerts</span>
                        </div>
                        <h3 class="metric-value" id="low-stock-count">0 Alerts</h3>
                    </div>
                </div>
                <div class="panel">
                    <div class="panel-header"><span class="panel-title">Active Service & Sales Triage List</span></div>
                    <div class="triage-list">
                        <div class="triage-card">
                            <div class="triage-header">
                                <span class="triage-type" style="background:var(--danger-bg); color:var(--danger-text);">Maintenance Request</span>
                                <span style="font-size:12px; color:var(--text-muted);">Client: Maria Cruz</span>
                            </div>
                            <div class="triage-info">
                                <h4>Water Pipe Burst (URGENT)</h4>
                                <p><i class="fas fa-map-marker-alt" style="color:var(--text-muted);"></i> Cebu City Branch | <i class="fas fa-calendar-alt" style="color:var(--text-muted);"></i> Requested: Today 9:30 AM</p>
                            </div>
                            <div class="triage-footer">
                                <span style="font-size:14px; font-weight:700; color:var(--navy-dark);">Status: Awaiting Dispatch</span>
                                <button class="btn btn-danger triage-btn"><i class="fas fa-plus-circle"></i> View & Triage</button>
                            </div>
                        </div>
                        <div class="triage-card">
                            <div class="triage-header">
                                <span class="triage-type" style="background:var(--warning-bg); color:var(--warning-text);">Sales Inquiry</span>
                                <span style="font-size:12px; color:var(--text-muted);">Client: New Investor John</span>
                            </div>
                            <div class="triage-info">
                                <h4>Inquiry on a 2-Set Package</h4>
                                <p><i class="fas fa-map-marker-alt" style="color:var(--text-muted);"></i> Manila City | <i class="fas fa-calendar-alt" style="color:var(--text-muted);"></i> Requested: Yesterday 4:00 PM</p>
                            </div>
                            <div class="triage-footer">
                                <span style="font-size:14px; font-weight:700; color:var(--navy-dark);">Status: Unassigned</span>
                                <button class="btn btn-primary triage-btn"><i class="fas fa-plus-circle"></i> View & Triage</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="service-requests" class="section">
                <div class="panel">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <div class="tabs">
                            <div class="tab active" onclick="switchReportTab(this, 'sales-inquiries')">Sales Inquiries</div>
                            <div class="tab" onclick="switchReportTab(this, 'maintenance-requests')">Maintenance Requests</div>
                        </div>
                        <button class="btn btn-outline btn-sm" onclick="refreshSalesInquiries()" style="margin-left: auto;">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                    </div>
                    <div id="sales-inquiries" class="tab-content active">
                        <div class="txn-list" id="sales-inquiries-list">
                            <div style="text-align: center; color: var(--text-muted); padding: 20px;">
                                <i class="fas fa-spinner fa-spin"></i> Loading sales inquiries...
                            </div>
                        </div>
                    </div>
<br>
                    <div id="maintenance-requests" class="tab-content" style="display: none;">
                        <div class="txn-list" id="maintenance-requests-list">
                            <div style="text-align: center; color: var(--text-muted); padding: 20px;">
                                <i class="fas fa-spinner fa-spin"></i> Loading maintenance requests...
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="sales-quotes" class="section">
                <div class="panel">
                    <div class="panel-header">
                        <span class="panel-title">Quotation Builder</span>
                    </div>
                    <div class="tabs" style="margin-bottom: 20px;">
                        <div class="tab active" onclick="switchQuoteTab(this, 'sales-quote-form')">Sales Quote</div>
                    </div>
                    
                    <!-- Sales Quote Form -->
                    <div id="sales-quote-form" class="quote-form-content">
                    <div class="content-grid-2">
                        <div>
                            <div class="form-group">
                                <label for="clientName" class="form-label">Client Name</label>
                                <input type="text" id="clientName" class="form-control" placeholder="E.g., New Investor John">
                            </div>
                            <div class="form-group">
                                <label for="clientContact" class="form-label">Client Contact</label>
                                <input type="text" id="clientContact" class="form-control" placeholder="E.g., +63 917 123 4567">
                            </div>
                            <div class="form-group">
                                <label for="clientEmail" class="form-label">Client Email</label>
                                <input type="email" id="clientEmail" class="form-control" placeholder="E.g., john.doe@email.com">
                            </div>
                            <div class="form-group">
                                <label for="packageType" class="form-label">Package / Product</label>
                                <select id="packageType" class="form-control" onchange="calculateSalesQuote()">
                                    <option value="">Select Package</option>
                                    <option value="600000">The Micro Start - 2 Sets (₱600,000)</option>
                                    <option value="900000" selected>The Essential Start - 3 Sets (₱900,000)</option>
                                    <option value="1200000">The Standard Shop - 4 Sets (₱1,200,000)</option>
                                    <option value="1500000">The Growth Model - 5 Sets (₱1,500,000)</option>
                                    <option value="2000000">The Premium Corner - 6 Sets (₱2,000,000)</option>
                                    <option value="2700000">The Anchor Laundromat - 8 Sets (₱2,700,000)</option>
                                    <option value="3500000">The Industrial Lite - 10 Sets (₱3,500,000)</option>
                                    <option value="4500000">The Multi-Load Center - 12 Sets (₱4,500,000)</option>
                                    <option value="6000000">The Technology Hub - 15 Sets (₱6,000,000)</option>
                                    <option value="8500000">The Flagship Enterprise - 20 Sets (₱8,500,000)</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="installation" class="form-label">Installation / Service Fee</label>
                                <input type="number" id="installation" class="form-control" value="15000" oninput="calculateSalesQuote()">
                            </div>
                            <div class="form-group">
                                <label for="notes" class="form-label">Quote Notes</label>
                                <textarea id="notes" class="form-control" rows="3" placeholder="Special requirements, warranty info, etc."></textarea>
                            </div>
                            <div class="form-group" style="margin-top: 30px;">
                               
                            </div>
                        </div>
                        <div class="quote-summary-box" style="padding:20px; border-radius:var(--radius-lg); height:fit-content;">
                            <h3 style="font-size:16px; margin-bottom:15px; border-bottom:1px solid #e2e8f0; padding-bottom:10px;">Quote Summary</h3>
                            <div class="quote-detail-row">
                                <span style="font-size:13px;">Package Value:</span> <strong id="val-package" style="color:var(--gold);">₱900,000.00</strong>
                            </div>
                            <div class="quote-detail-row">
                                <span style="font-size:13px;">Installation Fee:</span> <strong id="val-installation" style="color:var(--gold);">₱15,000.00</strong>
                            </div>
                            <div class="total-summary-card">
                                <div class="label">Total Amount Due</div>
                                <div class="value" id="val-total">₱915,000.00</div>
                            </div>
                            
                            <div class="quote-actions-block">
                                <div class="form-group">
                                    <label for="proofUpload" class="form-label">Photo Proof of Printed Quote</label>
                                    <div class="file-upload-wrapper">
                                        <label for="proofUpload" id="proof-label">Choose File</label>
                                        <input type="file" id="proofUpload" onchange="updateFileName(this)">
                                        <span class="file-upload-name" id="file-name">No file chosen</span>
                                    </div>
                                </div>
                                <button class="btn btn-gold" onclick="submitQuoteToManager()"><i class="fas fa-cloud-upload-alt"></i> Upload Proof & Send to Manager</button>
                                <button class="btn btn-primary" onclick="printQuote()"><i class="fas fa-print"></i> Print Quote</button>
                                <button class="btn btn-danger" style="background: white; border: 1px solid var(--danger-text); color: var(--danger-text); margin-top: 8px;" onclick="document.getElementById('clientName').value=''; document.getElementById('packageType').value=''; calculateSalesQuote()"><i class="fas fa-times-circle"></i> Clear Form</button>
                            </div>
                        </div>
                    </div>
                    </div>
                    

                </div>
            </div>

            <div id="job-scheduling" class="section">
                
                <div class="panel">
                    <div class="panel-header">
                        <span class="panel-title" id="calendar-title">Technician Team Calendar</span>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <button class="btn btn-outline" onclick="navigateMonth(-1)" style="padding: 8px 12px;"><i class="fas fa-chevron-left"></i></button>
                            <button class="btn btn-outline" onclick="navigateMonth(1)" style="padding: 8px 12px;"><i class="fas fa-chevron-right"></i></button>
                            <div class="status-badge status-ok" id="teams-available">3 Teams Available</div>
                        </div>
                    </div>
                    
                    <div class="calendar-grid" id="dynamic-calendar">
                        <div class="calendar-header">SUN</div>
                        <div class="calendar-header">MON</div>
                        <div class="calendar-header">TUE</div>
                        <div class="calendar-header">WED</div>
                        <div class="calendar-header">THU</div>
                        <div class="calendar-header">FRI</div>
                        <div class="calendar-header">SAT</div>
                        <!-- Calendar days will be generated dynamically -->
                    </div>
                </div>

                <br>

                <div class="panel" style="margin-bottom: 30px;">
                    <div class="panel-header">
                        <span class="panel-title">Jobs Awaiting Technician Assignment</span>
                        <div class="status-badge status-ok" id="jobs-ready-count">Loading...</div>
                    </div>

                    <div class="schedule-grid" id="jobs-awaiting-assignment"> 
                        
                        <div class="schedule-card" id="job-sq113"> 
                            <div class="job-detail">
                                <span style="font-size:12px; color:var(--text-muted); display:block;">JOB #SQ113 (Installation)</span>
                                <strong>Client: Ms. Alexia Perez</strong>
                                <div class="status" style="background:var(--success-bg); color:var(--success-text);">Payment Verified</div>
                            </div>
                            <div class="job-detail">
                                <span style="font-size:12px; color:var(--text-muted); display:block;">Job Location / Type</span>
                                <strong>Makati City Branch</strong>
                                <span style="font-size:13px; color:var(--text-muted);"><i class="fas fa-box-open"></i> The Micro Start (2 Sets)</span>
                            </div>
                            <form class="assignment-form" onsubmit="return handleAssignment(event, 'SQ113')" style="padding-top: 10px; border-top: 1px dashed #e2e8f0;"> 
                                <div class="form-group">
                                    <label for="techName-sq113" class="form-label">Assigned Technician Name</label>
                                    <input type="text" id="techName-sq113" name="tech_name" class="form-control" placeholder="E.g., Rico Diaz" required>
                                </div>
                                <div class="form-group">
                                    <label for="techContact-sq113" class="form-label">Technician Contact (Email/Phone)</label>
                                    <input type="text" id="techContact-sq113" name="tech_contact" class="form-control" placeholder="E.g., (0917) 123-4567" required>
                                </div>
                                
                                <div style="display: flex; gap: 10px;">
                                     <div class="form-group" style="flex: 1;">
                                        <label for="scheduleDate-sq113" class="form-label">Schedule Date</label>
                                        <input type="date" id="scheduleDate-sq113" name="schedule_date" class="form-control" required>
                                    </div>
                                     <div class="form-group" style="flex: 1;">
                                        <label for="scheduleTime-sq113" class="form-label">Schedule Time</label>
                                        <input type="time" id="scheduleTime-sq113" name="schedule_time" class="form-control" required>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="technicianTeam-sq113" class="form-label">Assign Technician Team</label>
                                    <select id="technicianTeam-sq113" name="technician_team" class="form-control" style="font-size:14px; padding: 12px;" required>
                                        <option value="">Select Technician Team</option>
                                        <option value="Team Alpha">Team Alpha (Manila)</option>
                                        <option value="Team Beta">Team Beta (Manila)</option>
                                        <option value="Team Charlie">Team Charlie (Cebu)</option>
                                    </select>
                                </div>
                                
                                <button type="submit" class="btn btn-gold" style="margin-top: 10px; padding: 12px; font-size: 14px; width: 100%;">
                                    <i class="fas fa-calendar-check"></i> Assign & Schedule Job
                                </button>
                            </form>
                            </div>

                        <div class="schedule-card" id="job-sq114" style="opacity: 0.7; border: 1px solid #e2e8f0;"> 
                            <div class="job-detail">
                                <span style="font-size:12px; color:var(--text-muted); display:block;">JOB #SQ114 (Installation)</span>
                                <strong>Client: Mr. Robert Lee</strong>
                                <div class="status" style="background:var(--warning-bg); color:var(--warning-text);">Awaiting Verification</div>
                            </div>
                            <div class="job-detail">
                                <span style="font-size:12px; color:var(--text-muted); display:block;">Job Location / Type</span>
                                <strong>Iloilo City Home</strong>
                                <span style="font-size:13px; color:var(--text-muted);"><i class="fas fa-box-open"></i> 1-Set Home Package</span>
                            </div>
                            <div class="schedule-actions"> 
                                <button class="btn btn-primary" style="opacity: 0.5; width: 50%;" disabled>
                                    Awaiting Manager Verification
                                </button>
                                <button class="btn btn-outline" style="width: 50%;" onclick="nav('service-requests', getNavButtonBySectionId('service-requests'))">
                                    Check Request Status
                                </button>
                            </div>
                    </div>
                </div>
</div>
            </div>
            <div id="inventory-check" class="section">
                <div class="panel">
                    <div class="panel-header">
                        <span class="panel-title">Real-Time Inventory Stock Check</span>
                    </div>
                    <div class="form-group">
                        <label for="inventorySearch" class="form-label">Search Product / Part Number</label>
                        <input type="text" id="inventorySearch" class="form-control" placeholder="Start typing to search inventory...">
                    </div>
                    <table style="margin-top: 20px;">
                        <thead>
                            <tr>
                                <th>Item Code</th>
                                <th>Product Name</th>
                                <th>Manila (Stock)</th>
                                <th>Cebu (Stock)</th>
                                <th>Bacolod (Stock)</th>
                                <th>Total Stock</th>
                            </tr>
                        </thead>
                        <tbody id="sec-inventory-table-body">
                            <!-- Filled from database via AJAX -->
                        </tbody>
                    </table>
                   
                </div>
            </div>

            <div id="customer-records" class="section">
                <!-- Client List View -->
                <div id="client-list-view" class="panel">
                    <div class="panel-header">
                        <span class="panel-title">Client Management</span>
                        <div class="search-box" style="margin: 0;">
                            <i class="fas fa-search" style="color: #94a3b8;"></i>
                            <input type="text" placeholder="Search clients..." id="client-search" oninput="searchClients(this.value)">
                        </div>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Client Name</th>
                                <th>Contact</th>
                                <th>Address</th>
                                <th>Total Jobs</th>
                                <th>Revenue</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="client-list-body">
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 40px; color: var(--text-muted);">
                                    <i class="fas fa-spinner fa-spin" style="font-size: 24px; margin-bottom: 10px;"></i>
                                    <div>Loading clients...</div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Client Profile View -->
                <div id="client-profile-view" class="panel" style="display: none;">
                    <div class="panel-header">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <button class="btn btn-outline" onclick="backToClientList()" style="padding: 8px 12px;">
                                <i class="fas fa-arrow-left"></i>
                            </button>
                            <span class="panel-title" id="client-profile-title">Client Profile</span>
                        </div>
                        <button class="btn btn-outline" onclick="editClientProfile()"><i class="fas fa-edit"></i> Edit Profile</button>
                    </div>
                    <div class="metrics-grid-4 kpi-row">
                        <div class="metric-card card-blue" style="min-height: 120px; padding: 18px;">
                            <i class="fas fa-list-alt bg-icon" style="font-size: 80px;"></i>
                            <div class="metric-header"><span class="metric-label">Total Jobs</span></div>
                            <h3 class="metric-value" style="font-size: 28px;" id="profile-total-jobs">0</h3>
                        </div>
                        <div class="metric-card card-green" style="min-height: 120px; padding: 18px;">
                            <i class="fas fa-hand-holding-usd bg-icon" style="font-size: 80px;"></i>
                            <div class="metric-header"><span class="metric-label">Total Revenue</span></div>
                            <h3 class="metric-value" style="font-size: 28px;" id="profile-total-revenue">₱0.00</h3>
                        </div>
                        <div class="metric-card card-orange" style="min-height: 120px; padding: 18px;">
                            <i class="fas fa-hourglass-half bg-icon" style="font-size: 80px;"></i>
                            <div class="metric-header"><span class="metric-label">Pending Payments</span></div>
                            <h3 class="metric-value" style="font-size: 28px;" id="profile-pending-payments">₱0.00</h3>
                        </div>
                        <div class="metric-card card-red" style="min-height: 120px; padding: 18px;">
                            <i class="fas fa-exclamation-triangle bg-icon" style="font-size: 80px;"></i>
                            <div class="metric-header"><span class="metric-label">Critical Alerts</span></div>
                            <h3 class="metric-value" style="font-size: 28px;" id="profile-critical-alerts">0</h3>
                        </div>
                    </div>
                    <h3 style="font-size:16px; font-weight:700; color:var(--navy-dark); margin-top: 10px; margin-bottom: 20px;">Recent Service History</h3>
                    <div class="txn-list" id="client-service-history">
                        <div style="text-align: center; padding: 40px; color: var(--text-muted);">
                            <i class="fas fa-history" style="font-size: 48px; margin-bottom: 10px; opacity: 0.3;"></i>
                            <div>No service history found</div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <div class="toast" id="toast">
        <i class="fas fa-info-circle" style="color: var(--gold);"></i>
        <span id="toast-msg"></span>
    </div>

    <div id="logout-modal-overlay" class="modal-overlay">
        <div class="modal-content delete-confirm">
            <div style="padding: 32px; display: flex; flex-direction: column; align-items: center; text-align: center;">
                <i class="fas fa-exclamation-triangle" style="font-size: 48px; color: var(--danger-text); margin-bottom: 20px;"></i>
                <h4 style="font-size: 18px; font-weight: 700; color: var(--navy-dark); margin-bottom: 10px;">Confirm Logout</h4>
                <p style="font-size: 14px; color: var(--text-main); margin-bottom: 30px;">Are you sure you want to end your current session?</p>
                <div style="display: flex; gap: 10px; width: 100%;">
                    <button type="button" class="btn btn-danger" style="flex: 1;" onclick="window.location.href='logout.php'"><i class="fas fa-sign-out-alt"></i> Logout</button>
                    <button type="button" class="btn btn-outline" style="flex: 1;" onclick="closeLogoutModal()">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const INVENTORY_API_URL = 'inventory_api.php';
        // --- JAVASCRIPT FUNCTIONS ---

        // Toast Notification
        function toast(msg) {
            const toastElement = document.getElementById('toast');
            document.getElementById('toast-msg').innerText = msg;
            toastElement.classList.add('show');
            setTimeout(() => {
                toastElement.classList.remove('show');
            }, 3000);
        }


        // Header Notification Dropdown Toggle
        async function toggleNotif() {
            const dropdown = document.getElementById('notif-dropdown');
            const isVisible = dropdown.classList.contains('show');
            
            if (!isVisible) {
                await loadNotifications();
            }
            
            dropdown.classList.toggle('show');
        }
        
        async function loadNotifications() {
            try {
                const response = await fetch('notification_api.php?action=get_notifications&role=secretary', {
                    credentials: 'include'
                });
                const result = await response.json();
                
                if (result.success && result.notifications.length > 0) {
                    renderNotifications(result.notifications);
                    updateNotificationBadge(result.count);
                } else {
                    document.querySelector('.notif-body').innerHTML = '<div style="padding:20px; text-align:center; color:#94a3b8; font-size:13px;">No new notifications</div>';
                    document.getElementById('notif-dot').style.display = 'none';
                }
            } catch (error) {
                console.error('Error loading notifications:', error);
            }
        }
        
        function renderNotifications(notifications) {
            const container = document.querySelector('.notif-body');
            container.innerHTML = '';
            
            notifications.forEach(notif => {
                const item = document.createElement('div');
                item.className = 'notif-item';
                
                const iconColors = {
                    'payment': { bg: '#dcfce7', text: '#166534' },
                    'quotation': { bg: '#e0f2fe', text: '#075985' },
                    'inventory': { bg: '#fee2e2', text: '#991b1b' },
                    'service': { bg: '#fef3c7', text: '#92400e' }
                };
                
                const colors = iconColors[notif.type] || { bg: '#f3f4f6', text: '#374151' };
                
                item.innerHTML = `
                    <div class="notif-icon" style="background:${colors.bg}; color:${colors.text};">
                        <i class="fas ${notif.icon || 'fa-info'}"></i>
                    </div>
                    <div class="notif-content">
                        <p>${notif.message}</p>
                        <span>${notif.time_ago}</span>
                    </div>
                `;
                
                container.appendChild(item);
            });
        }
        
        function updateNotificationBadge(count) {
            const badge = document.getElementById('notif-dot');
            if (count > 0) {
                badge.style.display = 'block';
            } else {
                badge.style.display = 'none';
            }
        }

        // Clear Notifications
        function clearNotif() {
            toast('Notifications cleared.');
            document.getElementById('notif-dot').style.display = 'none';
            document.getElementById('notif-dropdown').classList.remove('show');
        }
        
        async function loadNotificationCount() {
            try {
                const response = await fetch('notification_api.php?action=get_unread_count&role=secretary', {
                    credentials: 'include'
                });
                const result = await response.json();
                
                if (result.success) {
                    updateNotificationBadge(result.count);
                }
            } catch (error) {
                console.error('Error loading notification count:', error);
            }
        }
        
        // Real-time updates - refresh data periodically
        setInterval(loadNotificationCount, 15000); // Every 15 seconds
        
        // Auto-refresh client list when active
        setInterval(() => {
            const clientListView = document.getElementById('client-list-view');
            if (clientListView && clientListView.style.display !== 'none') {
                loadClientList();
            }
        }, 30000); // Every 30 seconds
        
        // Auto-refresh current client profile when viewing
        setInterval(() => {
            const clientProfileView = document.getElementById('client-profile-view');
            if (clientProfileView && clientProfileView.style.display !== 'none' && currentClientId) {
                loadClientProfile(currentClientId);
            }
        }, 45000); // Every 45 seconds
        
        async function loadLowStockCount() {
            try {
                const response = await fetch('inventory_api.php?branch=all', {
                    credentials: 'include'
                });
                const result = await response.json();
                
                if (result.success && result.items) {
                    const lowStock = result.items.filter(item => item.Quantity <= 5);
                    const countElem = document.getElementById('low-stock-count');
                    if (countElem) {
                        countElem.textContent = lowStock.length + ' Alert' + (lowStock.length !== 1 ? 's' : '');
                    }
                }
            } catch (error) {
                console.error('Error loading low stock count:', error);
            }
        }

        // Sidebar Navigation
        function getNavButtonBySectionId(sectionId) {
            return document.querySelector(`.nav-btn[onclick*="'${sectionId}'"]`);
        }

        function nav(sectionId, element) {
            // 1. Hide all sections
            document.querySelectorAll('.section').forEach(section => {
                section.classList.remove('active');
            });

            // 2. Show the target section
            document.getElementById(sectionId).classList.add('active');

            // 3. Update active state for nav buttons
            document.querySelectorAll('.nav-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            element.classList.add('active');

            // 4. Update Header Title
            const titles = {
                'sec-dashboard': 'Operations Dashboard',
                'service-requests': 'Service & Sales Triage',
                'sales-quotes': 'Sales Quotation Builder',
                'job-scheduling': 'Job Assignment & Scheduling', // NEW TITLE
                'inventory-check': 'Stock Check & Logistics',
                'customer-records': 'Client Management'
            };
            document.getElementById('page-title').innerText = titles[sectionId] || 'Dashboard';

            // Load live inventory when opening Inventory Check
            if (sectionId === 'inventory-check') {
                loadSecretaryInventory();
            }
            
            // Load sales inquiries when opening Service Requests
            if (sectionId === 'service-requests') {
                loadSalesInquiries();
            }
            
            // Load client list when opening Customer Records
            if (sectionId === 'customer-records') {
                loadClientList();
            }
        }

        // Tab Switching for Service Requests/Reports
        function switchReportTab(element, tabId) {
            // 1. Deactivate all tabs in the group
            element.parentElement.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });

            // 2. Activate the clicked tab
            element.classList.add('active');

            // 3. Hide all tab contents
            document.querySelectorAll('#service-requests .tab-content').forEach(content => {
                content.style.display = 'none';
                content.classList.remove('active'); // Keep opacity animation
            });
            
            // 4. Show the corresponding tab content
            const targetContent = document.getElementById(tabId);
            if (targetContent) {
                targetContent.style.display = 'block';
                // Trigger animation by adding the class after a tiny delay
                setTimeout(() => { targetContent.classList.add('active'); }, 50);
                
                // 5. Lazy load maintenance requests when tab is first clicked
                if (tabId === 'maintenance-requests' && !targetContent.dataset.loaded) {
                    loadMaintenanceRequests();
                    targetContent.dataset.loaded = 'true';
                }
            }
        }
        
        // Tab switching for Quote Forms
        function switchQuoteTab(element, formId) {
            // Deactivate all tabs
            element.parentElement.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            element.classList.add('active');
            
            // Hide all quote forms
            document.querySelectorAll('.quote-form-content').forEach(form => {
                form.style.display = 'none';
            });
            
            // Show selected form
            const targetForm = document.getElementById(formId);
            if (targetForm) {
                targetForm.style.display = 'block';
            }
        }

        // Sales Quote Logic
        function calculateSalesQuote() {
            const packageSelect = document.getElementById('packageType');
            const packageValue = parseFloat(packageSelect.value) || 0;
            const installationFee = parseFloat(document.getElementById('installation').value) || 0;
            
            // Calculate total without discount
            const total = packageValue + installationFee;

            document.getElementById('val-package').innerText = `₱${packageValue.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
            document.getElementById('val-installation').innerText = `₱${installationFee.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
            document.getElementById('val-total').innerText = `₱${total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
        }













        function printQuote() {
            // Get form data
            const clientName = document.getElementById('clientName')?.value || 'Not specified';
            const clientContact = document.getElementById('clientContact')?.value || 'Not provided';
            const clientEmail = document.getElementById('clientEmail')?.value || 'Not provided';
            const packageSelect = document.getElementById('packageType');
            const installationFee = parseFloat(document.getElementById('installation')?.value || 0);
            const notes = document.getElementById('notes')?.value || 'No special notes';
            
            if (!packageSelect) {
                toast('Error: Package selection not found');
                return;
            }
            
            const packageText = packageSelect.options[packageSelect.selectedIndex].text;
            const packageValue = parseFloat(packageSelect.value || 0);
            const total = packageValue + installationFee;
            const quoteNumber = 'QT' + Date.now().toString().slice(-6);
            const currentDate = new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
            
            // Create quote content
            const quoteContent = `
                <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 2px solid #2c3e50;">
                    <div style="text-align: center; margin-bottom: 25px; border-bottom: 2px solid #2c3e50; padding-bottom: 20px;">
                        <h1 style="color: #2c3e50; margin-bottom: 8px;">ATMICX Laundry Machine Trading</h1>
                        <p style="color: #666;">Professional Laundry Equipment Solutions<br>Email: info@atmicx.com | Phone: (034) 123-4567</p>
                        <h2 style="color: #2c3e50; margin: 15px 0;">SALES QUOTATION</h2>
                        <p style="color: #666;">${quoteNumber}</p>
                    </div>
                    
                    <div style="margin: 20px 0; border: 1px solid #ddd; padding: 15px;">
                        <h3 style="color: #2c3e50; margin-bottom: 10px;">Quote Information</h3>
                        <p>Quote Date: ${currentDate}</p>
                        <p>Valid Until: ${new Date(Date.now() + 30*24*60*60*1000).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</p>
                    </div>
                    
                    <div style="margin: 20px 0; border: 1px solid #ddd; padding: 15px;">
                        <h3 style="color: #2c3e50; margin-bottom: 10px;">Client Information</h3>
                        <p>Name: ${clientName}</p>
                        <p>Contact: ${clientContact}</p>
                        <p>Email: ${clientEmail}</p>
                    </div>
                    
                    <div style="margin: 20px 0; border: 1px solid #ddd; padding: 15px;">
                        <h3 style="color: #2c3e50; margin-bottom: 10px;">Package Details</h3>
                        <p>Selected Package: ${packageText}</p>
                    </div>
                    
                    <div style="margin: 20px 0; border: 2px solid #2c3e50; padding: 15px; background: #f8f9fa;">
                        <h3 style="color: #2c3e50; margin-bottom: 10px;">Investment Breakdown</h3>
                        <div style="display: flex; justify-content: space-between; margin: 8px 0;">
                            <span>Package Cost:</span>
                            <span>₱${packageValue.toLocaleString('en-US', { minimumFractionDigits: 2 })}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin: 8px 0;">
                            <span>Installation Fee:</span>
                            <span>₱${installationFee.toLocaleString('en-US', { minimumFractionDigits: 2 })}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-top: 15px; padding-top: 10px; border-top: 2px solid #2c3e50; font-weight: bold; font-size: 16px; color: #2c3e50;">
                            <span>TOTAL INVESTMENT:</span>
                            <span>₱${total.toLocaleString('en-US', { minimumFractionDigits: 2 })}</span>
                        </div>
                    </div>
                    
                    <div style="margin: 20px 0; background: #fffbf0; border: 1px solid #ffcc00; padding: 15px;">
                        <h3 style="color: #2c3e50; margin-bottom: 10px;">Special Notes & Requirements</h3>
                        <p>${notes}</p>
                    </div>
                    
                    <div style="text-align: center; margin: 15px 0; padding: 10px; background: #e8f4fd; border: 1px solid #0369a1; font-style: italic; color: #0369a1;">
                        This quotation is valid for 30 days from the date of issue.
                    </div>
                    
                    <div style="text-align: center; margin-top: 25px; padding-top: 20px; border-top: 2px solid #2c3e50;">
                        <p style="font-size: 12px; color: #666; line-height: 1.6;">
                            Thank you for considering ATMICX for your laundry equipment needs!<br>
                            <strong>Contact us to proceed with your investment.</strong>
                        </p>
                        <p style="font-size: 12px; color: #666; margin-top: 15px;">
                            Generated on: ${new Date().toLocaleString('en-US')}
                        </p>
                    </div>
                </div>
            `;
            
            // Create hidden container for printing
            let printContainer = document.getElementById('quote-print-container');
            if (!printContainer) {
                printContainer = document.createElement('div');
                printContainer.id = 'quote-print-container';
                printContainer.style.display = 'none';
                document.body.appendChild(printContainer);
            }
            
            printContainer.innerHTML = quoteContent;
            
            // Add print styles only once
            if (!document.getElementById('quote-print-styles')) {
                const printStyles = document.createElement('style');
                printStyles.id = 'quote-print-styles';
                printStyles.innerHTML = `
                    @media print {
                        body * { visibility: hidden; }
                        #quote-print-container,
                        #quote-print-container * { visibility: visible; }
                        #quote-print-container {
                            position: absolute;
                            left: 0;
                            top: 0;
                            width: 100%;
                            display: block !important;
                        }
                    }
                `;
                document.head.appendChild(printStyles);
            }
            
            // Trigger print
            window.print();
            toast('✅ Print dialog opened!');
        }
        
        // Prefill Sales Quote from a Triage (UPDATED)
        function prefillSalesQuote() {
            // This is a simple mock function to pre-fill the form based on a triage card
            nav('sales-quotes', getNavButtonBySectionId('sales-quotes'));
            document.getElementById('clientName').value = 'New Investor John';
            document.getElementById('clientContact').value = '+63 917 999 0000'; // Mock data for prefill
            document.getElementById('clientEmail').value = 'john@example.com'; // Mock data for prefill
            document.getElementById('installation').value = '15000';
            calculateSalesQuote();
            toast('Sales Quote form pre-filled for New Investor John.');
        }

        // NEW: Function to handle file name update
        function updateFileName(input) {
            const fileNameDisplay = document.getElementById('file-name');
            if (input.files.length > 0) {
                fileNameDisplay.textContent = input.files[0].name;
                fileNameDisplay.style.color = 'var(--navy-dark)';
            } else {
                fileNameDisplay.textContent = 'No file chosen';
                fileNameDisplay.style.color = 'var(--text-muted)';
            }
        }

        // NEW: Function to handle the team assignment form submission
        async function handleAssignment(event, quotationId) {
            event.preventDefault();
            
            const form = event.target;
            const selectedTeam = form.querySelector('select[name="technician_team"]').value;
            const techName = form.querySelector('input[name="tech_name"]').value;
            const techContact = form.querySelector('input[name="tech_contact"]').value;
            const scheduleDate = form.querySelector('input[name="schedule_date"]').value;
            const scheduleTime = form.querySelector('input[name="schedule_time"]').value;
            
            if (!selectedTeam || !techName || !scheduleDate || !scheduleTime) {
                toast('⚠️ Please fill in all required fields');
                return false;
            }
            
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Assigning...';
            
            try {
                const formData = new FormData();
                formData.append('action', 'assign_job');
                formData.append('quotation_id', quotationId);
                formData.append('technician_team', selectedTeam);
                formData.append('technician_name', techName);
                formData.append('technician_contact', techContact);
                formData.append('schedule_date', scheduleDate);
                formData.append('schedule_time', scheduleTime);
                
                const response = await fetch('secretary_quotations_api.php', {
                    method: 'POST',
                    credentials: 'include',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    toast(`✅ Job #QT-${String(quotationId).padStart(3, '0')} assigned to ${techName} (${selectedTeam})`);
                    
                    // Remove the card from the list
                    const card = document.getElementById(`job-${quotationId}`);
                    if (card) {
                        card.style.opacity = '0.5';
                        card.querySelector('.status').innerText = 'Scheduled';
                        card.querySelector('.status').style.background = 'var(--info-bg)';
                        card.querySelector('.status').style.color = 'var(--info-text)';
                        
                        setTimeout(() => {
                            card.remove();
                            loadJobsAwaitingAssignment(); // Reload the list
                        }, 1500);
                    }
                } else {
                    toast('❌ ' + (result.message || 'Failed to assign job'));
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
            } catch (error) {
                console.error('Error assigning job:', error);
                toast('❌ Error assigning job');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
            
            return false;
        }
        
        // Load jobs awaiting technician assignment
        async function loadJobsAwaitingAssignment() {
            try {
                const response = await fetch('secretary_quotations_api.php?action=get_jobs_for_assignment', {
                    credentials: 'include'
                });
                const result = await response.json();
                
                const container = document.getElementById('jobs-awaiting-assignment');
                const countBadge = document.getElementById('jobs-ready-count');
                
                if (!result.success || !result.jobs || result.jobs.length === 0) {
                    container.innerHTML = '<div style="text-align: center; padding: 40px; color: var(--text-muted);"><i class="fas fa-check-circle" style="font-size: 48px; opacity: 0.3; display: block; margin-bottom: 15px;"></i><p>No jobs awaiting assignment</p></div>';
                    countBadge.textContent = '0 Jobs';
                    return;
                }
                
                countBadge.textContent = `${result.jobs.length} Job${result.jobs.length !== 1 ? 's' : ''} Ready`;
                
                container.innerHTML = result.jobs.map(job => {
                    const jobRef = `QT-${String(job.Quotation_ID).padStart(3, '0')}`;
                    return `
                        <div class="schedule-card" id="job-${job.Quotation_ID}">
                            <div class="job-detail">
                                <span style="font-size:12px; color:var(--text-muted); display:block;">JOB #${jobRef} (Installation)</span>
                                <strong>Client: ${job.Client_Name || 'Unknown'}</strong>
                                <div class="status" style="background:var(--success-bg); color:var(--success-text);">Payment Verified</div>
                            </div>
                            <div class="job-detail">
                                <span style="font-size:12px; color:var(--text-muted); display:block;">Job Location / Type</span>
                                <strong>${job.Delivery_Address || job.Address || 'Location TBD'}</strong>
                                <span style="font-size:13px; color:var(--text-muted);"><i class="fas fa-box-open"></i> ${job.Package || 'Package TBD'}</span>
                            </div>
                            <form class="assignment-form" onsubmit="return handleAssignment(event, ${job.Quotation_ID})" style="padding-top: 10px; border-top: 1px dashed #e2e8f0;">
                                <div class="form-group">
                                    <label class="form-label">Assigned Technician Name</label>
                                    <input type="text" name="tech_name" class="form-control" placeholder="E.g., Rico Diaz" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Technician Contact (Email/Phone)</label>
                                    <input type="text" name="tech_contact" class="form-control" placeholder="E.g., (0917) 123-4567" required>
                                </div>
                                <div style="display: flex; gap: 10px;">
                                    <div class="form-group" style="flex: 1;">
                                        <label class="form-label">Schedule Date</label>
                                        <input type="date" name="schedule_date" class="form-control" required>
                                    </div>
                                    <div class="form-group" style="flex: 1;">
                                        <label class="form-label">Schedule Time</label>
                                        <input type="time" name="schedule_time" class="form-control" required>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Assign Technician Team</label>
                                    <select name="technician_team" class="form-control" style="font-size:14px; padding: 12px;" required>
                                        <option value="">Select Technician Team</option>
                                        <option value="Team Alpha">Team Alpha (Manila)</option>
                                        <option value="Team Beta">Team Beta (Manila)</option>
                                        <option value="Team Charlie">Team Charlie (Cebu)</option>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-gold" style="margin-top: 10px; padding: 12px; font-size: 14px; width: 100%;">
                                    <i class="fas fa-calendar-check"></i> Assign & Schedule Job
                                </button>
                            </form>
                        </div>
                    `;
                }).join('');
                
            } catch (error) {
                console.error('Error loading jobs:', error);
                document.getElementById('jobs-awaiting-assignment').innerHTML = '<div style="text-align: center; padding: 40px; color: var(--danger-text);"><i class="fas fa-exclamation-triangle" style="font-size: 48px; opacity: 0.3; display: block; margin-bottom: 15px;"></i><p>Error loading jobs</p></div>';
            }
        }
        
        // Calendar functionality
        let currentCalendarDate = new Date();
        let scheduledJobs = {}; // Cache for scheduled jobs
        
        // Generate calendar for current month
        function generateCalendar(year, month) {
            const firstDay = new Date(year, month, 1);
            const lastDay = new Date(year, month + 1, 0);
            const startDate = new Date(firstDay);
            startDate.setDate(startDate.getDate() - firstDay.getDay()); // Start from Sunday
            
            const calendar = document.getElementById('dynamic-calendar');
            const title = document.getElementById('calendar-title');
            
            if (!calendar || !title) return; // Safety check
            
            // Update title
            const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                               'July', 'August', 'September', 'October', 'November', 'December'];
            title.textContent = `Technician Team Calendar (${monthNames[month]} ${year})`;
            
            // Clear existing days (keep headers)
            const existingDays = calendar.querySelectorAll('.calendar-day');
            existingDays.forEach(day => day.remove());
            
            // Generate 42 days (6 weeks)
            for (let i = 0; i < 42; i++) {
                const date = new Date(startDate);
                date.setDate(startDate.getDate() + i);
                
                const dayElement = document.createElement('div');
                dayElement.className = 'calendar-day';
                
                const isCurrentMonth = date.getMonth() === month;
                const isToday = date.toDateString() === new Date().toDateString();
                
                if (!isCurrentMonth) {
                    dayElement.classList.add('inactive');
                }
                
                if (isToday) {
                    dayElement.classList.add('today');
                    dayElement.innerHTML = `${date.getDate()} (Today)`;
                } else {
                    dayElement.textContent = date.getDate();
                }
                
                // Add scheduled jobs for this date
                const dateKey = date.toISOString().split('T')[0];
                if (scheduledJobs[dateKey]) {
                    scheduledJobs[dateKey].forEach(job => {
                        const badge = document.createElement('span');
                        badge.className = 'event-badge';
                        badge.style.background = 'var(--success-bg)';
                        badge.style.color = 'var(--success-text)';
                        badge.style.marginTop = '4px';
                        badge.style.display = 'block';
                        badge.style.fontSize = '11px';
                        badge.style.cursor = 'pointer';
                        badge.textContent = `${job.team}: QT-${String(job.quotation_id).padStart(3, '0')}`;
                        badge.onclick = () => toast(`Job QT-${String(job.quotation_id).padStart(3, '0')}: ${job.team} - ${job.client}`);
                        dayElement.appendChild(badge);
                    });
                }
                
                calendar.appendChild(dayElement);
            }
        }
        
        // Navigate months
        function navigateMonth(direction) {
            currentCalendarDate.setMonth(currentCalendarDate.getMonth() + direction);
            generateCalendar(currentCalendarDate.getFullYear(), currentCalendarDate.getMonth());
            loadScheduledJobs(); // Reload jobs for new month
        }
        
        // Load scheduled jobs from database
        async function loadScheduledJobs() {
            try {
                const year = currentCalendarDate.getFullYear();
                const month = currentCalendarDate.getMonth() + 1;
                
                const response = await fetch(`secretary_quotations_api.php?action=get_scheduled_jobs&year=${year}&month=${month}`, {
                    credentials: 'include'
                });
                const result = await response.json();
                
                if (result.success && result.jobs) {
                    scheduledJobs = {};
                    
                    // Group jobs by date
                    result.jobs.forEach(job => {
                        const date = job.scheduled_date.split(' ')[0]; // Get date part
                        if (!scheduledJobs[date]) {
                            scheduledJobs[date] = [];
                        }
                        scheduledJobs[date].push({
                            quotation_id: job.Quotation_ID,
                            team: job.Technician_Team || 'Team Unknown',
                            client: job.Client_Name || 'Unknown Client'
                        });
                    });
                    
                    // Regenerate calendar with jobs
                    generateCalendar(currentCalendarDate.getFullYear(), currentCalendarDate.getMonth());
                }
            } catch (error) {
                console.error('Error loading scheduled jobs:', error);
            }
        }

        // Inventory loading for Secretary (read-only)
        async function loadSecretaryInventory() {
            const tbody = document.getElementById('sec-inventory-table-body');
            if (!tbody) return;

            try {
                const res = await fetch(INVENTORY_API_URL);
                const data = await res.json();

                if (!data.success) {
                    console.error('Failed to load inventory:', data.message);
                    toast('Failed to load inventory');
                    return;
                }

                // Group by Item_Name and branch
                const grouped = {};
                (data.items || []).forEach(item => {
                    const name = item.Item_Name || 'Unknown';
                    const branch = item.Branch || 'Manila HQ';
                    const qty = parseInt(item.Quantity ?? 0, 10);
                    const id = item.Item_ID ?? null;

                    if (!grouped[name]) {
                        grouped[name] = {
                            // Use Item_ID as the visible item code
                            code: id !== null ? String(id) : '',
                            name,
                            Manila: 0,
                            Cebu: 0,
                            Bacolod: 0
                        };
                    }

                    if (branch.toLowerCase().includes('manila')) grouped[name].Manila += qty;
                    else if (branch.toLowerCase().includes('cebu')) grouped[name].Cebu += qty;
                    else if (branch.toLowerCase().includes('bacolod')) grouped[name].Bacolod += qty;
                });

                tbody.innerHTML = '';

                Object.values(grouped).forEach(row => {
                    const total = row.Manila + row.Cebu + row.Bacolod;

                    const manilaText = `${row.Manila} Units`;
                    const cebuBadge = row.Cebu <= 0
                        ? `<span class="status-badge status-err" style="margin-left:8px;">CRIT</span>`
                        : '';
                    const bacolodBadge = row.Bacolod > 0 && row.Bacolod <= 5
                        ? `<span class="status-badge status-warn" style="margin-left:8px;">LOW</span>`
                        : '';

                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>${row.code || '-'}</td>
                        <td>${row.name}</td>
                        <td>${manilaText}</td>
                        <td>${row.Cebu} Units ${cebuBadge}</td>
                        <td>${row.Bacolod} Units ${bacolodBadge}</td>
                        <td>${total} Units</td>
                    `;
                    tbody.appendChild(tr);
                });

                if (!Object.keys(grouped).length) {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="6" style="font-size:13px; color:var(--text-muted); text-align:center;">
                                No inventory records found.
                            </td>
                        </tr>
                    `;
                }
            } catch (err) {
                console.error('Error loading inventory:', err);
                toast('Error loading inventory');
            }
        }

        function setupInventorySearch() {
            const input = document.getElementById('inventorySearch');
            const tbody = document.getElementById('sec-inventory-table-body');
            if (!input || !tbody) return;

            input.addEventListener('keyup', () => {
                const term = input.value.toLowerCase();
                Array.from(tbody.querySelectorAll('tr')).forEach(row => {
                    const text = row.innerText.toLowerCase();
                    row.style.display = text.includes(term) ? '' : 'none';
                });
            });
        }

        // Load maintenance requests from API
        function loadMaintenanceRequests() {
            const container = document.getElementById('maintenance-requests-list');
            
            fetch('service_request_api.php?action=list')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.requests) {
                    if (data.requests.length === 0) {
                        container.innerHTML = '<div style="text-align: center; color: var(--text-muted); padding: 20px;"><i class="fas fa-inbox"></i> No maintenance requests found</div>';
                        return;
                    }
                    
                    const requestsHTML = data.requests.map(request => {
                        const status = request.status || 'pending';
                        const priority = request.priority || 'medium';
                        
                        // Determine icon and color based on status
                        let iconClass = 'fas fa-wrench';
                        let statusColor = 'var(--primary-color)';
                        let statusBg = 'var(--primary-bg)';
                        
                        if (priority === 'high' || priority === 'urgent') {
                            statusColor = 'var(--danger-text)';
                            statusBg = 'var(--danger-bg)';
                            iconClass = 'fas fa-exclamation-triangle';
                        } else if (status === 'scheduled') {
                            statusColor = 'var(--success-text)';
                            statusBg = 'var(--success-bg)';
                            iconClass = 'fas fa-calendar-check';
                        }
                        
                        return `
                        <div class="txn-card" style="grid-template-columns: 80px 1.8fr 2.2fr 1.2fr;">
                            <div class="txn-icon-col" style="color:${statusColor};"><i class="${iconClass}"></i></div>
                            <div class="txn-client-col">
                                <span class="txn-ref" style="background:${statusBg}; color:${statusColor};">${priority.toUpperCase()} #MR${String(request.id).padStart(3, '0')}</span>
                                <div class="txn-client-name">${request.client_name || 'N/A'}</div>
                                <div class="txn-client-loc"><i class="fas fa-map-marker-alt"></i> ${request.location || 'Not specified'}</div>
                            </div>
                            <div class="txn-finance-col" style="border-right: 1px dashed #e2e8f0;">
                                <div class="finance-row"><span>Problem:</span> <strong>${request.problem_description || 'N/A'}</strong></div>
                                <div class="finance-row"><span>Priority:</span> ${priority.charAt(0).toUpperCase() + priority.slice(1)}</div>
                                ${request.scheduled_date ? `<div class="finance-row"><span>Scheduled:</span> ${new Date(request.scheduled_date).toLocaleDateString()}</div>` : ''}
                            </div>
                            <div class="txn-action-col">
                                ${(() => {
                                    if (status === 'scheduled') {
                                        return `<span style="color: var(--success-text); font-weight: bold;"><i class="fas fa-check"></i> Scheduled</span>`;
                                    } else if (status === 'pending_secretary_review') {
                                        return `
                                            <button class="btn btn-warning secretary-review-btn" 
                                                data-request-id="${request.id}" 
                                                data-client-name="${request.client_name || ''}" 
                                                data-problem="${request.problem_description || ''}">
                                                <i class="fas fa-eye"></i> Review
                                            </button>`;
                                    } else if (status === 'pending_manager_approval') {
                                        return `<span style="color: var(--warning-text); font-weight: bold;"><i class="fas fa-clock"></i> Awaiting Manager</span>`;
                                    } else if (status === 'approved') {
                                        return `
                                            <button class="btn btn-primary schedule-team-btn" 
                                                data-request-id="${request.id}" 
                                                data-client-name="${request.client_name || ''}" 
                                                data-problem="${request.problem_description || ''}" 
                                                data-location="${request.location || ''}">
                                                <i class="fas fa-calendar-plus"></i> Schedule Team
                                            </button>`;
                                    } else if (status === 'rejected_by_secretary' || status === 'rejected_by_manager') {
                                        return `<span style="color: var(--danger-text); font-weight: bold;"><i class="fas fa-times"></i> Rejected</span>`;
                                    } else {
                                        return `
                                            <button class="btn btn-primary schedule-team-btn" 
                                                data-request-id="${request.id}" 
                                                data-client-name="${request.client_name || ''}" 
                                                data-problem="${request.problem_description || ''}" 
                                                data-location="${request.location || ''}">
                                                <i class="fas fa-calendar-plus"></i> Schedule Team
                                            </button>`;
                                    }
                                })()}
                            </div>
                        </div>
                        `;
                    }).join('');
                    
                    container.innerHTML = requestsHTML;
                    
                    // Add event listeners to schedule team buttons
                    container.querySelectorAll('.schedule-team-btn').forEach(button => {
                        button.addEventListener('click', function() {
                            const requestId = this.dataset.requestId;
                            const clientName = this.dataset.clientName;
                            const problem = this.dataset.problem;
                            const location = this.dataset.location;
                            openTeamScheduler(requestId, clientName, problem, location);
                        });
                    });
                    
                    // Add event listeners to review buttons
                    container.querySelectorAll('.secretary-review-btn').forEach(button => {
                        button.addEventListener('click', function() {
                            const requestId = this.dataset.requestId;
                            const clientName = this.dataset.clientName;
                            const problem = this.dataset.problem;
                            openReviewModal(requestId, clientName, problem);
                        });
                    });
                } else {
                    container.innerHTML = '<div style="text-align: center; color: var(--text-muted); padding: 20px;"><i class="fas fa-exclamation-circle"></i> Error loading requests</div>';
                }
            })
            .catch(error => {
                console.error('Error loading maintenance requests:', error);
                container.innerHTML = '<div style="text-align: center; color: var(--danger-text); padding: 20px;"><i class="fas fa-exclamation-triangle"></i> Failed to load requests</div>';
            });
        }

        // INITIALIZATION
        document.addEventListener('DOMContentLoaded', (event) => {
            // Debug: Check if elements exist
            console.log('Initializing Secretary Dashboard...');
            const activeNavBtn = document.querySelector('.nav-btn.active');
            const dashboardSection = document.getElementById('sec-dashboard');
            console.log('Active Nav Button:', activeNavBtn);
            console.log('Dashboard Section:', dashboardSection);
            
            // 1. Set initial view to dashboard and activate the nav button
            if (activeNavBtn && dashboardSection) {
                nav('sec-dashboard', activeNavBtn);
                console.log('Dashboard initialized successfully');
                
                // Additional debugging
                const metricsGrid = dashboardSection.querySelector('.metrics-grid-4');
                const metricCards = dashboardSection.querySelectorAll('.metric-card');
                console.log('Metrics Grid:', metricsGrid);
                console.log('Metric Cards Count:', metricCards.length);
                console.log('Dashboard Section Styles:', window.getComputedStyle(dashboardSection));
                
                // Check if dashboard section is visible
                const rect = dashboardSection.getBoundingClientRect();
                console.log('Dashboard Section Position:', rect);
                console.log('Dashboard Section Classes:', dashboardSection.className);
            } else {
                console.error('Missing dashboard elements!');
                // Fallback: Ensure dashboard section is visible
                if (dashboardSection) {
                    dashboardSection.classList.add('active');
                }
            }
            
            // 2. Calculate initial sales quote summary
            calculateSalesQuote();
            
            // 3. Load notification count
            loadNotificationCount();
            
            // 4. Load low stock count
            loadLowStockCount();

            // 5. Wire up Triage Now buttons in Dashboard to navigate to Service Requests
            const triageButtons = document.querySelectorAll('.triage-btn');
            const serviceRequestButton = getNavButtonBySectionId('service-requests');
            if (serviceRequestButton) {
                triageButtons.forEach(btn => {
                    btn.onclick = (e) => {
                        e.preventDefault(); // Prevent default button action if any
                        nav('service-requests', serviceRequestButton);
                        
                        // Switch to the correct tab in Service Requests upon triage click
                        const targetTab = btn.parentElement.querySelector('h4').innerText.toLowerCase().includes('repair') || btn.parentElement.querySelector('span.metric-label').innerText.toLowerCase().includes('maintenance') ? 'maintenance-requests' : 'sales-inquiries';
                        
                        // Find the corresponding tab element and click it
                        const tabElement = document.querySelector(`#service-requests .tab[onclick*="'${targetTab}'"]`);
                        if (tabElement) {
                             switchReportTab(tabElement, targetTab);
                        }
                    };
                });
            }

            // 6. Prepare inventory search and initial data (optional initial load)
            setupInventorySearch();
            
            // 7. Load sales inquiries for initial display (maintenance requests loads lazily when tab is clicked)
            loadSalesInquiries();
            
            // 8. Load jobs awaiting assignment
            loadJobsAwaitingAssignment();
            
            // 9. Initialize calendar
            generateCalendar(currentCalendarDate.getFullYear(), currentCalendarDate.getMonth());
            loadScheduledJobs();
        });
        function openLogoutModal() {
            document.getElementById('logout-modal-overlay').classList.add('show');
        }

        function closeLogoutModal() {
            document.getElementById('logout-modal-overlay').classList.remove('show');
        }

        // Sales Inquiries Functions
        async function loadSalesInquiries() {
            try {
                const response = await fetch('secretary_quotations_api.php?action=get_pending_requests');
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const result = await response.json();
                
                if (result.success) {
                    displaySalesInquiries(result.requests || []);
                } else {
                    document.getElementById('sales-inquiries-list').innerHTML = 
                        '<div style="text-align: center; color: var(--text-muted); padding: 40px;">Error: ' + (result.message || 'Unknown error') + '</div>';
                }
            } catch (error) {
                console.error('Error loading sales inquiries:', error);
                document.getElementById('sales-inquiries-list').innerHTML = 
                    '<div style="text-align: center; color: var(--text-muted); padding: 40px;">Error loading sales inquiries: ' + error.message + '</div>';
            }
        }

        function displaySalesInquiries(inquiries) {
            const container = document.getElementById('sales-inquiries-list');
            if (!container) {
                return;
            }
            
            if (!inquiries || inquiries.length === 0) {
                container.innerHTML = '<div style="text-align: center; color: var(--text-muted); padding: 40px;">' +
                    '<i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 15px; opacity: 0.3;"></i>' +
                    '<p>No pending sales inquiries.</p>' +
                    '</div>';
                return;
            }
            
            let inquiriesHtml = '';
            
            inquiries.forEach((inquiry, index) => {
                const leadNumber = 'QR-' + inquiry.Quotation_ID.toString().padStart(3, '0');
                const clientLocation = inquiry.Address ? inquiry.Address.split(',')[0] : 'Unknown Location';
                const clientInitial = (inquiry.Client_Name || 'U').charAt(0).toUpperCase();
                
                inquiriesHtml += '<div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">' +
                    '<div style="display: flex; align-items: center; gap: 15px; flex: 1;">' +
                        '<div style="width: 48px; height: 48px; background: linear-gradient(135deg, #3b82f6, #1d4ed8); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; position: relative;">' +
                            clientInitial +
                            '<div style="position: absolute; bottom: -2px; right: -2px; width: 16px; height: 16px; background: #f59e0b; border: 2px solid white; border-radius: 50%;"></div>' +
                        '</div>' +
                        '<div>' +
                            '<div style="color: #0891b2; font-weight: 600; font-size: 13px; text-transform: uppercase; margin-bottom: 2px;">' +
                                'QUOTATION REQUEST #' + leadNumber +
                            '</div>' +
                            '<div style="color: #1f2937; font-weight: 600; font-size: 16px; margin-bottom: 2px;">' +
                                (inquiry.Client_Name || 'Unknown Client') +
                            '</div>' +
                            '<div style="color: #6b7280; font-size: 14px; display: flex; align-items: center; gap: 4px;">' +
                                '<i class="fas fa-map-marker-alt" style="font-size: 12px;"></i>' +
                                clientLocation +
                            '</div>' +
                        '</div>' +
                    '</div>' +
                    '<div style="flex: 1; padding: 0 20px;">' +
                        '<div style="display: grid; grid-template-columns: auto 1fr; gap: 8px 16px; align-items: center;">' +
                            '<div style="color: #6b7280; font-size: 14px;">Request:</div>' +
                            '<div style="color: #1f2937; font-weight: 600; font-size: 14px;">' + (inquiry.Package || 'Package Request') + '</div>' +
                            '<div style="color: #6b7280; font-size: 14px;">Action:</div>' +
                            '<div style="color: #0891b2; font-weight: 600; font-size: 14px;">Generate Package Quote</div>' +
                        '</div>' +
                    '</div>' +
                    '<div style="display: flex; gap: 10px;">' +
                        '<button class="btn create-quote-btn" data-quotation-id="' + inquiry.Quotation_ID + '" data-client-id="' + inquiry.Client_ID + '" data-client-name="' + (inquiry.Client_Name || '') + '" data-contact="' + (inquiry.Contact_Num || '') + '" data-package="' + (inquiry.Package || '') + '" data-amount="' + (inquiry.Amount || '') + '" data-handling-fee="' + (inquiry.Handling_Fee || 0) + '"' +
                                ' style="background: linear-gradient(135deg, #f59e0b, #d97706); color: white; padding: 10px 20px; border: none; border-radius: 8px; font-weight: 600; font-size: 13px; cursor: pointer; box-shadow: 0 2px 4px rgba(245, 158, 11, 0.3); transition: all 0.2s;">' +
                            '<i class="fas fa-file-invoice"></i> ' +
                            'Create Quotation' +
                        '</button>' +
                        '<button class="btn contact-client-btn" data-contact="' + (inquiry.Contact_Num || '') + '" data-client-name="' + (inquiry.Client_Name || '') + '"' +
                                ' style="background: transparent; color: #374151; padding: 8px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-weight: 500; font-size: 13px; cursor: pointer; transition: all 0.2s;">' +
                            '<i class="fas fa-phone"></i> Call Client' +
                        '</button>' +
                    '</div>' +
                '</div>';
            });
            
            container.innerHTML = inquiriesHtml;
            
            // Add event listeners for the buttons
            document.querySelectorAll('.create-quote-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const quotationId = this.dataset.quotationId;
                    const clientId = this.dataset.clientId;
                    const clientName = this.dataset.clientName;
                    const contact = this.dataset.contact;
                    const packageName = this.dataset.package;
                    const amount = this.dataset.amount;
                    const handlingFee = this.dataset.handlingFee;
                    navigateToCreateQuotation(quotationId, clientId, clientName, contact, packageName, amount, handlingFee);
                });
            });
            
            document.querySelectorAll('.contact-client-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const contact = this.dataset.contact;
                    const clientName = this.dataset.clientName;
                    contactClient(contact, clientName);
                });
            });
            
            // Update the badge count in the navigation
            if (inquiries.length > 0) {
                updateServiceRequestsBadge(inquiries.length);
            }
        }

        // Global variable to store quotation_id when editing from sales inquiry
        let CURRENT_QUOTATION_ID = null;
        
        function navigateToCreateQuotation(quotationId, clientId, clientName, contact, packageName, amount, handlingFee) {
            // Store the quotation_id globally so we can update instead of creating new
            CURRENT_QUOTATION_ID = quotationId;
            
            // Navigate to sales quotes section
            const salesQuotesButton = document.querySelector(`.nav-btn[onclick*="'sales-quotes'"]`);
            if (salesQuotesButton) {
                nav('sales-quotes', salesQuotesButton);
                
                // Pre-fill the sales quote form
                setTimeout(() => {
                    document.getElementById('clientName').value = clientName;
                    document.getElementById('clientContact').value = contact;
                    
                    // Pre-fill the installation/handling fee from the original quotation
                    if (handlingFee) {
                        document.getElementById('installation').value = parseFloat(handlingFee);
                    }
                    
                    // Match package by name first, then by amount as fallback
                    const packageSelect = document.getElementById('packageType');
                    if (packageSelect && (packageName || amount)) {
                        const options = packageSelect.options;
                        let matched = false;
                        
                        // Method 1: Try to match by package name (most reliable)
                        if (packageName) {
                            const cleanPackageName = packageName.toLowerCase().trim();
                            
                            for (let i = 0; i < options.length; i++) {
                                const optionText = options[i].text.toLowerCase();
                                
                                // Check if package name contains key identifiers
                                if (cleanPackageName.includes('micro start') && optionText.includes('micro start')) {
                                    packageSelect.selectedIndex = i;
                                    matched = true;
                                    break;
                                } else if (cleanPackageName.includes('essential start') && optionText.includes('essential start')) {
                                    packageSelect.selectedIndex = i;
                                    matched = true;
                                    break;
                                } else if (cleanPackageName.includes('standard shop') && optionText.includes('standard shop')) {
                                    packageSelect.selectedIndex = i;
                                    matched = true;
                                    break;
                                } else if (cleanPackageName.includes('growth model') && optionText.includes('growth model')) {
                                    packageSelect.selectedIndex = i;
                                    matched = true;
                                    break;
                                } else if (cleanPackageName.includes('premium corner') && optionText.includes('premium corner')) {
                                    packageSelect.selectedIndex = i;
                                    matched = true;
                                    break;
                                } else if (cleanPackageName.includes('anchor laundromat') && optionText.includes('anchor laundromat')) {
                                    packageSelect.selectedIndex = i;
                                    matched = true;
                                    break;
                                } else if (cleanPackageName.includes('industrial lite') && optionText.includes('industrial lite')) {
                                    packageSelect.selectedIndex = i;
                                    matched = true;
                                    break;
                                } else if (cleanPackageName.includes('multi-load center') && optionText.includes('multi-load center')) {
                                    packageSelect.selectedIndex = i;
                                    matched = true;
                                    break;
                                } else if (cleanPackageName.includes('technology hub') && optionText.includes('technology hub')) {
                                    packageSelect.selectedIndex = i;
                                    matched = true;
                                    break;
                                } else if (cleanPackageName.includes('flagship enterprise') && optionText.includes('flagship enterprise')) {
                                    packageSelect.selectedIndex = i;
                                    matched = true;
                                    break;
                                }
                            }
                        }
                        
                        // Method 2: Fallback to matching by amount if name matching failed
                        if (!matched && amount) {
                            const totalAmount = parseFloat(amount);
                            let bestMatch = -1;
                            let bestDiff = Infinity;
                            
                            for (let i = 0; i < options.length; i++) {
                                if (!options[i].value) continue;
                                
                                const basePrice = parseFloat(options[i].value);
                                const priceWithLogistics = basePrice * 1.05;
                                
                                const diff1 = Math.abs(basePrice - totalAmount);
                                const diff2 = Math.abs(priceWithLogistics - totalAmount);
                                const minDiff = Math.min(diff1, diff2);
                                
                                if (minDiff < bestDiff) {
                                    bestDiff = minDiff;
                                    bestMatch = i;
                                }
                                
                                if (diff1 === 0 || diff2 === 0) break;
                            }
                            
                            if (bestMatch !== -1) {
                                packageSelect.selectedIndex = bestMatch;
                            }
                        }
                    }
                    
                    // Recalculate the quote summary to show correct amounts
                    calculateSalesQuote();
                    
                    toast('Package automatically selected: ' + (packageName || 'Unknown'));
                }, 100);
            }
        }

        function updateSalesInquiriesStats(stats) {
            // Update navigation badge
            if (stats.pending_count > 0) {
                updateServiceRequestsBadge(stats.pending_count);
            }
        }

        function contactClient(contactNum, clientName) {
            if (contactNum && contactNum.trim()) {
                toast(`Contact: ${contactNum} - ${clientName}`);
            } else {
                toast(`No contact number available for ${clientName}`);
            }
        }

        function updateServiceRequestsBadge(count) {
            const badge = document.getElementById('service-requests-badge');
            if (badge && count > 0) {
                badge.textContent = count;
                badge.style.display = 'inline';
            } else if (badge) {
                badge.style.display = 'none';
            }
        }

        function refreshSalesInquiries() {
            loadSalesInquiries();
            toast('Sales inquiries refreshed');
        }
        
        async function submitQuoteToManager() {
            const proofFile = document.getElementById('proofUpload').files[0];
            const clientName = document.getElementById('clientName').value;
            const packageType = document.getElementById('packageType').value;
            const packageText = document.getElementById('packageType').options[document.getElementById('packageType').selectedIndex].text;
            const totalAmount = document.getElementById('val-total').textContent.replace(/[₱,]/g, '');
            
            if (!proofFile) {
                toast('Please upload proof of printed quote before submitting');
                return;
            }
            
            if (!clientName || !packageType) {
                toast('Please fill in client name and select package');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'submit_quote_for_verification');
            formData.append('debug', 'true'); // Add debug mode
            formData.append('proof_file', proofFile);
            formData.append('client_name', clientName);
            formData.append('package', packageText);
            formData.append('amount', parseFloat(totalAmount));
            formData.append('delivery_method', 'Standard Delivery');
            formData.append('handling_fee', document.getElementById('val-installation').textContent.replace(/[₱,]/g, '') || 0);
            
            // If updating an existing quotation from sales inquiry, pass the quotation_id
            if (CURRENT_QUOTATION_ID) {
                formData.append('quotation_id', CURRENT_QUOTATION_ID);
            }
            
            try {
                const response = await fetch('secretary_quote_api.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    toast('✅ Quote submitted to Manager for approval!');
                    
                    // Reset quotation_id after successful submission
                    CURRENT_QUOTATION_ID = null;
                    
                    // Clear the form
                    document.getElementById('clientName').value = '';
                    document.getElementById('packageType').value = '';
                    document.getElementById('proofUpload').value = '';
                    document.getElementById('file-name').textContent = 'No file chosen';
                    calculateSalesQuote();
                    
                    // Refresh the sales inquiries list
                    if (typeof loadSalesInquiries === 'function') {
                        loadSalesInquiries();
                    }
                    
                    // Show success message
                    setTimeout(() => {
                        toast('Manager will review and approve the quote before sending to client');
                    }, 2000);
                } else {
                    toast('❌ Error: ' + result.message);
                }
                
            } catch (error) {
                console.error('Error submitting quote:', error);
                toast('❌ Error submitting quote to manager');
            }
        }

    </script>

    <!-- Secretary Review Modal -->
    <div id="reviewModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-clipboard-check"></i> Review Service Request</h2>
                <span class="close" onclick="closeReviewModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Client Name:</label>
                    <input type="text" id="review-client-name" readonly>
                </div>
                <div class="form-group">
                    <label>Problem Description:</label>
                    <textarea id="review-problem" readonly rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label>Review Notes (Optional):</label>
                    <textarea id="review-notes" placeholder="Add any notes or comments..." rows="3"></textarea>
                </div>
                <div class="modal-actions">
                    <button class="btn btn-danger" onclick="submitReview('reject')">
                        <i class="fas fa-times"></i> Reject Request
                    </button>
                    <button class="btn btn-success" onclick="submitReview('approve')">
                        <i class="fas fa-check"></i> Send to Manager
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Team Scheduling Modal -->
    <div id="teamScheduleModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-users"></i> Schedule Maintenance Team</h2>
                <span class="close" onclick="closeTeamScheduler()">&times;</span>
            </div>
            <div class="modal-body">
                <div class="content-grid-2">
                    <div>
                        <div class="form-group">
                            <label class="form-label">Client Information</label>
                            <input type="text" id="schedule-client-name" class="form-control" readonly>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Problem Description</label>
                            <input type="text" id="schedule-problem" class="form-control" readonly>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Location</label>
                            <input type="text" id="schedule-location" class="form-control" readonly>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Assigned Team</label>
                            <select id="schedule-team" class="form-control" onchange="updateTeamInfo()">
                                <option value="">Select Team...</option>
                                <option value="alpha">Team Alpha - Installation Specialists</option>
                                <option value="beta">Team Beta - Maintenance & Repair</option>
                                <option value="gamma">Team Gamma - Emergency Response</option>
                                <option value="delta">Team Delta - Electrical & Plumbing</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <div class="form-group">
                            <label class="form-label">Schedule Date</label>
                            <input type="date" id="schedule-date" class="form-control" min="">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Time Slot</label>
                            <select id="schedule-time" class="form-control">
                                <option value="">Select Time...</option>
                                <option value="08:00">8:00 AM - 12:00 PM (Morning)</option>
                                <option value="13:00">1:00 PM - 5:00 PM (Afternoon)</option>
                                <option value="09:00">9:00 AM - 3:00 PM (Full Service)</option>
                                <option value="emergency">Emergency Call (ASAP)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Priority Level</label>
                            <select id="schedule-priority" class="form-control">
                                <option value="normal">Normal</option>
                                <option value="high" selected>High</option>
                                <option value="urgent">Urgent</option>
                                <option value="emergency">Emergency</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Special Notes</label>
                            <textarea id="schedule-notes" class="form-control" rows="3" placeholder="Special instructions, equipment needed, etc."></textarea>
                        </div>
                    </div>
                </div>
                
                <!-- Team Information Display -->
                <div id="team-info-display" style="display: none; margin-top: 20px; padding: 15px; background: var(--info-bg); border-radius: 8px; border: 1px solid #0ea5e9;">
                    <h4 style="margin-bottom: 10px; color: var(--info-text);"><i class="fas fa-info-circle"></i> Team Information</h4>
                    <div id="team-details"></div>
                </div>
                
                <div class="modal-actions" style="margin-top: 20px; text-align: right;">
                    <button class="btn btn-danger" onclick="closeTeamScheduler()" style="margin-right: 10px;">Cancel</button>
                    <button class="btn btn-gold" onclick="confirmTeamSchedule()"><i class="fas fa-check"></i> Schedule Team</button>
                </div>
            </div>
        </div>
    </div>

    <style>
        .modal {
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            backdrop-filter: blur(4px);
        }
        
        .modal-content {
            background-color: var(--white);
            margin: 2% auto;
            border-radius: 12px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            width: 90%;
            max-width: 900px;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            padding: 20px 30px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, var(--navy-dark) 0%, var(--navy-light) 100%);
            color: white;
            border-radius: 12px 12px 0 0;
        }
        
        .modal-header h2 {
            margin: 0;
            font-size: 24px;
            font-weight: 700;
        }
        
        .modal-body {
            padding: 30px;
        }
        
        .close {
            color: white;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            line-height: 1;
        }
        
        .close:hover {
            opacity: 0.7;
        }
        
        .modal-actions {
            border-top: 1px solid #e2e8f0;
            padding-top: 20px;
        }
    </style>

    <script>
        // Review Modal Functions
        function openReviewModal(requestId, clientName, problem) {
            console.log('Opening review modal for:', { requestId, clientName, problem });
            
            document.getElementById('review-client-name').value = clientName || '';
            document.getElementById('review-problem').value = problem || '';
            document.getElementById('review-notes').value = '';
            
            // Store request ID for later use
            document.getElementById('reviewModal').dataset.requestId = requestId;
            
            document.getElementById('reviewModal').style.display = 'block';
        }
        
        function closeReviewModal() {
            document.getElementById('reviewModal').style.display = 'none';
            document.getElementById('review-notes').value = '';
        }
        
        function submitReview(action) {
            const requestId = document.getElementById('reviewModal').dataset.requestId;
            const notes = document.getElementById('review-notes').value;
            
            console.log('Submitting review:', { requestId, action, notes });
            
            toast(`📋 ${action === 'approve' ? 'Sending to manager...' : 'Rejecting request...'}`);
            
            const formData = new FormData();
            formData.append('action', 'secretary_review');
            formData.append('request_id', requestId);
            formData.append('review_action', action);
            formData.append('notes', notes);
            
            // Debug: log what we're sending
            for (let [key, value] of formData.entries()) {
                console.log(key, value);
            }
            
            fetch('service_request_api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                console.log('Review response:', data);
                if (data.success) {
                    toast(`✅ ${data.message}`);
                    closeReviewModal();
                    loadMaintenanceRequests(); // Refresh the list
                } else {
                    toast('❌ Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Review error:', error);
                toast('❌ Failed to process review. Please try again.');
            });
        }
        
        // Team Scheduling Functions
        function openTeamScheduler(requestId, clientName, problem, location) {
            console.log('Opening scheduler for:', { requestId, clientName, problem, location }); // Debug log
            
            document.getElementById('schedule-client-name').value = clientName || '';
            document.getElementById('schedule-problem').value = problem || '';
            document.getElementById('schedule-location').value = location || '';
            
            // Store request ID for later use
            document.getElementById('teamScheduleModal').dataset.requestId = requestId;
            
            // Set minimum date to today
            const today = new Date();
            const tomorrow = new Date(today);
            tomorrow.setDate(tomorrow.getDate() + 1);
            document.getElementById('schedule-date').min = tomorrow.toISOString().split('T')[0];
            document.getElementById('schedule-date').value = tomorrow.toISOString().split('T')[0];
            
            document.getElementById('teamScheduleModal').style.display = 'block';
        }
        
        function closeTeamScheduler() {
            document.getElementById('teamScheduleModal').style.display = 'none';
            // Clear form
            document.getElementById('schedule-team').value = '';
            document.getElementById('schedule-time').value = '';
            document.getElementById('schedule-notes').value = '';
            document.getElementById('team-info-display').style.display = 'none';
        }
        
        function updateTeamInfo() {
            const selectedTeam = document.getElementById('schedule-team').value;
            const teamInfoDisplay = document.getElementById('team-info-display');
            const teamDetails = document.getElementById('team-details');
            
            const teamInfo = {
                'alpha': {
                    name: 'Team Alpha',
                    specialization: 'Installation Specialists',
                    members: ['John Santos (Lead)', 'Mark Rivera', 'Alex Chen'],
                    equipment: 'Installation tools, lifting equipment, electrical supplies',
                    availability: 'Available Mon-Sat, 8AM-6PM'
                },
                'beta': {
                    name: 'Team Beta',
                    specialization: 'Maintenance & Repair',
                    members: ['Carlos Lopez (Lead)', 'Maria Gonzalez', 'David Kim'],
                    equipment: 'Diagnostic tools, spare parts, repair kit',
                    availability: 'Available 24/7 including weekends'
                },
                'gamma': {
                    name: 'Team Gamma',
                    specialization: 'Emergency Response',
                    members: ['Robert Taylor (Lead)', 'Sarah Johnson'],
                    equipment: 'Emergency repair kit, mobile workshop',
                    availability: 'Emergency calls only, 24/7'
                },
                'delta': {
                    name: 'Team Delta',
                    specialization: 'Electrical & Plumbing',
                    members: ['Michael Brown (Lead)', 'Lisa Wang', 'Tom Wilson'],
                    equipment: 'Electrical tools, plumbing supplies, diagnostic equipment',
                    availability: 'Available Mon-Fri, 7AM-7PM'
                }
            };
            
            if (selectedTeam && teamInfo[selectedTeam]) {
                const team = teamInfo[selectedTeam];
                teamDetails.innerHTML = `
                    <div class="team-info-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; font-size: 14px;">
                        <div>
                            <strong>Specialization:</strong> ${team.specialization}<br>
                            <strong>Team Members:</strong><br>
                            ${team.members.map(member => `• ${member}`).join('<br>')}
                        </div>
                        <div>
                            <strong>Equipment:</strong> ${team.equipment}<br><br>
                            <strong>Availability:</strong> ${team.availability}
                        </div>
                    </div>
                `;
                teamInfoDisplay.style.display = 'block';
            } else {
                teamInfoDisplay.style.display = 'none';
            }
        }
        
        function confirmTeamSchedule() {
            const requestId = document.getElementById('teamScheduleModal').dataset.requestId;
            const clientName = document.getElementById('schedule-client-name').value;
            const team = document.getElementById('schedule-team').value;
            const date = document.getElementById('schedule-date').value;
            const time = document.getElementById('schedule-time').value;
            const priority = document.getElementById('schedule-priority').value;
            const notes = document.getElementById('schedule-notes').value;
            
            if (!team || !date || !time) {
                toast('Please fill in all required fields');
                return;
            }
            
            const teamNames = {
                'alpha': 'Team Alpha',
                'beta': 'Team Beta', 
                'gamma': 'Team Gamma',
                'delta': 'Team Delta'
            };
            
            const timeSlots = {
                '08:00': '8:00 AM - 12:00 PM',
                '13:00': '1:00 PM - 5:00 PM',
                '09:00': '9:00 AM - 3:00 PM',
                'emergency': 'Emergency Call (ASAP)'
            };
            
            // Submit to API
            toast('📅 Scheduling team...');
            
            const formData = new FormData();
            formData.append('action', 'schedule_team');
            formData.append('request_id', requestId);
            formData.append('team', teamNames[team]);
            formData.append('schedule_date', date);
            formData.append('schedule_time', time);
            formData.append('priority', priority);
            formData.append('notes', notes);
            
            fetch('service_request_api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    toast(`✅ ${teamNames[team]} scheduled for ${clientName}!`);
                    
                    setTimeout(() => {
                        const formattedDate = new Date(date).toLocaleDateString('en-US', {
                            weekday: 'long',
                            year: 'numeric',
                            month: 'long',
                            day: 'numeric'
                        });
                        
                        toast(`📋 Schedule Details: ${formattedDate}, ${timeSlots[time] || time}`);
                        
                        setTimeout(() => {
                            toast('📧 Client notification sent via SMS and email');
                            loadMaintenanceRequests(); // Refresh the list
                        }, 2000);
                    }, 1500);
                    
                    closeTeamScheduler();
                } else {
                    toast('❌ Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Team scheduling error:', error);
                toast('❌ Failed to schedule team. Please try again.');
            });
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('teamScheduleModal');
            if (event.target === modal) {
                closeTeamScheduler();
            }
        }

        // ===== CLIENT MANAGEMENT FUNCTIONS =====
        
        let currentClientId = null;
        let allClients = [];
        
        async function loadClientList(search = '') {
            console.log('Loading client list...');
            try {
                const url = search 
                    ? `client_api.php?action=get_all_clients&search=${encodeURIComponent(search)}`
                    : 'client_api.php?action=get_all_clients';
                    
                const response = await fetch(url, {
                    credentials: 'include'
                });
                const result = await response.json();
                
                if (result.success) {
                    allClients = result.clients;
                    renderClientList(result.clients);
                    console.log(`Loaded ${result.count} clients`);
                } else {
                    console.error('Failed to load clients:', result.message);
                    toast('Failed to load client list');
                    document.getElementById('client-list-body').innerHTML = `
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px; color: var(--danger-text);">
                                <i class="fas fa-exclamation-circle" style="font-size: 24px; margin-bottom: 10px;"></i>
                                <div>Failed to load clients</div>
                            </td>
                        </tr>
                    `;
                }
            } catch (error) {
                console.error('Error loading clients:', error);
                toast('Error loading clients: ' + error.message);
            }
        }
        
        function renderClientList(clients) {
            const tbody = document.getElementById('client-list-body');
            
            if (!clients || clients.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 40px; color: var(--text-muted);">
                            <i class="fas fa-users" style="font-size: 48px; margin-bottom: 10px; opacity: 0.3;"></i>
                            <div>No clients found</div>
                        </td>
                    </tr>
                `;
                return;
            }
            
            tbody.innerHTML = '';
            
            clients.forEach(client => {
                const row = document.createElement('tr');
                row.style.cursor = 'pointer';
                row.onclick = () => viewClientProfile(client.Client_ID);
                
                row.innerHTML = `
                    <td>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div class="avatar" style="width: 32px; height: 32px; font-size: 12px;">
                                ${client.Name.substring(0, 2).toUpperCase()}
                            </div>
                            <strong>${client.Name}</strong>
                        </div>
                    </td>
                    <td>${client.Contact_Num || 'N/A'}</td>
                    <td>${client.Address || 'N/A'}</td>
                    <td><span class="status-badge" style="background: var(--info-bg); color: var(--info-text);">${client.total_jobs || 0} jobs</span></td>
                    <td><strong>₱${parseFloat(client.total_revenue || 0).toLocaleString('en-PH', {maximumFractionDigits: 0})}</strong></td>
                    <td>
                        <button class="btn btn-primary" style="padding: 6px 12px; font-size: 12px;" onclick="event.stopPropagation(); viewClientProfile(${client.Client_ID})">
                            <i class="fas fa-eye"></i> View
                        </button>
                    </td>
                `;
                
                tbody.appendChild(row);
            });
        }
        
        function searchClients(query) {
            if (query.length === 0) {
                renderClientList(allClients);
            } else {
                const filtered = allClients.filter(client => 
                    client.Name.toLowerCase().includes(query.toLowerCase()) ||
                    (client.Contact_Num && client.Contact_Num.includes(query)) ||
                    (client.Address && client.Address.toLowerCase().includes(query.toLowerCase()))
                );
                renderClientList(filtered);
            }
        }
        
        async function viewClientProfile(clientId) {
            console.log('Loading profile for client ID:', clientId);
            currentClientId = clientId;
            
            // Show profile view, hide list view
            document.getElementById('client-list-view').style.display = 'none';
            document.getElementById('client-profile-view').style.display = 'block';
            
            try {
                const response = await fetch(`client_api.php?action=get_client_profile&client_id=${clientId}`, {
                    credentials: 'include'
                });
                const result = await response.json();
                
                if (result.success) {
                    renderClientProfile(result);
                } else {
                    console.error('Failed to load client profile:', result.message);
                    toast('Failed to load client profile');
                    backToClientList();
                }
            } catch (error) {
                console.error('Error loading client profile:', error);
                toast('Error loading client profile: ' + error.message);
                backToClientList();
            }
        }
        
        function renderClientProfile(data) {
            const client = data.client;
            const stats = data.stats;
            
            // Update title
            document.getElementById('client-profile-title').textContent = `Client Profile: ${client.Name}`;
            
            // Update KPI cards
            document.getElementById('profile-total-jobs').textContent = stats.total_jobs || 0;
            document.getElementById('profile-total-revenue').textContent = 
                '₱' + parseFloat(stats.total_revenue || 0).toLocaleString('en-PH', {maximumFractionDigits: 0});
            document.getElementById('profile-pending-payments').textContent = 
                '₱' + parseFloat(stats.pending_payments || 0).toLocaleString('en-PH', {maximumFractionDigits: 0});
            document.getElementById('profile-critical-alerts').textContent = stats.critical_alerts || 0;
            
            // Render service history
            renderServiceHistory(data.history, data.urgent);
        }
        
        function renderServiceHistory(history, urgent) {
            const container = document.getElementById('client-service-history');
            
            if ((!history || history.length === 0) && (!urgent || urgent.length === 0)) {
                container.innerHTML = `
                    <div style="text-align: center; padding: 40px; color: var(--text-muted);">
                        <i class="fas fa-history" style="font-size: 48px; margin-bottom: 10px; opacity: 0.3;"></i>
                        <div>No service history found</div>
                    </div>
                `;
                return;
            }
            
            container.innerHTML = '';
            
            // Render urgent items first
            if (urgent && urgent.length > 0) {
                urgent.forEach(item => {
                    const card = createUrgentServiceCard(item);
                    container.appendChild(card);
                });
            }
            
            // Render regular history
            if (history && history.length > 0) {
                history.forEach(item => {
                    const card = createHistoryCard(item);
                    container.appendChild(card);
                });
            }
        }
        
        function createUrgentServiceCard(item) {
            const card = document.createElement('div');
            card.className = 'txn-card';
            card.style.gridTemplateColumns = '80px 1.8fr 2.2fr 1.2fr';
            
            card.innerHTML = `
                <div class="txn-icon-col" style="color:var(--danger-text);"><i class="fas fa-wrench"></i></div>
                <div class="txn-client-col">
                    <span class="txn-ref" style="background:var(--danger-bg); color:var(--danger-text);">URGENT #${item.Service_ID}</span>
                    <div class="txn-client-name">${item.type || 'Service Request'}</div>
                    <div class="txn-client-loc"><i class="fas fa-clipboard"></i> Pending service</div>
                </div>
                <div class="txn-finance-col" style="border-right: 1px dashed #e2e8f0;">
                    <div class="finance-row"><span>Date:</span> <strong>${formatDate(item.Service_Date)}</strong></div>
                    <div class="finance-row"><span>Status:</span> ${item.Status}</div>
                </div>
                <div class="txn-action-col" style="background:var(--danger-bg); border-left: 1px solid var(--danger-text);">
                    <button class="btn btn-danger" style="color:white; background:var(--danger-text);"><i class="fas fa-clock"></i> URGENT</button>
                    <button class="btn btn-outline" style="border-color:var(--danger-text); color:var(--danger-text); background:var(--danger-bg);">View Details</button>
                </div>
            `;
            
            return card;
        }
        
        function createHistoryCard(item) {
            const card = document.createElement('div');
            card.className = 'txn-card';
            card.style.gridTemplateColumns = '80px 1.8fr 2.2fr 1.2fr';
            
            const statusColors = {
                'completed': { bg: 'var(--success-bg)', text: 'var(--success-text)', icon: 'fa-check-circle' },
                'pending': { bg: 'var(--warning-bg)', text: 'var(--warning-text)', icon: 'fa-clock' },
                'rejected': { bg: 'var(--danger-bg)', text: 'var(--danger-text)', icon: 'fa-times-circle' },
                'active': { bg: 'var(--info-bg)', text: 'var(--info-text)', icon: 'fa-sync' }
            };
            
            const status = statusColors[item.status_type] || statusColors['active'];
            
            card.innerHTML = `
                <div class="txn-icon-col" style="color:${status.text};"><i class="fas ${status.icon}"></i></div>
                <div class="txn-client-col">
                    <span class="txn-ref" style="background:${status.bg}; color:${status.text};">${item.ref || 'QT-' + item.Quotation_ID}</span>
                    <div class="txn-client-name">${item.Package}</div>
                    <div class="txn-client-loc"><i class="fas fa-truck"></i> ${item.Delivery_Method || 'N/A'}</div>
                </div>
                <div class="txn-finance-col" style="border-right: 1px dashed #e2e8f0;">
                    <div class="finance-row"><span>Date:</span> <strong>${formatDate(item.Date_Issued)}</strong></div>
                    <div class="finance-row"><span>Amount:</span> ₱${parseFloat(item.Amount).toLocaleString('en-PH', {maximumFractionDigits: 2})}</div>
                </div>
                <div class="txn-action-col">
                    <button class="btn btn-primary" onclick="viewQuotationDetails(${item.Quotation_ID})"><i class="fas fa-receipt"></i> View Invoice</button>
                    <button class="btn btn-outline" onclick="callClient('${item.Client_Name}')">Call Client</button>
                </div>
            `;
            
            return card;
        }
        
        function formatDate(dateString) {
            if (!dateString) return 'N/A';
            const date = new Date(dateString);
            const today = new Date();
            
            if (date.toDateString() === today.toDateString()) {
                return 'Today, ' + date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
            }
            
            return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
        }
        
        function backToClientList() {
            document.getElementById('client-profile-view').style.display = 'none';
            document.getElementById('client-list-view').style.display = 'block';
            currentClientId = null;
        }
        
        // Alias function for loadClientProfile (used by auto-refresh)
        async function loadClientProfile(clientId) {
            if (!clientId) return;
            
            try {
                const response = await fetch(`client_api.php?action=get_client_profile&client_id=${clientId}`, {
                    credentials: 'include'
                });
                const result = await response.json();
                
                if (result.success) {
                    renderClientProfile(result);
                } else {
                    console.error('Failed to refresh client profile:', result.message);
                }
            } catch (error) {
                console.error('Error refreshing client profile:', error);
            }
        }
        
        function addNewClient() {
            toast('Add New Client feature coming soon!');
        }
        
        function callClient(clientName) {
            toast(`Calling ${clientName}...`);
        }
        
        async function editClientProfile() {
            if (!currentClientId) {
                console.error('Error loading client data for edit: Error: Client ID is required');
                toast('⚠️ Please select a client first');
                return;
            }
            
            try {
                // Fetch current client data
                const response = await fetch(`client_api.php?action=get_client_profile&client_id=${currentClientId}`);
                const result = await response.json();
                
                if (!result.success) {
                    throw new Error(result.message || 'Failed to load client data');
                }
                
                const client = result.client;
                
                // Create edit modal
                const modalHTML = `
                    <div class="modal-overlay" id="edit-profile-modal" onclick="closeEditProfileModal(event)">
                        <div class="modal-content" onclick="event.stopPropagation()" style="max-width: 600px;">
                            <div class="modal-header">
                                <h3>Edit Client Profile</h3>
                                <button class="modal-close" onclick="closeEditProfileModal()">&times;</button>
                            </div>
                            <div class="modal-body">
                                <form id="edit-profile-form">
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                                        <div>
                                            <label style="display: block; font-weight: 600; margin-bottom: 8px; color: var(--navy-dark);">Name *</label>
                                            <input type="text" id="edit-name" value="${client.Name || ''}" required style="width: 100%; padding: 10px; border: 1px solid var(--border-light); border-radius: var(--radius-sm);">
                                        </div>
                                        <div>
                                            <label style="display: block; font-weight: 600; margin-bottom: 8px; color: var(--navy-dark);">Email *</label>
                                            <input type="email" id="edit-email" value="${client.Email || ''}" required style="width: 100%; padding: 10px; border: 1px solid var(--border-light); border-radius: var(--radius-sm);">
                                        </div>
                                    </div>
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                                        <div>
                                            <label style="display: block; font-weight: 600; margin-bottom: 8px; color: var(--navy-dark);">Contact Number *</label>
                                            <input type="text" id="edit-contact" value="${client.Contact_Num || ''}" required style="width: 100%; padding: 10px; border: 1px solid var(--border-light); border-radius: var(--radius-sm);">
                                        </div>
                                        <div>
                                            <label style="display: block; font-weight: 600; margin-bottom: 8px; color: var(--navy-dark);">Business Name</label>
                                            <input type="text" id="edit-business" value="${client.Business_Name || ''}" style="width: 100%; padding: 10px; border: 1px solid var(--border-light); border-radius: var(--radius-sm);">
                                        </div>
                                    </div>
                                    <div style="margin-bottom: 16px;">
                                        <label style="display: block; font-weight: 600; margin-bottom: 8px; color: var(--navy-dark);">Address *</label>
                                        <textarea id="edit-address" required style="width: 100%; padding: 10px; border: 1px solid var(--border-light); border-radius: var(--radius-sm); min-height: 80px;">${client.Address || ''}</textarea>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button class="btn btn-outline" onclick="closeEditProfileModal()">Cancel</button>
                                <button class="btn btn-primary" onclick="saveClientProfile()"><i class="fas fa-save"></i> Save Changes</button>
                            </div>
                        </div>
                    </div>
                `;
                
                // Remove existing modal if any
                const existingModal = document.getElementById('edit-profile-modal');
                if (existingModal) existingModal.remove();
                
                // Add modal to body
                document.body.insertAdjacentHTML('beforeend', modalHTML);
                
            } catch (error) {
                console.error('Error loading client data for edit:', error);
                toast('Failed to load client data');
            }
        }
        
        function closeEditProfileModal(event) {
            if (event && event.target.classList.contains('modal-overlay') === false) return;
            const modal = document.getElementById('edit-profile-modal');
            if (modal) modal.remove();
        }
        
        async function saveClientProfile() {
            const name = document.getElementById('edit-name').value.trim();
            const email = document.getElementById('edit-email').value.trim();
            const contact = document.getElementById('edit-contact').value.trim();
            const business = document.getElementById('edit-business').value.trim();
            const address = document.getElementById('edit-address').value.trim();
            
            if (!name || !email || !contact || !address) {
                toast('Please fill in all required fields', 'warning');
                return;
            }
            
            try {
                const formData = new FormData();
                formData.append('action', 'update_client');
                formData.append('client_id', currentClientId);
                formData.append('name', name);
                formData.append('email', email);
                formData.append('contact', contact);
                formData.append('business_name', business);
                formData.append('address', address);
                
                const response = await fetch('client_api.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    toast('✅ Client profile updated successfully!', 'success');
                    closeEditProfileModal();
                    // Reload client profile
                    loadClientProfile(currentClientId);
                } else {
                    throw new Error(result.message);
                }
            } catch (error) {
                console.error('Error updating client profile:', error);
                toast('Failed to update client profile: ' + error.message, 'error');
            }
        }
        
        async function viewQuotationDetails(quotationId) {
            console.log('📄 Loading quotation details for ID:', quotationId);
            
            try {
                const response = await fetch(`payment_verification_api.php?action=get_quote_details&quotation_id=${quotationId}`);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const result = await response.json();
                console.log('✅ Quote data received:', result);
                console.log('📦 Quote object:', result.quote);
                
                if (!result.success) {
                    throw new Error(result.message || 'Failed to fetch quotation details');
                }
                
                if (!result.quote) {
                    throw new Error('No quote data in response');
                }
                
                const quote = result.quote;
                console.log('🎯 Calling displayInvoiceModal with:', quote);
                displayInvoiceModal(quote);
                
            } catch (error) {
                console.error('❌ Error loading quotation details:', error);
                toast('❌ Failed to load invoice: ' + error.message);
            }
        }
        
        function displayInvoiceModal(quote) {
            console.log('🖼️ displayInvoiceModal called with quote:', quote);
            
            try {
                const totalAmount = parseFloat(quote.amount || 0) + parseFloat(quote.handling_fee || 0);
                const statusClass = ['Verified', 'Paid', 'Completed', 'Approved'].includes(quote.status) ? 'status-ok' : 'status-warn';
                
                console.log('💰 Total amount calculated:', totalAmount);
                console.log('🏷️ Status class:', statusClass);
                
                const modalHTML = `
                <div class="modal-overlay" id="invoice-modal" onclick="closeInvoiceModal(event)">
                    <div class="modal-content" onclick="event.stopPropagation()" style="max-width: 700px;">
                        <div class="modal-header" style="background: linear-gradient(135deg, var(--navy-dark), #475569); color: white; margin: -20px -20px 20px -20px; padding: 25px; border-radius: var(--radius-md) var(--radius-md) 0 0;">
                            <div>
                                <h3 style="margin: 0 0 8px 0; color: var(--gold);">INVOICE / QUOTATION</h3>
                                <p style="margin: 0; opacity: 0.9; font-size: 18px; font-weight: 700;">QT-${String(quote.quotation_id).padStart(4, '0')}</p>
                            </div>
                            <button class="modal-close" onclick="closeInvoiceModal()" style="background: rgba(255,255,255,0.2); color: white; border: none; font-size: 24px; width: 32px; height: 32px; border-radius: 50%; cursor: pointer;">&times;</button>
                        </div>
                        <div class="modal-body" style="padding: 0 20px 20px 20px;">
                            <!-- Client Information -->
                            <div style="background: var(--bg-secondary); padding: 20px; border-radius: var(--radius-md); margin-bottom: 20px;">
                                <h4 style="margin: 0 0 12px 0; color: var(--navy-dark); font-size: 14px;"><i class="fas fa-user"></i> Client Information</h4>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                                    <div>
                                        <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 4px;">Name</div>
                                        <div style="font-weight: 600;">${quote.client_name || 'N/A'}</div>
                                    </div>
                                    <div>
                                        <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 4px;">Contact</div>
                                        <div style="font-weight: 600;">${quote.client_contact || 'N/A'}</div>
                                    </div>
                                    <div style="grid-column: 1 / -1;">
                                        <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 4px;">Address</div>
                                        <div style="font-weight: 600;">${quote.client_address || 'N/A'}</div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Package & Transaction Details -->
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                                <div>
                                    <h5 style="margin: 0 0 8px 0; color: var(--navy-dark); font-size: 12px; text-transform: uppercase;">Package</h5>
                                    <p style="margin: 0; font-weight: 600; font-size: 15px;">${quote.package}</p>
                                </div>
                                <div>
                                    <h5 style="margin: 0 0 8px 0; color: var(--navy-dark); font-size: 12px; text-transform: uppercase;">Date Issued</h5>
                                    <p style="margin: 0; font-weight: 600;">${new Date(quote.date_issued).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</p>
                                </div>
                                <div>
                                    <h5 style="margin: 0 0 8px 0; color: var(--navy-dark); font-size: 12px; text-transform: uppercase;">Delivery Method</h5>
                                    <p style="margin: 0; font-weight: 600;">${quote.delivery_method || 'N/A'}</p>
                                </div>
                                <div>
                                    <h5 style="margin: 0 0 8px 0; color: var(--navy-dark); font-size: 12px; text-transform: uppercase;">Status</h5>
                                    <span class="status-badge ${statusClass}">${quote.status}</span>
                                </div>
                            </div>
                            
                            <!-- Amount Breakdown -->
                            <div style="background: var(--bg-light); border: 2px solid var(--border-light); padding: 20px; border-radius: var(--radius-md);">
                                <h4 style="margin: 0 0 15px 0; color: var(--navy-dark); font-size: 14px;"><i class="fas fa-calculator"></i> Amount Breakdown</h4>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                    <span>Package Amount:</span>
                                    <span style="font-weight: 600;">₱${parseFloat(quote.amount).toLocaleString('en-PH', { minimumFractionDigits: 2 })}</span>
                                </div>
                                ${quote.handling_fee > 0 ? `
                                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                    <span>Handling Fee:</span>
                                    <span style="font-weight: 600;">₱${parseFloat(quote.handling_fee).toLocaleString('en-PH', { minimumFractionDigits: 2 })}</span>
                                </div>
                                ` : ''}
                                <hr style="margin: 15px 0; border: none; border-top: 2px solid var(--border-light);">
                                <div style="display: flex; justify-content: space-between; font-size: 18px; font-weight: 700;">
                                    <span>TOTAL AMOUNT:</span>
                                    <span style="color: var(--success-text);">₱${totalAmount.toLocaleString('en-PH', { minimumFractionDigits: 2 })}</span>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-outline" onclick="closeInvoiceModal()">Close</button>
                            <button class="btn btn-primary" onclick="printInvoice(${JSON.stringify(quote).replace(/"/g, '&quot;')})"><i class="fas fa-print"></i> Print Invoice</button>
                        </div>
                    </div>
                </div>
            `;
            
            // Remove existing modal if any
            const existingModal = document.getElementById('invoice-modal');
            if (existingModal) {
                console.log('🗑️ Removing existing modal');
                existingModal.remove();
            }
            
            // Add modal to body
            console.log('➕ Adding modal to body');
            document.body.insertAdjacentHTML('beforeend', modalHTML);
            
            // Verify modal was added and show it
            const newModal = document.getElementById('invoice-modal');
            if (newModal) {
                console.log('✅ Modal successfully added to DOM');
                newModal.classList.add('show');
                console.log('👁️ Modal show class added');
            } else {
                console.error('❌ Modal was not added to DOM!');
            }
            
        } catch (error) {
            console.error('❌ Error in displayInvoiceModal:', error);
            toast('❌ Error displaying invoice: ' + error.message);
        }
    }
        
        function closeInvoiceModal(event) {
            if (event && event.target.classList.contains('modal-overlay') === false) return;
            const modal = document.getElementById('invoice-modal');
            if (modal) modal.remove();
        }
        
        function printInvoice(quote) {
            const totalAmount = parseFloat(quote.amount) + parseFloat(quote.handling_fee || 0);
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
                    <title>Invoice - QT-${String(quote.quotation_id).padStart(4, '0')}</title>
                    <meta charset="UTF-8">
                    <style>
                        * { margin: 0; padding: 0; box-sizing: border-box; }
                        body { font-family: 'Segoe UI', Tahoma, sans-serif; background: white; color: #333; padding: 20px; }
                        .invoice { max-width: 500px; margin: 0 auto; padding: 20px; border: 2px solid #2c3e50; }
                        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #2c3e50; padding-bottom: 15px; }
                        .company-name { font-size: 20px; font-weight: bold; color: #2c3e50; margin-bottom: 5px; }
                        .company-info { font-size: 12px; color: #666; line-height: 1.5; }
                        .invoice-title { font-size: 18px; font-weight: bold; margin: 15px 0; text-align: center; }
                        .invoice-id { font-size: 16px; text-align: center; color: #666; margin-bottom: 20px; }
                        .section { margin: 20px 0; }
                        .section-title { font-size: 12px; font-weight: bold; color: #2c3e50; margin-bottom: 10px; text-transform: uppercase; border-bottom: 1px solid #ddd; padding-bottom: 5px; }
                        .info-row { display: flex; justify-content: space-between; margin: 8px 0; font-size: 13px; }
                        .info-label { color: #666; }
                        .info-value { font-weight: 600; }
                        .amount-section { border: 1px solid #ddd; padding: 15px; background: #f8f9fa; margin: 20px 0; }
                        .amount-row { display: flex; justify-content: space-between; margin: 8px 0; font-size: 13px; }
                        .total-row { border-top: 2px solid #333; margin-top: 15px; padding-top: 10px; font-weight: bold; font-size: 16px; }
                        .footer { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 2px solid #2c3e50; }
                        .footer-text { font-size: 11px; color: #666; line-height: 1.6; }
                        @media print { body { margin: 0; padding: 10px; } .invoice { border: 1px solid #2c3e50; margin: 0; } }
                    </style>
                </head>
                <body>
                    <div class="invoice">
                        <div class="header">
                            <div class="company-name">ATMICX Laundry Machine Trading</div>
                            <div class="company-info">Professional Laundry Solutions<br>Email: info@atmicx.com | Phone: (034) 123-4567</div>
                        </div>
                        <div class="invoice-title">INVOICE / QUOTATION</div>
                        <div class="invoice-id">QT-${String(quote.quotation_id).padStart(4, '0')}</div>
                        <div class="section">
                            <div class="section-title">Client Information</div>
                            <div class="info-row"><span class="info-label">Name:</span> <span class="info-value">${quote.client_name}</span></div>
                            <div class="info-row"><span class="info-label">Contact:</span> <span class="info-value">${quote.client_contact || 'N/A'}</span></div>
                            <div class="info-row"><span class="info-label">Address:</span> <span class="info-value">${quote.client_address || 'N/A'}</span></div>
                        </div>
                        <div class="section">
                            <div class="section-title">Transaction Details</div>
                            <div class="info-row"><span class="info-label">Date Issued:</span> <span class="info-value">${new Date(quote.date_issued).toLocaleDateString('en-US')}</span></div>
                            <div class="info-row"><span class="info-label">Package:</span> <span class="info-value">${quote.package}</span></div>
                            <div class="info-row"><span class="info-label">Delivery:</span> <span class="info-value">${quote.delivery_method || 'N/A'}</span></div>
                            <div class="info-row"><span class="info-label">Status:</span> <span class="info-value">${quote.status}</span></div>
                        </div>
                        <div class="section">
                            <div class="section-title">Amount Breakdown</div>
                            <div class="amount-section">
                                <div class="amount-row"><span>Package Amount:</span><span>₱${parseFloat(quote.amount).toLocaleString('en-PH', { minimumFractionDigits: 2 })}</span></div>
                                ${quote.handling_fee > 0 ? `<div class="amount-row"><span>Handling Fee:</span><span>₱${parseFloat(quote.handling_fee).toLocaleString('en-PH', { minimumFractionDigits: 2 })}</span></div>` : ''}
                                <div class="amount-row total-row"><span>TOTAL AMOUNT:</span><span>₱${totalAmount.toLocaleString('en-PH', { minimumFractionDigits: 2 })}</span></div>
                            </div>
                        </div>
                        <div class="footer">
                            <div class="footer-text">Thank you for choosing ATMICX Laundry Machine Trading!<br>For inquiries, please contact us at the above information.<br><strong>This is a computer-generated invoice.</strong></div>
                            <div style="margin-top: 15px; font-size: 10px; color: #999;">Printed on: ${currentDate}</div>
                        </div>
                    </div>
                </body>
                </html>
            `;
            
            // Create iframe for printing
            let printFrame = document.getElementById('print-invoice-frame');
            if (printFrame) printFrame.remove();
            
            printFrame = document.createElement('iframe');
            printFrame.id = 'print-invoice-frame';
            printFrame.style.cssText = 'position: absolute; width: 0; height: 0; border: none; visibility: hidden;';
            document.body.appendChild(printFrame);
            
            // Write content and print
            const frameDoc = printFrame.contentWindow.document;
            frameDoc.open();
            frameDoc.write(receiptContent);
            frameDoc.close();
            
            setTimeout(() => {
                printFrame.contentWindow.focus();
                printFrame.contentWindow.print();
                setTimeout(() => printFrame && printFrame.remove(), 1000);
            }, 250);
            
            toast('✅ Print dialog opened!');
        }

        // =====================================================
        // REAL-TIME UPDATES SYSTEM
        // =====================================================
        
        class RealtimeUpdateManager {
            constructor() {
                this.updateInterval = 10000; // Check every 10 seconds
                this.lastCheckTimestamp = Math.floor(Date.now() / 1000);
                this.isActive = true;
                this.currentSection = 'sec-dashboard';
                this.intervalId = null;
                this.previousCounts = {};
                
                console.log('🔄 Real-time updates initialized');
            }
            
            start() {
                this.stop(); // Clear any existing interval
                
                // Initial check
                this.checkForUpdates();
                
                // Start periodic checking
                this.intervalId = setInterval(() => {
                    if (this.isActive) {
                        this.checkForUpdates();
                    }
                }, this.updateInterval);
                
                console.log('✅ Real-time updates started (checking every ' + (this.updateInterval / 1000) + 's)');
            }
            
            stop() {
                if (this.intervalId) {
                    clearInterval(this.intervalId);
                    this.intervalId = null;
                    console.log('⏸️ Real-time updates paused');
                }
            }
            
            setActiveSection(sectionId) {
                this.currentSection = sectionId;
                console.log('📍 Active section:', sectionId);
            }
            
            async checkForUpdates() {
                try {
                    // Get current counts
                    const response = await fetch('secretary_realtime_api.php?action=get_counts');
                    const data = await response.json();
                    
                    if (!data.success) {
                        console.warn('Failed to fetch updates:', data.message);
                        return;
                    }
                    
                    const counts = data.counts;
                    
                    // Check for changes and update UI
                    this.handleCountChanges(counts);
                    
                    // Check for new items since last check
                    const updateResponse = await fetch(
                        `secretary_realtime_api.php?action=check_updates&last_check=${this.lastCheckTimestamp}`
                    );
                    const updateData = await updateResponse.json();
                    
                    if (updateData.success && updateData.updates.has_updates) {
                        this.handleNewUpdates(updateData.updates);
                    }
                    
                    // Update timestamp
                    this.lastCheckTimestamp = counts.timestamp;
                    this.previousCounts = counts;
                    
                } catch (error) {
                    console.error('❌ Error checking for updates:', error);
                }
            }
            
            handleCountChanges(newCounts) {
                // Update notification badges
                if (newCounts.unread_notifications !== undefined) {
                    const notifBadge = document.querySelector('.notification-badge');
                    if (notifBadge) {
                        if (newCounts.unread_notifications > 0) {
                            notifBadge.textContent = newCounts.unread_notifications;
                            notifBadge.style.display = 'flex';
                        } else {
                            notifBadge.style.display = 'none';
                        }
                    }
                }
                
                // Update low stock badge
                if (newCounts.low_stock !== undefined) {
                    const stockBadge = document.querySelector('.stock-badge');
                    if (stockBadge && newCounts.low_stock > 0) {
                        stockBadge.textContent = newCounts.low_stock;
                        stockBadge.style.display = 'flex';
                    }
                }
                
                // Check if counts increased
                const hasNewMaintenance = this.previousCounts.pending_maintenance !== undefined && 
                                         newCounts.pending_maintenance > this.previousCounts.pending_maintenance;
                const hasNewSales = this.previousCounts.new_sales !== undefined && 
                                   newCounts.new_sales > this.previousCounts.new_sales;
                
                // Show toast notifications for new items
                if (hasNewMaintenance) {
                    const diff = newCounts.pending_maintenance - this.previousCounts.pending_maintenance;
                    this.showUpdateNotification('🔧 New Maintenance Request', `${diff} new request(s) need review`, 'maintenance');
                }
                
                if (hasNewSales) {
                    const diff = newCounts.new_sales - this.previousCounts.new_sales;
                    this.showUpdateNotification('💼 New Sales Inquiry', `${diff} new inquiry(s) received`, 'sales');
                }
            }
            
            handleNewUpdates(updates) {
                console.log('📬 New updates detected:', updates.sections);
                
                // Auto-refresh current section if it has updates
                if (updates.sections.includes('maintenance') && 
                    this.currentSection === 'service-requests') {
                    this.refreshSection('maintenance');
                }
                
                if (updates.sections.includes('sales') && 
                    this.currentSection === 'service-requests') {
                    this.refreshSection('sales');
                }
            }
            
            refreshSection(section) {
                console.log('🔄 Auto-refreshing section:', section);
                
                switch(section) {
                    case 'maintenance':
                        if (typeof loadMaintenanceRequests === 'function') {
                            loadMaintenanceRequests();
                            console.log('✅ Maintenance requests refreshed');
                        }
                        break;
                    case 'sales':
                        if (typeof loadSalesInquiries === 'function') {
                            loadSalesInquiries();
                            console.log('✅ Sales inquiries refreshed');
                        }
                        break;
                    case 'jobs':
                        if (typeof loadJobsAwaitingAssignment === 'function') {
                            loadJobsAwaitingAssignment();
                            console.log('✅ Jobs awaiting assignment refreshed');
                        }
                        break;
                }
            }
            
            showUpdateNotification(title, message, type) {
                // Create toast notification
                const toastHTML = `
                    <div class="realtime-toast ${type}" style="
                        position: fixed;
                        top: 80px;
                        right: 20px;
                        background: white;
                        padding: 16px 20px;
                        border-radius: 8px;
                        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                        z-index: 10000;
                        min-width: 300px;
                        border-left: 4px solid var(--gold);
                        animation: slideInRight 0.3s ease;
                    ">
                        <div style="font-weight: 600; color: var(--navy-dark); margin-bottom: 4px;">
                            ${title}
                        </div>
                        <div style="color: var(--text-muted); font-size: 14px;">
                            ${message}
                        </div>
                        <button onclick="this.parentElement.remove()" style="
                            position: absolute;
                            top: 8px;
                            right: 8px;
                            background: none;
                            border: none;
                            color: var(--text-muted);
                            cursor: pointer;
                            font-size: 18px;
                            padding: 4px;
                        ">&times;</button>
                    </div>
                `;
                
                document.body.insertAdjacentHTML('beforeend', toastHTML);
                
                // Auto-remove after 5 seconds
                setTimeout(() => {
                    const toasts = document.querySelectorAll('.realtime-toast');
                    if (toasts.length > 0) {
                        toasts[toasts.length - 1].remove();
                    }
                }, 5000);
                
                // Play notification sound (optional)
                this.playNotificationSound();
            }
            
            playNotificationSound() {
                try {
                    const audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBSuBzvLZiTYIGmm98OScTgwOUKnn77RgGwU7k9jzzn0vBSF1xe/glEILElyx6OyrWBUIQ5zd8sFuIAUsgs/z3Ik2CBxqvvDlm0wMDlCq5+63YxsHO5LY9NB7LgUgdMXu35FEDBJarun');
                    audio.volume = 0.3;
                    audio.play().catch(() => {}); // Ignore errors if sound can't play
                } catch (e) {
                    // Silently fail if audio not supported
                }
            }
            
            manualRefresh() {
                console.log('🔄 Manual refresh triggered');
                this.lastCheckTimestamp = Math.floor(Date.now() / 1000) - 60; // Check last minute
                this.checkForUpdates();
                
                // Refresh current visible content
                switch(this.currentSection) {
                    case 'service-requests':
                        loadMaintenanceRequests();
                        loadSalesInquiries();
                        break;
                    case 'job-scheduling':
                        loadJobsAwaitingAssignment();
                        loadScheduledJobs();
                        break;
                }
            }
        }
        
        // Initialize real-time update manager
        const realtimeManager = new RealtimeUpdateManager();
        
        // Start updates when page loads
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                realtimeManager.start();
            }, 2000); // Wait 2 seconds after page load
        });
        
        // Track active section for smart updates
        const originalNav = window.nav;
        window.nav = function(sectionId, btn) {
            originalNav(sectionId, btn);
            realtimeManager.setActiveSection(sectionId);
        };
        
        // Add manual refresh button functionality
        function addRefreshButton() {
            const headerRight = document.querySelector('.header-right');
            if (headerRight && !document.getElementById('manual-refresh-btn')) {
                const refreshBtn = document.createElement('button');
                refreshBtn.id = 'manual-refresh-btn';
                refreshBtn.className = 'icon-btn';
                refreshBtn.innerHTML = '<i class="fas fa-sync-alt"></i>';
                refreshBtn.title = 'Refresh Data';
                refreshBtn.style.cssText = 'margin-right: 10px; transition: transform 0.3s;';
                refreshBtn.onclick = function() {
                    this.style.transform = 'rotate(360deg)';
                    setTimeout(() => { this.style.transform = 'rotate(0deg)'; }, 300);
                    realtimeManager.manualRefresh();
                    toast('🔄 Refreshing data...');
                };
                
                headerRight.insertBefore(refreshBtn, headerRight.firstChild);
            }
        }
        
        // Add refresh button after page loads
        setTimeout(addRefreshButton, 1000);
        
        // Add CSS animation
        const style = document.createElement('style');
        style.textContent = `
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
            
            .realtime-toast {
                animation: slideInRight 0.3s ease;
            }
            
            .pulse-animation {
                animation: pulse 2s infinite;
            }
            
            @keyframes pulse {
                0%, 100% { opacity: 1; }
                50% { opacity: 0.5; }
            }
        `;
        document.head.appendChild(style);
        
        // Pause updates when page is hidden, resume when visible
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                console.log('📱 Page hidden - pausing updates');
                realtimeManager.stop();
            } else {
                console.log('📱 Page visible - resuming updates');
                realtimeManager.start();
            }
        });
        
        console.log('✨ Real-time update system loaded');
    </script>

</body>
</html>
