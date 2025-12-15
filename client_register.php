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

$username = $input['username'] ?? '';
$email = $input['email'] ?? '';
$password = $input['password'] ?? '';
$contact = $input['contact'] ?? '';
$address = $input['address'] ?? '';


if (!$email || !$username || !$password) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Username, Email, and Password are required.']);
    exit;
}

// Basic validation
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format.']);
    exit;
}

if (strlen($password) < 8) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters long.']);
    exit;
}

// Database connection
$host = 'localhost';
$dbname = 'atmicxdb';
$username_db = 'root';
$password_db = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username_db, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if username or email already exists in the client table
    $stmt = $pdo->prepare("SELECT Client_ID FROM client WHERE Name = ? OR Email = ?");
    $stmt->execute([$username, $email]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Username or email already taken.']);
        exit;
    }

    // Hash password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Insert new client
    $stmt = $pdo->prepare("INSERT INTO client (Name, Email, Password_Hash, Contact_Num, Address) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$username, $email, $password_hash, $contact, $address]);

    echo json_encode(['success' => true, 'message' => 'Registration successful! You can now log in.']);
} catch (PDOException $e) {
    http_response_code(500);
    // Note: Be careful with error messages in production
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>