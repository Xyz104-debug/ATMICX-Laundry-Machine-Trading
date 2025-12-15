<?php
session_start();

// Prevent browser caching
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
header("Pragma: no-cache"); // HTTP 1.0.
header("Expires: 0"); // Proxies.

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'manager') {
    header('Location: atmicxLOGIN.html');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Dashboard | ATMICX Trading</title>
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
        
        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 24px;
        }
        /* Logo Image Style */
        .brand img {
            height: 35px;
            width: auto;
            object-fit: contain;
        }
        .brand h2 { font-size: 20px; font-weight: 700; letter-spacing: -0.02em; margin: 0; }
        .brand span { color: var(--gold); }

        .user-profile-box {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: var(--radius-md);
            padding: 12px 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: -8px;
            transition: var(--transition);
            cursor: pointer;
            position: relative;
        }
        .user-profile-box:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.2);
            transform: translateY(-1px);
        }
        .avatar {
            width: 38px; height: 38px;
            border-radius: 8px;
            background: linear-gradient(135deg, var(--gold), #fcd34d);
            color: var(--navy-dark);
            display: flex; align-items: center; justify-content: center;
            font-weight: 800; font-size: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.2);
        }
        .user-info { flex: 1; }
        .user-info .name { font-size: 14px; font-weight: 600; color: white; line-height: 1.2; display: flex; align-items: center; gap: 6px; }
        .user-info .role { font-size: 11px; color: #94a3b8; font-weight: 500; margin-top: 2px; }
        .settings-icon { color: #94a3b8; font-size: 14px; transition: 0.2s; }
        .user-profile-box:hover .settings-icon { color: var(--gold); transform: rotate(90deg); }

        .nav-links { list-style: none; flex: 1; }
        .nav-item { margin-bottom: 6px; }
        
        .nav-btn {
            width: 100%;
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 16px;
            background: transparent;
            border: none;
            color: #94a3b8;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            border-radius: var(--radius-md);
            transition: var(--transition);
        }

        .nav-btn:hover { background: rgba(255, 255, 255, 0.05); color: var(--white); transform: translateX(4px); }
        .nav-btn.active { 
            background: linear-gradient(90deg, var(--gold), #b49226); 
            color: var(--navy-dark); 
            font-weight: 700;
            box-shadow: 0 4px 12px rgba(212, 175, 55, 0.2);
        }
        .nav-btn.active:hover { transform: none; }

        .sidebar-footer {
            margin-top: auto;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            justify-content: center;
        }
        .logout-btn {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 12px;
            background: rgba(239, 68, 68, 0.15);
            color: #fca5a5;
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-radius: var(--radius-md);
            cursor: pointer;
            font-weight: 600;
            transition: var(--transition);
        }
        .logout-btn:hover { background: #ef4444; color: white; border-color: #ef4444; }

        /* --- MAIN CONTENT --- */
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            position: relative;
        }

        .header {
            height: 80px;
            background: var(--bg-body);
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 40px;
            flex-shrink: 0;
            border-bottom: 1px solid #e2e8f0;
        }
        .header h1 { font-size: 24px; font-weight: 800; color: var(--navy-dark); letter-spacing: -0.02em; }
        
        .header-actions { display: flex; gap: 16px; align-items: center; position: relative; }
        .search-box {
            background: var(--white);
            padding: 10px 20px;
            border-radius: 30px;
            display: flex; align-items: center; gap: 10px;
            width: 280px;
            border: 1px solid #e2e8f0;
            transition: var(--transition);
        }
        .search-box:focus-within { border-color: var(--gold); box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.1); }
        .search-box input { border: none; outline: none; width: 100%; font-size: 13px; color: var(--text-main); }

        .notif-btn {
            position: relative;
            width: 40px; height: 40px;
            border-radius: 50%;
            background: white;
            border: 1px solid #e2e8f0;
            cursor: pointer;
            display: flex; justify-content: center; align-items: center;
            color: var(--navy-dark);
            transition: 0.2s;
        }
        .notif-btn:hover { background: #f1f5f9; }
        .notif-badge {
            position: absolute; top: -2px; right: -2px;
            width: 10px; height: 10px;
            background: #ef4444; border: 2px solid #fff;
            border-radius: 50%;
        }
        .notif-dropdown {
            position: absolute;
            top: 50px; right: 0;
            width: 320px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            border: 1px solid #e2e8f0;
            z-index: 100;
            display: none;
            animation: slideDown 0.2s ease;
        }
        .notif-dropdown.show { display: block; }
        @keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        
        .notif-header { padding: 15px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; }
        .notif-header h4 { font-size: 14px; font-weight: 700; margin: 0; }
        .notif-header button { font-size: 11px; color: var(--text-muted); background: none; border: none; cursor: pointer; text-decoration: underline; }
        
        .notif-body { max-height: 300px; overflow-y: auto; }
        .notif-item { padding: 15px; border-bottom: 1px solid #f1f5f9; display: flex; gap: 12px; align-items: start; transition: 0.2s; }
        .notif-item:hover { background: #f8fafc; }
        .notif-item:last-child { border-bottom: none; }
        .notif-icon { width: 32px; height: 32px; background: #e0f2fe; color: #0369a1; border-radius: 50%; display: flex; justify-content: center; align-items: center; flex-shrink: 0; font-size: 12px; }
        .notif-content p { font-size: 13px; margin: 0 0 4px 0; color: var(--text-main); font-weight: 500; }
        .notif-content span { font-size: 11px; color: var(--text-muted); }

        .dashboard-view {
            flex: 1;
            overflow-y: auto;
            padding: 32px 40px;
            scrollbar-width: thin;
        }

        .section { display: none; opacity: 0; transform: translateY(10px); transition: all 0.4s ease; }
        .section.active { display: block; opacity: 1; transform: translateY(0); }

        /* --- METRICS & PANELS --- */
        .metrics-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-bottom: 32px; }
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
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(255,255,255,0.1);
        }
        .metric-card:hover { transform: translateY(-8px); box-shadow: 0 20px 35px -5px rgba(0, 0, 0, 0.25); }
        .metric-card i.bg-icon { position: absolute; right: -15px; top: 10px; font-size: 110px; opacity: 0.15; transform: rotate(-15deg); transition: all 0.3s ease; }
        .metric-card:hover i.bg-icon { transform: rotate(0deg) scale(1.1); opacity: 0.2; }

        .metric-header { display: flex; align-items: center; gap: 10px; z-index: 2; margin-bottom: 5px; }
        .metric-icon-small { width: 32px; height: 32px; background: rgba(255,255,255,0.2); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 14px; backdrop-filter: blur(4px); }
        .metric-label { font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; opacity: 0.9; }
        .metric-value { font-size: 34px; font-weight: 800; z-index: 2; margin: 10px 0; letter-spacing: -1px; }
        .metric-footer { z-index: 2; display: flex; align-items: center; gap: 8px; font-size: 12px; font-weight: 500; opacity: 0.9; background: rgba(0,0,0,0.1); width: fit-content; padding: 4px 10px; border-radius: 20px; }

        .card-green { background: linear-gradient(135deg, #059669 0%, #10b981 100%); }
        .card-orange { background: linear-gradient(135deg, #d97706 0%, #f59e0b 100%); }
        .card-red { background: linear-gradient(135deg, #b91c1c 0%, #ef4444 100%); }
        .card-blue { background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%); }

        .panel { background: var(--white); border-radius: var(--radius-lg); padding: 32px; box-shadow: var(--shadow-card); height: 100%; border: 1px solid #e2e8f0; display: flex; flex-direction: column; }
        .content-grid-2 { display: grid; grid-template-columns: 2fr 1fr; gap: 24px; min-height: 400px; }
        .panel-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .panel-title { font-size: 18px; font-weight: 700; color: var(--navy-dark); }

        .inventory-list { border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; }
        .inventory-row { display: flex; justify-content: space-between; align-items: center; padding: 16px 20px; border-bottom: 1px solid #e2e8f0; }
        .inventory-row:last-child { border-bottom: none; }
        .inventory-row:nth-child(odd) { background-color: var(--white); }
        .inventory-row:nth-child(even) { background-color: #f8fafc; }
        .inventory-row h4 { font-size: 14px; font-weight: 700; margin-bottom: 4px; color: var(--navy-dark); }
        .inventory-row p { font-size: 13px; color: var(--text-muted); margin: 0; }

        .audit-list { display: flex; flex-direction: column; gap: 0; overflow-y: auto; flex: 1; border: 1px solid #e2e8f0; border-radius: 12px; }
        .audit-item { display: flex; gap: 16px; padding: 16px 20px; border-bottom: 1px solid #f1f5f9; align-items: center; transition: 0.2s; }
        .audit-item:hover { background: #f8fafc; }
        .audit-item:last-child { border-bottom: none; }
        .time-stamp { font-size: 12px; color: var(--text-muted); margin-top: 4px; display: block; }

        /* --- IMPROVED SALES CHART UI --- */
        .chart-container {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            height: 300px; /* Increased Height */
            padding: 30px 20px 10px;
            background-color: #ffffff;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            position: relative;
            /* Dashed Grid Lines */
            background-image: linear-gradient(to bottom, #f1f5f9 1px, transparent 1px);
            background-size: 100% 50px;
        }

        .chart-column {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-end;
            width: 100%;
            height: 100%;
            margin: 0 10px;
            position: relative;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .chart-column:hover { transform: translateY(-5px); }

        /* Fancy Gradient Bars */
        .bar {
            width: 40px;
            border-radius: 8px 8px 0 0;
            background: linear-gradient(180deg, var(--navy-light) 0%, var(--navy-dark) 100%);
            opacity: 0.8;
            transition: all 0.3s ease;
            position: relative;
            box-shadow: 0 4px 10px rgba(15, 23, 42, 0.2);
        }
        
        .chart-column:hover .bar { opacity: 1; box-shadow: 0 8px 20px rgba(15, 23, 42, 0.3); }
        
        /* Highlight Bar (Gold) */
        .chart-column.highlight .bar {
            background: linear-gradient(180deg, var(--gold) 0%, #b49226 100%);
            opacity: 1;
            box-shadow: 0 4px 15px rgba(212, 175, 55, 0.4);
        }

        .bar-value {
            font-size: 12px;
            font-weight: 700;
            color: var(--navy-dark);
            margin-bottom: 8px;
            background: white;
            padding: 4px 8px;
            border-radius: 6px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            border: 1px solid #f1f5f9;
        }

        .bar-label {
            margin-top: 12px;
            font-size: 12px;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
        }

        .top-tech-list { display: flex; flex-direction: column; gap: 15px; }
        .tech-card { display: flex; align-items: center; gap: 15px; padding: 15px; background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; transition: 0.2s; }
        .tech-card:hover { border-color: var(--gold); box-shadow: var(--shadow-card); }
        .tech-info { flex: 1; }
        .tech-name { font-weight: 700; color: var(--navy-dark); font-size: 14px; }
        .tech-role { font-size: 12px; color: var(--text-muted); }
        .tech-stat { text-align: right; }
        .tech-rev { font-weight: 700; color: var(--success-text); font-size: 14px; }
        .tech-count { font-size: 11px; color: var(--text-muted); }

        /* KPI Cards in Reports */
        .kpi-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 24px; }
        
        .status-badge { padding: 6px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; display: inline-flex; align-items: center; gap: 6px; }
        .status-ok { background: var(--success-bg); color: var(--success-text); }
        .status-warn { background: var(--warning-bg); color: var(--warning-text); }
        .status-err { background: var(--danger-bg); color: var(--danger-text); }

        .btn { padding: 10px 18px; border-radius: 8px; border: none; font-weight: 600; cursor: pointer; transition: 0.2s; font-size: 13px; display: inline-flex; align-items: center; justify-content: center; gap: 8px; }
        .btn-primary { background: var(--navy-dark); color: white; width: 100%; }
        .btn-primary:hover { background: var(--navy-light); transform: translateY(-2px); }
        .btn-danger { background: white; border: 1px solid var(--danger-text); color: var(--danger-text); width: 60px; }
        .btn-danger:hover { background: var(--danger-bg); }
        .btn-outline { background: white; border: 1px solid #cbd5e1; color: var(--text-main); width: auto; }
        .btn-ghost { background: transparent; color: var(--text-muted); font-size: 12px; text-decoration: underline; cursor: pointer; border: none; width: auto; padding: 0; }

        .tabs { display: flex; gap: 10px; margin-bottom: 30px; border-bottom: 2px solid #e2e8f0; }
        .tab { padding: 12px 24px; cursor: pointer; color: var(--text-muted); font-weight: 600; border-bottom: 2px solid transparent; margin-bottom: -2px; transition: 0.2s; }
        .tab:hover { color: var(--navy-dark); }
        .tab.active { color: var(--navy-dark); border-bottom-color: var(--gold); }

        /* Toast */
        .toast { position: fixed; bottom: 30px; right: 30px; background: var(--navy-dark); color: white; padding: 16px 24px; border-radius: 12px; display: flex; align-items: center; gap: 12px; transform: translateY(100px); transition: 0.3s; opacity: 0; box-shadow: 0 10px 30px rgba(0,0,0,0.2); z-index: 1000; }
        .toast.show { transform: translateY(0); opacity: 1; }

        .txn-list { display: flex; flex-direction: column; gap: 15px; }
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

        table { width: 100%; border-collapse: separate; border-spacing: 0 10px; }
        th { text-align: left; padding: 0 16px 8px; font-size: 12px; color: var(--text-muted); text-transform: uppercase; font-weight: 700; }
        td { padding: 16px; background: var(--white); font-size: 14px; border-top: 1px solid #f1f5f9; border-bottom: 1px solid #f1f5f9; }
        td:first-child { border-left: 1px solid #f1f5f9; border-top-left-radius: 12px; border-bottom-left-radius: 12px; }
        td:last-child { border-right: 1px solid #f1f5f9; border-top-right-radius: 12px; border-bottom-right-radius: 12px; }
        tbody tr:nth-child(odd) td { background-color: var(--white); }
        tbody tr:nth-child(even) td { background-color: #f8fafc; }

        .progress-bar-bg { height: 6px; background: #f1f5f9; border-radius: 10px; margin-top: 12px; overflow: hidden; }
        .progress-bar-fill { height: 100%; border-radius: 10px; }

        .cat-item { margin-bottom: 16px; }
        .cat-header { display: flex; justify-content: space-between; font-size: 13px; font-weight: 600; color: var(--text-main); margin-bottom: 6px; }
        .cat-bar-bg { height: 8px; background: #f1f5f9; border-radius: 4px; overflow: hidden; }
        .cat-bar-fill { height: 100%; border-radius: 4px; }

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
                <div><h2>ATMICX <span>Admin</span></h2></div>
            </div>
            
            <div class="user-profile-box" onclick="toast('Opening Profile Settings...')">
                <div class="avatar"><?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?></div>
                <div class="user-info">
                    <div class="name"><?php echo $_SESSION['username']; ?> <i class="fas fa-check-circle" style="color:var(--gold); font-size:12px;"></i></div>
                    <div class="role"><?php echo ucfirst($_SESSION['role']); ?></div>
                </div>
                <i class="fas fa-cog settings-icon"></i>
            </div>
        </div>

        <ul class="nav-links">
            <li class="nav-item"><button class="nav-btn active" onclick="nav('dashboard', this)"><i class="fas fa-th-large"></i> Dashboard</button></li>
            <li class="nav-item"><button class="nav-btn" onclick="nav('payment', this)"><i class="fas fa-file-invoice-dollar"></i> Payment Verify <span style="margin-left:auto; background:var(--gold); color:var(--navy-dark); font-size:10px; padding:2px 6px; border-radius:4px;">2</span></button></li>
            <li class="nav-item"><button class="nav-btn" onclick="nav('inventory', this)"><i class="fas fa-boxes"></i> Inventory</button></li>
            
            <li class="nav-item"><button class="nav-btn" onclick="nav('reports', this)"><i class="fas fa-chart-pie"></i> Sales Reports</button></li>
            <li class="nav-item"><button class="nav-btn" onclick="nav('users', this)"><i class="fas fa-users-cog"></i> Users & Staff</button></li>
        </ul>

        <div class="sidebar-footer">
            <button class="logout-btn" onclick="openLogoutModal()">
                <i class="fas fa-sign-out-alt"></i> Logout
            </button>
        </div>
    </aside>

    <main class="main-content">
        <header class="header">
            <h1 id="page-title">Executive Dashboard</h1>
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
                            <div class="notif-icon"><i class="fas fa-info"></i></div>
                            <div class="notif-content">
                                <p>New Payment from John Doe</p>
                                <span>2 mins ago</span>
                            </div>
                        </div>
                        <div class="notif-item">
                            <div class="notif-icon" style="background:#fee2e2; color:#991b1b;"><i class="fas fa-exclamation-triangle"></i></div>
                            <div class="notif-content">
                                <p>Critical Stock: Haier Pro XL</p>
                                <span>1 hour ago</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <div class="dashboard-view">

            <div id="dashboard" class="section active">
                <div class="metrics-grid" style="grid-template-columns: repeat(2, 1fr);">
                    <div class="metric-card card-green">
                        <i class="fas fa-coins bg-icon"></i>
                        <div class="metric-header">
                            <div class="metric-icon-small"><i class="fas fa-coins"></i></div>
                            <span class="metric-label">Total Revenue</span>
                        </div>
                        <h3 class="metric-value">₱1,850,000</h3>
                        <div class="metric-footer"><i class="fas fa-arrow-up"></i> 12% vs last month</div>
                    </div>
                    <div class="metric-card card-orange">
                        <i class="fas fa-clock bg-icon"></i>
                        <div class="metric-header">
                            <div class="metric-icon-small"><i class="fas fa-hourglass-half"></i></div>
                            <span class="metric-label">Pending Verify</span>
                        </div>
                        <h3 class="metric-value" id="pending-amt">₱338,500</h3>
                        <div class="metric-footer">2 Transactions waiting</div>
                    </div>
                    
                </div>
                <div class="content-grid-2">
                    <div class="panel">
                        <div class="panel-header"><span class="panel-title">Inventory Alerts</span></div>
                        <div class="inventory-list">
                            <div class="inventory-row">
                                <div style="display:flex; gap:15px; align-items:center;">
                                    <div style="width:8px; height:8px; background:var(--danger-text); border-radius:50%;"></div>
                                    <div><h4>Restock Needed: "Haier Pro XL"</h4><p>Cebu Branch is currently at <strong>0 units</strong>.</p></div>
                                </div>
                                <span class="status-badge status-err">Critical</span>
                            </div>
                            <div class="inventory-row">
                                <div style="display:flex; gap:15px; align-items:center;">
                                    <div style="width:8px; height:8px; background:var(--warning-text); border-radius:50%;"></div>
                                    <div><h4>Low Stock: "PCB Boards"</h4><p>Bacolod Branch is down to <strong>2 units</strong>.</p></div>
                                </div>
                                <span class="status-badge status-warn">Warning</span>
                            </div>
                            <div class="inventory-row">
                                <div style="display:flex; gap:15px; align-items:center;">
                                    <div style="width:8px; height:8px; background:var(--navy-light); border-radius:50%;"></div>
                                    <div><h4>Transfer Pending: "Drain Hoses"</h4><p>10 Units in transit to Cebu.</p></div>
                                </div>
                                <span class="status-badge status-ok">In Transit</span>
                            </div>
                        </div>
                    </div>
                    <div class="panel">
                        <div class="panel-header"><span class="panel-title">Recent Activity</span></div>
                        <div class="audit-list">
                            <div class="audit-item">
                                <div class="avatar" style="background:#e0f2fe; color:#0369a1;"><i class="fas fa-user-edit"></i></div>
                                <div><div style="font-weight:600; font-size:14px;">Secretary Jane voided Quote #105</div><span class="time-stamp">10 mins ago</span></div>
                            </div>
                            <div class="audit-item">
                                <div class="avatar" style="background:#dcfce7; color:#166534;"><i class="fas fa-truck"></i></div>
                                <div><div style="font-weight:600; font-size:14px;">Stock Transfer to Bacolod</div><span class="time-stamp">2 hours ago</span></div>
                            </div>
                            <div class="audit-item">
                                <div class="avatar" style="background:#f1f5f9; color:#64748b;"><i class="fas fa-sign-in-alt"></i></div>
                                <div><div style="font-weight:600; font-size:14px;">Admin Login Detected</div><span class="time-stamp">4 hours ago</span></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="payment" class="section">
                <div class="metrics-grid-4">
                    <div class="metric-card card-orange" style="min-height: 140px;">
                        <i class="fas fa-hourglass-half bg-icon"></i>
                        <div class="metric-header"><div class="metric-icon-small"><i class="fas fa-clock"></i></div><span class="metric-label">Pending</span></div>
                        <h3 class="metric-value">2</h3>
                    </div>
                    <div class="metric-card card-green" style="min-height: 140px;">
                        <i class="fas fa-check-circle bg-icon"></i>
                        <div class="metric-header"><div class="metric-icon-small"><i class="fas fa-check"></i></div><span class="metric-label">Verified</span></div>
                        <h3 class="metric-value">15</h3>
                    </div>
                    <div class="metric-card card-red" style="min-height: 140px;">
                        <i class="fas fa-times-circle bg-icon"></i>
                        <div class="metric-header"><div class="metric-icon-small"><i class="fas fa-times"></i></div><span class="metric-label">Rejected</span></div>
                        <h3 class="metric-value">1</h3>
                    </div>
                    <div class="metric-card card-blue" style="min-height: 140px;">
                        <i class="fas fa-wallet bg-icon"></i>
                        <div class="metric-header"><div class="metric-icon-small"><i class="fas fa-coins"></i></div><span class="metric-label">Volume</span></div>
                        <h3 class="metric-value">₱338k</h3>
                    </div>
                </div>

                <div class="panel-header">
                    <span class="panel-title">Transaction Inbox</span>
                    <button class="btn btn-outline" onclick="toast('Filters applied')"><i class="fas fa-filter"></i> Filter List</button>
                </div>

                <div class="txn-list">
                    <div class="txn-card" id="txn-1">
                        <div class="txn-icon-col"><i class="fas fa-box-open"></i></div>
                        <div class="txn-client-col">
                            <span class="txn-ref">#QT-205</span>
                            <div class="txn-client-name">John Doe</div>
                            <div class="txn-client-loc"><i class="fas fa-map-marker-alt"></i> Bacolod City</div>
                            <div style="margin-top:4px; font-size:11px; color:var(--text-muted);">Package Sale (2-Set)</div>
                        </div>
                        <div class="txn-finance-col">
                            <div class="finance-row"><span>Package Base Price</span><span>₱470,000</span></div>
                            <div class="finance-row"><span>Handling / Delivery Fee</span><span>+ ₱10,000</span></div>
                            <div class="finance-row total"><span>Total Project Cost</span><span>₱480,000</span></div>
                            <div style="margin-top:10px;">
                                <div style="display:flex; justify-content:space-between; font-size:10px; font-weight:700; color:var(--info-text);">
                                    <span>AMOUNT PAID (70% DP)</span>
                                    <span style="font-size:14px;">₱336,000.00</span>
                                </div>
                                <div class="progress-bar-bg"><div class="progress-bar-fill" style="width:70%; background:var(--info-text);"></div></div>
                            </div>
                        </div>
                        <div class="txn-action-col">
                            <button class="btn btn-outline" style="width:100%; border-color:#e2e8f0; color:var(--text-muted);" onclick="toast('Viewing Proof...')"><i class="fas fa-paperclip"></i> View Proof</button>
                            <button class="btn btn-primary" style="width:100%;" onclick="verifyTxn('txn-1', 336000)">Verify</button>
                            <button class="btn btn-danger" style="width:100%;" onclick="rejectTxn('txn-1')">Reject</button>
                        </div>
                    </div>

                    <div class="txn-card" id="txn-2">
                        <div class="txn-icon-col" style="background:#fff7ed;">
                            <i class="fas fa-wrench" style="color: var(--warning-text);"></i>
                        </div>
                        <div class="txn-client-col">
                            <span class="txn-ref" style="background:var(--warning-bg); color:var(--warning-text);">#SVC-99</span>
                            <div class="txn-client-name">Maria Cruz</div>
                            <div class="txn-client-loc"><i class="fas fa-map-marker-alt"></i> Talisay City</div>
                            <div style="margin-top:4px; font-size:11px; color:var(--text-muted);">Repair Service (Washer)</div>
                        </div>
                        <div class="txn-finance-col">
                            <div class="finance-row"><span>Labor Cost</span><span>₱1,000</span></div>
                            <div class="finance-row"><span>Parts (Motor) & Logistics</span><span>+ ₱1,500</span></div>
                            <div class="finance-row total"><span>Total Charged</span><span>₱2,500</span></div>
                            <div style="margin-top:10px;">
                                <div style="display:flex; justify-content:space-between; font-size:10px; font-weight:700; color:var(--success-text);">
                                    <span>AMOUNT PAID (FULL)</span>
                                    <span style="font-size:14px;">₱2,500.00</span>
                                </div>
                                <div class="progress-bar-bg"><div class="progress-bar-fill" style="width:100%; background:var(--success-text);"></div></div>
                            </div>
                        </div>
                        <div class="txn-action-col">
                            <button class="btn btn-outline" style="width:100%; border-color:#e2e8f0; color:var(--text-muted);" onclick="toast('Cash Payment Confirmed')"><i class="fas fa-money-bill-wave"></i> Cash</button>
                            <button class="btn btn-primary" style="width:100%;" onclick="verifyTxn('txn-2', 2500)">Verify</button>
                            <button class="btn btn-danger" style="width:100%;" onclick="rejectTxn('txn-2')">Reject</button>
                        </div>
                    </div>
                    
                    <div id="empty-state" style="display:none; padding:60px; text-align:center; color:var(--text-muted);">
                        <i class="fas fa-check-circle" style="font-size:64px; color:var(--success-bg); margin-bottom:24px;"></i>
                        <h3 style="color:var(--navy-dark);">All Caught Up!</h3>
                        <p>No pending transactions to verify.</p>
                    </div>

                    <div class="panel" style="margin-top: 32px;">
                        <div class="panel-header">
                            <span class="panel-title">Transaction History</span>
                            <div class="search-box" style="width: 200px;">
                                <i class="fas fa-search" style="color: #94a3b8;"></i>
                                <input type="text" placeholder="Search history...">
                            </div>
                        </div>
                        <table>
                            <thead>
                                <tr>
                                    <th>Ref ID</th>
                                    <th>Client</th>
                                    <th>Amount</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="txn-history-body">
                                <tr>
                                    <td><strong>#QT-104</strong></td>
                                    <td>Michael Scott</td>
                                    <td>₱12,500</td>
                                    <td>Dec 10, 2025</td>
                                    <td><span class="status-badge status-err">Rejected</span></td>
                                    <td><button class="btn-ghost">Details</button></td>
                                </tr>
                                <tr>
                                    <td><strong>#SVC-88</strong></td>
                                    <td>Pam Beesly</td>
                                    <td>₱4,200</td>
                                    <td>Dec 09, 2025</td>
                                    <td><span class="status-badge status-ok">Verified</span></td>
                                    <td><button class="btn-ghost">Details</button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div id="inventory" class="section">
                <div class="panel">
                    <div class="tabs">
                        <div class="tab active" onclick="switchTab('tab-master', this)">Master Stock (Manila)</div>
                        <div class="tab" onclick="switchTab('tab-transfer', this)">Branch Transfer</div>
                        <div class="tab" onclick="switchTab('tab-logs', this)">Deductions Log</div>
                    </div>

                    <div id="tab-master" class="tab-content">
                        <div class="content-grid-2">
                            <div style="background:#f8fafc; padding:24px; border-radius:12px;">
                                <h4 style="margin-bottom:20px; color:var(--navy-dark);">Receive Shipment</h4>
                                <div class="form-group"><label class="form-label">Item Name</label><input type="text" class="form-control" value="Haier Pro XL" id="inv-item"></div>
                                <div class="form-group"><label class="form-label">Quantity Received</label><input type="number" class="form-control" value="50" id="inv-qty"></div>
                                <div class="form-group"><label class="form-label">Destination Warehouse</label><input type="text" class="form-control" value="Manila HQ" readonly style="background:#e2e8f0;"></div>
                                <button class="btn btn-primary" onclick="receiveStock()"><i class="fas fa-plus"></i> Add to Inventory</button>
                            </div>
                            <div>
                                <h4 style="margin-bottom:20px; color:var(--navy-dark);">Current HQ Stock</h4>
                                <table>
                                    <thead><tr><th>Item</th><th>Qty</th></tr></thead>
                                    <tbody id="stock-table">
                                        <tr><td>Haier Pro XL</td><td><span class="status-badge status-ok" id="stock-xl">50</span></td></tr>
                                        <tr><td>PCB Boards</td><td><span class="status-badge status-ok">120</span></td></tr>
                                        <tr><td>Drain Hoses</td><td><span class="status-badge status-warn">15</span></td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div id="tab-transfer" class="tab-content" style="display:none;">
                        <div class="content-grid-2">
                            <div style="background:#f8fafc; padding:24px; border-radius:12px;">
                                <h4 style="margin-bottom:20px; color:var(--navy-dark);">Initiate Transfer</h4>
                                <div class="form-group"><label class="form-label">Destination Branch</label>
                                    <select class="form-control"><option>Bacolod Branch</option><option>Cebu Branch</option></select>
                                </div>
                                <div class="form-group"><label class="form-label">Select Item</label>
                                    <select class="form-control"><option>Haier Pro XL</option><option>PCB Boards</option></select>
                                </div>
                                <div class="form-group"><label class="form-label">Quantity to Transfer</label><input type="number" class="form-control" placeholder="0"></div>
                                <button class="btn btn-primary" onclick="toast('Transfer Request Created')">Create Transfer Order</button>
                            </div>
                            <div style="background:#f8fafc; padding:24px; border-radius:12px; height:100%; display: flex; flex-direction: column;">
                                <h4 style="margin-bottom:20px; color:var(--navy-dark);">Recent Transfers</h4>
                                <div class="audit-list" style="border: none; background: transparent; flex: 1;">
                                    <div class="audit-item" style="background: white; border-radius: 8px; margin-bottom: 8px; padding: 12px;">
                                        <div class="avatar" style="width:32px; height:32px; font-size:12px; background:var(--navy-dark); color:white;">TO</div>
                                        <div><div style="font-weight:600; font-size:13px;">10x Motors</div><span style="font-size:11px; color:var(--text-muted);">To Cebu • Pending</span></div>
                                    </div>
                                    <div class="audit-item" style="background: white; border-radius: 8px; padding: 12px;">
                                        <div class="avatar" style="width:32px; height:32px; font-size:12px; background:var(--success-bg); color:var(--success-text);">OK</div>
                                        <div><div style="font-weight:600; font-size:13px;">5x Washers</div><span style="font-size:11px; color:var(--text-muted);">To Bacolod • Received</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="tab-logs" class="tab-content" style="display:none;">
                        <table>
                            <thead><tr><th>Log ID</th><th>Item</th><th>Action / Reason</th><th>Date</th></tr></thead>
                            <tbody>
                                <tr><td>#991</td><td>1x Heating Element</td><td><span class="status-badge status-ok">Paid Repair</span> Repair #SVC-505</td><td>Dec 12, 09:30 AM</td></tr>
                                <tr><td>#990</td><td>2x Haier Pro XL</td><td><span class="status-badge status-ok">Sale</span> Order #QT-201</td><td>Dec 11, 04:00 PM</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div id="audit" class="section">
                </div>

            <div id="reports" class="section">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                    <h2 style="font-size: 20px; font-weight: 700; color: var(--navy-dark); margin: 0;">Performance Analytics</h2>
                    <div style="background: white; border: 1px solid #e2e8f0; padding: 8px 16px; border-radius: 8px; font-size: 13px; color: var(--text-main); cursor: pointer;">
                        <i class="far fa-calendar-alt"></i> This Month <i class="fas fa-chevron-down" style="font-size: 10px; margin-left: 8px;"></i>
                    </div>
                </div>

                <div class="kpi-row">
                    <div class="metric-card card-green" style="height: 160px;">
                        <i class="fas fa-chart-line bg-icon"></i>
                        <div class="metric-header">
                            <div class="metric-icon-small"><i class="fas fa-coins"></i></div>
                            <span class="metric-label">Total Revenue</span>
                        </div>
                        <h3 class="metric-value">₱2,100,000</h3>
                        <div class="metric-footer"><i class="fas fa-arrow-up"></i> 12% Growth</div>
                    </div>

                    <div class="metric-card card-blue" style="height: 160px;">
                        <i class="fas fa-tasks bg-icon"></i>
                        <div class="metric-header">
                            <div class="metric-icon-small"><i class="fas fa-check-double"></i></div>
                            <span class="metric-label">Completed Jobs</span>
                        </div>
                        <h3 class="metric-value">45</h3>
                        <div class="metric-footer"><i class="fas fa-arrow-up"></i> 5% vs last mo</div>
                    </div>

                    <div class="metric-card card-orange" style="height: 160px;">
                        <i class="fas fa-receipt bg-icon"></i>
                        <div class="metric-header">
                            <div class="metric-icon-small"><i class="fas fa-tag"></i></div>
                            <span class="metric-label">Avg Ticket</span>
                        </div>
                        <h3 class="metric-value">₱46,500</h3>
                        <div class="metric-footer"><i class="fas fa-arrow-down"></i> 2% Decrease</div>
                    </div>

                    <div class="metric-card card-red" style="height: 160px;">
                        <i class="fas fa-users bg-icon"></i>
                        <div class="metric-header">
                            <div class="metric-icon-small"><i class="fas fa-user-friends"></i></div>
                            <span class="metric-label">Active Techs</span>
                        </div>
                        <h3 class="metric-value">8 / 10</h3>
                        <div class="metric-footer">2 on Leave</div>
                    </div>
                </div>

                <div class="content-grid-2">
                    <div class="panel">
                        <div class="panel-header"><span class="panel-title">Revenue Trend</span></div>
                        <div class="chart-container">
                            <div class="chart-column">
                                <div class="bar-value">₱800k</div>
                                <div class="bar" style="height: 40%;"></div>
                                <div class="bar-label">Jun</div>
                            </div>
                            <div class="chart-column">
                                <div class="bar-value">₱1.1M</div>
                                <div class="bar" style="height: 55%;"></div>
                                <div class="bar-label">Jul</div>
                            </div>
                            <div class="chart-column">
                                <div class="bar-value">₱900k</div>
                                <div class="bar" style="height: 45%;"></div>
                                <div class="bar-label">Aug</div>
                            </div>
                            <div class="chart-column">
                                <div class="bar-value">₱1.4M</div>
                                <div class="bar" style="height: 70%;"></div>
                                <div class="bar-label">Sep</div>
                            </div>
                            <div class="chart-column">
                                <div class="bar-value">₱1.2M</div>
                                <div class="bar" style="height: 60%;"></div>
                                <div class="bar-label">Oct</div>
                            </div>
                            <div class="chart-column highlight">
                                <div class="bar-value">₱2.1M</div>
                                <div class="bar" style="height: 90%;"></div>
                                <div class="bar-label">Nov</div>
                            </div>
                        </div>
                    </div>

                    <div class="panel">
                        <div class="panel-header"><span class="panel-title">Service Mix</span></div>
                        <div style="margin-bottom: 30px;">
                            <div class="cat-item">
                                <div class="cat-header"><span>Package Sales</span><span>80%</span></div>
                                <div class="cat-bar-bg"><div class="cat-bar-fill" style="width: 80%; background: var(--info-text);"></div></div>
                            </div>
                            <div class="cat-item">
                                <div class="cat-header"><span>Repair Services</span><span>15%</span></div>
                                <div class="cat-bar-bg"><div class="cat-bar-fill" style="width: 15%; background: var(--warning-text);"></div></div>
                            </div>
                            <div class="cat-item">
                                <div class="cat-header"><span>Handling Fees</span><span>5%</span></div>
                                <div class="cat-bar-bg"><div class="cat-bar-fill" style="width: 5%; background: var(--success-text);"></div></div>
                            </div>
                        </div>

                        <div class="panel-header"><span class="panel-title">Top Techs</span></div>
                        <div class="top-tech-list">
                            <div class="tech-card">
                                <div class="avatar" style="width: 32px; height: 32px; background: var(--navy-dark); color: white; font-size: 11px;">1</div>
                                <div class="tech-info">
                                    <div class="tech-name">Team Alpha</div>
                                    <div class="tech-role">Setup Crew</div>
                                </div>
                                <div class="tech-stat">
                                    <div class="tech-rev">₱2.5M</div>
                                    <div class="tech-count">5 Jobs</div>
                                </div>
                            </div>
                            <div class="tech-card">
                                <div class="avatar" style="width: 32px; height: 32px; background: #e2e8f0; color: var(--text-main); font-size: 11px;">2</div>
                                <div class="tech-info">
                                    <div class="tech-name">Team Beta</div>
                                    <div class="tech-role">Repair Unit</div>
                                </div>
                                <div class="tech-stat">
                                    <div class="tech-rev">₱50k</div>
                                    <div class="tech-count">20 Jobs</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="users" class="section">
                <div class="panel">
                    <div class="panel-header"><span class="panel-title">Staff Accounts</span><button class="btn btn-primary" style="width:auto;" onclick="addUser()"><i class="fas fa-plus"></i> Add User</button></div>
                    <table>
                        <thead><tr><th>Name</th><th>Role</th><th>Status</th><th>Actions</th></tr></thead>
                        <tbody>
                            <tr id="user-row-jane">
                                <td><strong>Jane Doe</strong><br><span id="email-jane" style="font-size:12px; color:var(--text-muted);">jane@atmicx.com</span></td>
                                <td id="role-jane">Secretary</td>
                                <td id="status-jane"><span class="status-badge status-ok">Active</span></td>
                                <td>
                                    <button class="btn btn-outline" style="padding:6px 12px;" onclick="editUser('Jane Doe', 'jane', 'jane@atmicx.com')">Edit</button>
                                    <button class="btn btn-danger" style="padding:6px 12px; margin-left: 8px;" onclick="deleteUser('jane')">Delete</button>
                                </td>
                            </tr>
                            <tr id="user-row-alpha">
                                <td><strong>Team Alpha</strong><br><span id="email-alpha" style="font-size:12px; color:var(--text-muted);">tech.alpha@atmicx.com</span></td>
                                <td id="role-alpha">Technician</td>
                                <td id="status-alpha"><span class="status-badge status-ok">Active</span></td>
                                <td>
                                    <button class="btn btn-outline" style="padding:6px 12px;" onclick="editUser('Team Alpha', 'alpha', 'tech.alpha@atmicx.com')">Edit</button>
                                    <button class="btn btn-danger" style="padding:6px 12px; margin-left: 8px;" onclick="deleteUser('alpha')">Delete</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>

    <div id="add-user-modal-overlay" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="panel-title">Add New Staff Account</h4>
                <button class="modal-close-btn" onclick="closeModal('add-user-modal-overlay')"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <form id="add-user-form">
                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="add-user-name" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="add-user-email" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="add-user-role">Role</label>
                        <select class="form-control" id="add-user-role">
                            <option value="Manager">Manager</option>
                            <option value="Technician" selected>Technician</option>
                            <option value="Secretary">Secretary</option>
                            <option value="Customer">Customer (View-only)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="add-user-status">Initial Status</label>
                        <select class="form-control" id="add-user-status">
                            <option value="Active">Active</option>
                            <option value="On Leave">On Leave</option>
                            <option value="Terminated">Terminated</option>
                        </select>
                    </div>

                    <div style="margin-top: 30px; display: flex; gap: 10px;">
                        <button type="button" class="btn btn-primary" onclick="saveNewUser()"><i class="fas fa-user-plus"></i> Create Account</button>
                        <button type="button" class="btn btn-outline" onclick="closeModal('add-user-modal-overlay')">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div id="edit-user-modal-overlay" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="panel-title">Edit User: <span id="modal-user-name"></span></h4>
                <button class="modal-close-btn" onclick="closeModal('edit-user-modal-overlay')"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <form id="edit-user-form">
                    <input type="hidden" id="modal-user-id-prefix" value="">
                    
                    <div class="form-group">
                        <label class="form-label">User Email</label>
                        <input type="email" class="form-control" id="modal-user-email" readonly style="background:#e2e8f0;">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="modal-user-role">Role</label>
                        <select class="form-control" id="modal-user-role">
                            <option value="Manager">Manager</option>
                            <option value="Technician">Technician</option>
                            <option value="Secretary">Secretary</option>
                            <option value="Customer">Customer (View-only)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="modal-user-status">Account Status</label>
                        <select class="form-control" id="modal-user-status">
                            <option value="Active">Active</option>
                            <option value="On Leave">On Leave</option>
                            <option value="Terminated">Terminated</option>
                        </select>
                    </div>

                    <div style="margin-top: 30px; display: flex; gap: 10px;">
                        <button type="button" class="btn btn-primary" onclick="saveUserChanges()"><i class="fas fa-save"></i> Save Changes</button>
                        <button type="button" class="btn btn-outline" onclick="closeModal('edit-user-modal-overlay')">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="delete-confirm-modal-overlay" class="modal-overlay">
        <div class="modal-content delete-confirm">
            <div style="padding: 32px; display: flex; flex-direction: column; align-items: center; text-align: center;">
                <i class="fas fa-exclamation-triangle" style="font-size: 48px; color: var(--danger-text); margin-bottom: 20px;"></i>
                <h4 style="font-size: 18px; font-weight: 700; color: var(--navy-dark); margin-bottom: 10px;">Confirm Deletion</h4>
                <p style="font-size: 14px; color: var(--text-main); margin-bottom: 20px;">Are you sure you want to permanently delete user <strong id="delete-user-name"></strong>?</p>
                <p style="font-size: 12px; color: var(--danger-text); margin-bottom: 30px; font-weight: 600;">This action cannot be undone.</p>
                
                <input type="hidden" id="delete-user-id-prefix" value="">

                <div style="display: flex; gap: 10px; width: 100%;">
                    <button type="button" class="btn btn-danger" style="flex: 1;" id="confirm-delete-btn"><i class="fas fa-trash-alt"></i> Delete Account</button>
                    <button type="button" class="btn btn-outline" style="flex: 1;" onclick="closeModal('delete-confirm-modal-overlay')">Cancel</button>
                </div>
            </div>
        </div>
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
    
    <div id="toast" class="toast">
        <i class="fas fa-check-circle" style="color: #4ade80;"></i>
        <span id="toast-msg">Action Successful</span>
    </div>

    <script>
        function nav(id, btn) {
            document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
            document.getElementById(id).classList.add('active');
            document.querySelectorAll('.nav-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            const titles = {'dashboard':'Executive Dashboard', 'payment':'Payment Verification', 'inventory':'Inventory Distribution', 'reports':'Sales Reports', 'users':'User Management'};
            document.getElementById('page-title').innerText = titles[id];
        }

        function switchTab(id, btn) {
            document.querySelectorAll('.tab-content').forEach(c => c.style.display = 'none');
            document.getElementById(id).style.display = 'block';
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            btn.classList.add('active');
        }

        // Notification Logic
        function toggleNotif() {
            const dropdown = document.getElementById('notif-dropdown');
            dropdown.classList.toggle('show');
        }
        function clearNotif() {
            document.querySelector('.notif-body').innerHTML = '<div style="padding:20px; text-align:center; color:#94a3b8; font-size:13px;">No new notifications</div>';
            document.getElementById('notif-dot').style.display = 'none';
        }

        // SEARCH FUNCTIONALITY
        document.getElementById('global-search').addEventListener('keyup', (e) => {
            if(e.key === 'Enter') {
                toast(`Searching database for "${e.target.value}"...`);
            }
        });

        // --- MODAL HELPERS ---
        function closeModal(id) {
            document.getElementById(id).classList.remove('show');
        }

        // --- USER ADD/EDIT/DELETE FUNCTIONALITY ---

        function addUser() {
            // Open the new Add User modal
            document.getElementById('add-user-form').reset();
            document.getElementById('add-user-modal-overlay').classList.add('show');
        }
        
        // NEW FUNCTION: Handles saving the new user
        function saveNewUser() {
            const name = document.getElementById('add-user-name').value;
            const email = document.getElementById('add-user-email').value;
            const role = document.getElementById('add-user-role').value;
            const status = document.getElementById('add-user-status').value;

            // Simple validation
            if (!name || !email) {
                alert("Full Name and Email are required.");
                return;
            }

            // Simple ID generation for demo purposes
            const idPrefix = email.split('@')[0].replace(/[^a-z0-9]/gi, '').toLowerCase().substring(0, 10) + Math.floor(Math.random() * 100);
            
            let statusClass = 'status-ok';
            if (status === 'On Leave') {
                statusClass = 'status-warn';
            } else if (status === 'Terminated') {
                statusClass = 'status-err';
            }

            const newRow = `
                <tr id="user-row-${idPrefix}">
                    <td><strong>${name}</strong><br><span id="email-${idPrefix}" style="font-size:12px; color:var(--text-muted);">${email}</span></td>
                    <td id="role-${idPrefix}">${role}</td>
                    <td id="status-${idPrefix}"><span class="status-badge ${statusClass}">${status}</span></td>
                    <td>
                        <button class="btn btn-outline" style="padding:6px 12px;" onclick="editUser('${name}', '${idPrefix}', '${email}')">Edit</button>
                        <button class="btn btn-danger" style="padding:6px 12px; margin-left: 8px;" onclick="deleteUser('${idPrefix}')">Delete</button>
                    </td>
                </tr>
            `;

            document.querySelector('#users table tbody').insertAdjacentHTML('beforeend', newRow);
            
            closeModal('add-user-modal-overlay');
            toast(`New user ${name} (${role}) created successfully.`);
        }

        function editUser(name, idPrefix, email) {
            const currentRole = document.getElementById(`role-${idPrefix}`).innerText;
            // Get the current status text from the badge (e.g., "Active")
            const currentStatusElement = document.getElementById(`status-${idPrefix}`).querySelector('.status-badge');
            const currentStatus = currentStatusElement ? currentStatusElement.innerText : 'Active'; // Default to Active

            // Populate modal fields
            document.getElementById('modal-user-name').innerText = name;
            document.getElementById('modal-user-id-prefix').value = idPrefix;
            document.getElementById('modal-user-email').value = email;
            document.getElementById('modal-user-role').value = currentRole;
            document.getElementById('modal-user-status').value = currentStatus;

            // Show the modal
            document.getElementById('edit-user-modal-overlay').classList.add('show');
        }

        function saveUserChanges() {
            const idPrefix = document.getElementById('modal-user-id-prefix').value;
            const userName = document.getElementById('modal-user-name').innerText;
            const newRole = document.getElementById('modal-user-role').value;
            const newStatus = document.getElementById('modal-user-status').value;

            // 1. Update the Role
            document.getElementById(`role-${idPrefix}`).innerText = newRole;

            // 2. Update the Status Badge
            let statusClass = 'status-ok';
            if (newStatus === 'On Leave') {
                statusClass = 'status-warn';
            } else if (newStatus === 'Terminated') {
                statusClass = 'status-err';
            }

            const statusCell = document.getElementById(`status-${idPrefix}`);
            statusCell.innerHTML = `<span class="status-badge ${statusClass}">${newStatus}</span>`;

            // 3. Close modal and show toast
            closeModal('edit-user-modal-overlay');
            toast(`User ${userName} updated to Role: ${newRole} & Status: ${newStatus}`);
        }

        // MODIFIED FUNCTION: Opens the custom delete confirmation modal
        function deleteUser(idPrefix) {
            const row = document.getElementById(`user-row-${idPrefix}`);
            if (!row) return;

            const userName = row.querySelector('strong').innerText;
            
            // Pass the necessary data to the modal
            document.getElementById('delete-user-name').innerText = userName;
            document.getElementById('delete-user-id-prefix').value = idPrefix;

            // Set the dynamic onclick for the confirmation button
            document.getElementById('confirm-delete-btn').setAttribute('onclick', `confirmDeleteUser('${idPrefix}')`);

            document.getElementById('delete-confirm-modal-overlay').classList.add('show');
        }

        // NEW FUNCTION: Handles the actual deletion after confirmation
        function confirmDeleteUser(idPrefix) {
            const row = document.getElementById(`user-row-${idPrefix}`);
            const userName = document.getElementById('delete-user-name').innerText; // Get the name from the modal

            if (row) {
                row.remove();
                closeModal('delete-confirm-modal-overlay');
                toast(`User ${userName} has been permanently deleted.`);
            }
        }
        // --- END USER ADD/EDIT/DELETE FUNCTIONALITY ---


        // INVENTORY LOGIC
        function receiveStock() {
            let item = document.getElementById('inv-item').value;
            let qty = parseInt(document.getElementById('inv-qty').value);
            
            if(item && qty) {
                // Visualize update
                let current = parseInt(document.getElementById('stock-xl').innerText);
                document.getElementById('stock-xl').innerText = current + qty;
                toast(`Successfully received ${qty} units of ${item}.`);
            } else {
                alert("Please enter valid item and quantity.");
            }
        }

        // Helper to add transaction to history table
        function addToHistory(ref, client, amount, status) {
            const tbody = document.getElementById('txn-history-body');
            const row = document.createElement('tr');
            
            const badgeClass = status === 'Verified' ? 'status-ok' : 'status-err';
            
            row.innerHTML = `
                <td><strong>${ref}</strong></td>
                <td>${client}</td>
                <td>₱${amount.toLocaleString()}</td>
                <td>Just Now</td>
                <td><span class="status-badge ${badgeClass}">${status}</span></td>
                <td><button class="btn-ghost">Details</button></td>
            `;
            
            // Insert at the top
            tbody.insertBefore(row, tbody.firstChild);
        }

        function verifyTxn(id, amount) {
            const card = document.getElementById(id);
            // Get client name and ref for history
            const clientName = card.querySelector('.txn-client-name').innerText;
            const refId = card.querySelector('.txn-ref').innerText;

            card.style.opacity = '0';
            card.style.transform = 'translateX(50px)';
            
            setTimeout(() => {
                card.style.display = 'none';
                
                // Add to history
                addToHistory(refId, clientName, amount, 'Verified');

                const remaining = document.querySelectorAll('.txn-card:not([style*="display: none"])').length - 1; // Exclude the one we're processing
                const total = document.querySelectorAll('.txn-card').length;
                if(remaining === 0) { 
                    document.getElementById('empty-state').style.display = 'block';
                    document.getElementById('pending-amt').innerText = "₱0.00";
                }
                toast(`Payment Verified: ₱${amount.toLocaleString()}`);
            }, 300);
        }

        function rejectTxn(id) {
            if(confirm("Reject this payment?")) {
                const card = document.getElementById(id);
                // Get client name and ref for history
                const clientName = card.querySelector('.txn-client-name').innerText;
                const refId = card.querySelector('.txn-ref').innerText;
                
                // Get amount text and parse it (removing non-numeric chars)
                const amountText = card.querySelector('.txn-finance-col .progress-bar-bg').previousElementSibling.querySelector('span:last-child').innerText;
                const amount = parseFloat(amountText.replace(/[^0-9.-]+/g,""));

                card.style.display = 'none';
                
                // Add to history
                addToHistory(refId, clientName, amount, 'Rejected');
                
                toast('Transaction Rejected');
            }
        }

        function toast(msg) {
            const t = document.getElementById('toast');
            document.getElementById('toast-msg').innerText = msg;
            t.classList.add('show');
            setTimeout(() => t.classList.remove('show'), 3000);
        }

        function openLogoutModal() {
            document.getElementById('logout-modal-overlay').classList.add('show');
        }

        function closeLogoutModal() {
            document.getElementById('logout-modal-overlay').classList.remove('show');
        }
    </script>
</body>
</html>
