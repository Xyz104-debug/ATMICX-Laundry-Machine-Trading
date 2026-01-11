<?php
session_start();
header('Content-Type: application/json');

// Client authentication check
if (!isset($_SESSION['client_id']) || $_SESSION['role'] !== 'client') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
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
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

// Submit Feedback
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate input
    if (!isset($input['rating']) || !isset($input['category']) || !isset($input['message'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }
    
    $rating = intval($input['rating']);
    $category = trim($input['category']);
    $message = trim($input['message']);
    
    // Validate rating
    if ($rating < 1 || $rating > 5) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Rating must be between 1 and 5']);
        exit;
    }
    
    // Validate category
    $allowed_categories = ['General', 'Service Quality', 'Product Quality', 'Customer Support', 'Pricing', 'Delivery', 'Other'];
    if (!in_array($category, $allowed_categories)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid category']);
        exit;
    }
    
    // Validate message
    if (strlen($message) < 10) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Message must be at least 10 characters']);
        exit;
    }
    
    if (strlen($message) > 1000) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Message must not exceed 1000 characters']);
        exit;
    }
    
    try {
        // Insert feedback
        $stmt = $pdo->prepare("
            INSERT INTO client_feedback (Client_ID, Rating, Category, Message, Status, Created_At)
            VALUES (?, ?, ?, ?, 'new', NOW())
        ");
        
        $stmt->execute([
            $_SESSION['client_id'],
            $rating,
            $category,
            $message
        ]);
        
        $feedback_id = $pdo->lastInsertId();
        
        // Log the feedback submission
        $log_file = __DIR__ . '/logs/system/feedback_submissions.log';
        if (!file_exists(dirname($log_file))) {
            mkdir(dirname($log_file), 0777, true);
        }
        $log_entry = date('Y-m-d H:i:s') . " - Client ID: {$_SESSION['client_id']} submitted feedback ID: {$feedback_id} with rating: {$rating}\n";
        file_put_contents($log_file, $log_entry, FILE_APPEND);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Feedback submitted successfully',
            'feedback_id' => $feedback_id
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to submit feedback: ' . $e->getMessage()]);
    }
}

// Get Client's Feedback History
elseif ($method === 'GET') {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                Feedback_ID,
                Rating,
                Category,
                Message,
                Status,
                Manager_Response,
                Responded_At,
                Created_At
            FROM client_feedback
            WHERE Client_ID = ?
            ORDER BY Created_At DESC
        ");
        
        $stmt->execute([$_SESSION['client_id']]);
        $feedbacks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'feedbacks' => $feedbacks
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to fetch feedback: ' . $e->getMessage()]);
    }
}

else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>
