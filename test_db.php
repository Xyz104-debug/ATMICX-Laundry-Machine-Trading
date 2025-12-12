<?php
// Test database connection and table
$host = 'localhost';
$dbname = 'atmicxdb';
$username_db = 'root';
$password_db = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username_db, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Database connection successful.<br>";

    // Check if users table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'user'");
    if ($stmt->rowCount() > 0) {
        echo "User table exists.<br>";

        // Check columns
        $stmt = $pdo->query("DESCRIBE user");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        echo "Columns: " . implode(', ', $columns) . "<br>";

        // Check required columns
        $required = ['User_ID', 'Name', 'email', 'PasswordHash', 'Role', 'reset_token', 'reset_expires'];
        $missing = array_diff($required, $columns);
        if ($missing) {
            echo "Missing columns: " . implode(', ', $missing) . "<br>";
        } else {
            echo "All required columns present.<br>";
        }
    } else {
        echo "User table does not exist.<br>";
    }
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
}
?>