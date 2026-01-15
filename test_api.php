<?php
// Test the client quotations API directly
session_start();
$_SESSION['client_id'] = 2; // Set to client ID 2
$_SESSION['role'] = 'client';

// Call the API
$response = file_get_contents('http://localhost/ATMICX-Laundry-Machine-Trading/client_quotations_api.php?action=get_quotations');
echo "API Response:\n";
echo $response;
echo "\n\n";

// Also test direct SQL
$host = 'localhost';
$dbname = 'atmicxdb';
$username_db = 'root';
$password_db = '';

$pdo = new PDO("mysql:host=$host;dbname=$dbname", $username_db, $password_db);
$sql = "SELECT Quotation_ID, Status FROM quotation 
        WHERE Client_ID = 2 
        AND Status NOT IN ('Awaiting Secretary Review', 'Pending Manager Approval', 'Awaiting Manager Approval')
        ORDER BY Date_Issued DESC";
$stmt = $pdo->query($sql);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Direct SQL Query Results:\n";
print_r($results);
?>
