<?php
// Test file to check if the quotation API is working
session_start();

// Set up test client session
$_SESSION['client_id'] = 1;
$_SESSION['role'] = 'client';

echo "<h2>Testing Quotation API</h2>";

// Test database connection and quotation table structure
$host = 'localhost';
$dbname = 'atmicxdb';
$username_db = 'root';
$password_db = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username_db, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p>✓ Database connection established</p>";
    
    // Check table structure
    echo "<h3>Quotation Table Structure:</h3>";
    $stmt = $pdo->query("DESCRIBE quotation");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($col['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Default']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Try to fetch quotations
    echo "<h3>Test Fetch Quotations:</h3>";
    $sql = "SELECT 
                q.Quotation_ID,
                q.Package,
                q.Amount,
                q.Date_Issued,
                q.Status,
                q.Delivery_Method,
                q.Handling_Fee,
                q.Proof_File,
                q.Created_By,
                u.Name as User_Name
            FROM quotation q
            LEFT JOIN user u ON q.User_ID = u.User_ID
            WHERE q.Client_ID = ?
            ORDER BY q.Date_Issued DESC
            LIMIT 5";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([1]);
    $quotations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Found " . count($quotations) . " quotations</p>";
    
    if (count($quotations) > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Package</th><th>Amount</th><th>Status</th><th>Proof_File</th><th>Created_By</th></tr>";
        foreach ($quotations as $q) {
            echo "<tr>";
            echo "<td>" . $q['Quotation_ID'] . "</td>";
            echo "<td>" . htmlspecialchars($q['Package']) . "</td>";
            echo "<td>" . $q['Amount'] . "</td>";
            echo "<td>" . htmlspecialchars($q['Status']) . "</td>";
            echo "<td>" . ($q['Proof_File'] ? htmlspecialchars($q['Proof_File']) : 'NULL') . "</td>";
            echo "<td>" . ($q['Created_By'] ? htmlspecialchars($q['Created_By']) : 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<hr>";
    echo "<h3>Test API Call:</h3>";
    echo "<p>Making request to client_quotations_api.php...</p>";
    
    // Make actual API call
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost/ATMICX-Laundry-Machine-Trading/client_quotations_api.php?action=get_quotations');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "<p>HTTP Status Code: " . $http_code . "</p>";
    echo "<pre>";
    echo htmlspecialchars($response);
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>
