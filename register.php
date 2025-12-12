<?php
error_reporting(E_ALL & ~E_NOTICE);
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Adjust for security
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

$role = $input['role'] ?? '';
$email = $input['email'] ?? '';
$username = $input['username'] ?? '';
$password = $input['password'] ?? '';

if (!$role || !$email || !$username || !$password) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing fields']);
    exit;
}

// Basic validation
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email']);
    exit;
}

if (strlen($password) < 8) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters']);
    exit;
}

// Database connection
if (!extension_loaded('pdo')) {
    echo json_encode(['success' => false, 'message' => 'PDO extension not loaded']);
    exit;
}
$host = 'localhost';
$dbname = 'atmicxdb';
$username_db = 'root';
$password_db = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username_db, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if username or email already exists
    $stmt = $pdo->prepare("SELECT User_ID FROM user WHERE Name = ? OR email = ?");
    $stmt->execute([$username, $email]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Username or email already exists']);
        exit;
    }

    // Hash password
    $options = [
        'memory_cost' => 65536,
        'time_cost' => 2,
        'threads' => 1
    ];
    $password_hash = password_hash($password, PASSWORD_ARGON2ID, $options);

    // Insert new user
    $stmt = $pdo->prepare("INSERT INTO user (Name, email, PasswordHash, Role) VALUES (?, ?, ?, ?)");
    $stmt->execute([$username, $email, $password_hash, $role]);

    echo json_encode(['success' => true, 'message' => 'Registration successful']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>