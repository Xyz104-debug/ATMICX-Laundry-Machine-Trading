<?php
/**
 * Client API for Secretary Dashboard
 * Handles client management operations
 */

require_once 'logger.php';
require_once 'role_session_manager.php';

// Start secretary session
RoleSessionManager::start('secretary');

// Check authentication
if (!RoleSessionManager::isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access - Please log in as secretary']);
    exit;
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
    ATMICXLogger::logError('Client API database connection failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'get_all_clients':
        try {
            $search = $_GET['search'] ?? '';
            
            if ($search) {
                $stmt = $pdo->prepare("
                    SELECT 
                        c.Client_ID,
                        c.Name,
                        c.Contact_Num,
                        c.Address,
                        COUNT(DISTINCT q.Quotation_ID) as total_jobs,
                        COALESCE(SUM(CASE WHEN q.Status IN ('Verified', 'Paid', 'Completed') THEN q.Amount ELSE 0 END), 0) as total_revenue,
                        COALESCE(SUM(CASE WHEN q.Status = 'Payment Submitted' THEN q.Amount ELSE 0 END), 0) as pending_payments,
                        COUNT(CASE WHEN s.Status = 'submitted' AND s.Priority = 'urgent' THEN 1 END) as critical_alerts
                    FROM client c
                    LEFT JOIN quotation q ON c.Client_ID = q.Client_ID
                    LEFT JOIN service s ON c.Client_ID = s.Client_ID
                    WHERE c.Name LIKE :search 
                        OR c.Contact_Num LIKE :search 
                        OR c.Address LIKE :search
                    GROUP BY c.Client_ID
                    ORDER BY c.Name ASC
                ");
                $stmt->execute(['search' => "%$search%"]);
            } else {
                $stmt = $pdo->query("
                    SELECT 
                        c.Client_ID,
                        c.Name,
                        c.Contact_Num,
                        c.Address,
                        COUNT(DISTINCT q.Quotation_ID) as total_jobs,
                        COALESCE(SUM(CASE WHEN q.Status IN ('Verified', 'Paid', 'Completed') THEN q.Amount ELSE 0 END), 0) as total_revenue,
                        COALESCE(SUM(CASE WHEN q.Status = 'Payment Submitted' THEN q.Amount ELSE 0 END), 0) as pending_payments,
                        COUNT(CASE WHEN s.Status = 'submitted' AND s.Priority = 'urgent' THEN 1 END) as critical_alerts
                    FROM client c
                    LEFT JOIN quotation q ON c.Client_ID = q.Client_ID
                    LEFT JOIN service s ON c.Client_ID = s.Client_ID
                    GROUP BY c.Client_ID
                    ORDER BY c.Name ASC
                ");
            }
            
            $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            ATMICXLogger::logAPI('get_all_clients', true);
            echo json_encode(['success' => true, 'clients' => $clients, 'count' => count($clients)]);
            
        } catch (Exception $e) {
            ATMICXLogger::logError('Get all clients error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to get clients: ' . $e->getMessage()]);
        }
        break;
        
    case 'get_client_profile':
        try {
            $client_id = $_GET['client_id'] ?? '';
            
            if (!$client_id) {
                echo json_encode(['success' => false, 'message' => 'Client ID is required']);
                break;
            }
            
            // Get client basic info
            $stmt = $pdo->prepare("SELECT * FROM client WHERE Client_ID = :id LIMIT 1");
            $stmt->execute(['id' => $client_id]);
            $client = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$client) {
                echo json_encode(['success' => false, 'message' => 'Client not found']);
                break;
            }
            
            // Get client statistics
            $stats_query = $pdo->prepare("
                SELECT 
                    COUNT(DISTINCT q.Quotation_ID) as total_jobs,
                    COALESCE(SUM(CASE WHEN q.Status IN ('Verified', 'Paid', 'Completed') THEN q.Amount ELSE 0 END), 0) as total_revenue,
                    COALESCE(SUM(CASE WHEN q.Status = 'Payment Submitted' THEN q.Amount ELSE 0 END), 0) as pending_payments,
                    COUNT(CASE WHEN s.Status = 'submitted' AND s.Priority = 'urgent' THEN 1 END) as critical_alerts
                FROM client c
                LEFT JOIN quotation q ON c.Client_ID = q.Client_ID
                LEFT JOIN service s ON c.Client_ID = s.Client_ID
                WHERE c.Client_ID = :id
            ");
            $stats_query->execute(['id' => $client_id]);
            $stats = $stats_query->fetch(PDO::FETCH_ASSOC);
            
            // Get recent service history (quotations)
            $history_query = $pdo->prepare("
                SELECT 
                    q.Quotation_ID,
                    CONCAT('QT-', LPAD(q.Quotation_ID, 4, '0')) as ref,
                    q.Package,
                    q.Amount,
                    q.Status,
                    q.Date_Issued,
                    q.Delivery_Method,
                    CASE 
                        WHEN q.Status = 'Payment Submitted' THEN 'pending'
                        WHEN q.Status IN ('Verified', 'Paid', 'Completed') THEN 'completed'
                        WHEN q.Status = 'Rejected' THEN 'rejected'
                        ELSE 'active'
                    END as status_type
                FROM quotation q
                WHERE q.Client_ID = :id
                ORDER BY q.Date_Issued DESC
                LIMIT 10
            ");
            $history_query->execute(['id' => $client_id]);
            $history = $history_query->fetchAll(PDO::FETCH_ASSOC);
            
            // Get urgent service requests (if any) - simplified
            $urgent_query = $pdo->prepare("
                SELECT 
                    s.Service_ID,
                    s.Status,
                    s.Service_Date,
                    'Urgent Service' as type,
                    'urgent' as priority
                FROM service s
                WHERE s.Client_ID = :id 
                    AND s.Status IN ('pending', 'submitted')
                ORDER BY s.Service_Date DESC
                LIMIT 5
            ");
            $urgent_query->execute(['id' => $client_id]);
            $urgent = $urgent_query->fetchAll(PDO::FETCH_ASSOC);
            
            $response = [
                'success' => true,
                'client' => $client,
                'stats' => $stats,
                'history' => $history,
                'urgent' => $urgent
            ];
            
            ATMICXLogger::logAPI('get_client_profile', true);
            echo json_encode($response);
            
        } catch (Exception $e) {
            ATMICXLogger::logError('Get client profile error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to get client profile: ' . $e->getMessage()]);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}
?>
