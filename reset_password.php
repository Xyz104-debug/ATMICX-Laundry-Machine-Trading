<?php
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['token'])) {
    $token = $_GET['token'];
    // Show reset form
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Reset Password</title>
        <style>
            body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
            form { max-width: 300px; margin: auto; }
            input { display: block; width: 100%; margin: 10px 0; padding: 10px; }
            button { padding: 10px; background: #152238; color: white; border: none; cursor: pointer; }
        </style>
    </head>
    <body>
        <h2>Reset Your Password</h2>
        <form action="reset_password.php" method="POST">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            <input type="password" name="password" placeholder="New Password" required>
            <input type="password" name="confirm_password" placeholder="Confirm Password" required>
            <button type="submit">Reset Password</button>
        </form>
    </body>
    </html>
    <?php
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($password !== $confirm) {
        die('Passwords do not match.');
    }

    if (strlen($password) < 8) {
        die('Password must be at least 8 characters.');
    }

    // Database connection
    $host = 'localhost';
    $dbname = 'atmicxdb';
    $username_db = 'root';
    $password_db = '';

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username_db, $password_db);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Find user by token
        $stmt = $pdo->prepare("SELECT User_ID FROM user WHERE reset_token = ? AND reset_expires > NOW()");
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            die('Invalid or expired token.');
        }

        // Hash new password
        $options = [
            'memory_cost' => 65536,
            'time_cost' => 2,
            'threads' => 1
        ];
        $password_hash = password_hash($password, PASSWORD_ARGON2ID, $options);

        // Update password and clear token
        $stmt = $pdo->prepare("UPDATE user SET PasswordHash = ?, reset_token = NULL, reset_expires = NULL WHERE User_ID = ?");
        $stmt->execute([$password_hash, $user['User_ID']]);

        echo 'Password reset successfully. <a href="atmicxLOGIN.html">Login</a>';
    } catch (PDOException $e) {
        error_log($e->getMessage());
        die('Database error');
    }
}
?>