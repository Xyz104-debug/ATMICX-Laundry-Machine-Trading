<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once 'role_session_manager.php';

    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST');
    header('Access-Control-Allow-Headers: Content-Type');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        // Allow GET requests for debugging
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            echo json_encode(['success' => false, 'message' => 'This API requires POST requests with username/email and password']);
            exit;
        }
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

    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username_db, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if user table exists, if not fall back to hardcoded accounts
    $stmt = $pdo->query("SHOW TABLES LIKE 'user'");
    $tableExists = $stmt->fetchColumn();
    
    if (!$tableExists) {
        // Fall back to hardcoded accounts if user table doesn't exist
        $valid_credentials = [
            'manager' => ['username' => 'manager', 'password' => 'manager123', 'user_id' => 1],
            'secretary' => ['username' => 'secretary', 'password' => 'secretary123', 'user_id' => 2]
        ];
        
        $role_key = $role;
        if ($username === 'manager') $role_key = 'manager';
        if ($username === 'secretary') $role_key = 'secretary';
        
        if (!isset($valid_credentials[$role_key]) || 
            $valid_credentials[$role_key]['username'] !== $username || 
            $valid_credentials[$role_key]['password'] !== $password) {
            
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
            exit;
        }
        
        // Start session for this role
        RoleSessionManager::start($role);
        
        // Create the session
        RoleSessionManager::login(
            $valid_credentials[$role_key]['user_id'],
            $username,
            $role
        );
        
        echo json_encode([
            'success' => true,
            'message' => "Logged in as $role",
            'user_id' => $valid_credentials[$role_key]['user_id'],
            'username' => $username,
            'role' => $role,
            'redirect' => $role === 'manager' ? 'atmicxMANAGER.php' : 'armicxSECRETARY.php'
        ]);
        
    } else {
        // Use database authentication - check both username and email
        $stmt = $pdo->prepare("SELECT * FROM user WHERE (username = ? OR email = ?) AND role = ?");
        $stmt->execute([$username, $username, $role]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'User not found or invalid role']);
            exit;
        }
        
        // Verify password
        $password_valid = false;
        if (isset($user['password']) && !empty($user['password'])) {
            if (password_verify($password, $user['password'])) {
                $password_valid = true;
            } elseif ($password === $user['password']) {
                $password_valid = true;
            }
        }
        
        if (!$password_valid) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Invalid password']);
            exit;
        }
        
        // Start session for this role
        RoleSessionManager::start($role);
        
        // Create the session using database user data
        RoleSessionManager::login(
            $user['id'],
            $user['username'] ?? $user['email'],
            $user['role']
        );
        
        echo json_encode([
            'success' => true,
            'message' => "Logged in as {$user['role']}",
            'user_id' => $user['id'],
            'username' => $user['username'] ?? $user['email'],
            'role' => $user['role'],
            'redirect' => $user['role'] === 'manager' ? 'atmicxMANAGER.php' : 'armicxSECRETARY.php'
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Login failed: ' . $e->getMessage()]);
}
?>