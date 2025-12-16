<?php
// test_db.php
// A simple script to test the database connection.

header('Content-Type: text/plain');

$host = 'localhost';
$dbname = 'atmicxdb';
$username_db = 'root';
$password_db = '';

echo "Attempting to connect to database...\n";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username_db, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connection successful!\n";

    $stmt = $pdo->query("SELECT 1");
    $result = $stmt->fetchColumn();
    echo "Test query successful. Result: " . $result;

} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
} catch (Exception $e) {
    echo "An unexpected error occurred: " . $e->getMessage();
}

?>
