<?php
require_once 'role_session_manager.php';

// Start secretary session for this API
RoleSessionManager::start('secretary');

// Debug mode for testing
$debug_mode = isset($_GET['debug']) || isset($_POST['debug']);

// Check if user is logged in as secretary (skip in debug mode)
if (!$debug_mode && (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'secretary')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access - Please log in as secretary']);
    exit;
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

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'submit_quote_for_verification':
        $quotation_id = $_POST['quotation_id'] ?? null;
        $client_name = $_POST['client_name'] ?? '';
        $package = $_POST['package'] ?? '';
        $amount = $_POST['amount'] ?? 0;
        $delivery_method = $_POST['delivery_method'] ?? 'Standard Delivery';
        $handling_fee = $_POST['handling_fee'] ?? 0;
        
        // Handle file upload
        $proof_filename = null;
        if (isset($_FILES['proof_file']) && $_FILES['proof_file']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/quote_proofs/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_extension = pathinfo($_FILES['proof_file']['name'], PATHINFO_EXTENSION);
            $proof_filename = 'quote_proof_' . time() . '_' . rand(1000, 9999) . '.' . $file_extension;
            
            if (!move_uploaded_file($_FILES['proof_file']['tmp_name'], $upload_dir . $proof_filename)) {
                echo json_encode(['success' => false, 'message' => 'Failed to upload proof file']);
                exit;
            }
        }
        
        try {
            // Get a valid User_ID for debug mode
            $user_id = $_SESSION['user_id'] ?? null;
            if ($debug_mode) {
                $stmt = $pdo->prepare("SELECT User_ID FROM user WHERE Role IN ('secretary', 'manager') LIMIT 1");
                $stmt->execute();
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                $user_id = $user ? $user['User_ID'] : null;
                
                if (!$user_id) {
                    echo json_encode(['success' => false, 'message' => 'No valid user found in database']);
                    exit;
                }
            }
            
            if ($quotation_id) {
                // Update existing quotation
                // Note: Proof_File column doesn't exist, so we skip it
                $sql = "UPDATE quotation 
                        SET Status = 'Awaiting Manager Approval', 
                            Package = ?, 
                            Amount = ?,
                            Delivery_Method = ?,
                            Handling_Fee = ?,
                            User_ID = ?
                        WHERE Quotation_ID = ?";
                
                $stmt = $pdo->prepare($sql);
                $result = $stmt->execute([
                    $package,
                    $amount,
                    $delivery_method,
                    $handling_fee,
                    $user_id,
                    $quotation_id
                ]);
                
                if ($stmt->rowCount() > 0) {
                    // Store proof filename if uploaded
                    if ($proof_filename) {
                        // You could add a separate table for proof files or add a column to quotation table
                        // For now, we'll just note that proof was uploaded
                    }
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'Quote submitted to Manager for approval',
                        'quotation_id' => $quotation_id
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to update quotation']);
                }
            } else {
                // Create new quotation for existing client inquiry
                // First try to find client by name, or create if not exists
                $stmt = $pdo->prepare("SELECT Client_ID FROM client WHERE Name LIKE ? LIMIT 1");
                $stmt->execute(['%' . $client_name . '%']);
                $client = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$client) {
                    // Create new client if not found
                    $stmt = $pdo->prepare("INSERT INTO client (Name, Contact_Num, Address) VALUES (?, ?, ?)");
                    $stmt->execute([$client_name, '09123456789', 'Auto-generated Address']);
                    $client_id = $pdo->lastInsertId();
                } else {
                    $client_id = $client['Client_ID'];
                }
                
                $sql = "INSERT INTO quotation (Client_ID, User_ID, Package, Amount, Date_Issued, Status, Delivery_Method, Handling_Fee, Proof_File) 
                        VALUES (?, ?, ?, ?, CURDATE(), 'Awaiting Manager Approval', ?, ?, ?)";
                
                $stmt = $pdo->prepare($sql);
                $result = $stmt->execute([
                    $client_id,
                    $user_id,
                    $package,
                    $amount,
                    $delivery_method,
                    $handling_fee,
                    $proof_filename
                ]);
                
                if ($result) {
                    $new_quotation_id = $pdo->lastInsertId();
                    echo json_encode([
                        'success' => true,
                        'message' => 'Quote created and submitted to Manager for approval',
                        'quotation_id' => $new_quotation_id
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to create quotation']);
                }
            }
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error processing quote: ' . $e->getMessage()]);
        }
        break;
        
    case 'update_quotation_status':
        $quotation_id = $_POST['quotation_id'] ?? null;
        $status = $_POST['status'] ?? '';
        
        if (!$quotation_id || !$status) {
            echo json_encode(['success' => false, 'message' => 'Quotation ID and status required']);
            exit;
        }
        
        try {
            $sql = "UPDATE quotation SET Status = ? WHERE Quotation_ID = ?";
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([$status, $quotation_id]);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Quotation status updated']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Quotation not found']);
            }
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error updating status: ' . $e->getMessage()]);
        }
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}
?>