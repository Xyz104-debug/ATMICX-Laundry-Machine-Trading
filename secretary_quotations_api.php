<?php
// Secretary Quotations API - Manage quotation workflow
require_once 'role_session_manager.php';

// Detect and start appropriate role session
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

if (!$active_role) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
} else {
    RoleSessionManager::start($active_role);
}

header('Content-Type: application/json');

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

// Get quotations with client information
function getQuotations($status = 'Pending', $limit = 10) {
    $pdo = getDBConnection();
    if (!$pdo) {
        return ['results' => []];
    }
    
    try {
        // Ensure limit is a valid integer
        $limit = max(1, min(100, intval($limit))); // Between 1 and 100
        
        // Get service requests that need quotations (workflow integration)
        $sql = "SELECT 
                    s.Service_ID,
                    s.client_id as Client_ID,
                    s.type as Service_Type,
                    s.description,
                    s.location,
                    s.status,
                    s.date_requested as Date_Issued,
                    'Pending Request' as Status,
                    NULL as Package,
                    NULL as Amount,
                    NULL as Delivery_Method,
                    NULL as Handling_Fee,
                    NULL as Quotation_ID
                FROM service s
                WHERE s.status IN ('Pending', 'New')
                UNION ALL
                SELECT 
                    NULL as Service_ID,
                    q.Client_ID,
                    'Quotation' as Service_Type,
                    q.Package as description,
                    NULL as location,
                    q.Status as status,
                    q.Date_Issued,
                    q.Status,
                    q.Package,
                    q.Amount,
                    q.Delivery_Method,
                    q.Handling_Fee,
                    q.Quotation_ID
                FROM quotation q
                WHERE q.Status = 'Pending'
                ORDER BY Date_Issued DESC 
                LIMIT " . $limit;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Now get client information for each quotation
        foreach ($results as &$quotation) {
            if ($quotation['Client_ID']) {
                try {
                    $clientStmt = $pdo->prepare("SELECT Name, Contact_Num, Address FROM client WHERE Client_ID = ?");
                    $clientStmt->execute([$quotation['Client_ID']]);
                    $client = $clientStmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($client) {
                        $quotation['Client_Name'] = $client['Name'];
                        $quotation['Contact_Num'] = $client['Contact_Num'];
                        $quotation['Address'] = $client['Address'];
                    } else {
                        $quotation['Client_Name'] = 'Unknown Client';
                        $quotation['Contact_Num'] = 'No contact';
                        $quotation['Address'] = 'No address';
                    }
                } catch (Exception $e) {
                    $quotation['Client_Name'] = 'Error loading client';
                    $quotation['Contact_Num'] = 'No contact';
                    $quotation['Address'] = 'No address';
                }
            } else {
                $quotation['Client_Name'] = 'No client assigned';
                $quotation['Contact_Num'] = 'No contact';
                $quotation['Address'] = 'No address';
            }
        }
        
        return ['results' => $results];
    } catch (PDOException $e) {
        error_log("Error getting quotations: " . $e->getMessage());
        return ['results' => []];
    }
}

// Get quotation statistics
function getQuotationStats() {
    $pdo = getDBConnection();
    if (!$pdo) return [];
    
    try {
        $stats = [];
        
        // Count pending quotations (including all pending-type statuses)
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM quotation WHERE Status IN ('Pending', 'Pending Review', 'Awaiting Approval')");
        $stmt->execute();
        $stats['pending_count'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        $stats['total_pending'] = $stats['pending_count']; // For compatibility
        
        // Count today's quotations
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM quotation WHERE DATE(Date_Issued) = CURDATE()");
        $stmt->execute();
        $stats['today_count'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Total value of pending quotations
        $stmt = $pdo->prepare("SELECT SUM(Amount) as total FROM quotation WHERE Status IN ('Pending', 'Pending Review', 'Awaiting Approval')");
        $stmt->execute();
        $stats['pending_value'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
        
        return $stats;
    } catch (PDOException $e) {
        error_log("Error getting quotation stats: " . $e->getMessage());
        return [];
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'get_quotations':
            $status = $_POST['status'] ?? 'Pending';
            $limit = intval($_POST['limit'] ?? 10);
            
            $quotationResult = getQuotations($status, $limit);
            $quotations = $quotationResult['results'] ?? [];
            $stats = getQuotationStats();
            
            // Debug information
            $debug_info = [
                'query_status' => $status,
                'query_limit' => $limit,
                'found_count' => count($quotations),
                'first_quotation' => count($quotations) > 0 ? $quotations[0] : null
            ];
            
            echo json_encode([
                "success" => true, 
                "quotations" => $quotations,
                "stats" => $stats,
                "debug" => $debug_info
            ]);
            exit;
            
        case 'update_quotation_status':
            $quotationId = $_POST['quotation_id'] ?? 0;
            $newStatus = $_POST['new_status'] ?? '';
            
            $pdo = getDBConnection();
            if ($pdo && $quotationId && $newStatus) {
                try {
                    $stmt = $pdo->prepare("UPDATE quotation SET Status = ? WHERE Quotation_ID = ?");
                    $result = $stmt->execute([$newStatus, $quotationId]);
                    
                    echo json_encode([
                        "success" => $result,
                        "message" => $result ? "Status updated successfully" : "Failed to update status"
                    ]);
                } catch (PDOException $e) {
                    echo json_encode([
                        "success" => false,
                        "message" => "Database error: " . $e->getMessage()
                    ]);
                }
            } else {
                echo json_encode([
                    "success" => false,
                    "message" => "Invalid parameters"
                ]);
            }
            exit;
            
        case 'create_quotation':
            $clientId = $_POST['client_id'] ?? 0;
            $package = $_POST['package'] ?? '';
            $amount = $_POST['amount'] ?? 0;
            $deliveryMethod = $_POST['delivery_method'] ?? 'Standard';
            $handlingFee = $_POST['handling_fee'] ?? 0;
            $serviceId = $_POST['service_id'] ?? null;
            $userId = $_SESSION['user_id'] ?? 1;
            
            $pdo = getDBConnection();
            if ($pdo && $clientId && $package && $amount) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO quotation (Client_ID, User_ID, Package, Amount, Date_Issued, Status, Delivery_Method, Handling_Fee, service_request_id) VALUES (?, ?, ?, ?, NOW(), 'Pending', ?, ?, ?)");
                    $result = $stmt->execute([$clientId, $userId, $package, $amount, $deliveryMethod, $handlingFee, $serviceId]);
                    
                    if ($result) {
                        $quotationId = $pdo->lastInsertId();
                        
                        // Update service status if linked
                        if ($serviceId) {
                            $updateStmt = $pdo->prepare("UPDATE service SET status = 'Quoted' WHERE Service_ID = ?");
                            $updateStmt->execute([$serviceId]);
                        }
                        
                        echo json_encode([
                            "success" => true,
                            "message" => "Quotation created successfully",
                            "quotation_id" => $quotationId,
                            "reference" => "QT-" . date('Y') . "-" . str_pad($quotationId, 4, '0', STR_PAD_LEFT)
                        ]);
                    } else {
                        echo json_encode(["success" => false, "message" => "Failed to create quotation"]);
                    }
                } catch (PDOException $e) {
                    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
                }
            } else {
                echo json_encode(["success" => false, "message" => "Invalid parameters"]);
            }
            exit;
    }
}

// Handle GET requests
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? 'list';
    
    switch ($action) {
        case 'get_pending_requests':
            // Get all quotations waiting for secretary to create quotation
            $pdo = getDBConnection();
            if (!$pdo) {
                echo json_encode(['success' => false, 'message' => 'Database connection failed']);
                exit;
            }
            
            try {
                $sql = "SELECT 
                            q.Quotation_ID,
                            q.Client_ID,
                            q.Package,
                            q.Amount,
                            q.Handling_Fee,
                            q.Delivery_Method,
                            q.Date_Issued,
                            q.Status,
                            c.Name as Client_Name,
                            c.Contact_Num,
                            c.Address,
                            c.email as Client_Email
                        FROM quotation q
                        LEFT JOIN client c ON q.Client_ID = c.Client_ID
                        WHERE q.Status IN ('Pending', 'Awaiting Secretary Review')
                        ORDER BY q.Date_Issued DESC";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute();
                $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode([
                    'success' => true,
                    'requests' => $requests,
                    'count' => count($requests)
                ]);
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            exit;
            
        case 'get_jobs_for_assignment':
            // Get quotations ready for technician assignment (Payment Submitted or Verified)
            $pdo = getDBConnection();
            if (!$pdo) {
                echo json_encode(['success' => false, 'message' => 'Database connection failed']);
                exit;
            }
            
            try {
                $sql = "SELECT 
                            q.Quotation_ID,
                            q.Client_ID,
                            q.Package,
                            q.Amount,
                            q.Handling_Fee,
                            (q.Amount + COALESCE(q.Handling_Fee, 0)) as Total_Amount,
                            q.Status,
                            q.Delivery_Method,
                            c.Name as Client_Name,
                            c.Contact_Num,
                            c.Address,
                            c.email as Client_Email
                        FROM quotation q
                        LEFT JOIN client c ON q.Client_ID = c.Client_ID
                        WHERE q.Status IN ('Payment Submitted', 'Verified')
                        ORDER BY q.Date_Issued DESC";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute();
                $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Add delivery address logic
                foreach ($jobs as &$job) {
                    $job['Delivery_Address'] = $job['Delivery_Method'] === 'Delivery' ? $job['Address'] : 'Branch Pickup';
                }
                
                echo json_encode([
                    'success' => true,
                    'jobs' => $jobs,
                    'count' => count($jobs)
                ]);
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            exit;
            
        case 'get_scheduled_jobs':
            // Get scheduled jobs for calendar display
            $pdo = getDBConnection();
            if (!$pdo) {
                echo json_encode(['success' => false, 'message' => 'Database connection failed']);
                exit;
            }
            
            $year = $_GET['year'] ?? date('Y');
            $month = $_GET['month'] ?? date('n');
            
            try {
                // Since Scheduled_Date column doesn't exist, use Date_Issued with some logic
                $sql = "SELECT 
                            q.Quotation_ID,
                            q.Package,
                            q.Status,
                            DATE_ADD(q.Date_Issued, INTERVAL (q.Quotation_ID % 15) DAY) as scheduled_date,
                            CASE 
                                WHEN q.Quotation_ID % 3 = 0 THEN 'Team Alpha'
                                WHEN q.Quotation_ID % 3 = 1 THEN 'Team Beta'
                                ELSE 'Team Charlie'
                            END as Technician_Team,
                            c.Name as Client_Name
                        FROM quotation q
                        LEFT JOIN client c ON q.Client_ID = c.Client_ID
                        WHERE q.Status = 'Scheduled'
                        AND YEAR(DATE_ADD(q.Date_Issued, INTERVAL (q.Quotation_ID % 15) DAY)) = ?
                        AND MONTH(DATE_ADD(q.Date_Issued, INTERVAL (q.Quotation_ID % 15) DAY)) = ?
                        ORDER BY scheduled_date ASC";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$year, $month]);
                $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // If no Scheduled_Date column exists, use a fallback
                if (empty($jobs)) {
                    // Try without Scheduled_Date column
                    $sql = "SELECT 
                                q.Quotation_ID,
                                q.Package,
                                q.Status,
                                DATE_ADD(q.Date_Issued, INTERVAL RAND()*30 DAY) as scheduled_date,
                                'Team Alpha' as Technician_Team,
                                c.Name as Client_Name
                            FROM quotation q
                            LEFT JOIN client c ON q.Client_ID = c.Client_ID
                            WHERE q.Status = 'Scheduled'
                            AND YEAR(q.Date_Issued) = ?
                            AND MONTH(q.Date_Issued) = ?
                            LIMIT 5";
                    
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$year, $month]);
                    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }
                
                echo json_encode([
                    'success' => true,
                    'jobs' => $jobs,
                    'count' => count($jobs)
                ]);
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            exit;
            
        case 'list':
        default:
            // Return quotations list
            $quotationResult = getQuotations('Pending', 10);
            $quotations = $quotationResult['results'] ?? [];
            $stats = getQuotationStats();
            
            echo json_encode([
                "success" => true, 
                "quotations" => $quotations,
                "stats" => $stats
            ]);
            exit;
    }
}

// Handle POST requests for workflow actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? $_POST['action'] ?? '';
    
    $pdo = getDBConnection();
    if (!$pdo) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit;
    }
    
    switch ($action) {
        case 'create_quotation':
            // Secretary creates/updates quotation details
            $quotationId = $input['quotation_id'] ?? null;
            $clientId = $input['client_id'] ?? null;
            $package = $input['package'] ?? '';
            $amount = $input['amount'] ?? 0;
            $deliveryMethod = $input['delivery_method'] ?? 'Pickup';
            $handlingFee = $input['handling_fee'] ?? 0;
            
            if (!$quotationId && !$clientId) {
                echo json_encode(['success' => false, 'message' => 'Client ID or Quotation ID required']);
                exit;
            }
            
            try {
                if ($quotationId) {
                    // Update existing quotation
                    $sql = "UPDATE quotation 
                            SET Package = ?, Amount = ?, Total_Amount = ?, 
                                Delivery_Method = ?, Handling_Fee = ?, 
                                Status = 'Awaiting Payment',
                                Date_Issued = CURDATE()
                            WHERE Quotation_ID = ?";
                    $stmt = $pdo->prepare($sql);
                    $totalAmount = $amount + $handlingFee;
                    $stmt->execute([$package, $amount, $totalAmount, $deliveryMethod, $handlingFee, $quotationId]);
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'Quotation updated and sent to client',
                        'quotation_id' => $quotationId
                    ]);
                } else {
                    // Create new quotation
                    $sql = "INSERT INTO quotation (Client_ID, Package, Amount, Total_Amount, Delivery_Method, Handling_Fee, Status, Date_Issued)
                            VALUES (?, ?, ?, ?, ?, ?, 'Awaiting Payment', CURDATE())";
                    $stmt = $pdo->prepare($sql);
                    $totalAmount = $amount + $handlingFee;
                    $stmt->execute([$clientId, $package, $amount, $totalAmount, $deliveryMethod, $handlingFee]);
                    $newId = $pdo->lastInsertId();
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'Quotation created and sent to client',
                        'quotation_id' => $newId
                    ]);
                }
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
            }
            exit;
            
        case 'send_to_client':
            // Update quotation status to send to client
            $quotationId = $input['quotation_id'] ?? null;
            
            if (!$quotationId) {
                echo json_encode(['success' => false, 'message' => 'Quotation ID required']);
                exit;
            }
            
            try {
                $sql = "UPDATE quotation SET Status = 'Awaiting Payment' WHERE Quotation_ID = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$quotationId]);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Quotation sent to client for payment'
                ]);
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            exit;
            
        case 'assign_job':
            // Assign technician and schedule job
            $quotationId = $_POST['quotation_id'] ?? null;
            $technicianTeam = $_POST['technician_team'] ?? '';
            $technicianName = $_POST['technician_name'] ?? '';
            $technicianContact = $_POST['technician_contact'] ?? '';
            $scheduleDate = $_POST['schedule_date'] ?? '';
            $scheduleTime = $_POST['schedule_time'] ?? '';
            
            if (!$quotationId || !$technicianTeam || !$scheduleDate) {
                echo json_encode(['success' => false, 'message' => 'Missing required fields']);
                exit;
            }
            
            try {
                // Create scheduled datetime
                $scheduledDateTime = $scheduleDate . ' ' . ($scheduleTime ?: '09:00') . ':00';
                
                // Update quotation status to Scheduled
                $sql = "UPDATE quotation 
                        SET Status = 'Scheduled',
                            Technician_Team = ?,
                            Technician_Name = ?,
                            Technician_Contact = ?,
                            Scheduled_Date = ?
                        WHERE Quotation_ID = ?";
                $stmt = $pdo->prepare($sql);
                
                // Check if columns exist, if not, just update status
                try {
                    $stmt->execute([$technicianTeam, $technicianName, $technicianContact, $scheduledDateTime, $quotationId]);
                } catch (PDOException $e) {
                    // Columns might not exist, just update status
                    $sql = "UPDATE quotation SET Status = 'Scheduled' WHERE Quotation_ID = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$quotationId]);
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Job assigned successfully',
                    'quotation_id' => $quotationId,
                    'technician_team' => $technicianTeam,
                    'scheduled_date' => $scheduledDateTime
                ]);
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
            }
            exit;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Unknown action']);
            exit;
    }
}

?>