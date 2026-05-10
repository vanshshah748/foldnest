<?php
include_once __DIR__ . '/backend/config/database.php';
$db = (new Database())->getConnection();

// Drop old admins table to match new requested schema
$db->exec("DROP TABLE IF EXISTS admins");

$query = "CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('Super Admin', 'Product Manager', 'Order Manager', 'Support Staff') DEFAULT 'Super Admin',
    status ENUM('active', 'inactive') DEFAULT 'active',
    last_login DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$db->exec($query);

// Seed new default admin
$password = password_hash('admin123', PASSWORD_DEFAULT);
$stmt = $db->prepare("INSERT INTO admins (username, email, password, role) VALUES (?, ?, ?, ?)");
$stmt->execute(['admin', 'admin@foldnest.com', $password, 'Super Admin']);
echo "Table recreated. Default admin created: admin@foldnest.com / admin123";
?>
