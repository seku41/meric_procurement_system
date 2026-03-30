<?php
/**
 * Hotel Procurement System - Database Setup Script
 * 
 * This script creates the complete database structure with all tables,
 * indexes, and foreign keys required for the system.
 * 
 * Requirements:
 * - PHP 7.4+ with PDO and PDO_MySQL extensions
 * - MySQL 5.7+ or MariaDB 10.3+
 * - MySQL user with CREATE DATABASE and CREATE TABLE permissions
 */

// Database connection settings.
// Render should provide these as environment variables. Local XAMPP can still
// fall back to the existing defaults.
$host = getenv('DB_HOST') ?: 'localhost';
$port = getenv('DB_PORT') ?: '3306';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';

// Database name
$dbName = getenv('DB_NAME') ?: 'sekum_db';

try {
    // Connect to MySQL without selecting a database
    $pdo = new PDO("mysql:host=$host;port=$port", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✅ Database '$dbName' created successfully or already exists.\n\n";
    
    // Select the database
    $pdo->exec("USE `$dbName`");
    
    // =====================================================
    // Create users table
    // =====================================================
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `users` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `username` VARCHAR(50) NOT NULL UNIQUE,
            `password` VARCHAR(255) NOT NULL COMMENT 'Hashed password using PHP password_hash()',
            `email` VARCHAR(100) NOT NULL UNIQUE,
            `role` ENUM('admin', 'vendor', 'customer') NOT NULL DEFAULT 'vendor',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_username` (`username`),
            INDEX `idx_email` (`email`),
            INDEX `idx_role` (`role`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ Users table created successfully.\n";
    
    // =====================================================
    // Create vendors table
    // =====================================================
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `vendors` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(100) NOT NULL,
            `contact_info` VARCHAR(255) DEFAULT NULL,
            `id_number` VARCHAR(50) UNIQUE DEFAULT NULL,
            `supply_items` TEXT DEFAULT NULL COMMENT 'JSON array of items vendor can supply',
            `items` TEXT DEFAULT NULL COMMENT 'Additional items information',
            `prices` TEXT DEFAULT NULL COMMENT 'Pricing information',
            `specifications` TEXT DEFAULT NULL COMMENT 'Product specifications',
            `size` VARCHAR(50) DEFAULT NULL,
            `color` VARCHAR(50) DEFAULT NULL,
            `location` VARCHAR(255) DEFAULT NULL,
            `delivery_method` VARCHAR(100) DEFAULT NULL,
            `delivery_date` DATE DEFAULT NULL,
            `payment_method` VARCHAR(100) DEFAULT NULL,
            `payment_details` TEXT DEFAULT NULL COMMENT 'JSON object with payment details',
            `images` TEXT DEFAULT NULL COMMENT 'JSON array of image URLs/paths',
            `status` ENUM('pending', 'approved', 'declined') DEFAULT 'pending',
            `user_id` INT DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
            INDEX `idx_name` (`name`),
            INDEX `idx_id_number` (`id_number`),
            INDEX `idx_status` (`status`),
            INDEX `idx_user_id` (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ Vendors table created successfully.\n";
    
    // =====================================================
    // Create orders table
    // =====================================================
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `orders` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `vendor_id` INT NOT NULL,
            `user_id` INT DEFAULT NULL COMMENT 'Optional user who placed order',
            `product` VARCHAR(100) NOT NULL,
            `quantity` INT NOT NULL,
            `unit_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `total_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `specifications` TEXT DEFAULT NULL,
            `size` VARCHAR(50) DEFAULT NULL,
            `color` VARCHAR(50) DEFAULT NULL,
            `payment_method` VARCHAR(50) DEFAULT NULL,
            `payment_details` TEXT DEFAULT NULL COMMENT 'JSON object',
            `vendor_id_number` VARCHAR(50) DEFAULT NULL,
            `location` VARCHAR(255) DEFAULT NULL,
            `delivery_method` VARCHAR(100) DEFAULT NULL,
            `delivery_date` DATE DEFAULT NULL,
            `deadline` DATE DEFAULT NULL,
            `status` ENUM('pending', 'processing', 'completed', 'cancelled') DEFAULT 'pending',
            `payment_status` ENUM('pending', 'paid') DEFAULT 'pending',
            `inventory_recorded` TINYINT(1) NOT NULL DEFAULT 0,
            `order_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`vendor_id`) REFERENCES `vendors`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
            INDEX `idx_vendor_id` (`vendor_id`),
            INDEX `idx_user_id` (`user_id`),
            INDEX `idx_status` (`status`),
            INDEX `idx_payment_status` (`payment_status`),
            INDEX `idx_order_date` (`order_date`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ Orders table created successfully.\n";
    
    // =====================================================
    // Create inventory table
    // =====================================================
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `inventory` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `item_name` VARCHAR(150) NOT NULL,
            `category` VARCHAR(100) DEFAULT NULL,
            `quantity` INT NOT NULL DEFAULT 0,
            `unit` VARCHAR(30) DEFAULT NULL,
            `reorder_level` INT NOT NULL DEFAULT 0,
            `notes` TEXT DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_inventory_item_name` (`item_name`),
            INDEX `idx_inventory_category` (`category`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "Inventory table created successfully.\n";
    
    // =====================================================
    // Verify tables were created
    // =====================================================
    $tables = ['users', 'vendors', 'orders', 'inventory'];
    $allCreated = true;
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() == 0) {
            echo "⚠️  Warning: Table '$table' may not have been created.\n";
            $allCreated = false;
        }
    }
    
    if ($allCreated) {
        echo "\n✅ All tables verified successfully!\n";
    }
    
    // =====================================================
    // Display summary
    // =====================================================
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "📊 Database Setup Summary\n";
    echo str_repeat("=", 60) . "\n";
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM `$table`");
        $count = $stmt->fetch()['count'];
        echo "  • $table: $count records\n";
    }
    
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "✅ Database setup completed successfully!\n\n";
    echo "📝 Next Steps:\n";
    echo "   1. Go to: http://localhost/Procurement/setup_admin.html\n";
    echo "   2. Create an admin user account\n";
    echo "   3. Login at: http://localhost/Procurement/index.html\n\n";
    
} catch (PDOException $e) {
    echo "\n❌ Database setup failed!\n\n";
    echo "Error: " . $e->getMessage() . "\n\n";
    echo "🔧 Troubleshooting Tips:\n";
    echo "   1. Make sure XAMPP/WAMP is running (Apache and MySQL)\n";
    echo "   2. Check if MySQL is running on port 3306\n";
    echo "   3. Verify MySQL credentials in this file:\n";
    echo "      - Host: $host\n";
    echo "      - User: $user\n";
    echo "      - Password: " . (empty($pass) ? '(empty)' : '***') . "\n";
    echo "   4. Make sure MySQL user has CREATE DATABASE permission\n";
    echo "   5. Check MySQL error logs for more details\n";
    echo "   6. Verify db.php has matching credentials\n\n";
    exit(1);
}
?>
