<?php
// Clean orphaned records
$pdo = new PDO('mysql:host=localhost;dbname=atmicxdb', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "Cleaning orphaned records...\n\n";

$pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

// Delete orphaned quotations (no client)
$deleted = $pdo->exec('DELETE FROM quotation WHERE Client_ID IS NOT NULL AND Client_ID NOT IN (SELECT Client_ID FROM client)');
echo "Deleted $deleted orphaned quotations\n";

// Delete orphaned services (no client)
$deleted = $pdo->exec('DELETE FROM service WHERE Client_ID IS NOT NULL AND Client_ID NOT IN (SELECT Client_ID FROM client)');
echo "Deleted $deleted orphaned services\n";

// Delete orphaned payments (no quotation)
$deleted = $pdo->exec('DELETE FROM payment WHERE Quotation_ID IS NOT NULL AND Quotation_ID NOT IN (SELECT Quotation_ID FROM quotation)');
echo "Deleted $deleted orphaned payments\n";

$pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

echo "\nFinal counts:\n";
$tables = ['client', 'quotation', 'service', 'payment', 'user', 'inventory'];
foreach ($tables as $table) {
    $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
    echo "  $table: $count\n";
}

echo "\nCleanup complete!\n";
?>
