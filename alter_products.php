<?php
include_once __DIR__ . '/backend/config/database.php';
$db = (new Database())->getConnection();

$query = "ALTER TABLE products 
    ADD COLUMN slug VARCHAR(255) NULL AFTER name,
    ADD COLUMN subcategory_id INT NULL AFTER category_id,
    ADD COLUMN specifications TEXT NULL AFTER description,
    ADD COLUMN material VARCHAR(100) NULL,
    ADD COLUMN dimensions VARCHAR(100) NULL,
    ADD COLUMN color VARCHAR(50) NULL,
    ADD COLUMN weight DECIMAL(10,2) NULL,
    ADD COLUMN is_featured TINYINT(1) DEFAULT 0,
    ADD COLUMN is_trending TINYINT(1) DEFAULT 0,
    ADD COLUMN tags VARCHAR(255) NULL,
    ADD COLUMN seo_title VARCHAR(255) NULL,
    ADD COLUMN seo_description TEXT NULL,
    ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL";

try {
    $db->exec($query);
    echo "Products table altered successfully.";
} catch (PDOException $e) {
    if ($e->getCode() == '42S21') { // Duplicate column
        echo "Columns already exist.";
    } else {
        echo "Error: " . $e->getMessage();
    }
}
?>
