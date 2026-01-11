<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'role_session_manager.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
    exit;
}

$username = $input['username'] ?? '';
$password = $input['password'] ?? '';
$role = $input['role'] ?? '';

if (!$username || !$password || !$role) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing fields']);
    exit;
}

if (!in_array($role, ['manager', 'secretary'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid role']);
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
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Database authentication
try {
    // Check if user exists with the given username OR email
    $stmt = $pdo->prepare("SELECT User_ID as id, Name as username, PasswordHash as password, Role as role, email FROM user WHERE (Name = ? OR email = ?)");
    $stmt->execute([$username, $username]);
    $user_by_identity = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user_by_identity) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => "User '$username' not found. Available users: blazeking58 (secretary), blazeking5 (manager) or use emails: barbasgaming1@gmail.com, hughz2004@gmail.com"]);
        exit;
    }
    
    // Check if role matches
    if (strtolower($user_by_identity['role']) !== strtolower($role)) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => "User '$username' exists but has role '{$user_by_identity['role']}', not '$role'"]);
        exit;
    }
    
    $user = $user_by_identity;
    
    // Verify password (check if password is hashed or plain text)
    $password_valid = false;
    if (password_verify($password, $user['password'])) {
        // Hashed password verification
        $password_valid = true;
    } elseif ($password === $user['password']) {
        // Plain text password verification (for existing accounts)
        $password_valid = true;
    }
    
    if (!$password_valid) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Invalid password for user: ' . $username]);
        exit;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Authentication failed: ' . $e->getMessage()]);
    exit;
}

try {
    // Start session for this role
    RoleSessionManager::start($role);
    
    // Create the session using database user data
    RoleSessionManager::login(
        $user['id'],
        $user['username'],
        $user['role']
    );
    
    echo json_encode([
        'success' => true,
        'message' => "Logged in as {$user['role']}",
        'user_id' => $user['id'],
        'username' => $user['username'],
        'role' => $user['role'],
        'redirect' => $user['role'] === 'manager' ? 'atmicxMANAGER.php' : 'armicxSECRETARY.php'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Login failed: ' . $e->getMessage()]);
}
?>