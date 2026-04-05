<?php
/**
 * Creates the tables required by the app inside the configured database.
 * For hosted MySQL services such as Aiven, create the database first in the
 * provider dashboard, then run this script to create the tables.
 */

require_once __DIR__ . '/db.php';

$tables = ['users', 'vendors', 'orders', 'inventory'];
$schemaStatements = [
    "CREATE TABLE IF NOT EXISTS `users` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(50) NOT NULL,
        `password` VARCHAR(255) NOT NULL,
        `email` VARCHAR(100) NOT NULL,
        `role` ENUM('admin', 'vendor', 'customer') NOT NULL DEFAULT 'vendor',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `uq_users_username` (`username`),
        UNIQUE KEY `uq_users_email` (`email`),
        KEY `idx_users_role` (`role`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    "CREATE TABLE IF NOT EXISTS `vendors` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `contact_info` VARCHAR(255) DEFAULT NULL,
        `id_number` VARCHAR(50) DEFAULT NULL,
        `supply_items` TEXT DEFAULT NULL,
        `items` TEXT DEFAULT NULL,
        `prices` TEXT DEFAULT NULL,
        `specifications` TEXT DEFAULT NULL,
        `size` VARCHAR(50) DEFAULT NULL,
        `color` VARCHAR(50) DEFAULT NULL,
        `location` VARCHAR(255) DEFAULT NULL,
        `delivery_method` VARCHAR(100) DEFAULT NULL,
        `delivery_date` DATE DEFAULT NULL,
        `payment_method` VARCHAR(100) DEFAULT NULL,
        `payment_details` TEXT DEFAULT NULL,
        `images` TEXT DEFAULT NULL,
        `status` ENUM('pending', 'approved', 'declined') DEFAULT 'pending',
        `user_id` INT UNSIGNED DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `uq_vendors_id_number` (`id_number`),
        KEY `idx_vendors_user_id` (`user_id`),
        KEY `idx_vendors_status` (`status`),
        KEY `idx_vendors_name` (`name`),
        CONSTRAINT `fk_vendors_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    "CREATE TABLE IF NOT EXISTS `orders` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `vendor_id` INT UNSIGNED NOT NULL,
        `user_id` INT UNSIGNED DEFAULT NULL,
        `product` VARCHAR(100) NOT NULL,
        `quantity` INT UNSIGNED NOT NULL,
        `unit_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        `total_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        `specifications` TEXT DEFAULT NULL,
        `size` VARCHAR(50) DEFAULT NULL,
        `color` VARCHAR(50) DEFAULT NULL,
        `payment_method` VARCHAR(50) DEFAULT NULL,
        `payment_details` TEXT DEFAULT NULL,
        `vendor_id_number` VARCHAR(50) DEFAULT NULL,
        `location` VARCHAR(255) DEFAULT NULL,
        `delivery_method` VARCHAR(100) DEFAULT NULL,
        `delivery_date` DATE DEFAULT NULL,
        `deadline` DATE DEFAULT NULL,
        `status` ENUM('pending', 'processing', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
        `payment_status` ENUM('pending', 'paid') NOT NULL DEFAULT 'pending',
        `inventory_recorded` TINYINT(1) NOT NULL DEFAULT 0,
        `order_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
        KEY `idx_orders_vendor_id` (`vendor_id`),
        KEY `idx_orders_user_id` (`user_id`),
        KEY `idx_orders_status` (`status`),
        KEY `idx_orders_payment_status` (`payment_status`),
        KEY `idx_orders_order_date` (`order_date`),
        CONSTRAINT `fk_orders_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
        CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    "CREATE TABLE IF NOT EXISTS `inventory` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `item_name` VARCHAR(150) NOT NULL,
        `category` VARCHAR(100) DEFAULT NULL,
        `quantity` INT NOT NULL DEFAULT 0,
        `unit` VARCHAR(30) DEFAULT NULL,
        `reorder_level` INT NOT NULL DEFAULT 0,
        `notes` TEXT DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY `idx_inventory_item_name` (`item_name`),
        KEY `idx_inventory_category` (`category`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
];

try {
    foreach ($schemaStatements as $statement) {
        $pdo->exec($statement);
    }

    echo "Database connection OK.\n";
    echo "Target database: {$dbConfig['name']}\n";
    echo "Host: {$dbConfig['host']}:{$dbConfig['port']}\n";
    echo "SSL mode: {$dbConfig['ssl_mode']}\n\n";

    foreach ($tables as $table) {
        $count = $pdo->query("SELECT COUNT(*) AS count FROM `$table`")->fetch()['count'];
        echo "[ok] $table table ready ($count rows)\n";
    }

    echo "\nNext steps:\n";
    echo "1. Open ../frontend/setup_admin.html to create the first admin user.\n";
    echo "2. Open ../frontend/index.html and log in.\n";
} catch (PDOException $e) {
    http_response_code(500);
    echo "Database setup failed.\n\n";
    echo "Error: {$e->getMessage()}\n\n";
    echo "Checks:\n";
    echo "1. Confirm the database named '{$dbConfig['name']}' already exists in Aiven or locally.\n";
    echo "2. Confirm DB_HOST, DB_PORT, DB_NAME, DB_USER/DB_USERNAME, and DB_PASS/DB_PASSWORD are correct.\n";
    echo "3. If using Aiven, set DB_SSL_MODE=require in Render.\n";
    echo "4. If your provider requires a custom CA bundle, set DB_SSL_CA to its absolute path inside the container.\n";
    exit(1);
}
