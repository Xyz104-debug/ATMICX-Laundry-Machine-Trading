<?php
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

if (!isset($input['email']) || !filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'A valid email is required.']);
    exit;
}

$email = $input['email'];

$host = 'localhost';
$dbname = 'atmicxdb';
$username_db = 'root';
$password_db = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username_db, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("SELECT Client_ID FROM client WHERE Email = ?");
    $stmt->execute([$email]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($client) {
        // Generate a secure token
        $token = bin2hex(random_bytes(32));
        // Set expiry to 1 hour from now
        $expires = date('Y-m-d H:i:s', time() + 3600);

        // Store the token and expiry in the client's record
        // This assumes your `client` table has `reset_token` and `reset_expires` columns.
        $updateStmt = $pdo->prepare(
            "UPDATE client SET reset_token = ?, reset_expires = ? WHERE Client_ID = ?"
        );
        $updateStmt->execute([$token, $expires, $client['Client_ID']]);

        // In a real application, you would email this link.
        // For this demo, we will return it in the response.
        $resetLink = "http://localhost/ATMICX-Laundry-Machine-Trading/reset_password.php?token=$token&type=client";
        
        echo json_encode([
            'success' => true, 
            'message' => 'A password reset link has been generated. Please use the link below to reset your password.',
            'reset_link' => $resetLink 
        ]);

    } else {
        // To prevent user enumeration, we send a generic success response even if the email is not found.
        echo json_encode(['success' => true, 'message' => 'If an account with that email exists, a reset link has been generated.']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    // Avoid exposing detailed error messages in a production environment
    echo json_encode(['success' => false, 'message' => 'A database error occurred.']);
}
?>