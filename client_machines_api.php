<?php
error_reporting(E_ALL & ~E_NOTICE);
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// Check if client is logged in
if (!isset($_SESSION['client_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please log in as a client.']);
    exit;
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
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

$client_id = $_SESSION['client_id'];
$action = $_GET['action'] ?? 'get_owned_machines';

/**
 * Parse package string to extract machine counts
 * Examples: "2 Sets (2 Washers + 2 Dryers)", "The Micro Start (2 Sets)", "3 Sets"
 */
function parseMachineCount($packageString) {
    // Try to find "X Washers + Y Dryers" pattern
    if (preg_match('/(\d+)\s*Washers?\s*\+\s*(\d+)\s*Dryers?/i', $packageString, $matches)) {
        return [
            'washers' => (int)$matches[1],
            'dryers' => (int)$matches[2]
        ];
    }
    
    // Try to find "X Sets" pattern - assumes equal washers and dryers
    if (preg_match('/(\d+)\s*Sets?/i', $packageString, $matches)) {
        $sets = (int)$matches[1];
        return [
            'washers' => $sets,
            'dryers' => $sets
        ];
    }
    
    return [
        'washers' => 0,
        'dryers' => 0
    ];
}

/**
 * Generate machine serial numbers based on quotation
 */
function generateMachineSerials($quotationId, $machineType, $count, $purchaseDate) {
    $machines = [];
    $typePrefix = $machineType === 'Washer' ? 'W' : 'D';
    
    for ($i = 1; $i <= $count; $i++) {
        $serialNum = sprintf('%s-Q%d-%03d', $typePrefix, $quotationId, $i);
        $machines[] = [
            'serial_number' => $serialNum,
            'type' => $machineType,
            'model' => $machineType === 'Washer' ? 'W-18' : 'D-25', // Default models
            'purchased_date' => $purchaseDate,
            'status' => 'Active',
            'quotation_id' => $quotationId
        ];
    }
    
    return $machines;
}

switch ($action) {
    case 'get_owned_machines':
        try {
            // Fetch all completed/verified/approved quotations for this client
            // Status can be: Verified, Approved, Completed, Paid
            $sql = "SELECT 
                        Quotation_ID,
                        Package,
                        Status,
                        Date_Issued,
                        Amount
                    FROM quotation
                    WHERE Client_ID = ? 
                    AND Status IN ('Verified', 'Approved', 'Completed', 'Paid')
                    ORDER BY Date_Issued ASC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$client_id]);
            $quotations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $allMachines = [];
            $stats = [
                'total_washers' => 0,
                'total_dryers' => 0,
                'total_machines' => 0,
                'total_packages' => count($quotations)
            ];
            
            foreach ($quotations as $quote) {
                $machineCount = parseMachineCount($quote['Package']);
                
                // Generate washer serials
                $washers = generateMachineSerials(
                    $quote['Quotation_ID'],
                    'Washer',
                    $machineCount['washers'],
                    $quote['Date_Issued']
                );
                
                // Generate dryer serials
                $dryers = generateMachineSerials(
                    $quote['Quotation_ID'],
                    'Dryer',
                    $machineCount['dryers'],
                    $quote['Date_Issued']
                );
                
                $allMachines = array_merge($allMachines, $washers, $dryers);
                
                $stats['total_washers'] += $machineCount['washers'];
                $stats['total_dryers'] += $machineCount['dryers'];
            }
            
            $stats['total_machines'] = $stats['total_washers'] + $stats['total_dryers'];
            
            echo json_encode([
                'success' => true,
                'machines' => $allMachines,
                'stats' => $stats
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to fetch machines: ' . $e->getMessage()
            ]);
        }
        break;
        
    case 'get_machine_details':
        try {
            $serial = $_GET['serial'] ?? '';
            
            if (empty($serial)) {
                echo json_encode(['success' => false, 'message' => 'Serial number required']);
                exit;
            }
            
            // Extract quotation ID from serial (format: W-Q1-001)
            if (preg_match('/[WD]-Q(\d+)-\d+/', $serial, $matches)) {
                $quotationId = (int)$matches[1];
                
                // Fetch quotation details
                $sql = "SELECT 
                            q.Quotation_ID,
                            q.Package,
                            q.Status,
                            q.Date_Issued,
                            q.Amount,
                            c.Name as Client_Name,
                            c.Business_Name
                        FROM quotation q
                        LEFT JOIN client c ON q.Client_ID = c.Client_ID
                        WHERE q.Quotation_ID = ? AND q.Client_ID = ?";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$quotationId, $client_id]);
                $quote = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($quote) {
                    $machineType = substr($serial, 0, 1) === 'W' ? 'Washer' : 'Dryer';
                    
                    echo json_encode([
                        'success' => true,
                        'machine' => [
                            'serial_number' => $serial,
                            'type' => $machineType,
                            'model' => $machineType === 'Washer' ? 'W-18' : 'D-25',
                            'purchased_date' => $quote['Date_Issued'],
                            'status' => 'Active',
                            'package' => $quote['Package'],
                            'quotation_ref' => 'QT-' . $quote['Quotation_ID']
                        ]
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Machine not found']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid serial number format']);
            }
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to fetch machine details: ' . $e->getMessage()
            ]);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}
?>
