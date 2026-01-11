<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
        exit;
    }

    $username = $input['username'] ?? '';
    $password = $input['password'] ?? '';
    $role = $input['role'] ?? '';

    if (!$username || !$password || !$role) {
        echo json_encode(['success' => false, 'message' => 'Missing fields']);
        exit;
    }

    // Start basic session
    session_start();

    // Database connection
    $host = 'localhost';
    $dbname = 'atmicxdb';
    $username_db = 'root';
    $password_db = '';

    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username_db, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if user table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'user'");
    $tableExists = $stmt->fetchColumn();
    
    if (!$tableExists) {
        // Fallback to hardcoded accounts
        $valid_credentials = [
            'manager' => 'manager123',
            'secretary' => 'secretary123'
        ];
        
        if (($username === 'manager' || $username === 'secretary') && 
            isset($valid_credentials[$username]) && 
            $valid_credentials[$username] === $password) {
            
            $_SESSION['user_id'] = $username === 'manager' ? 1 : 2;
            $_SESSION['username'] = $username;
            $_SESSION['role'] = $username;
            
            echo json_encode([
                'success' => true,
                'message' => "Logged in as $username",
                'role' => $username,
                'redirect' => $username === 'manager' ? 'atmicxMANAGER.php' : 'armicxSECRETARY.php'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
        }
    } else {
        // Use database authentication
        $stmt = $pdo->prepare("SELECT * FROM user WHERE (username = ? OR email = ?) AND role = ?");
        $stmt->execute([$username, $username, $role]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'User not found']);
            exit;
        }
        
        // Simple password check
        if ($password === $user['password'] || password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'] ?? $user['email'];
            $_SESSION['role'] = $user['role'];
            
            echo json_encode([
                'success' => true,
                'message' => "Logged in as {$user['role']}",
                'role' => $user['role'],
                'redirect' => $user['role'] === 'manager' ? 'atmicxMANAGER.php' : 'armicxSECRETARY.php'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid password']);
        }
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>