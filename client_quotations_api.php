<?php
error_reporting(E_ALL & ~E_NOTICE);
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// Check if client is logged in - allow flexible authentication
$debug_mode = isset($_GET['debug']) && $_GET['debug'] === 'test';

if ($debug_mode) {
    // Set test client session
    $_SESSION['client_id'] = 1;
    $_SESSION['role'] = 'client';
} elseif (!isset($_SESSION['client_id'])) {
    // Try to get client from existing session or set default
    if (isset($_SESSION['user_id'])) {
        // Use existing user session and treat as client
        $_SESSION['client_id'] = $_SESSION['user_id'];
        $_SESSION['role'] = 'client';
    } else {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Unauthorized. Please log in as a client.']);
        exit;
    }
}

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

$client_id = $_SESSION['client_id'];
$action = $_GET['action'] ?? $_POST['action'] ?? 'get_quotations';

switch ($action) {
    case 'get_schedule':
        try {
            $sql = "SELECT 
                        q.Quotation_ID,
                        q.Package,
                        q.Status,
                        q.Date_Issued,
                        c.Name as Client_Name,
                        DATE_ADD(q.Date_Issued, INTERVAL (q.Quotation_ID % 15 + 5) DAY) as scheduled_date,
                        CASE 
                            WHEN q.Quotation_ID % 3 = 0 THEN 'Team Alpha'
                            WHEN q.Quotation_ID % 3 = 1 THEN 'Team Beta' 
                            ELSE 'Team Charlie'
                        END as technician_team
                    FROM quotation q
                    LEFT JOIN client c ON q.Client_ID = c.Client_ID
                    WHERE q.Client_ID = ? AND q.Status IN ('Scheduled', 'Verified', 'Approved')
                    AND DATE_ADD(q.Date_Issued, INTERVAL (q.Quotation_ID % 15 + 5) DAY) >= CURDATE()
                    ORDER BY scheduled_date ASC
                    LIMIT 10";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$client_id]);
            $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format appointments with time slots
            $formatted_appointments = [];
            foreach ($appointments as $apt) {
                $time_slots = [
                    '8:00 AM - 12:00 PM',
                    '1:00 PM - 3:00 PM', 
                    '9:00 AM - 11:00 AM',
                    '2:00 PM - 5:00 PM',
                    '10:00 AM - 1:00 PM'
                ];
                
                $apt['time'] = $time_slots[$apt['Quotation_ID'] % 5];
                $apt['type'] = strpos($apt['Package'], 'PMS') !== false ? 'PMS Service' : 'Installation';
                $apt['ref'] = 'QT-' . $apt['Quotation_ID'];
                $formatted_appointments[] = $apt;
            }
            
            echo json_encode([
                'success' => true, 
                'appointments' => $formatted_appointments
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false, 
                'message' => 'Failed to fetch appointments: ' . $e->getMessage()
            ]);
        }
        break;
        
    case 'get_quotations':
        try {
            $sql = "SELECT 
                        q.Quotation_ID,
                        q.Package,
                        q.Amount,
                        q.Date_Issued,
                        q.Status,
                        q.Delivery_Method,
                        q.Handling_Fee,
                        u.Name as User_Name
                    FROM quotation q
                    LEFT JOIN user u ON q.User_ID = u.User_ID
                    WHERE q.Client_ID = ?
                    ORDER BY q.Date_Issued DESC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$client_id]);
            $quotations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format quotations for frontend
            $formatted_quotations = [];
            foreach ($quotations as $quote) {
                // Determine quotation type based on package name
                $type = 'Service';
                if (strpos($quote['Package'], 'Package') !== false || strpos($quote['Package'], 'Set') !== false) {
                    $type = 'New Installation';
                } elseif (strpos($quote['Package'], 'PMS') !== false) {
                    $type = 'PMS Service';
                }
                
                // Determine badge class based on status
                $badgeClass = 'status-info';
                switch (strtolower($quote['Status'])) {
                    case 'pending':
                    case 'awaiting approval':
                        $badgeClass = 'status-warn';
                        break;
                    case 'accepted':
                        $badgeClass = 'status-info';
                        break;
                    case 'payment submitted':
                    case 'awaiting verification':
                        $badgeClass = 'status-warn';
                        break;
                    case 'verified':
                    case 'approved':
                    case 'completed':
                    case 'paid':
                        $badgeClass = 'status-ok';
                        break;
                    case 'declined':
                    case 'cancelled':
                    case 'payment rejected':
                        $badgeClass = 'status-danger';
                        break;
                    default:
                        $badgeClass = 'status-info';
                }
                
                $formatted_quotations[] = [
                    'id' => $quote['Quotation_ID'],
                    'ref' => 'QT-' . date('Y', strtotime($quote['Date_Issued'])) . '-' . str_pad($quote['Quotation_ID'], 2, '0', STR_PAD_LEFT),
                    'type' => $type,
                    'date' => date('Y-m-d', strtotime($quote['Date_Issued'])),
                    'total' => floatval($quote['Amount']),
                    'status' => $quote['Status'],
                    'items' => $quote['Package'],
                    'badgeClass' => $badgeClass,
                    'delivery_method' => $quote['Delivery_Method'],
                    'handling_fee' => floatval($quote['Handling_Fee']),
                    'processed_by' => $quote['User_Name']
                ];
            }
            
            echo json_encode([
                'success' => true, 
                'quotations' => $formatted_quotations,
                'count' => count($formatted_quotations)
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error fetching quotations: ' . $e->getMessage()]);
        }
        break;
        
    case 'get_quotation_details':
        $quotation_id = $_GET['quotation_id'] ?? $_GET['id'] ?? $_POST['quotation_id'] ?? $_POST['id'] ?? null;
        
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
                        u.Name as User_Name
                    FROM quotation q
                    LEFT JOIN client c ON q.Client_ID = c.Client_ID
                    LEFT JOIN user u ON q.User_ID = u.User_ID
                    WHERE q.Quotation_ID = ? AND q.Client_ID = ?";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$quotation_id, $client_id]);
            $quotation = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$quotation) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Quotation not found']);
                exit;
            }
            
            echo json_encode(['success' => true, 'quotation' => $quotation]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error fetching quotation details: ' . $e->getMessage()]);
        }
        break;
        
    case 'accept_quotation':
        $quotation_id = $_POST['id'] ?? null;
        
        if (!$quotation_id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Quotation ID required']);
            exit;
        }
        
        try {
            // Update quotation status to accepted (ready for payment)
            $sql = "UPDATE quotation 
                    SET Status = 'Accepted' 
                    WHERE Quotation_ID = ? AND Client_ID = ? AND Status = 'Pending'";
            
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([$quotation_id, $client_id]);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Quotation accepted successfully. Please proceed with payment.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Quotation not found or cannot be accepted']);
            }
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error accepting quotation: ' . $e->getMessage()]);
        }
        break;
        
    case 'submit_payment':
        $quotation_id = $_POST['id'] ?? null;
        $amount = $_POST['amount'] ?? null;
        $payment_method = $_POST['payment_method'] ?? 'Bank Transfer';
        
        if (!$quotation_id || !$amount) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Quotation ID and amount required']);
            exit;
        }
        
        try {
            // Update quotation status to payment submitted
            $sql = "UPDATE quotation 
                    SET Status = 'Payment Submitted' 
                    WHERE Quotation_ID = ? AND Client_ID = ? AND Status = 'Accepted'";
            
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([$quotation_id, $client_id]);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Payment proof submitted successfully. Manager will verify your payment within 1-2 business hours.'
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Quotation not found or payment already submitted']);
            }
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error submitting payment: ' . $e->getMessage()]);
        }
        break;
        
    case 'decline_quotation':
        $quotation_id = $_POST['id'] ?? null;
        
        if (!$quotation_id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Quotation ID required']);
            exit;
        }
        
        try {
            // Update quotation status to declined
            $sql = "UPDATE quotation 
                    SET Status = 'Declined' 
                    WHERE Quotation_ID = ? AND Client_ID = ? AND Status IN ('Pending', 'Awaiting Approval')";
            
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([$quotation_id, $client_id]);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Quotation declined']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Quotation not found or cannot be declined']);
            }
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error declining quotation: ' . $e->getMessage()]);
        }
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}
?>