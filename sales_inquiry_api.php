<?php
// Sales Inquiry API for handling package investment requests
session_start();

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

// Create sales inquiry from package investment request
function createSalesInquiry($clientId, $packageData) {
    $pdo = getDBConnection();
    if (!$pdo) {
        return ["success" => false, "message" => "Database connection failed"];
    }
    
    try {
        // Get client information
        $stmt = $pdo->prepare("SELECT Name, Contact_Num, Address FROM client WHERE Client_ID = ?");
        $stmt->execute([$clientId]);
        $client = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$client) {
            return ["success" => false, "message" => "Client not found"];
        }
        
        // Package definitions
        $packages = [
            'P-1-MicroStart' => ["name" => "The Micro Start", "sets" => "2 Sets", "price" => 600000],
            'P-2-EssentialStart' => ["name" => "The Essential Start", "sets" => "3 Sets", "price" => 900000],
            'P-3-StandardShop' => ["name" => "The Standard Shop", "sets" => "4 Sets", "price" => 1200000],
            'P-4-GrowthModel' => ["name" => "The Growth Model", "sets" => "5 Sets", "price" => 1500000],
            'P-5-PremiumCorner' => ["name" => "The Premium Corner", "sets" => "6 Sets", "price" => 2000000],
            'P-6-AnchorLaundromat' => ["name" => "The Anchor Laundromat", "sets" => "8 Sets", "price" => 2700000],
            'P-7-IndustrialLite' => ["name" => "The Industrial Lite", "sets" => "10 Sets", "price" => 3500000],
            'P-8-MultiLoadCenter' => ["name" => "The Multi-Load Center", "sets" => "12 Sets", "price" => 4500000],
            'P-9-TechnologyHub' => ["name" => "The Technology Hub", "sets" => "15 Sets", "price" => 6000000],
            'P-10-FlagshipEnterprise' => ["name" => "The Flagship Enterprise", "sets" => "20 Sets", "price" => 8500000]
        ];
        
        // Get package details
        $package = $packages[$packageData['package']] ?? $packages['P-1-MicroStart'];
        $logisticsFee = ($packageData['logistics'] === 'standard') ? $package['price'] * 0.05 : 0;
        $totalAmount = $package['price'] + $logisticsFee;
        
        // Create quotation entry
        $stmt = $pdo->prepare("INSERT INTO quotation 
            (Client_ID, Package, Amount, Date_Issued, Status, Delivery_Method, Handling_Fee) 
            VALUES (?, ?, ?, CURDATE(), 'Awaiting Secretary Review', ?, ?)");
        
        $result = $stmt->execute([
            $clientId,
            $package['name'] . ' (' . $package['sets'] . ')',
            $totalAmount,
            $packageData['logistics'] === 'standard' ? 'Standard Delivery' : 'Self Pickup',
            $logisticsFee
        ]);
        
        if ($result) {
            $quotationId = $pdo->lastInsertId();
            
            return [
                "success" => true, 
                "message" => "Investment request sent to secretary successfully",
                "quotation_id" => $quotationId,
                "total_amount" => $totalAmount,
                "client_name" => $client['Name']
            ];
        } else {
            return ["success" => false, "message" => "Failed to create sales inquiry"];
        }
        
    } catch (PDOException $e) {
        error_log("Error creating sales inquiry: " . $e->getMessage());
        return ["success" => false, "message" => "Database error: " . $e->getMessage()];
    }
}

// Get recent sales inquiries for secretary dashboard
function getSalesInquiries($limit = 10) {
    $pdo = getDBConnection();
    if (!$pdo) return [];
    
    try {
        $sql = "SELECT q.*, c.Name as Client_Name, c.Contact_Num, c.Address 
                FROM quotation q 
                LEFT JOIN client c ON q.Client_ID = c.Client_ID 
                WHERE q.Status IN ('Awaiting Secretary Review', 'Pending Manager Approval') 
                ORDER BY q.Date_Issued DESC 
                LIMIT ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$limit]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting sales inquiries: " . $e->getMessage());
        return [];
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'send_package_request':
            // Flexible client authentication
            $client_id = null;
            
            if (isset($_SESSION['client_id'])) {
                $client_id = $_SESSION['client_id'];
            } elseif (isset($_SESSION['user_id'])) {
                // Use existing user session as client
                $client_id = $_SESSION['user_id'];
                $_SESSION['client_id'] = $client_id;
            } else {
                // Create a test client for debugging
                $client_id = 1;
                $_SESSION['client_id'] = $client_id;
            }
            
            $packageData = [
                'package' => $_POST['package'] ?? '',
                'logistics' => $_POST['logistics'] ?? 'standard'
            ];
            
            $result = createSalesInquiry($client_id, $packageData);
            echo json_encode($result);
            exit;
            
        case 'get_sales_inquiries':
            $inquiries = getSalesInquiries($_POST['limit'] ?? 10);
            echo json_encode(["success" => true, "inquiries" => $inquiries]);
            exit;
    }
}

// Handle GET requests for testing
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['test'])) {
    echo "Sales Inquiry API is working!";
}

?>