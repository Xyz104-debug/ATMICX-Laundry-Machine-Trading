<?php
session_start();

header('Content-Type: application/json');

// Debug session information
error_log("Session data: " . print_r($_SESSION, true));

// Check if client is logged in
if (!isset($_SESSION['client_id'])) {
    error_log("Client session check failed - client_id not set");
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$client_id = $_SESSION['client_id'];

// Database connection
$host = 'localhost';
$dbname = 'atmicxdb';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get quotations - simplified query without service table joins for now
    $stmt = $pdo->prepare("
        SELECT q.Quotation_ID, q.Package, q.Amount, q.Date_Issued, q.Status, q.Delivery_Method
        FROM quotation q
        WHERE q.Client_ID = ?
        ORDER BY q.Date_Issued DESC
    ");
    $stmt->execute([$client_id]);
    $quotations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format quotations for client display
    $formatted_quotations = [];
    foreach ($quotations as $quote) {
        $reference = "QT-" . str_pad($quote['Quotation_ID'], 4, '0', STR_PAD_LEFT);
            
        $formatted_quotations[] = [
            'id' => $quote['Quotation_ID'],
            'reference' => $reference,
            'package' => $quote['Package'],
            'amount' => $quote['Amount'],
            'date_issued' => $quote['Date_Issued'],
            'status' => $quote['Status'],
            'delivery_method' => $quote['Delivery_Method'],
            'is_service_request' => false,
            'service_id' => null,
            'problem_description' => null
        ];
    }
    
    echo json_encode([
        'success' => true,
        'quotations' => $formatted_quotations
    ]);
    
} catch (Exception $e) {
    error_log("Client quotations error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Failed to load quotations: ' . $e->getMessage()
    ]);
}
?>