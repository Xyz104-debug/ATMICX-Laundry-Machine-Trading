<?php
// Setup script to add missing database components and directories

header('Content-Type: text/plain');
echo "Setting up missing ATMICX components...\n\n";

// Database configuration
$host = 'localhost';
$dbname = 'atmicxdb';
$username_db = 'root';
$password_db = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username_db, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ Database connection established\n";
    
    // 1. Update payment table structure
    echo "\n1. Updating payment table structure...\n";
    
    $alterQueries = [
        "ALTER TABLE payment ADD COLUMN IF NOT EXISTS quotation_id INT NULL AFTER Service_ID",
        "ALTER TABLE payment ADD COLUMN IF NOT EXISTS service_request_id INT NULL AFTER quotation_id",
        "ALTER TABLE payment ADD COLUMN IF NOT EXISTS payment_date DATETIME DEFAULT CURRENT_TIMESTAMP AFTER Date_Paid",
        "ALTER TABLE payment ADD COLUMN IF NOT EXISTS verification_date DATETIME NULL AFTER payment_date",
        "ALTER TABLE payment ADD COLUMN IF NOT EXISTS proof_file_path VARCHAR(500) NULL AFTER Proof_Image",
        "ALTER TABLE payment MODIFY COLUMN Status ENUM('pending', 'approved', 'rejected', 'verified') DEFAULT 'pending'"
    ];
    
    foreach ($alterQueries as $query) {
        try {
            $pdo->exec($query);
            echo "  ✓ " . substr($query, 0, 50) . "...\n";
        } catch (Exception $e) {
            echo "  - " . substr($query, 0, 50) . "... (already exists)\n";
        }
    }
    
    // 2. Add foreign key constraints if missing
    echo "\n2. Adding foreign key constraints...\n";
    
    $fkQueries = [
        "ALTER TABLE payment ADD CONSTRAINT fk_payment_quotation FOREIGN KEY (quotation_id) REFERENCES quotation(Quotation_ID) ON DELETE SET NULL",
        "ALTER TABLE payment ADD CONSTRAINT fk_payment_service_request FOREIGN KEY (service_request_id) REFERENCES service(Service_ID) ON DELETE SET NULL"
    ];
    
    foreach ($fkQueries as $query) {
        try {
            $pdo->exec($query);
            echo "  ✓ Added foreign key constraint\n";
        } catch (Exception $e) {
            echo "  - Foreign key constraint already exists or skipped\n";
        }
    }
    
    // 3. Create missing indexes
    echo "\n3. Creating performance indexes...\n";
    
    $indexQueries = [
        "CREATE INDEX IF NOT EXISTS idx_payment_status ON payment(Status)",
        "CREATE INDEX IF NOT EXISTS idx_payment_date ON payment(payment_date)",
        "CREATE INDEX IF NOT EXISTS idx_service_status ON service(Status)",
        "CREATE INDEX IF NOT EXISTS idx_quotation_status ON quotation(Status)"
    ];
    
    foreach ($indexQueries as $query) {
        try {
            $pdo->exec($query);
            echo "  ✓ " . substr($query, 25, 30) . "...\n";
        } catch (Exception $e) {
            echo "  - Index already exists\n";
        }
    }
    
    // 4. Create upload directories
    echo "\n4. Creating upload directories...\n";
    
    $directories = [
        'uploads/payment_proofs/',
        'uploads/service_documents/',
        'uploads/quotation_attachments/',
        'uploads/client_files/',
        'logs/system/',
        'logs/payments/',
        'logs/services/',
        'tmp/uploads/',
        'tmp/reports/'
    ];
    
    foreach ($directories as $dir) {
        if (!is_dir($dir)) {
            if (mkdir($dir, 0755, true)) {
                echo "  ✓ Created directory: $dir\n";
            } else {
                echo "  ✗ Failed to create directory: $dir\n";
            }
        } else {
            echo "  - Directory exists: $dir\n";
        }
    }
    
    // 5. Create .htaccess for upload security
    echo "\n5. Setting up upload security...\n";
    
    $htaccessContent = "# Deny direct access to uploaded files\nDeny from all\n<Files *.jpg>\n    Allow from all\n</Files>\n<Files *.jpeg>\n    Allow from all\n</Files>\n<Files *.png>\n    Allow from all\n</Files>\n<Files *.gif>\n    Allow from all\n</Files>\n<Files *.pdf>\n    Allow from all\n</Files>";
    
    $htaccessPaths = ['uploads/payment_proofs/.htaccess', 'uploads/service_documents/.htaccess'];
    
    foreach ($htaccessPaths as $path) {
        if (!file_exists($path)) {
            file_put_contents($path, $htaccessContent);
            echo "  ✓ Created security file: $path\n";
        } else {
            echo "  - Security file exists: $path\n";
        }
    }
    
    echo "\n✅ Setup completed successfully!\n";
    echo "\nNext steps:\n";
    echo "1. Test payment submission workflow\n";
    echo "2. Verify file upload functionality\n";
    echo "3. Check service-to-payment integration\n";
    
} catch (Exception $e) {
    echo "\n❌ Setup failed: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat('=', 50) . "\n";
echo "ATMICX Setup Script Complete\n";
echo str_repeat('=', 50) . "\n";
?>