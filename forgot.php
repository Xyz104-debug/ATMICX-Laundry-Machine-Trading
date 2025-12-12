<?php
error_reporting(E_ALL & ~E_NOTICE);
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
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
if (!$input || !isset($input['email'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$email = $input['email'];

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email']);
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

    // Check if email exists
    $stmt = $pdo->prepare("SELECT User_ID, Name FROM user WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        // For security, don't reveal if email exists
        echo json_encode(['success' => true, 'message' => 'If the email exists, a reset link has been sent.']);
        exit;
    }

    // Generate reset token
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

    // Store token
    $stmt = $pdo->prepare("UPDATE user SET reset_token = ?, reset_expires = ? WHERE User_ID = ?");
    $stmt->execute([$token, $expires, $user['User_ID']]);

    // Send email
    $resetLink = "http://localhost/ATMICX-Laundry-Machine-Trading/reset_password.php?token=$token";
    $subject = 'Password Reset Request';
    $message = "Click the link to reset your password: $resetLink\n\nThis link expires in 1 hour.";
    $headers = 'From: noreply@atmicx.com';

    if (mail($email, $subject, $message, $headers)) {
        echo json_encode(['success' => true, 'message' => 'Reset link sent to your email.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to send email.']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>