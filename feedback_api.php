<?php
require_once 'role_session_manager.php';
header('Content-Type: application/json');

// Manager authentication check
RoleSessionManager::start('manager');
if (!RoleSessionManager::isAuthenticated() || RoleSessionManager::getRole() !== 'manager') {
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

// Get All Feedback (with filters)
if ($method === 'GET' && !isset($_GET['action'])) {
    try {
        // Build query with filters
        $where_clauses = [];
        $params = [];
        
        // Filter by status
        if (isset($_GET['status']) && !empty($_GET['status'])) {
            $where_clauses[] = "cf.Status = ?";
            $params[] = $_GET['status'];
        }
        
        // Filter by category
        if (isset($_GET['category']) && !empty($_GET['category'])) {
            $where_clauses[] = "cf.Category = ?";
            $params[] = $_GET['category'];
        }
        
        // Filter by rating
        if (isset($_GET['rating']) && !empty($_GET['rating'])) {
            $where_clauses[] = "cf.Rating = ?";
            $params[] = intval($_GET['rating']);
        }
        
        // Filter by date range
        if (isset($_GET['date_from']) && !empty($_GET['date_from'])) {
            $where_clauses[] = "DATE(cf.Created_At) >= ?";
            $params[] = $_GET['date_from'];
        }
        
        if (isset($_GET['date_to']) && !empty($_GET['date_to'])) {
            $where_clauses[] = "DATE(cf.Created_At) <= ?";
            $params[] = $_GET['date_to'];
        }
        
        $where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";
        
        $stmt = $pdo->prepare("
            SELECT 
                cf.Feedback_ID,
                cf.Client_ID,
                c.Name as Client_Name,
                c.Contact_Num,
                cf.Rating,
                cf.Category,
                cf.Message,
                cf.Status,
                cf.Manager_Response,
                cf.Responded_At,
                cf.Created_At
            FROM client_feedback cf
            JOIN client c ON cf.Client_ID = c.Client_ID
            {$where_sql}
            ORDER BY cf.Created_At DESC
        ");
        
        $stmt->execute($params);
        $feedbacks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get statistics
        $stats_stmt = $pdo->query("
            SELECT 
                COUNT(*) as total_feedback,
                AVG(Rating) as avg_rating,
                SUM(CASE WHEN Status = 'new' THEN 1 ELSE 0 END) as new_count,
                SUM(CASE WHEN Status = 'reviewed' THEN 1 ELSE 0 END) as reviewed_count,
                SUM(CASE WHEN Status = 'responded' THEN 1 ELSE 0 END) as responded_count,
                SUM(CASE WHEN Status = 'resolved' THEN 1 ELSE 0 END) as resolved_count,
                SUM(CASE WHEN Rating = 5 THEN 1 ELSE 0 END) as five_star,
                SUM(CASE WHEN Rating = 4 THEN 1 ELSE 0 END) as four_star,
                SUM(CASE WHEN Rating = 3 THEN 1 ELSE 0 END) as three_star,
                SUM(CASE WHEN Rating = 2 THEN 1 ELSE 0 END) as two_star,
                SUM(CASE WHEN Rating = 1 THEN 1 ELSE 0 END) as one_star
            FROM client_feedback
        ");
        $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'feedbacks' => $feedbacks,
            'stats' => $stats
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to fetch feedback: ' . $e->getMessage()]);
    }
}

// Get Single Feedback Detail
elseif ($method === 'GET' && isset($_GET['action']) && $_GET['action'] === 'detail' && isset($_GET['id'])) {
    try {
        $feedback_id = intval($_GET['id']);
        
        $stmt = $pdo->prepare("
            SELECT 
                cf.Feedback_ID,
                cf.Client_ID,
                c.Name as Client_Name,
                c.Contact_Num,
                c.Email,
                cf.Rating,
                cf.Category,
                cf.Message,
                cf.Status,
                cf.Manager_Response,
                cf.Responded_At,
                cf.Created_At
            FROM client_feedback cf
            JOIN client c ON cf.Client_ID = c.Client_ID
            WHERE cf.Feedback_ID = ?
        ");
        
        $stmt->execute([$feedback_id]);
        $feedback = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($feedback) {
            echo json_encode([
                'success' => true,
                'feedback' => $feedback
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Feedback not found']);
        }
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to fetch feedback: ' . $e->getMessage()]);
    }
}

// Update Feedback Status
elseif ($method === 'PUT' && isset($_GET['action']) && $_GET['action'] === 'status') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['feedback_id']) || !isset($input['status'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }
    
    $feedback_id = intval($input['feedback_id']);
    $status = trim($input['status']);
    
    // Validate status
    $allowed_statuses = ['new', 'reviewed', 'responded', 'resolved'];
    if (!in_array($status, $allowed_statuses)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("
            UPDATE client_feedback 
            SET Status = ?
            WHERE Feedback_ID = ?
        ");
        
        $stmt->execute([$status, $feedback_id]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Status updated successfully'
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to update status: ' . $e->getMessage()]);
    }
}

// Respond to Feedback
elseif ($method === 'POST' && isset($_GET['action']) && $_GET['action'] === 'respond') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['feedback_id']) || !isset($input['response'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }
    
    $feedback_id = intval($input['feedback_id']);
    $response = trim($input['response']);
    
    if (strlen($response) < 10) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Response must be at least 10 characters']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("
            UPDATE client_feedback 
            SET Manager_Response = ?, 
                Status = 'responded', 
                Responded_At = NOW()
            WHERE Feedback_ID = ?
        ");
        
        $stmt->execute([$response, $feedback_id]);
        
        // Log the response
        $log_file = __DIR__ . '/logs/system/feedback_responses.log';
        if (!file_exists(dirname($log_file))) {
            mkdir(dirname($log_file), 0777, true);
        }
        $log_entry = date('Y-m-d H:i:s') . " - Manager responded to feedback ID: {$feedback_id}\n";
        file_put_contents($log_file, $log_entry, FILE_APPEND);
        
        echo json_encode([
            'success' => true,
            'message' => 'Response sent successfully'
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to send response: ' . $e->getMessage()]);
    }
}

else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>
