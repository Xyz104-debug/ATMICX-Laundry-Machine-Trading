<?php
error_reporting(E_ALL & ~E_NOTICE);

require_once 'role_session_manager.php';

// Try to detect which role session exists
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

// If no role session found, try default session
if (!$active_role) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
} else {
    // Start the appropriate role session
    RoleSessionManager::start($active_role);
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Adjust or restrict in production
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Check authentication using RoleSessionManager
if (!RoleSessionManager::isAuthenticated()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$current_role = RoleSessionManager::getRole();
if (!in_array(strtolower($current_role), ['manager', 'secretary'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized - requires manager or secretary role (current: ' . $current_role . ')']);
    exit;
}

// Database connection (reusing settings from login.php)
if (!extension_loaded('pdo')) {
    echo json_encode(['success' => false, 'message' => 'PDO extension not loaded']);
    exit;
}

$host = 'localhost';
$dbname = 'atmicxdb';
$username_db = 'root';
$password_db = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username_db, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB connection failed: ' . $e->getMessage()]);
    exit;
}

// Helper: send JSON error
function json_error($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // LIST USERS
    try {
        $stmt = $pdo->prepare("SELECT User_ID, Name, Role, Status, email FROM user ORDER BY User_ID ASC");
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Ensure all fields have default values
        foreach ($users as &$user) {
            $user['Status'] = $user['Status'] ?? 'Active';
            $user['email'] = $user['email'] ?? '';
            $user['Role'] = $user['Role'] ?? 'User';
        }

        echo json_encode([
            'success' => true,
            'users' => $users,
            'count' => count($users)
        ]);
        
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false, 
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
    exit;
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        json_error('Invalid JSON payload');
    }

    $action = $input['action'] ?? '';

    switch ($action) {
        case 'create':
            $name   = trim($input['name'] ?? '');
            $email  = trim($input['email'] ?? '');
            $role   = trim($input['role'] ?? '');
            $status = trim($input['status'] ?? '');

            if ($name === '' || $email === '' || $role === '') {
                json_error('Name, email and role are required');
            }

            // Basic default status
            if ($status === '') {
                $status = 'Active';
            }

            // Generate a temporary password for new staff (to be changed later)
            $tempPassword = bin2hex(random_bytes(4)); // 8 hex chars
            $passwordHash = password_hash($tempPassword, PASSWORD_DEFAULT);

            // Insert new user
            $stmt = $pdo->prepare(
                "INSERT INTO user (Name, PasswordHash, Role, Status, email) 
                 VALUES (:name, :passwordHash, :role, :status, :email)"
            );
            $stmt->execute([
                ':name'         => $name,
                ':passwordHash' => $passwordHash,
                ':role'         => $role,
                ':status'       => $status,
                ':email'        => $email,
            ]);

            $newId = (int)$pdo->lastInsertId();

            echo json_encode([
                'success'   => true,
                'message'   => 'User created',
                'user'      => [
                    'User_ID' => $newId,
                    'Name'    => $name,
                    'Role'    => $role,
                    'Status'  => $status,
                    'email'   => $email,
                ],
                // Optionally return temp password for debug/admin; in real systems, send via email
                'tempPassword' => $tempPassword,
            ]);
            exit;

        case 'update':
            $userId = (int)($input['user_id'] ?? 0);
            $role   = trim($input['role'] ?? '');
            $status = trim($input['status'] ?? '');

            if ($userId <= 0) {
                json_error('Invalid user_id');
            }
            if ($role === '') {
                json_error('Role is required');
            }
            if ($status === '') {
                $status = 'Active';
            }

            $stmt = $pdo->prepare(
                "UPDATE user SET Role = :role, Status = :status WHERE User_ID = :id"
            );
            $stmt->execute([
                ':role'   => $role,
                ':status' => $status,
                ':id'     => $userId,
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'User updated',
            ]);
            exit;

        case 'delete':
            $userId = (int)($input['user_id'] ?? 0);
            if ($userId <= 0) {
                json_error('Invalid user_id');
            }

            // Optional: prevent deleting own account
            if ($userId === (int)$_SESSION['user_id']) {
                json_error('You cannot delete your own account', 403);
            }

            $stmt = $pdo->prepare("DELETE FROM user WHERE User_ID = :id");
            $stmt->execute([':id' => $userId]);

            echo json_encode([
                'success' => true,
                'message' => 'User deleted',
            ]);
            exit;

        default:
            json_error('Unknown action', 400);
    }
}

// For any other method
json_error('Method not allowed', 405);


