<?php
/**
 * COMPLETE WORKFLOW API
 * Handles the entire flow: Client Request → Secretary Quote → Client Payment → Manager Verification
 */

session_start();
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

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    
    // ========== STEP 1: CLIENT SUBMITS REQUEST ==========
    case 'client_submit_request':
        $client_id = $_SESSION['client_id'] ?? $_POST['client_id'] ?? null;
        $service_type = $_POST['service_type'] ?? '';
        $description = $_POST['description'] ?? '';
        $location = $_POST['location'] ?? '';
        
        if (!$client_id || !$service_type) {
            echo json_encode(['success' => false, 'message' => 'Client ID and service type required']);
            exit;
        }
        
        try {
            $sql = "INSERT INTO service (client_id, type, description, location, status, date_requested) 
                    VALUES (:client_id, :type, :description, :location, 'Pending', NOW())";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':client_id' => $client_id,
                ':type' => $service_type,
                ':description' => $description,
                ':location' => $location
            ]);
            
            $service_id = $pdo->lastInsertId();
            
            echo json_encode([
                'success' => true,
                'message' => 'Service request submitted successfully',
                'service_id' => $service_id,
                'next_step' => 'Secretary will review your request and create a quotation'
            ]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error submitting request: ' . $e->getMessage()]);
        }
        break;
    
    // ========== STEP 2: SECRETARY CREATES QUOTATION ==========
    case 'secretary_create_quote':
        $user_id = $_SESSION['user_id'] ?? $_POST['user_id'] ?? 1; // Secretary ID
        $client_id = $_POST['client_id'] ?? null;
        $service_id = $_POST['service_id'] ?? null;
        $package = $_POST['package'] ?? '';
        $amount = $_POST['amount'] ?? 0;
        $delivery_method = $_POST['delivery_method'] ?? 'Standard';
        $handling_fee = $_POST['handling_fee'] ?? 0;
        
        if (!$client_id || !$package || !$amount) {
            echo json_encode(['success' => false, 'message' => 'Client ID, package, and amount required']);
            exit;
        }
        
        try {
            $sql = "INSERT INTO quotation (Client_ID, User_ID, Package, Amount, Date_Issued, Status, Delivery_Method, Handling_Fee, service_request_id) 
                    VALUES (:client_id, :user_id, :package, :amount, NOW(), 'Pending', :delivery_method, :handling_fee, :service_id)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':client_id' => $client_id,
                ':user_id' => $user_id,
                ':package' => $package,
                ':amount' => $amount,
                ':delivery_method' => $delivery_method,
                ':handling_fee' => $handling_fee,
                ':service_id' => $service_id
            ]);
            
            $quotation_id = $pdo->lastInsertId();
            
            // Update service status
            if ($service_id) {
                $updateService = "UPDATE service SET status = 'Quoted' WHERE Service_ID = ?";
                $stmtUpdate = $pdo->prepare($updateService);
                $stmtUpdate->execute([$service_id]);
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Quotation created successfully',
                'quotation_id' => $quotation_id,
                'reference' => 'QT-' . date('Y') . '-' . str_pad($quotation_id, 4, '0', STR_PAD_LEFT),
                'next_step' => 'Client will review and accept the quotation'
            ]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error creating quotation: ' . $e->getMessage()]);
        }
        break;
    
    // ========== STEP 3: CLIENT ACCEPTS AND PAYS ==========
    case 'client_accept_quote':
        $client_id = $_SESSION['client_id'] ?? $_POST['client_id'] ?? null;
        $quotation_id = $_POST['quotation_id'] ?? null;
        
        if (!$client_id || !$quotation_id) {
            echo json_encode(['success' => false, 'message' => 'Client ID and quotation ID required']);
            exit;
        }
        
        try {
            $sql = "UPDATE quotation 
                    SET Status = 'Accepted' 
                    WHERE Quotation_ID = ? AND Client_ID = ? AND Status = 'Pending'";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$quotation_id, $client_id]);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Quotation accepted successfully',
                    'next_step' => 'Please proceed with payment'
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Quotation not found or already processed']);
            }
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error accepting quotation: ' . $e->getMessage()]);
        }
        break;
    
    case 'client_submit_payment':
        $client_id = $_SESSION['client_id'] ?? $_POST['client_id'] ?? null;
        $quotation_id = $_POST['quotation_id'] ?? null;
        $amount_paid = $_POST['amount_paid'] ?? 0;
        $payment_method = $_POST['payment_method'] ?? 'Bank Transfer';
        
        if (!$client_id || !$quotation_id) {
            echo json_encode(['success' => false, 'message' => 'Client ID and quotation ID required']);
            exit;
        }
        
        try {
            // Handle file upload
            $proof_file_path = null;
            if (isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = 'uploads/payment_proofs/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $file_extension = pathinfo($_FILES['payment_proof']['name'], PATHINFO_EXTENSION);
                $file_name = 'proof_' . $client_id . '_' . $quotation_id . '_' . time() . '.' . $file_extension;
                $proof_file_path = $upload_dir . $file_name;
                
                if (!move_uploaded_file($_FILES['payment_proof']['tmp_name'], $proof_file_path)) {
                    echo json_encode(['success' => false, 'message' => 'Failed to upload payment proof']);
                    exit;
                }
            }
            
            // Update quotation status
            $sql = "UPDATE quotation 
                    SET Status = 'Payment Submitted', 
                        proof_file = :proof_file,
                        amount_paid = :amount_paid,
                        payment_method = :payment_method
                    WHERE Quotation_ID = ? AND Client_ID = ? AND Status = 'Accepted'";
            
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':proof_file', $proof_file_path);
            $stmt->bindParam(':amount_paid', $amount_paid);
            $stmt->bindParam(':payment_method', $payment_method);
            $stmt->execute([$quotation_id, $client_id]);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Payment submitted successfully',
                    'proof_path' => $proof_file_path,
                    'next_step' => 'Manager will verify your payment'
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Quotation not found or payment already submitted']);
            }
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error submitting payment: ' . $e->getMessage()]);
        }
        break;
    
    // ========== STEP 4: MANAGER VERIFIES PAYMENT ==========
    case 'manager_verify_payment':
        $user_id = $_SESSION['user_id'] ?? $_POST['user_id'] ?? 1; // Manager ID
        $quotation_id = $_POST['quotation_id'] ?? null;
        $action_type = $_POST['action_type'] ?? 'approve'; // approve or reject
        $remarks = $_POST['remarks'] ?? '';
        
        if (!$quotation_id) {
            echo json_encode(['success' => false, 'message' => 'Quotation ID required']);
            exit;
        }
        
        try {
            if ($action_type === 'approve') {
                $sql = "UPDATE quotation 
                        SET Status = 'Verified', 
                            verified_by = :user_id,
                            verification_date = NOW(),
                            verification_remarks = :remarks
                        WHERE Quotation_ID = ? AND Status = 'Payment Submitted'";
                
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':user_id', $user_id);
                $stmt->bindParam(':remarks', $remarks);
                $stmt->execute([$quotation_id]);
                
                if ($stmt->rowCount() > 0) {
                    // Update service status to Completed
                    $updateService = "UPDATE service s 
                                     INNER JOIN quotation q ON s.Service_ID = q.service_request_id 
                                     SET s.status = 'Completed' 
                                     WHERE q.Quotation_ID = ?";
                    $stmtService = $pdo->prepare($updateService);
                    $stmtService->execute([$quotation_id]);
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'Payment verified successfully',
                        'next_step' => 'Order processing complete'
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Quotation not found or already verified']);
                }
                
            } else { // reject
                $sql = "UPDATE quotation 
                        SET Status = 'Payment Rejected', 
                            verified_by = :user_id,
                            verification_date = NOW(),
                            verification_remarks = :remarks
                        WHERE Quotation_ID = ? AND Status = 'Payment Submitted'";
                
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':user_id', $user_id);
                $stmt->bindParam(':remarks', $remarks);
                $stmt->execute([$quotation_id]);
                
                if ($stmt->rowCount() > 0) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Payment rejected',
                        'next_step' => 'Client can resubmit payment'
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Quotation not found']);
                }
            }
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error verifying payment: ' . $e->getMessage()]);
        }
        break;
    
    // ========== GET PENDING ITEMS FOR EACH ROLE ==========
    case 'get_pending_for_secretary':
        try {
            // Get pending service requests
            $sql = "SELECT s.*, c.Name as Client_Name, c.Contact_Num 
                    FROM service s 
                    LEFT JOIN client c ON s.client_id = c.Client_ID 
                    WHERE s.status IN ('Pending', 'New') 
                    ORDER BY s.date_requested DESC 
                    LIMIT 50";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'pending_requests' => $requests,
                'count' => count($requests)
            ]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error fetching pending requests: ' . $e->getMessage()]);
        }
        break;
    
    case 'get_pending_for_client':
        $client_id = $_SESSION['client_id'] ?? $_GET['client_id'] ?? null;
        
        if (!$client_id) {
            echo json_encode(['success' => false, 'message' => 'Client ID required']);
            exit;
        }
        
        try {
            // Get quotations for client
            $sql = "SELECT q.*, u.Name as Secretary_Name 
                    FROM quotation q 
                    LEFT JOIN user u ON q.User_ID = u.User_ID 
                    WHERE q.Client_ID = ? 
                    ORDER BY q.Date_Issued DESC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$client_id]);
            $quotations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'quotations' => $quotations,
                'count' => count($quotations)
            ]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error fetching quotations: ' . $e->getMessage()]);
        }
        break;
    
    case 'get_pending_for_manager':
        try {
            // Get submitted payments waiting for verification
            $sql = "SELECT q.*, c.Name as Client_Name, c.Contact_Num, u.Name as Secretary_Name 
                    FROM quotation q 
                    LEFT JOIN client c ON q.Client_ID = c.Client_ID 
                    LEFT JOIN user u ON q.User_ID = u.User_ID 
                    WHERE q.Status = 'Payment Submitted' 
                    ORDER BY q.Date_Issued DESC 
                    LIMIT 50";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'pending_payments' => $payments,
                'count' => count($payments)
            ]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error fetching pending payments: ' . $e->getMessage()]);
        }
        break;
    
    // ========== GET WORKFLOW STATUS ==========
    case 'get_workflow_status':
        $quotation_id = $_GET['quotation_id'] ?? null;
        
        if (!$quotation_id) {
            echo json_encode(['success' => false, 'message' => 'Quotation ID required']);
            exit;
        }
        
        try {
            $sql = "SELECT q.*, 
                           c.Name as Client_Name, 
                           u.Name as Secretary_Name,
                           s.type as Service_Type,
                           s.status as Service_Status
                    FROM quotation q 
                    LEFT JOIN client c ON q.Client_ID = c.Client_ID 
                    LEFT JOIN user u ON q.User_ID = u.User_ID 
                    LEFT JOIN service s ON q.service_request_id = s.Service_ID 
                    WHERE q.Quotation_ID = ?";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$quotation_id]);
            $workflow = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($workflow) {
                echo json_encode([
                    'success' => true,
                    'workflow' => $workflow,
                    'current_stage' => determineStage($workflow['Status'])
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Quotation not found']);
            }
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error fetching workflow status: ' . $e->getMessage()]);
        }
        break;
    
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

// Helper function to determine current stage
function determineStage($status) {
    $stages = [
        'Pending' => 'Step 1: Awaiting Secretary Review',
        'Accepted' => 'Step 2: Awaiting Client Payment',
        'Payment Submitted' => 'Step 3: Awaiting Manager Verification',
        'Verified' => 'Step 4: Payment Verified - Order Processing',
        'Completed' => 'Completed',
        'Payment Rejected' => 'Payment Rejected - Resubmission Required',
        'Declined' => 'Declined by Client'
    ];
    
    return $stages[$status] ?? 'Unknown Stage';
}
?>
