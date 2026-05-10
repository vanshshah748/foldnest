<?php
// backend/run_migration.php
// Run this file ONCE to apply database upgrades and seed admin data
// Usage: Open in browser — http://localhost/foldnest/backend/run_migration.php

header("Content-Type: text/html; charset=UTF-8");

echo "<h1>🚀 FoldNest Full Database Setup & Migration</h1>";
echo "<hr>";

$env_path = realpath(__DIR__ . '/../.env');
$host = 'localhost';
$db_name = 'ecommerce_db';
$username = 'root';
$password = '';

if (file_exists($env_path)) {
    $env = parse_ini_file($env_path);
    $host = isset($env['DB_HOST']) ? trim($env['DB_HOST'], '"\'') : 'localhost';
    $db_name = isset($env['DB_NAME']) ? trim($env['DB_NAME'], '"\'') : 'ecommerce_db';
    $username = isset($env['DB_USER']) ? trim($env['DB_USER'], '"\'') : 'root';
    $password = isset($env['DB_PASS']) ? trim($env['DB_PASS'], '"\'') : '';
}

try {
    // Connect WITHOUT dbname to create it if it doesn't exist
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Drop database to ensure clean state (Commented out for safety)
    // $pdo->exec("DROP DATABASE IF EXISTS `$db_name`");
    
    // Create database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "<p style='color:green;'>✅ Database `$db_name` created or verified.</p>";
    
    // Connect to the database
    $pdo->exec("USE `$db_name`");
    
    // 1. Create Users Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        full_name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        phone VARCHAR(15) DEFAULT NULL,
        password_hash VARCHAR(255) NOT NULL,
        role ENUM('customer', 'admin') DEFAULT 'customer',
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "<p style='color:green;'>✅ `users` table ready.</p>";
    
    // 2. Create Categories Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "<p style='color:green;'>✅ `categories` table ready.</p>";
    
    // 3. Create Products Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        category_id INT,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        price DECIMAL(10, 2) NOT NULL,
        old_price DECIMAL(10, 2) DEFAULT 0,
        rating DECIMAL(3, 1) DEFAULT 0,
        stock_quantity INT DEFAULT 0,
        image_url VARCHAR(255),
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "<p style='color:green;'>✅ `products` table ready.</p>";
    
    // 4. Create Cart Items Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS cart_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        UNIQUE KEY unique_cart_item (user_id, product_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "<p style='color:green;'>✅ `cart_items` table ready.</p>";
    
    // 5. Create Orders Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tracking_id VARCHAR(20) DEFAULT NULL UNIQUE,
        user_id INT NOT NULL,
        address_id INT DEFAULT NULL,
        total_amount DECIMAL(10, 2) NOT NULL,
        payment_method VARCHAR(50) DEFAULT 'COD',
        payment_status ENUM('pending','paid','failed','refunded') DEFAULT 'pending',
        status ENUM('pending','confirmed','packed','shipped','out_for_delivery','delivered','cancelled') DEFAULT 'pending',
        estimated_delivery DATE DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_order_status (status),
        INDEX idx_user_orders (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "<p style='color:green;'>✅ `orders` table ready.</p>";
    
    // 6. Create Order Items Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT NOT NULL,
        price_at_purchase DECIMAL(10, 2) NOT NULL,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "<p style='color:green;'>✅ `order_items` table ready.</p>";

    // 7. Create User Addresses Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_addresses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        mobile_number VARCHAR(15) NOT NULL,
        alternate_mobile VARCHAR(15) DEFAULT NULL,
        address_line_1 VARCHAR(255) NOT NULL,
        address_line_2 VARCHAR(255) DEFAULT NULL,
        landmark VARCHAR(100) DEFAULT NULL,
        city VARCHAR(100) NOT NULL,
        state VARCHAR(100) NOT NULL,
        pincode VARCHAR(10) NOT NULL,
        country VARCHAR(50) DEFAULT 'India',
        address_type ENUM('Home', 'Office') DEFAULT 'Home',
        is_default TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_user_addresses (user_id),
        INDEX idx_pincode (pincode)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "<p style='color:green;'>✅ `user_addresses` table ready.</p>";

    // 8. Create Admins Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS admins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(100) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        full_name VARCHAR(100) DEFAULT NULL,
        role ENUM('super_admin', 'admin', 'manager') DEFAULT 'admin',
        is_active TINYINT(1) DEFAULT 1,
        last_login TIMESTAMP NULL DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "<p style='color:green;'>✅ `admins` table ready.</p>";

    // 9. Seed Default Admin User
    $checkStmt = $pdo->query("SELECT id FROM admins WHERE username = 'admin'");
    if ($checkStmt->rowCount() == 0) {
        $adminPassword = password_hash('Admin@123', PASSWORD_BCRYPT);
        $insertAdmin = $pdo->prepare("INSERT INTO admins (username, email, password_hash, full_name, role) VALUES (?, ?, ?, ?, ?)");
        $insertAdmin->execute(['admin', 'admin@foldnest.com', $adminPassword, 'Super Admin', 'super_admin']);
        echo "<p style='color:green;'>✅ Default admin created: <b>admin@foldnest.com / Admin@123</b></p>";
    } else {
        echo "<p style='color:orange;'>⚠️ Admin user already exists.</p>";
    }

    echo "<hr><h2>🎉 All tables created successfully!</h2>";
    echo "<p>You can now test registration, login, and address save.</p>";

} catch (PDOException $e) {
    echo "<h2 style='color:red;'>❌ Setup failed</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>
