<?php
$pdo = new PDO('mysql:host=localhost;dbname=atmicxdb', 'root', '');
$pdo->exec('DELETE FROM inventory');
echo 'All inventory data deleted';
?>
