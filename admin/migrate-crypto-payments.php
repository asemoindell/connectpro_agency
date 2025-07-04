<?php
/**
 * Database Migration: Add USDT and Bitcoin Support
 * This script updates the payments table and adds crypto wallet management
 */

require_once '../config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "Starting crypto payment migration...\n";
    
    // Check if the table exists and get its structure
    $tableInfo = $pdo->query("DESCRIBE payments")->fetchAll();
    $columns = array_column($tableInfo, 'Field');
    
    echo "Current table columns: " . implode(', ', $columns) . "\n";
    
    // Check if we need to rename 'gateway' column to 'payment_method'
    if (in_array('gateway', $columns) && !in_array('payment_method', $columns)) {
        echo "Renaming 'gateway' column to 'payment_method'...\n";
        $pdo->exec("ALTER TABLE payments CHANGE COLUMN gateway payment_method ENUM('stripe', 'paypal', 'usdt', 'bitcoin', 'bank_transfer') NOT NULL");
    }
    
    // Update payment_method enum to include crypto options if not already present
    echo "Updating payment_method enum to include crypto options...\n";
    $pdo->exec("ALTER TABLE payments MODIFY COLUMN payment_method ENUM('stripe', 'paypal', 'usdt', 'bitcoin', 'bank_transfer') NOT NULL");
    
    // Check if we need to rename 'status' column to 'payment_status'
    if (in_array('status', $columns) && !in_array('payment_status', $columns)) {
        echo "Renaming 'status' column to 'payment_status'...\n";
        $pdo->exec("ALTER TABLE payments CHANGE COLUMN status payment_status ENUM('pending', 'processing', 'completed', 'failed', 'refunded') DEFAULT 'pending'");
    }
    
    // Add admin_notes column if it doesn't exist
    if (!in_array('admin_notes', $columns)) {
        echo "Adding admin_notes column...\n";
        $pdo->exec("ALTER TABLE payments ADD COLUMN admin_notes TEXT AFTER gateway_response");
    }
    
    // Create crypto_wallets table
    echo "Creating crypto_wallets table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS crypto_wallets (
            id INT PRIMARY KEY AUTO_INCREMENT,
            currency ENUM('usdt', 'bitcoin') NOT NULL,
            network VARCHAR(50) DEFAULT 'mainnet',
            address VARCHAR(200) NOT NULL UNIQUE,
            label VARCHAR(100),
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    
    // Insert default crypto wallet addresses
    echo "Inserting default crypto wallet addresses...\n";
    $pdo->exec("
        INSERT IGNORE INTO crypto_wallets (currency, address, label, is_active) VALUES
        ('bitcoin', '1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa', 'Primary Bitcoin Wallet (Demo)', TRUE),
        ('usdt', 'TQNDzxPm9qNcfEuaGc7w2YW8nA5K8v5Z2m', 'Primary USDT Wallet TRC20 (Demo)', TRUE)
    ");
    
    // Create payment_fees table
    echo "Creating payment_fees table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS payment_fees (
            id INT PRIMARY KEY AUTO_INCREMENT,
            payment_method ENUM('stripe', 'paypal', 'usdt', 'bitcoin', 'bank_transfer') NOT NULL,
            fee_type ENUM('percentage', 'fixed', 'combined') DEFAULT 'percentage',
            percentage_fee DECIMAL(5,4) DEFAULT 0.0000,
            fixed_fee DECIMAL(10,2) DEFAULT 0.00,
            minimum_fee DECIMAL(10,2) DEFAULT 0.00,
            maximum_fee DECIMAL(10,2) DEFAULT NULL,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    
    // Insert default payment processing fees
    echo "Inserting default payment processing fees...\n";
    $pdo->exec("
        INSERT IGNORE INTO payment_fees (payment_method, fee_type, percentage_fee, fixed_fee, minimum_fee) VALUES
        ('stripe', 'combined', 0.0290, 0.30, 0.30),
        ('paypal', 'combined', 0.0349, 0.49, 0.49),
        ('usdt', 'fixed', 0.0000, 2.00, 2.00),
        ('bitcoin', 'percentage', 0.0050, 0.00, 0.00),
        ('bank_transfer', 'fixed', 0.0000, 5.00, 5.00)
    ");
    
    // Add indexes for better performance
    echo "Adding database indexes...\n";
    try {
        $pdo->exec("CREATE INDEX idx_payments_status ON payments(payment_status)");
    } catch (Exception $e) {
        // Index might already exist
    }
    
    try {
        $pdo->exec("CREATE INDEX idx_payments_method ON payments(payment_method)");
    } catch (Exception $e) {
        // Index might already exist
    }
    
    try {
        $pdo->exec("CREATE INDEX idx_payments_created ON payments(created_at)");
    } catch (Exception $e) {
        // Index might already exist
    }
    
    try {
        $pdo->exec("CREATE INDEX idx_payments_booking ON payments(booking_id)");
    } catch (Exception $e) {
        // Index might already exist
    }
    
    // Create payment method statistics view
    echo "Creating payment method statistics view...\n";
    $pdo->exec("
        CREATE OR REPLACE VIEW payment_method_stats AS
        SELECT 
            payment_method,
            COUNT(*) as transaction_count,
            SUM(amount) as total_amount,
            AVG(amount) as average_amount,
            SUM(CASE WHEN payment_status = 'completed' THEN amount ELSE 0 END) as completed_amount,
            COUNT(CASE WHEN payment_status = 'completed' THEN 1 END) as completed_count,
            COUNT(CASE WHEN payment_status = 'pending' THEN 1 END) as pending_count,
            COUNT(CASE WHEN payment_status = 'failed' THEN 1 END) as failed_count
        FROM payments 
        GROUP BY payment_method
    ");
    
    echo "\n✅ Crypto payment migration completed successfully!\n\n";
    echo "Summary of changes:\n";
    echo "- Updated payments table to support USDT and Bitcoin\n";
    echo "- Added crypto wallet management system\n";
    echo "- Added payment processing fees configuration\n";
    echo "- Created database indexes for better performance\n";
    echo "- Added payment method statistics view\n";
    echo "\nYou can now:\n";
    echo "- Accept USDT and Bitcoin payments\n";
    echo "- Manage crypto wallet addresses in the database\n";
    echo "- View payment statistics by method\n";
    echo "- Configure different processing fees per payment method\n";
    
} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>
