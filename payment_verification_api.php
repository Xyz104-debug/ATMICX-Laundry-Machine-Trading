<?php
require_once 'role_session_manager.php';
require_once 'logger.php';
require_once 'security.php';

// Handle different authentication based on action
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// For client actions (payment submission), use client session
if ($action === 'submit_payment_proof') {
    // Use standard PHP session (not role session manager)
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    
    // Debug logging for client session
    error_log("Payment submission - Session data: " . print_r($_SESSION, true));
    
    if (!isset($_SESSION['client_id'])) {
        error_log("Payment submission failed - no client_id in session");
        echo json_encode(['success' => false, 'message' => 'User not logged in - please log in as client']);
        exit;
    }
    
    error_log("Payment submission - Client ID: " . $_SESSION['client_id']);
} else {
    // For manager/secretary actions, detect and use appropriate role session
    $active_role = null;
    $session_names = [
        'manager' => 'ATMICX_MGR_SESSION',
        'secretary' => 'ATMICX_SEC_SESSION'
    ];

    foreach ($session_names as $role => $session_name) {
        if (isset($_COOKIE[$session_name])) {
            $active_role = $role;
            break;
        }
    }

    if ($active_role) {
        RoleSessionManager::start($active_role);
    } else {
        // Fallback to manager for backward compatibility
        RoleSessionManager::start('manager');
    }
    
    // Debug mode for testing
    $debug_mode = isset($_GET['debug']) && $_GET['debug'] === 'true';
    
    // Rate limiting check
    if (!$debug_mode) {
        $client_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        if (!ATMICXSecurity::checkRateLimit($client_ip, 100, 3600)) {
            ATMICXLogger::logError("Rate limit exceeded for IP: $client_ip");
            http_response_code(429);
            echo json_encode(['success' => false, 'message' => 'Rate limit exceeded. Please try again later.']);
            exit;
        }
    }
    
    // Check if user is logged in (allow both manager and secretary roles for flexible access)
    // Temporarily bypass authentication for debugging
    $bypass_auth = isset($_GET['bypass']) && $_GET['bypass'] === 'true';
    if (!$debug_mode && !$bypass_auth && (!RoleSessionManager::isAuthenticated() || !in_array(strtolower(RoleSessionManager::getRole()), ['manager', 'secretary']))) {
        ATMICXLogger::logError('Unauthorized payment verification attempt - Role: ' . (RoleSessionManager::getRole() ?? 'none'));
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Unauthorized access - Please log in as manager or secretary']);
        exit;
    }
}

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

$action = $_GET['action'] ?? $_POST['action'] ?? 'get_pending_payments';

switch ($action) {
    case 'debug_session':
        $session_info = [
            'session_id' => session_id(),
            'user_id' => RoleSessionManager::getUserId(),
            'role' => RoleSessionManager::getRole(),
            'username' => RoleSessionManager::getUsername(),
            'debug_mode' => $debug_mode ? 'true' : 'false',
            'is_authenticated' => RoleSessionManager::isAuthenticated(),
            'current_role_context' => RoleSessionManager::getCurrentRole(),
            'all_session_data' => RoleSessionManager::get()
        ];
        echo json_encode($session_info, JSON_PRETTY_PRINT);
        break;
        
    case 'get_payment_stats':
        try {
            $stats = [];
            
            // Count pending payments - WORKFLOW INTEGRATION
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM quotation WHERE Status IN ('Payment Submitted', 'Awaiting Verification', 'Awaiting Manager Approval')");
            $stmt->execute();
            $stats['pending_count'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            // Count verified payments
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM quotation WHERE Status IN ('Verified', 'Paid', 'Completed')");
            $stmt->execute();
            $stats['verified_count'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            // Count rejected payments
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM quotation WHERE Status = 'Payment Rejected'");
            $stmt->execute();
            $stats['rejected_count'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            // Total pending payment volume
            $stmt = $pdo->prepare("SELECT SUM(Amount) as total FROM quotation WHERE Status IN ('Payment Submitted', 'Awaiting Verification', 'Awaiting Manager Approval', 'Accepted')");
            $stmt->execute();
            $stats['pending_volume'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
            
            echo json_encode(['success' => true, 'stats' => $stats]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error fetching payment stats: ' . $e->getMessage()]);
        }
        break;
    
    case 'submit_payment_proof':
        // Handle payment proof submission from clients
        // Session already started above based on action
        
        if (!isset($_SESSION['client_id'])) {
            echo json_encode(['success' => false, 'message' => 'User not logged in']);
            exit();
        }
        
        $client_id = $_SESSION['client_id'];
        $quote_reference = $_POST['quote_reference'] ?? '';
        $amount_paid = $_POST['amount_paid'] ?? '';
        
        if (empty($quote_reference) || empty($amount_paid)) {
            echo json_encode(['success' => false, 'message' => 'Quote reference and amount are required']);
            exit();
        }
        
        try {
            // Get quotation details
            // First try to parse the reference to get the actual ID
            $quotation_id = null;
            $service_id = null;
            
            if (strpos($quote_reference, 'QT-') === 0) {
                // Regular quotation reference: QT-0001
                $quotation_id = intval(substr($quote_reference, 3));
            } elseif (strpos($quote_reference, 'SR-') === 0) {
                // Service request reference: SR-001
                $service_id = intval(substr($quote_reference, 3));
            }
            
            if ($quotation_id) {
                $quotation_query = "SELECT q.*, s.Service_ID as service_request_id
                                   FROM quotation q 
                                   LEFT JOIN service s ON q.service_request_id = s.Service_ID 
                                   WHERE q.Quotation_ID = ?";
                $stmt = $pdo->prepare($quotation_query);
                $stmt->execute([$quotation_id]);
            } elseif ($service_id) {
                $quotation_query = "SELECT q.*, s.Service_ID as service_request_id
                                   FROM quotation q 
                                   LEFT JOIN service s ON q.service_request_id = s.Service_ID 
                                   WHERE q.service_request_id = ?";
                $stmt = $pdo->prepare($quotation_query);
                $stmt->execute([$service_id]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid quote reference format']);
                exit();
            }
            
            $quotation = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$quotation) {
                echo json_encode(['success' => false, 'message' => 'Invalid quote reference']);
                exit();
            }
            
            // Handle file upload
            $proof_file_path = null;
            if (isset($_FILES['proof_file']) && $_FILES['proof_file']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = 'uploads/payment_proofs/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $file_extension = pathinfo($_FILES['proof_file']['name'], PATHINFO_EXTENSION);
                $file_name = 'proof_' . $client_id . '_' . $quotation['id'] . '_' . time() . '.' . $file_extension;
                $proof_file_path = $upload_dir . $file_name;
                
                if (!move_uploaded_file($_FILES['proof_file']['tmp_name'], $proof_file_path)) {
                    echo json_encode(['success' => false, 'message' => 'Failed to upload proof file']);
                    exit();
                }
            }
            
            // Create payment record
            $insert_payment = "INSERT INTO payment (quotation_id, Client_ID, Amount_Paid, payment_date, proof_file_path, Status, service_request_id) 
                              VALUES (?, ?, ?, NOW(), ?, 'pending', ?)";
            $stmt = $pdo->prepare($insert_payment);
            $stmt->execute([
                $quotation['Quotation_ID'], 
                $client_id, 
                $amount_paid, 
                $proof_file_path, 
                $quotation['service_request_id']
            ]);
            
            // Update quotation status
            $update_quotation = "UPDATE quotation SET Status = 'Payment Submitted' WHERE Quotation_ID = ?";
            $stmt = $pdo->prepare($update_quotation);
            $stmt->execute([$quotation['Quotation_ID']]);
            
            echo json_encode(['success' => true, 'message' => 'Payment proof submitted successfully']);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error submitting payment: ' . $e->getMessage()]);
        }
        break;
        
    case 'get_pending_payments':
        try {
            $sql = "SELECT 
                        q.Quotation_ID,
                        q.Client_ID,
                        q.Package,
                        q.Amount,
                        q.Date_Issued,
                        q.Status,
                        q.Delivery_Method,
                        q.Handling_Fee,
                        c.Name as Client_Name,
                        c.Contact_Num,
                        c.Address,
                        u.Name as Secretary_Name,
                        p.Payment_ID,
                        p.Amount_Paid,
                        p.Date_Paid,
                        p.Proof_Image,
                        CONCAT('QT-', LPAD(q.Quotation_ID, 4, '0')) as reference_number
                    FROM quotation q
                    LEFT JOIN client c ON q.Client_ID = c.Client_ID
                    LEFT JOIN user u ON q.User_ID = u.User_ID
                    LEFT JOIN payment p ON q.Quotation_ID = p.quotation_id
                    WHERE q.Status IN ('Awaiting Manager Approval', 'Accepted', 'Payment Submitted', 'Awaiting Verification')
                    ORDER BY q.Date_Issued DESC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format for manager interface
            $formatted_payments = [];
            foreach ($payments as $payment) {
                // Determine if this is a payment submission or quotation approval
                $has_payment = !empty($payment['Payment_ID']);
                
                // Determine type and icon
                $type = 'Package Sale';
                $icon = 'box-open';
                if (strpos($payment['Package'], 'Repair') !== false) {
                    $type = 'Repair Service';
                    $icon = 'wrench';
                } elseif (strpos($payment['Package'], 'PMS') !== false) {
                    $type = 'PMS Service';
                    $icon = 'cogs';
                }
                
                $base_amount = $payment['Amount'] - ($payment['Handling_Fee'] ?? 0);
                $is_service = (strpos($payment['Package'], 'Repair') !== false || strpos($payment['Package'], 'PMS') !== false);
                $payment_percentage = $is_service ? 100 : 70;
                $amount_to_pay = $is_service ? $payment['Amount'] : ($payment['Amount'] * 0.7);
                
                if ($has_payment && $payment['Status'] === 'Payment Submitted') {
                    // This is a submitted payment proof - use payment verification format
                    $formatted_payments[] = [
                        'id' => $payment['Quotation_ID'],
                        'ref' => $payment['reference_number'],
                        'client_name' => $payment['Client_Name'],
                        'location' => $payment['Address'] ?? 'No address',
                        'type' => $type,
                        'package_description' => $payment['Package'],
                        'icon' => $icon,
                        'base_price' => $base_amount,
                        'handling_fee' => $payment['Handling_Fee'] ?? 0,
                        'total_cost' => $payment['Amount'],
                        'amount_paid' => $payment['Amount_Paid'] ?? $amount_to_pay,
                        'payment_percentage' => $payment['Amount_Paid'] ? round(($payment['Amount_Paid'] / $payment['Amount']) * 100) : $payment_percentage,
                        'date_issued' => $payment['Date_Issued'],
                        'status' => $payment['Status'],
                        'processed_by' => $payment['Secretary_Name'],
                        'payment_date' => $payment['Date_Paid'],
                        'proof_file_path' => $payment['Proof_Image'],
                        'payment_id' => $payment['Payment_ID'],
                        'quotation_type' => 'payment_verification'
                    ];
                } else {
                    // This is a quotation awaiting approval
                    $formatted_payments[] = [
                        'id' => $payment['Quotation_ID'],
                        'ref' => $payment['reference_number'],
                        'client_name' => $payment['Client_Name'] ?? 'Unknown Client',
                        'location' => $payment['Address'] ?? 'No address',
                        'type' => $type,
                        'package_description' => $payment['Package'],
                        'icon' => $icon,
                        'base_price' => $base_amount,
                        'handling_fee' => $payment['Handling_Fee'] ?? 0,
                        'total_cost' => $payment['Amount'],
                        'amount_paid' => $amount_to_pay,
                        'payment_percentage' => $payment_percentage,
                        'date_issued' => $payment['Date_Issued'],
                        'status' => $payment['Status'],
                        'processed_by' => $payment['Secretary_Name'],
                        'quotation_type' => 'quotation_approval'
                    ];
                }
            }
            
            echo json_encode([
                'success' => true, 
                'payments' => $formatted_payments,
                'count' => count($formatted_payments)
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error fetching pending payments: ' . $e->getMessage()]);
        }
        break;
        
    case 'verify_payment':
        $quotation_id = $_POST['quotation_id'] ?? null;
        
        if (!$quotation_id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Quotation ID required']);
            exit;
        }
        
        try {
            // Check current status to determine next action
            $stmt = $pdo->prepare("SELECT Status, Package, Amount FROM quotation WHERE Quotation_ID = ?");
            $stmt->execute([$quotation_id]);
            $quotation = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$quotation) {
                echo json_encode(['success' => false, 'message' => 'Quotation not found']);
                exit;
            }
            
            $new_status = '';
            $message = '';
            
            if ($quotation['Status'] === 'Awaiting Manager Approval') {
                // Quote from secretary - approve and send to client
                $new_status = 'Approved';
                $message = 'Quote approved and sent to client';
            } else {
                // Payment from client - verify payment
                $new_status = 'Verified';
                $message = 'Payment verified successfully';
            }
            
            // Update quotation status
            $sql = "UPDATE quotation 
                    SET Status = ? 
                    WHERE Quotation_ID = ? AND Status IN ('Awaiting Manager Approval', 'Accepted', 'Payment Submitted', 'Awaiting Verification')";
            
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([$new_status, $quotation_id]);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode([
                    'success' => true, 
                    'message' => $message,
                    'amount' => $quotation['Amount'],
                    'package' => $quotation['Package'],
                    'action' => $quotation['Status'] === 'Awaiting Manager Approval' ? 'approved' : 'verified'
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Quotation not found or cannot be processed']);
            }
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error processing quotation: ' . $e->getMessage()]);
        }
        break;
    
    case 'verify_payment_proof':
        // Handle payment proof verification from the new integrated system
        if (!$bypass_auth && (!RoleSessionManager::isAuthenticated() || RoleSessionManager::getRole() !== 'manager')) {
            echo json_encode(['success' => false, 'message' => 'Only managers can verify payments']);
            exit();
        }
        
        $payment_id = $_POST['payment_id'] ?? '';
        $verification_status = $_POST['status'] ?? '';
        
        if (empty($payment_id) || empty($verification_status)) {
            echo json_encode(['success' => false, 'message' => 'Payment ID and status are required']);
            exit();
        }
        
        try {
            // Update payment status
            $update_sql = "UPDATE payment SET status = ?, verification_date = NOW() WHERE Payment_ID = ?";
            $stmt = $pdo->prepare($update_sql);
            $stmt->execute([$verification_status, $payment_id]);
            
            // If payment is approved and linked to service request, update service status
            if ($verification_status === 'approved') {
                $service_check_sql = "SELECT p.service_request_id, p.quotation_id FROM payment p WHERE p.Payment_ID = ?";
                $service_check_stmt = $pdo->prepare($service_check_sql);
                $service_check_stmt->execute([$payment_id]);
                $service_result = $service_check_stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($service_result && $service_result['service_request_id']) {
                    // Update service request status
                    $update_service_sql = "UPDATE service SET status = 'paid', updated_at = NOW() WHERE Service_ID = ?";
                    $update_service_stmt = $pdo->prepare($update_service_sql);
                    $update_service_stmt->execute([$service_result['service_request_id']]);
                }
                
                if ($service_result && $service_result['quotation_id']) {
                    // Update quotation status
                    $update_quotation_sql = "UPDATE quotation SET status = 'paid' WHERE Quotation_ID = ?";
                    $update_quotation_stmt = $pdo->prepare($update_quotation_sql);
                    $update_quotation_stmt->execute([$service_result['quotation_id']]);
                }
            }
            
            $message = ($verification_status === 'approved') ? 'Payment approved and service updated' : 'Payment rejected';
            echo json_encode(['success' => true, 'message' => $message]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error processing verification: ' . $e->getMessage()]);
        }
        break;
        
    case 'reject_payment':
        $quotation_id = $_POST['quotation_id'] ?? null;
        $reason = $_POST['reason'] ?? 'Rejected by manager';
        
        if (!$quotation_id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Quotation ID required']);
            exit;
        }
        
        try {
            // Check current status to determine rejection type
            $stmt = $pdo->prepare("SELECT Status FROM quotation WHERE Quotation_ID = ?");
            $stmt->execute([$quotation_id]);
            $quotation = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$quotation) {
                echo json_encode(['success' => false, 'message' => 'Quotation not found']);
                exit;
            }
            
            $rejection_status = '';
            $message = '';
            
            if ($quotation['Status'] === 'Awaiting Manager Approval') {
                // Quote from secretary - reject and send back to secretary
                $rejection_status = 'Rejected by Manager';
                $message = 'Quote rejected - returned to secretary for revision';
            } else {
                // Payment from client - reject payment
                $rejection_status = 'Payment Rejected';
                $message = 'Payment rejected';
            }
            
            // Update quotation status
            $sql = "UPDATE quotation 
                    SET Status = ? 
                    WHERE Quotation_ID = ? AND Status IN ('Awaiting Manager Approval', 'Accepted', 'Payment Submitted', 'Awaiting Verification')";
            
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([$rejection_status, $quotation_id]);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode([
                    'success' => true, 
                    'message' => $message,
                    'reason' => $reason
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Quotation not found or cannot be rejected']);
            }
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error rejecting quotation: ' . $e->getMessage()]);
        }
        break;
        
    case 'get_payment_history':
        try {
            $sql = "SELECT 
                        q.Quotation_ID,
                        q.Package,
                        q.Amount,
                        q.Date_Issued,
                        q.Status,
                        c.Name as Client_Name,
                        CONCAT('QT-', LPAD(q.Quotation_ID, 4, '0')) as reference_number
                    FROM quotation q
                    LEFT JOIN client c ON q.Client_ID = c.Client_ID
                    WHERE q.Status IN ('Verified', 'Paid', 'Completed', 'Approved', 'Rejected')
                    ORDER BY q.Date_Issued DESC
                    LIMIT 50";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format history for frontend
            $formatted_history = [];
            foreach ($history as $item) {
                $formatted_history[] = [
                    'ref' => $item['reference_number'],
                    'quotation_id' => $item['Quotation_ID'],
                    'client_name' => $item['Client_Name'] ?? 'Unknown Client',
                    'package' => $item['Package'],
                    'amount' => floatval($item['Amount']),
                    'date' => $item['Date_Issued'],
                    'status' => $item['Status']
                ];
            }
            
            echo json_encode(['success' => true, 'history' => $formatted_history]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error fetching payment history: ' . $e->getMessage()]);
        }
        break;
        
    case 'get_quote_details':
        $quotation_id = $_GET['quotation_id'] ?? $_POST['quotation_id'] ?? null;
        
        if (!$quotation_id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Quotation ID required']);
            exit;
        }
        
        try {
            $sql = "SELECT 
                        q.*,
                        c.Name as Client_Name,
                        c.Contact_Num,
                        c.Address,
                        c.Email,
                        u.Name as Created_By
                    FROM quotation q
                    LEFT JOIN client c ON q.Client_ID = c.Client_ID
                    LEFT JOIN user u ON q.User_ID = u.User_ID
                    WHERE q.Quotation_ID = ?";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$quotation_id]);
            $quote = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$quote) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Quote not found']);
                exit;
            }
            
            // Format the quote data
            $formatted_quote = [
                'quotation_id' => $quote['Quotation_ID'],
                'client_name' => $quote['Client_Name'],
                'client_contact' => $quote['Contact_Num'],
                'client_address' => $quote['Address'],
                'client_email' => $quote['Email'],
                'package' => $quote['Package'],
                'amount' => floatval($quote['Amount']),
                'handling_fee' => floatval($quote['Handling_Fee']),
                'delivery_method' => $quote['Delivery_Method'],
                'date_issued' => $quote['Date_Issued'],
                'status' => $quote['Status'],
                'created_by' => $quote['Created_By'],
                'proof_file' => $quote['Proof_File']
            ];
            
            echo json_encode(['success' => true, 'quote' => $formatted_quote]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error fetching quote details: ' . $e->getMessage()]);
        }
        break;
    
    case 'get_payment_details':
        $quotation_id = $_GET['quotation_id'] ?? null;
        
        if (!$quotation_id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Quotation ID required']);
            exit;
        }
        
        try {
            // Get payment details with proof file
            $sql = "SELECT 
                        p.Payment_ID,
                        p.Amount_Paid,
                        p.payment_date,
                        p.proof_file_path,
                        p.Proof_Image,
                        p.Status,
                        q.Quotation_ID,
                        q.Package,
                        q.Amount,
                        c.Client_ID,
                        c.Name as Client_Name,
                        c.Contact_Num,
                        c.Email,
                        CONCAT('QT-', LPAD(q.Quotation_ID, 4, '0')) as reference
                    FROM payment p
                    INNER JOIN quotation q ON p.quotation_id = q.Quotation_ID
                    INNER JOIN client c ON q.Client_ID = c.Client_ID
                    WHERE q.Quotation_ID = ?
                    ORDER BY p.payment_date DESC
                    LIMIT 1";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$quotation_id]);
            $payment = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$payment) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Payment not found for this quotation']);
                exit;
            }
            
            echo json_encode(['success' => true, 'payment' => $payment]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error fetching payment details: ' . $e->getMessage()]);
        }
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}
?>