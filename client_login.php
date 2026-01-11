<?php
error_reporting(0);
ini_set('display_errors', 0);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON provided.']);
    exit;
}

$email = $input['email'] ?? '';
$password = $input['password'] ?? '';

if (empty($email) || empty($password)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email and password are required.']);
    exit;
}

// Database connection details
$host = 'localhost';
$dbname = 'atmicxdb';
$username_db = 'root';
$password_db = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username_db, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Find the client by email
    $stmt = $pdo->prepare("SELECT Client_ID, Name, Email, Password_Hash FROM client WHERE Email = ?");
    $stmt->execute([$email]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verify password
    if ($client && password_verify($password, $client['Password_Hash'])) {
        // Password is correct, start the session
        $_SESSION['client_id'] = $client['Client_ID'];
        $_SESSION['email'] = $client['Email'];
        $_SESSION['name'] = $client['Name'];
        $_SESSION['role'] = 'client'; 

        echo json_encode(['success' => true, 'message' => 'Login successful.']);
    } else {
        // Invalid credentials
        echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'A database error occurred.']);
}
?>