<?php
// Test database connection and user accounts
try {
    $pdo = new PDO('mysql:host=localhost;dbname=atmicxdb', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Database connection: SUCCESS\n\n";
    
    // Check users table
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM user");
    $result = $stmt->fetch();
    echo "📊 Users in database: " . $result['count'] . "\n";
    
    // Check clients table
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM client");
    $result = $stmt->fetch();
    echo "📊 Clients in database: " . $result['count'] . "\n\n";
    
    // Check sample user with password hash
    $stmt = $pdo->query("SELECT User_ID, Name, Email, Role, PasswordHash FROM user LIMIT 3");
    echo "📋 Sample Users:\n";
    while ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "  - ID: {$user['User_ID']}, Name: {$user['Name']}, Email: {$user['Email']}, Role: {$user['Role']}\n";
        echo "    Password Hash: " . substr($user['PasswordHash'], 0, 20) . "...\n";
    }
    
    // Check sample client
    echo "\n📋 Sample Clients:\n";
    $stmt = $pdo->query("SELECT Client_ID, Name, Email, Password_Hash FROM client LIMIT 3");
    while ($client = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "  - ID: {$client['Client_ID']}, Name: {$client['Name']}, Email: {$client['Email']}\n";
        echo "    Password Hash: " . substr($client['Password_Hash'], 0, 20) . "...\n";
    }
    
    // Test password verification
    echo "\n🔐 Testing password hashing:\n";
    $testPassword = "test123";
    $hash = password_hash($testPassword, PASSWORD_ARGON2ID, [
        'memory_cost' => 65536,
        'time_cost' => 4,
        'threads' => 1
    ]);
    echo "  Test password: $testPassword\n";
    echo "  Hash: " . substr($hash, 0, 30) . "...\n";
    echo "  Verify: " . (password_verify($testPassword, $hash) ? "✅ SUCCESS" : "❌ FAILED") . "\n";
    
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}
?>
