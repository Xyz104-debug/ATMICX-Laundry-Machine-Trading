<?php
/**
 * Clean Test Data Script
 * Removes test/sample data from the database
 */

// Database connection
$host = 'localhost';
$dbname = 'atmicxdb';
$username_db = 'root';
$password_db = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username_db, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== Cleaning Test Data ===\n\n";
    
    // Get current counts
    echo "Current Record Counts:\n";
    $tables = ['client', 'quotation', 'service', 'payment', 'user', 'inventory'];
    $counts_before = [];
    foreach ($tables as $table) {
        try {
            $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
            $counts_before[$table] = $count;
            echo "  $table: $count\n";
        } catch (Exception $e) {
            echo "  $table: Table not found\n";
            $counts_before[$table] = 0;
        }
    }
    
    echo "\n";
    
    // Ask for confirmation
    echo "This will remove test data (users with test emails, sample quotations, etc.)\n";
    echo "Type 'YES' to continue: ";
    $handle = fopen("php://stdin", "r");
    $line = trim(fgets($handle));
    fclose($handle);
    
    if ($line !== 'YES') {
        echo "Operation cancelled.\n";
        exit;
    }
    
    echo "\nCleaning data...\n\n";
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Disable foreign key checks temporarily
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    // 1. Delete test users (with testemail@gmail.com or test in name)
    echo "1. Removing test users...\n";
    $stmt = $pdo->prepare("DELETE FROM user WHERE email LIKE '%test%' OR Name LIKE '%test%'");
    $stmt->execute();
    echo "   Removed " . $stmt->rowCount() . " test users\n";
    
    // 2. Delete quotations with test clients or no real data
    echo "2. Removing test quotations...\n";
    $stmt = $pdo->prepare("DELETE FROM quotation WHERE Client_ID NOT IN (SELECT Client_ID FROM client)");
    $stmt->execute();
    echo "   Removed " . $stmt->rowCount() . " orphaned quotations\n";
    
    // 3. Delete services with no valid client
    echo "3. Removing test services...\n";
    $stmt = $pdo->prepare("DELETE FROM service WHERE Client_ID NOT IN (SELECT Client_ID FROM client)");
    $stmt->execute();
    echo "   Removed " . $stmt->rowCount() . " orphaned services\n";
    
    // 4. Delete payments with no valid quotation
    echo "4. Removing test payments...\n";
    $stmt = $pdo->prepare("DELETE FROM payment WHERE Quotation_ID NOT IN (SELECT Quotation_ID FROM quotation)");
    $stmt->execute();
    echo "   Removed " . $stmt->rowCount() . " orphaned payments\n";
    
    // 5. Delete test clients (with test email)
    echo "5. Removing test clients...\n";
    $stmt = $pdo->prepare("DELETE FROM client WHERE Email LIKE '%test%' OR Email LIKE '%example%' OR Email LIKE '%sample%'");
    $stmt->execute();
    echo "   Removed " . $stmt->rowCount() . " test clients\n";
    
    // 6. Clean up inventory test items
    echo "6. Cleaning test inventory items...\n";
    $stmt = $pdo->prepare("DELETE FROM inventory WHERE Item_Name LIKE '%test%' OR Item_Name LIKE '%sample%'");
    $stmt->execute();
    echo "   Removed " . $stmt->rowCount() . " test inventory items\n";
    
    // Re-enable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    // Commit transaction
    $pdo->commit();
    
    echo "\n=== Cleanup Complete ===\n\n";
    
    // Get final counts
    echo "Final Record Counts:\n";
    foreach ($tables as $table) {
        try {
            $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
            $removed = $counts_before[$table] - $count;
            echo "  $table: $count (removed: $removed)\n";
        } catch (Exception $e) {
            echo "  $table: 0\n";
        }
    }
    
    echo "\nTest data cleaned successfully!\n";
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
