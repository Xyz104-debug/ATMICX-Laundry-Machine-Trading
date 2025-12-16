<?php
// This script is for debugging purposes only.
// It should be deleted after the login issue is resolved.

error_reporting(E_ALL);
ini_set('display_errors', 1);

// --- Configuration ---
// Enter the email and password you are trying to log in with.
$test_email = "hughz2004@gmail.com";
$test_password = "password";
// ---------------------

echo "<h1>Login Debugging Script</h1>";
echo "<p>Testing with email: <strong>$test_email</strong></p>";
echo "<p>Testing with password: <strong>$test_password</strong></p>";
echo "<hr>";

// --- Database Connection ---
$host = 'localhost';
$dbname = 'atmicxdb';
$username_db = 'root';
$password_db = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username_db, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p style='color:green;'>Database connection successful.</p>";
} catch (PDOException $e) {
    echo "<p style='color:red;'>Database connection failed: " . $e->getMessage() . "</p>";
    exit;
}

// --- Check if user exists ---
try {
    $stmt = $pdo->prepare("SELECT * FROM user WHERE Email = ?");
    $stmt->execute([$test_email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        echo "<p style='color:green;'>User found with email: " . htmlspecialchars($user['Email']) . "</p>";
        echo "<p>Stored Role: <strong>" . htmlspecialchars($user['Role']) . "</strong></p>";
        echo "<p>Stored Password Hash: <strong>" . htmlspecialchars($user['PasswordHash']) . "</strong></p>";

        // --- Verify Password ---
        if (password_verify($test_password, $user['PasswordHash'])) {
            echo "<p style='color:green; font-weight:bold;'>Password verification successful!</p>";
            echo "<p>You should be able to log in with this email and password.</p>";
        } else {
            echo "<p style='color:red; font-weight:bold;'>Password verification failed!</p>";
            echo "<p>The password you entered is incorrect.</p>";
        }
    } else {
        echo "<p style='color:red;'>User not found with email: " . $test_email . "</p>";
        echo "<p>Please make sure this email is registered in the 'user' table.</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>An error occurred: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><strong>Note:</strong> If the password hash looks like a plain password, it means the passwords are not being hashed correctly during registration.</p>";

?>
