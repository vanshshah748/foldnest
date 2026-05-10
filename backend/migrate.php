<?php
// backend/migrate.php
include_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

try {
    // 1. Temporarily disable foreign key checks
    $db->exec("SET FOREIGN_KEY_CHECKS = 0");

    // 2. Truncate tables to clear out old dummy data
    $db->exec("TRUNCATE TABLE order_items");
    $db->exec("TRUNCATE TABLE orders");
    $db->exec("TRUNCATE TABLE cart_items");
    $db->exec("TRUNCATE TABLE products");
    $db->exec("TRUNCATE TABLE categories");

    // 3. Alter the products table to match the exact JSON keys as requested
    // We add rating and old_price.
    // If they already exist, this might throw an error, so we suppress it or check.
    try {
        $db->exec("ALTER TABLE products ADD COLUMN rating DECIMAL(3,1) DEFAULT 0.0");
    } catch(PDOException $e) {}
    
    try {
        $db->exec("ALTER TABLE products ADD COLUMN old_price DECIMAL(10,2) DEFAULT 0.0");
    } catch(PDOException $e) {}

    // 4. Read the JSON file
    $jsonPath = 'C:/Users/Vansh/OneDrive/Desktop/final year project/frontend/data/products.json';
    if (!file_exists($jsonPath)) {
        die("products.json not found at $jsonPath");
    }
    
    $jsonData = file_get_contents($jsonPath);
    // Strip trailing commas which are invalid in JSON but common
    $jsonData = preg_replace('/,\s*([\]}])/m', '$1', $jsonData);
    
    // Also remove BOM if present
    $jsonData = preg_replace('/^[\xef\xbb\xbf]+/', '', $jsonData);
    
    $products = json_decode($jsonData, true);
    
    if (!$products) {
        die("Invalid JSON data: " . json_last_error_msg());
    }

    $categoryMap = [];

    // Prepare statements
    $catStmt = $db->prepare("INSERT INTO categories (name) VALUES (:name)");
    $prodStmt = $db->prepare("INSERT INTO products (id, name, description, price, old_price, rating, stock_quantity, category_id, image_url) 
                              VALUES (:id, :name, :description, :price, :old_price, :rating, :stock, :category_id, :image)");

    // 5. Insert data
    foreach ($products as $p) {
        $catName = $p['category'];
        
        // Insert category if not exists
        if (!isset($categoryMap[$catName])) {
            $catStmt->bindParam(':name', $catName);
            $catStmt->execute();
            $categoryMap[$catName] = $db->lastInsertId();
        }
        
        $catId = $categoryMap[$catName];
        
        $prodStmt->execute([
            ':id' => $p['id'],
            ':name' => $p['title'],
            ':description' => $p['description'],
            ':price' => $p['price'],
            ':old_price' => isset($p['oldPrice']) ? $p['oldPrice'] : 0,
            ':rating' => isset($p['rating']) ? $p['rating'] : 0,
            ':stock' => $p['stock'],
            ':category_id' => $catId,
            ':image' => $p['image']
        ]);
    }

    // Re-enable foreign key checks
    $db->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    echo "Migration completed successfully! " . count($products) . " products imported.";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
