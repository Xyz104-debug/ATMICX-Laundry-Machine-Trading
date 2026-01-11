<?php
require_once 'role_session_manager.php';

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
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'complete_service':
        // Mark service as completed
        if (!RoleSessionManager::isAuthenticated() || !in_array(RoleSessionManager::getRole(), ['manager', 'secretary'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
            exit;
        }
        
        $service_id = $_POST['service_id'] ?? '';
        $completion_notes = $_POST['completion_notes'] ?? '';
        $technician_name = $_POST['technician_name'] ?? '';
        
        try {
            // Update service status to completed
            $sql = "UPDATE service SET 
                    status = 'completed', 
                    completion_notes = ?, 
                    completed_by = ?, 
                    completion_date = NOW(), 
                    updated_at = NOW() 
                    WHERE Service_ID = ?";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$completion_notes, $technician_name, $service_id]);
            
            // Get service details for notification
            $service_sql = "SELECT s.*, c.Name as client_name, c.Contact_Num 
                           FROM service s 
                           LEFT JOIN client c ON s.Client_ID = c.Client_ID 
                           WHERE s.Service_ID = ?";
            $service_stmt = $pdo->prepare($service_sql);
            $service_stmt->execute([$service_id]);
            $service_data = $service_stmt->fetch(PDO::FETCH_ASSOC);
            
            // Create notification for client
            if ($service_data) {
                $notification_title = "Service Completed - " . $service_data['type'];
                $notification_message = "Your {$service_data['type']} service has been completed by {$technician_name}. " . 
                                      ($completion_notes ? "Notes: {$completion_notes}" : "");
                
                $notify_sql = "INSERT INTO secretary_notifications 
                              (title, message, type, target_role, Client_ID, related_table, related_id, created_at, status) 
                              VALUES (?, ?, 'success', 'client', ?, 'service', ?, NOW(), 'unread')";
                $notify_stmt = $pdo->prepare($notify_sql);
                $notify_stmt->execute([$notification_title, $notification_message, $service_data['Client_ID'], $service_id]);
            }
            
            echo json_encode(['success' => true, 'message' => 'Service marked as completed']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Failed to complete service: ' . $e->getMessage()]);
        }
        break;
        
    case 'get_completed_services':
        // Get list of completed services
        $limit = $_GET['limit'] ?? 50;
        $client_id = $_GET['client_id'] ?? null;
        
        try {
            $sql = "SELECT s.*, c.Name as client_name, c.Contact_Num, 
                           q.Amount as service_cost, q.Status as payment_status
                    FROM service s
                    LEFT JOIN client c ON s.Client_ID = c.Client_ID
                    LEFT JOIN quotation q ON s.Service_ID = q.service_request_id
                    WHERE s.status = 'completed'";
            
            $params = [];
            if ($client_id) {
                $sql .= " AND s.Client_ID = ?";
                $params[] = $client_id;
            }
            
            $sql .= " ORDER BY s.completion_date DESC LIMIT ?";
            $params[] = (int)$limit;
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'services' => $services]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Failed to get completed services']);
        }
        break;
        
    case 'get_service_progress':
        // Get service progress statistics
        try {
            $stats_sql = "SELECT 
                         COUNT(CASE WHEN status = 'submitted' THEN 1 END) as pending_review,
                         COUNT(CASE WHEN status = 'reviewed' THEN 1 END) as pending_approval,
                         COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved_waiting,
                         COUNT(CASE WHEN status = 'paid' THEN 1 END) as paid_ready,
                         COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
                         COUNT(*) as total_services
                         FROM service 
                         WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            
            $stmt = $pdo->prepare($stats_sql);
            $stmt->execute();
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'stats' => $stats]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Failed to get service progress']);
        }
        break;
        
    case 'schedule_service':
        // Schedule service with technician
        if (!RoleSessionManager::isAuthenticated() || !in_array(RoleSessionManager::getRole(), ['manager', 'secretary'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
            exit;
        }
        
        $service_id = $_POST['service_id'] ?? '';
        $scheduled_date = $_POST['scheduled_date'] ?? '';
        $scheduled_time = $_POST['scheduled_time'] ?? '';
        $assigned_team = $_POST['assigned_team'] ?? '';
        $scheduling_notes = $_POST['notes'] ?? '';
        
        try {
            $sql = "UPDATE service SET 
                    scheduled_date = ?, 
                    scheduled_time = ?, 
                    assigned_team = ?, 
                    scheduling_notes = ?, 
                    status = 'scheduled',
                    updated_at = NOW() 
                    WHERE Service_ID = ?";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$scheduled_date, $scheduled_time, $assigned_team, $scheduling_notes, $service_id]);
            
            // Get service details for notification
            $service_sql = "SELECT s.*, c.Name as client_name FROM service s LEFT JOIN client c ON s.Client_ID = c.Client_ID WHERE s.Service_ID = ?";
            $service_stmt = $pdo->prepare($service_sql);
            $service_stmt->execute([$service_id]);
            $service_data = $service_stmt->fetch(PDO::FETCH_ASSOC);
            
            // Create notification
            if ($service_data) {
                $notification_title = "Service Scheduled - " . $service_data['type'];
                $notification_message = "Your service has been scheduled for {$scheduled_date} at {$scheduled_time}. Team: {$assigned_team}";
                
                $notify_sql = "INSERT INTO secretary_notifications 
                              (title, message, type, target_role, Client_ID, related_table, related_id, created_at, status) 
                              VALUES (?, ?, 'info', 'client', ?, 'service', ?, NOW(), 'unread')";
                $notify_stmt = $pdo->prepare($notify_sql);
                $notify_stmt->execute([$notification_title, $notification_message, $service_data['Client_ID'], $service_id]);
            }
            
            echo json_encode(['success' => true, 'message' => 'Service scheduled successfully']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Failed to schedule service: ' . $e->getMessage()]);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}
?>