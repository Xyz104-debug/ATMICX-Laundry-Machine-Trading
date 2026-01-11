<?php
// Cleanup test quotations
$host = 'localhost';
$dbname = 'atmicxdb';
$username_db = 'root';
$password_db = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username_db, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    if (isset($_GET['action']) && $_GET['action'] === 'clear_test') {
        // Delete test quotations
        $stmt = $pdo->prepare("DELETE FROM quotation WHERE Package LIKE '%Test%' OR Package LIKE '%Micro Start%' OR Package LIKE '%Essential Start%' OR Package LIKE '%Standard Shop%'");
        $deleted = $stmt->execute();
        $count = $stmt->rowCount();
        
        echo "Cleared $count test quotations.<br>";
    }
    
    echo "<h3>Current Quotation Data:</h3>";
    
    // Show all quotations
    $stmt = $pdo->prepare("SELECT q.*, c.Name as Client_Name FROM quotation q LEFT JOIN client c ON q.Client_ID = c.Client_ID ORDER BY q.Date_Issued DESC");
    $stmt->execute();
    $quotations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Total quotations: " . count($quotations) . "<br><br>";
    
    foreach ($quotations as $quote) {
        echo "<div style='border: 1px solid #ddd; padding: 10px; margin: 5px 0; background: #f9f9f9;'>";
        echo "<strong>ID:</strong> " . $quote['Quotation_ID'] . " | ";
        echo "<strong>Client:</strong> " . ($quote['Client_Name'] ?: 'Unknown') . " | ";
        echo "<strong>Status:</strong> " . $quote['Status'] . "<br>";
        echo "<strong>Package:</strong> " . $quote['Package'] . "<br>";
        echo "<strong>Amount:</strong> ₱" . number_format($quote['Amount'], 2) . " | ";
        echo "<strong>Date:</strong> " . $quote['Date_Issued'] . "<br>";
        echo "</div>";
    }
    
    echo "<br><a href='?action=clear_test' style='color: red;'>Clear Test Data</a>";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>