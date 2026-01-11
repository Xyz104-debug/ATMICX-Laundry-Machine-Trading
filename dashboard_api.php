<?php
require_once 'logger.php';
require_once 'role_session_manager.php';

// Try to detect which role session exists
$active_role = null;
$session_names = [
    'manager' => 'ATMICX_MGR_SESSION',
    'secretary' => 'ATMICX_SEC_SESSION',
    'client' => 'ATMICX_CLIENT_SESSION'
];

foreach ($session_names as $role => $session_name) {
    if (isset($_COOKIE[$session_name])) {
        $active_role = $role;
        break;
    }
}

// If no role session found, try default session
if (!$active_role) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
} else {
    // Start the appropriate role session
    RoleSessionManager::start($active_role);
}

header('Content-Type: application/json');

// Database connection
$host = 'localhost';
$dbname = 'atmicxdb';
$username_db = 'root';
$password_db = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username_db, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    ATMICXLogger::logError('Dashboard API database connection failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? 'get_dashboard_stats';

switch ($action) {
    case 'get_dashboard_stats':
        try {
            $stats = [];
            
            // Service Statistics
            $service_stats = $pdo->query("
                SELECT 
                    COUNT(*) as total_services,
                    COUNT(CASE WHEN status = 'submitted' THEN 1 END) as pending_services,
                    COUNT(CASE WHEN status IN ('reviewed', 'approved') THEN 1 END) as active_services,
                    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_services,
                    COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as today_services
                FROM service
            ")->fetch(PDO::FETCH_ASSOC);
            
            // Payment Statistics
            $payment_stats = $pdo->query("
                SELECT 
                    COUNT(*) as total_payments,
                    COUNT(CASE WHEN Status = 'pending' THEN 1 END) as pending_payments,
                    COUNT(CASE WHEN Status = 'approved' THEN 1 END) as approved_payments,
                    COALESCE(SUM(CASE WHEN Status = 'pending' THEN Amount_Paid END), 0) as pending_amount,
                    COALESCE(SUM(CASE WHEN Status = 'approved' THEN Amount_Paid END), 0) as approved_amount
                FROM payment
            ")->fetch(PDO::FETCH_ASSOC);
            
            // Quotation Statistics
            $quotation_stats = $pdo->query("
                SELECT 
                    COUNT(*) as total_quotations,
                    COUNT(CASE WHEN Status = 'Awaiting Manager Approval' THEN 1 END) as pending_approval,
                    COUNT(CASE WHEN Status = 'Accepted' THEN 1 END) as accepted_quotes,
                    COALESCE(SUM(CASE WHEN Status = 'Accepted' THEN Amount END), 0) as total_value,
                    COUNT(CASE WHEN DATE(Date_Issued) = CURDATE() THEN 1 END) as today_quotes
                FROM quotation
            ")->fetch(PDO::FETCH_ASSOC);
            
            // Client Statistics
            $client_stats = $pdo->query("
                SELECT 
                    COUNT(*) as total_clients,
                    COUNT(CASE WHEN DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as new_clients_30d
                FROM client
            ")->fetch(PDO::FETCH_ASSOC);
            
            // Recent Activity
            $recent_activity = $pdo->query("
                (SELECT 'service' as type, Service_ID as id, CONCAT('New service request: ', type) as description, created_at as timestamp
                 FROM service ORDER BY created_at DESC LIMIT 5)
                UNION ALL
                (SELECT 'payment' as type, Payment_ID as id, CONCAT('Payment received: ₱', Amount_Paid) as description, payment_date as timestamp
                 FROM payment ORDER BY payment_date DESC LIMIT 5)
                UNION ALL
                (SELECT 'quotation' as type, Quotation_ID as id, CONCAT('Quotation issued: ₱', Amount) as description, Date_Issued as timestamp
                 FROM quotation ORDER BY Date_Issued DESC LIMIT 5)
                ORDER BY timestamp DESC LIMIT 10
            ")->fetchAll(PDO::FETCH_ASSOC);
            
            $stats = [
                'services' => $service_stats,
                'payments' => $payment_stats,
                'quotations' => $quotation_stats,
                'clients' => $client_stats,
                'recent_activity' => $recent_activity,
                'last_updated' => date('Y-m-d H:i:s')
            ];
            
            ATMICXLogger::logAPI('get_dashboard_stats', true);
            echo json_encode(['success' => true, 'stats' => $stats]);
            
        } catch (Exception $e) {
            ATMICXLogger::logError('Dashboard stats error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to get dashboard statistics']);
        }
        break;
        
    case 'get_sales_reports':
        try {
            $data = [];
            
            // Total Revenue - Sum of all verified/paid/completed quotations
            $revenue_query = $pdo->query("
                SELECT COALESCE(SUM(Amount), 0) as total_revenue
                FROM quotation
                WHERE Status IN ('Verified', 'Paid', 'Completed')
            ")->fetch(PDO::FETCH_ASSOC);
            $data['total_revenue'] = $revenue_query['total_revenue'];
            
            // Completed Jobs - Count of completed services
            $jobs_query = $pdo->query("
                SELECT COUNT(*) as completed_jobs
                FROM service
                WHERE Status = 'Completed'
            ")->fetch(PDO::FETCH_ASSOC);
            $data['completed_jobs'] = $jobs_query['completed_jobs'];
            
            // Average Ticket - Average quotation amount
            $avg_query = $pdo->query("
                SELECT AVG(Amount) as avg_ticket
                FROM quotation
                WHERE Status IN ('Accepted', 'Payment Submitted', 'Verified', 'Paid', 'Completed')
            ")->fetch(PDO::FETCH_ASSOC);
            $data['avg_ticket'] = $avg_query['avg_ticket'];
            
            // Active Techs/Users - Count of active staff
            $users_query = $pdo->query("
                SELECT 
                    COUNT(*) as total_users,
                    COUNT(CASE WHEN Status = 'Active' THEN 1 END) as active_users
                FROM user
                WHERE Role IN ('secretary', 'technician')
            ")->fetch(PDO::FETCH_ASSOC);
            $data['total_users'] = $users_query['total_users'];
            $data['active_users'] = $users_query['active_users'];
            
            // Monthly Revenue Trend (last 6 months)
            $trend_query = $pdo->query("
                SELECT 
                    DATE_FORMAT(Date_Issued, '%b') as month,
                    DATE_FORMAT(Date_Issued, '%Y-%m') as month_key,
                    SUM(Amount) as revenue
                FROM quotation
                WHERE Status IN ('Verified', 'Paid', 'Completed')
                    AND Date_Issued >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                GROUP BY month_key, month
                ORDER BY month_key ASC
            ")->fetchAll(PDO::FETCH_ASSOC);
            $data['revenue_trend'] = $trend_query;
            
            // Service Mix - Percentage breakdown
            $mix_query = $pdo->query("
                SELECT 
                    SUM(CASE WHEN Package LIKE '%Package%' OR Package LIKE '%Set%' THEN Amount ELSE 0 END) as package_sales,
                    SUM(CASE WHEN Package LIKE '%Repair%' THEN Amount ELSE 0 END) as repair_services,
                    SUM(Handling_Fee) as handling_fees,
                    SUM(Amount) as total_amount
                FROM quotation
                WHERE Status IN ('Accepted', 'Payment Submitted', 'Verified', 'Paid', 'Completed')
            ")->fetch(PDO::FETCH_ASSOC);
            
            $total = $mix_query['total_amount'] > 0 ? $mix_query['total_amount'] : 1;
            $data['service_mix'] = [
                'package_sales' => round(($mix_query['package_sales'] / $total) * 100, 1),
                'repair_services' => round(($mix_query['repair_services'] / $total) * 100, 1),
                'handling_fees' => round(($mix_query['handling_fees'] / $total) * 100, 1)
            ];
            
            // Top Performers - Secretaries/Users by quotation value
            $top_users = $pdo->query("
                SELECT 
                    u.Name,
                    u.Role,
                    COUNT(q.Quotation_ID) as job_count,
                    SUM(q.Amount) as total_revenue
                FROM quotation q
                LEFT JOIN user u ON q.User_ID = u.User_ID
                WHERE q.Status IN ('Verified', 'Paid', 'Completed')
                    AND u.User_ID IS NOT NULL
                GROUP BY u.User_ID, u.Name, u.Role
                ORDER BY total_revenue DESC
                LIMIT 5
            ")->fetchAll(PDO::FETCH_ASSOC);
            $data['top_performers'] = $top_users;
            
            ATMICXLogger::logAPI('get_sales_reports', true);
            echo json_encode(['success' => true, 'data' => $data]);
            
        } catch (Exception $e) {
            ATMICXLogger::logError('Sales reports error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to get sales reports: ' . $e->getMessage()]);
        }
        break;
        
    case 'get_system_health':
        try {
            $health = [];
            
            // Database health
            $db_health = $pdo->query("SELECT 1")->fetchColumn();
            $health['database'] = $db_health ? 'healthy' : 'error';
            
            // File system health
            $upload_dirs = ['uploads/payment_proofs/', 'uploads/service_documents/', 'logs/system/'];
            $health['file_system'] = 'healthy';
            foreach ($upload_dirs as $dir) {
                if (!is_dir($dir) || !is_writable($dir)) {
                    $health['file_system'] = 'warning';
                    break;
                }
            }
            
            // Recent errors
            $error_logs = ATMICXLogger::getRecentLogs('errors', 10);
            $health['recent_errors'] = count($error_logs);
            $health['error_level'] = count($error_logs) > 5 ? 'high' : (count($error_logs) > 0 ? 'low' : 'none');
            
            // Performance metrics
            $health['memory_usage'] = memory_get_usage(true);
            $health['peak_memory'] = memory_get_peak_usage(true);
            $health['execution_time'] = microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"];
            
            echo json_encode(['success' => true, 'health' => $health]);
            
        } catch (Exception $e) {
            ATMICXLogger::logError('System health check error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'System health check failed']);
        }
        break;
        
    case 'get_performance_metrics':
        try {
            // Get API response times from logs
            $api_logs = ATMICXLogger::getRecentLogs('api', 50);
            
            // Process logs to extract metrics
            $metrics = [
                'total_api_calls' => count($api_logs),
                'successful_calls' => 0,
                'failed_calls' => 0,
                'average_response_time' => 0
            ];
            
            foreach ($api_logs as $log) {
                if (strpos($log, 'SUCCESS') !== false) {
                    $metrics['successful_calls']++;
                } else if (strpos($log, 'FAILED') !== false) {
                    $metrics['failed_calls']++;
                }
            }
            
            $metrics['success_rate'] = $metrics['total_api_calls'] > 0 
                ? round(($metrics['successful_calls'] / $metrics['total_api_calls']) * 100, 2) 
                : 100;
            
            echo json_encode(['success' => true, 'metrics' => $metrics]);
            
        } catch (Exception $e) {
            ATMICXLogger::logError('Performance metrics error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to get performance metrics']);
        }
        break;
        
    case 'get_dashboard_data':
        try {
            $data = [];
            
            // Total Revenue from verified/paid/completed quotations
            $revenue_query = $pdo->query("
                SELECT COALESCE(SUM(Amount), 0) as total_revenue
                FROM quotation
                WHERE Status IN ('Verified', 'Paid', 'Completed')
            ")->fetch(PDO::FETCH_ASSOC);
            $data['total_revenue'] = $revenue_query['total_revenue'];
            
            // Pending Verification - payments waiting for approval
            $pending_query = $pdo->query("
                SELECT 
                    COALESCE(SUM(q.Amount), 0) as pending_amount,
                    COUNT(*) as pending_count
                FROM quotation q
                WHERE q.Status = 'Payment Submitted'
            ")->fetch(PDO::FETCH_ASSOC);
            $data['pending_amount'] = $pending_query['pending_amount'];
            $data['pending_count'] = $pending_query['pending_count'];
            
            // Inventory Alerts - products with low stock or critical stock
            $inventory_alerts = $pdo->query("
                SELECT 
                    Item_ID,
                    Item_Name,
                    Branch,
                    SUM(Quantity) as total_stock
                FROM inventory
                GROUP BY Item_Name, Branch
                HAVING total_stock <= 5
                ORDER BY total_stock ASC
                LIMIT 10
            ")->fetchAll(PDO::FETCH_ASSOC);
            $data['inventory_alerts'] = $inventory_alerts;
            
            // Recent Activity - latest transactions
            $recent_activity = $pdo->query("
                SELECT 
                    'quotation' as type,
                    Quotation_ID as id,
                    CONCAT(Package, ' - ', Status) as description,
                    Date_Issued as timestamp
                FROM quotation
                ORDER BY Date_Issued DESC
                LIMIT 5
            ")->fetchAll(PDO::FETCH_ASSOC);
            $data['recent_activity'] = $recent_activity;
            
            ATMICXLogger::logAPI('get_dashboard_data', true);
            echo json_encode(['success' => true, 'data' => $data]);
            
        } catch (Exception $e) {
            ATMICXLogger::logError('Dashboard data error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to get dashboard data: ' . $e->getMessage()]);
        }
        break;
    
    case 'get_deduction_logs':
        try {
            $logs = [];
            
            // Get repair deductions from service_item table (actual inventory items used)
            $repair_query = $pdo->query("
                SELECT 
                    'Repair' as action_type,
                    i.Item_Name as item_name,
                    si.Quantity_Used as quantity,
                    CONCAT('Repair #SVC-', LPAD(s.Service_ID, 3, '0')) as reference,
                    COALESCE(s.Service_Date, NOW()) as date
                FROM service_item si
                JOIN inventory i ON si.Item_ID = i.Item_ID
                JOIN service s ON si.Service_ID = s.Service_ID
                WHERE si.Quantity_Used > 0
                ORDER BY s.Service_Date DESC
                LIMIT 30
            ");
            
            while ($row = $repair_query->fetch(PDO::FETCH_ASSOC)) {
                $logs[] = $row;
            }
            
            // Get sales deductions from quotations (inferring common items from package names)
            // Since we don't have quotation_items table, we'll extract item info from approved quotations
            $sales_query = $pdo->query("
                SELECT 
                    'Sale' as action_type,
                    CASE 
                        WHEN Package LIKE '%Haier%' OR Package LIKE '%Pro XL%' THEN 'Haier Pro XL'
                        WHEN Package LIKE '%Washing%' OR Package LIKE '%Washer%' THEN 'Washing Machine'
                        WHEN Package LIKE '%Dryer%' THEN 'Dryer Unit'
                        WHEN Package LIKE '%PCB%' OR Package LIKE '%Board%' THEN 'PCB Boards'
                        WHEN Package LIKE '%Motor%' THEN 'Motors'
                        WHEN Package LIKE '%Heating%' THEN 'Heating Element'
                        ELSE SUBSTRING_INDEX(Package, ' ', 2)
                    END as item_name,
                    CASE 
                        WHEN Package LIKE '%5 Set%' THEN 5
                        WHEN Package LIKE '%2 Set%' OR Package LIKE '%2-Set%' THEN 2
                        WHEN Package LIKE '%3 Set%' THEN 3
                        ELSE 1
                    END as quantity,
                    CONCAT('Order #QT-', LPAD(Quotation_ID, 3, '0')) as reference,
                    COALESCE(Date_Issued, NOW()) as date
                FROM quotation
                WHERE Status IN ('Approved', 'Verified', 'Payment Submitted')
                ORDER BY Date_Issued DESC
                LIMIT 30
            ");
            
            while ($row = $sales_query->fetch(PDO::FETCH_ASSOC)) {
                $logs[] = $row;
            }
            
            // Sort all logs by date descending
            usort($logs, function($a, $b) {
                return strtotime($b['date']) - strtotime($a['date']);
            });
            
            // Limit to 50 most recent
            $logs = array_slice($logs, 0, 50);
            
            ATMICXLogger::logAPI('get_deduction_logs', true);
            echo json_encode(['success' => true, 'logs' => $logs]);
            
        } catch (Exception $e) {
            ATMICXLogger::logError('Deduction logs error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to get deduction logs: ' . $e->getMessage()]);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}
?>