<?php
require_once 'role_session_manager.php';

// Start secretary session
RoleSessionManager::start('secretary');

header('Content-Type: application/json');

// Verify secretary session (allow if authenticated or if auto_login is set)
if (!RoleSessionManager::isAuthenticated() || RoleSessionManager::getRole() !== 'secretary') {
    // Check if there's a secretary session cookie
    if (!isset($_COOKIE['ATMICX_SEC_SESSION'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
}

// Database connection
function getDBConnection() {
    $host = 'localhost';
    $dbname = 'atmicxdb';
    $username_db = 'root';
    $password_db = '';
    
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username_db, $password_db);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        return null;
    }
}

$action = $_GET['action'] ?? '';

try {
    $pdo = getDBConnection();
    
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }
    
    switch ($action) {
        case 'get_counts':
            // Get counts for various sections
            $counts = [];
            
            // 1. Maintenance requests pending secretary review
            try {
                $stmt = $pdo->query("
                    SELECT COUNT(*) as count 
                    FROM service 
                    WHERE (type = 'maintenance' OR type IS NULL)
                    AND Status = 'pending_secretary_review'
                ");
                $counts['pending_maintenance'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];
            } catch (Exception $e) {
                error_log("Error counting pending maintenance: " . $e->getMessage());
                $counts['pending_maintenance'] = 0;
            }
            
            // 2. Pending manager approval
            try {
                $stmt = $pdo->query("
                    SELECT COUNT(*) as count 
                    FROM service 
                    WHERE Status = 'pending_manager_approval'
                ");
                $counts['pending_manager'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];
            } catch (Exception $e) {
                error_log("Error counting pending manager: " . $e->getMessage());
                $counts['pending_manager'] = 0;
            }
            
            // 3. New sales inquiries (last 24 hours)
            try {
                $stmt = $pdo->query("
                    SELECT COUNT(*) as count 
                    FROM quotation 
                    WHERE Date >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                ");
                $counts['new_sales'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];
            } catch (Exception $e) {
                error_log("Error counting new sales: " . $e->getMessage());
                $counts['new_sales'] = 0;
            }
            
            // 4. Unread notifications (if notification table exists)
            try {
                $stmt = $pdo->query("SHOW TABLES LIKE 'notification'");
                if ($stmt->rowCount() > 0) {
                    $stmt = $pdo->query("
                        SELECT COUNT(*) as count 
                        FROM notification 
                        WHERE is_read = 0
                    ");
                    $counts['unread_notifications'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];
                } else {
                    $counts['unread_notifications'] = 0;
                }
            } catch (Exception $e) {
                error_log("Error counting notifications: " . $e->getMessage());
                $counts['unread_notifications'] = 0;
            }
            
            // 5. Low stock items
            try {
                $stmt = $pdo->query("
                    SELECT COUNT(*) as count 
                    FROM inventory 
                    WHERE Quantity < Reorder_Level
                ");
                $counts['low_stock'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];
            } catch (Exception $e) {
                error_log("Error counting low stock: " . $e->getMessage());
                $counts['low_stock'] = 0;
            }
            
            // 6. Pending payments
            try {
                $stmt = $pdo->query("
                    SELECT COUNT(*) as count 
                    FROM payment 
                    WHERE Status = 'pending'
                ");
                $counts['pending_payments'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];
            } catch (Exception $e) {
                error_log("Error counting pending payments: " . $e->getMessage());
                $counts['pending_payments'] = 0;
            }
            
            // Add timestamp for tracking
            $counts['timestamp'] = time();
            
            echo json_encode([
                'success' => true,
                'counts' => $counts
            ]);
            break;
            
        case 'check_updates':
            // Check if there are new updates since last check
            $lastCheck = isset($_GET['last_check']) ? (int)$_GET['last_check'] : 0;
            
            $updates = [
                'has_updates' => false,
                'sections' => []
            ];
            
            // Check for new maintenance requests
            if ($lastCheck > 0) {
                try {
                    $stmt = $pdo->prepare("
                        SELECT COUNT(*) as count 
                        FROM service 
                        WHERE (type = 'maintenance' OR type IS NULL)
                        AND date_requested >= FROM_UNIXTIME(?)
                    ");
                    $stmt->execute([$lastCheck]);
                    $newMaintenance = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];
                    
                    if ($newMaintenance > 0) {
                        $updates['has_updates'] = true;
                        $updates['sections'][] = 'maintenance';
                        $updates['new_maintenance_count'] = $newMaintenance;
                    }
                } catch (Exception $e) {
                    error_log("Error checking new maintenance: " . $e->getMessage());
                }
                
                // Check for new sales inquiries
                try {
                    $stmt = $pdo->prepare("
                        SELECT COUNT(*) as count 
                        FROM quotation 
                        WHERE Date >= FROM_UNIXTIME(?)
                    ");
                    $stmt->execute([$lastCheck]);
                    $newSales = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];
                    
                    if ($newSales > 0) {
                        $updates['has_updates'] = true;
                        $updates['sections'][] = 'sales';
                        $updates['new_sales_count'] = $newSales;
                    }
                } catch (Exception $e) {
                    error_log("Error checking new sales: " . $e->getMessage());
                }
                
                // Check for payment status changes
                try {
                    $stmt = $pdo->prepare("
                        SELECT COUNT(*) as count 
                        FROM payment 
                        WHERE payment_date >= FROM_UNIXTIME(?)
                    ");
                    $stmt->execute([$lastCheck]);
                    $newPayments = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];
                    
                    if ($newPayments > 0) {
                        $updates['has_updates'] = true;
                        $updates['sections'][] = 'payments';
                        $updates['new_payment_count'] = $newPayments;
                    }
                } catch (Exception $e) {
                    error_log("Error checking new payments: " . $e->getMessage());
                }
            }
            
            $updates['timestamp'] = time();
            
            echo json_encode([
                'success' => true,
                'updates' => $updates
            ]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
    
} catch (Exception $e) {
    error_log("Secretary realtime API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error occurred'
    ]);
}
