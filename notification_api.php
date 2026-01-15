<?php
require_once 'role_session_manager.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

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

$action = $_GET['action'] ?? $_POST['action'] ?? 'get_notifications';

switch ($action) {
    case 'create_notification':
        $title = $_POST['title'] ?? '';
        $message = $_POST['message'] ?? '';
        $type = $_POST['type'] ?? 'info'; // info, success, warning, error
        $target_role = $_POST['target_role'] ?? 'secretary'; // secretary, manager, client
        $target_user_id = $_POST['target_user_id'] ?? null;
        $related_table = $_POST['related_table'] ?? null;
        $related_id = $_POST['related_id'] ?? null;
        
        try {
            $sql = "INSERT INTO secretary_notifications 
                    (title, message, type, target_role, target_user_id, related_table, related_id, created_at, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), 'unread')";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$title, $message, $type, $target_role, $target_user_id, $related_table, $related_id]);
            
            echo json_encode(['success' => true, 'message' => 'Notification created']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Failed to create notification: ' . $e->getMessage()]);
        }
        break;
        
    case 'get_notifications':
        $role = $_GET['role'] ?? 'secretary';
        $user_id = $_GET['user_id'] ?? null;
        $limit = $_GET['limit'] ?? 10;
        
        try {
            $notifications = [];
            
            // Get real-time notifications from database activity
            if ($role === 'manager') {
                // Payment submissions waiting
                $payment_notifs = $pdo->query("
                    SELECT 
                        CONCAT('New payment submission from ', c.Name) as message,
                        'payment' as type,
                        'fa-money-bill-wave' as icon,
                        q.Quotation_ID as related_id,
                        COALESCE(p.Date_Paid, NOW()) as created_at
                    FROM payment p
                    JOIN quotation q ON p.quotation_id = q.Quotation_ID
                    JOIN client c ON q.Client_ID = c.Client_ID
                    WHERE q.Status = 'Payment Submitted'
                    ORDER BY COALESCE(p.Date_Paid, NOW()) DESC
                    LIMIT 3
                ")->fetchAll(PDO::FETCH_ASSOC);
                
                // Quotations awaiting approval
                $quote_notifs = $pdo->query("
                    SELECT 
                        CONCAT('Quotation awaiting approval: ', Package) as message,
                        'quotation' as type,
                        'fa-file-invoice' as icon,
                        Quotation_ID as related_id,
                        Date_Issued as created_at
                    FROM quotation
                    WHERE Status = 'Awaiting Manager Approval'
                    ORDER BY Date_Issued DESC
                    LIMIT 3
                ")->fetchAll(PDO::FETCH_ASSOC);
                
                // Low stock alerts
                $stock_notifs = $pdo->query("
                    SELECT 
                        CONCAT('Low stock alert: ', Item_Name, ' at ', Branch, ' (', Quantity, ' left)') as message,
                        'inventory' as type,
                        'fa-exclamation-triangle' as icon,
                        Item_ID as related_id,
                        NOW() as created_at
                    FROM inventory
                    WHERE Quantity <= 3
                    ORDER BY Quantity ASC
                    LIMIT 3
                ")->fetchAll(PDO::FETCH_ASSOC);
                
                $notifications = array_merge($payment_notifs, $quote_notifs, $stock_notifs);
                
            } elseif ($role === 'secretary') {
                // Pending service requests
                $service_notifs = $pdo->query("
                    SELECT 
                        CONCAT('Service request pending') as message,
                        'service' as type,
                        'fa-tools' as icon,
                        Service_ID as related_id,
                        Service_Date as created_at
                    FROM service
                    WHERE Status = 'pending'
                    ORDER BY Service_Date DESC
                    LIMIT 3
                ")->fetchAll(PDO::FETCH_ASSOC);
                
                // Quotations sent to clients
                $sent_quotes = $pdo->query("
                    SELECT 
                        CONCAT('Quotation accepted: ', Package) as message,
                        'quotation' as type,
                        'fa-check-circle' as icon,
                        Quotation_ID as related_id,
                        Date_Issued as created_at
                    FROM quotation
                    WHERE Status IN ('Accepted')
                    ORDER BY Date_Issued DESC
                    LIMIT 3
                ")->fetchAll(PDO::FETCH_ASSOC);
                
                // Low stock alerts
                $stock_notifs = $pdo->query("
                    SELECT 
                        CONCAT('Low stock: ', Item_Name, ' (', Quantity, ' units)') as message,
                        'inventory' as type,
                        'fa-box-open' as icon,
                        Item_ID as related_id,
                        NOW() as created_at
                    FROM inventory
                    WHERE Quantity <= 5
                    ORDER BY Quantity ASC
                    LIMIT 2
                ")->fetchAll(PDO::FETCH_ASSOC);
                
                $notifications = array_merge($service_notifs, $sent_quotes, $stock_notifs);
            } elseif ($role === 'client') {
                // Get client ID
                $client_id = $_GET['client_id'] ?? $user_id;
                
                if (!$client_id) {
                    echo json_encode(['success' => false, 'message' => 'Client ID required']);
                    exit;
                }
                
                // Quotation status updates
                $quote_notifs = $pdo->prepare("
                    SELECT 
                        CONCAT('Quotation ', 
                            CASE Status
                                WHEN 'Approved' THEN 'approved'
                                WHEN 'Scheduled' THEN 'scheduled for installation'
                                WHEN 'Completed' THEN 'completed'
                                WHEN 'Payment Verified' THEN 'payment verified'
                                WHEN 'Awaiting Manager Approval' THEN 'is being reviewed'
                                ELSE LOWER(Status)
                            END,
                            ': ', Package
                        ) as message,
                        'quotation' as type,
                        CASE Status
                            WHEN 'Approved' THEN 'fa-check-circle'
                            WHEN 'Scheduled' THEN 'fa-calendar-check'
                            WHEN 'Completed' THEN 'fa-check-double'
                            WHEN 'Payment Verified' THEN 'fa-money-check-alt'
                            WHEN 'Awaiting Manager Approval' THEN 'fa-clock'
                            ELSE 'fa-file-invoice'
                        END as icon,
                        Quotation_ID as related_id,
                        Date_Issued as created_at
                    FROM quotation
                    WHERE Client_ID = ?
                    AND (Status IN ('Approved', 'Scheduled', 'Completed', 'Payment Verified', 'Awaiting Manager Approval')
                         OR Date_Issued >= DATE_SUB(NOW(), INTERVAL 7 DAY))
                    ORDER BY Date_Issued DESC
                    LIMIT 5
                ");
                $quote_notifs->execute([$client_id]);
                $quote_results = $quote_notifs->fetchAll(PDO::FETCH_ASSOC);
                
                // Service completion notifications for owned machines
                $service_notifs = $pdo->prepare("
                    SELECT 
                        CONCAT('Service ', LOWER(type), ' ', 
                            CASE Status
                                WHEN 'completed' THEN 'completed'
                                WHEN 'scheduled' THEN 'scheduled'
                                WHEN 'in_progress' THEN 'in progress'
                                ELSE LOWER(Status)
                            END,
                            ' for your ', location) as message,
                        'service' as type,
                        CASE Status
                            WHEN 'completed' THEN 'fa-check-circle'
                            WHEN 'scheduled' THEN 'fa-calendar-alt'
                            WHEN 'in_progress' THEN 'fa-tools'
                            ELSE 'fa-wrench'
                        END as icon,
                        Service_ID as related_id,
                        Service_Date as created_at
                    FROM service
                    WHERE Client_ID = ?
                    AND Status IN ('scheduled', 'completed', 'in_progress')
                    ORDER BY Service_Date DESC
                    LIMIT 3
                ");
                $service_notifs->execute([$client_id]);
                $service_results = $service_notifs->fetchAll(PDO::FETCH_ASSOC);
                
                // Owned machines needing attention
                $machine_notifs = $pdo->prepare("
                    SELECT 
                        CONCAT('Your ', Asset_Type, ' (', Asset_ID, ') has a ', 
                            CASE 
                                WHEN EXISTS (SELECT 1 FROM service WHERE asset_id = a.Asset_ID AND Status = 'scheduled' LIMIT 1) 
                                THEN 'scheduled service'
                                ELSE 'completed service'
                            END
                        ) as message,
                        'machine' as type,
                        'fa-cog' as icon,
                        a.Asset_ID as related_id,
                        s.Service_Date as created_at
                    FROM asset a
                    JOIN service s ON a.Asset_ID = s.asset_id
                    WHERE a.Client_ID = ?
                    AND s.Status IN ('scheduled', 'completed')
                    AND s.Service_Date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                    GROUP BY a.Asset_ID
                    ORDER BY s.Service_Date DESC
                    LIMIT 3
                ");
                $machine_notifs->execute([$client_id]);
                $machine_results = $machine_notifs->fetchAll(PDO::FETCH_ASSOC);
                
                $notifications = array_merge($quote_results, $service_results, $machine_results);
            }
            
            // Sort by timestamp
            usort($notifications, function($a, $b) {
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            });
            
            // Limit
            $notifications = array_slice($notifications, 0, (int)$limit);
            
            // Add time ago
            foreach ($notifications as &$notif) {
                $notif['time_ago'] = timeAgo($notif['created_at']);
                $notif['status'] = 'unread';
            }
            
            echo json_encode(['success' => true, 'notifications' => $notifications, 'count' => count($notifications)]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Failed to get notifications: ' . $e->getMessage()]);
        }
        break;
        
    case 'mark_read':
        $notification_id = $_POST['notification_id'] ?? '';
        
        try {
            $sql = "UPDATE secretary_notifications SET status = 'read' WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$notification_id]);
            
            echo json_encode(['success' => true, 'message' => 'Notification marked as read']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Failed to mark notification as read']);
        }
        break;
        
    case 'get_unread_count':
        $role = $_GET['role'] ?? 'secretary';
        $user_id = $_GET['user_id'] ?? null;
        
        try {
            $count = 0;
            
            if ($role === 'manager') {
                // Count payment submissions + quotations awaiting + low stock
                $result = $pdo->query("
                    SELECT 
                        (SELECT COUNT(*) FROM quotation WHERE Status = 'Payment Submitted') +
                        (SELECT COUNT(*) FROM quotation WHERE Status = 'Awaiting Manager Approval') +
                        (SELECT COUNT(*) FROM inventory WHERE Quantity <= 3)
                    as total_count
                ")->fetch(PDO::FETCH_ASSOC);
                $count = $result['total_count'];
            } elseif ($role === 'secretary') {
                // Count pending services + low stock
                $result = $pdo->query("
                    SELECT 
                        (SELECT COUNT(*) FROM service WHERE Status = 'pending') +
                        (SELECT COUNT(*) FROM inventory WHERE Quantity <= 5)
                    as total_count
                ")->fetch(PDO::FETCH_ASSOC);
                $count = $result['total_count'];
            } elseif ($role === 'client') {
                // Get client ID
                $client_id = $_GET['client_id'] ?? $user_id;
                
                if (!$client_id) {
                    $count = 0;
                } else {
                    // Count quotations and services with updates
                    $stmt = $pdo->prepare("
                        SELECT 
                            (SELECT COUNT(*) FROM quotation 
                             WHERE Client_ID = ? 
                             AND (Status IN ('Approved', 'Scheduled', 'Completed', 'Payment Verified', 'Awaiting Manager Approval')
                                  OR Date_Issued >= DATE_SUB(NOW(), INTERVAL 7 DAY))) +
                            (SELECT COUNT(*) FROM service 
                             WHERE Client_ID = ? 
                             AND Status IN ('scheduled', 'completed', 'in_progress')) +
                            (SELECT COUNT(DISTINCT a.Asset_ID) FROM asset a
                             JOIN service s ON a.Asset_ID = s.asset_id
                             WHERE a.Client_ID = ?
                             AND s.Status IN ('scheduled', 'completed')
                             AND s.Service_Date >= DATE_SUB(NOW(), INTERVAL 30 DAY))
                        as total_count
                    ");
                    $stmt->execute([$client_id, $client_id, $client_id]);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    $count = $result['total_count'];
                }
            }
            
            echo json_encode(['success' => true, 'count' => $count]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Failed to get unread count']);
        }
        break;
        
    case 'get_recent_activity':
        $role = $_GET['role'] ?? 'manager';
        $limit = $_GET['limit'] ?? 10;
        
        try {
            $activities = [];
            
            // Recent quotations
            $quote_activity = $pdo->query("
                SELECT 
                    'quotation' as type,
                    CONCAT('Quotation ', Status, ': ', Package) as description,
                    Date_Issued as created_at,
                    'fa-file-invoice' as icon
                FROM quotation
                ORDER BY Date_Issued DESC
                LIMIT 5
            ")->fetchAll(PDO::FETCH_ASSOC);
            
            // Recent payments
            $payment_activity = $pdo->query("
                SELECT 
                    'payment' as type,
                    CONCAT('Payment received: ₱', FORMAT(Amount_Paid, 2)) as description,
                    Date_Paid as created_at,
                    'fa-money-bill-wave' as icon
                FROM payment
                WHERE Date_Paid IS NOT NULL
                ORDER BY Date_Paid DESC
                LIMIT 5
            ")->fetchAll(PDO::FETCH_ASSOC);
            
            // Recent inventory changes
            $inventory_activity = $pdo->query("
                SELECT 
                    'inventory' as type,
                    CONCAT('Inventory update: ', Item_Name, ' at ', Branch) as description,
                    NOW() as created_at,
                    'fa-boxes' as icon
                FROM inventory
                WHERE Quantity < 10
                ORDER BY Item_ID DESC
                LIMIT 3
            ")->fetchAll(PDO::FETCH_ASSOC);
            
            $activities = array_merge($quote_activity, $payment_activity, $inventory_activity);
            
            // Sort by timestamp
            usort($activities, function($a, $b) {
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            });
            
            // Limit
            $activities = array_slice($activities, 0, (int)$limit);
            
            // Add time ago
            foreach ($activities as &$activity) {
                $activity['time_ago'] = timeAgo($activity['created_at']);
            }
            
            echo json_encode([
                'success' => true, 
                'activities' => $activities,
                'count' => count($activities)
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Failed to get recent activity: ' . $e->getMessage()]);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

function timeAgo($timestamp) {
    if (!$timestamp) return 'Just now';
    
    $time = strtotime($timestamp);
    $diff = time() - $time;
    
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' min' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date('M j, Y', $time);
    }
}
?>