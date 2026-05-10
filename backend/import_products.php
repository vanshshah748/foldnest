<?php
// backend/import_products.php
// Import products from products.json into the MySQL database

header("Content-Type: text/plain");
include_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    die("Database connection failed.\n");
}

echo "Starting product import from JSON...\n";

// Load JSON
$json_path = __DIR__ . '/../frontend/data/products.json';
if (!file_exists($json_path)) {
    die("Error: products.json not found at $json_path\n");
}

$json_data = file_get_contents($json_path);

$json_data = file_get_contents($json_path);

// Strip BOM
$bom = pack('H*','EFBBBF');
$json_data = preg_replace("/^$bom/", '', $json_data);
$json_data = trim($json_data);

$products = json_decode($json_data, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    die("Error parsing JSON: " . json_last_error_msg() . "\n");
}

if (!$products) {
    die("Error: No products array found in JSON.\n");
}

// Prepare category fetching/insertion
$cat_cache = [];
$stmt_get_cat = $db->prepare("SELECT id FROM categories WHERE name = :name LIMIT 1");
$stmt_ins_cat = $db->prepare("INSERT INTO categories (name, description) VALUES (:name, :desc)");

// Prepare product insertion
$stmt_check_prod = $db->prepare("SELECT id FROM products WHERE name = :name LIMIT 1");
$stmt_ins_prod = $db->prepare("INSERT INTO products (category_id, name, description, price, old_price, rating, stock_quantity, image_url, is_active) 
                               VALUES (:cat_id, :name, :desc, :price, :old_price, :rating, :stock, :img, 1)");

$importedCount = 0;
$skippedCount = 0;

foreach ($products as $p) {
    // Get or Create Category
    $cat_name = isset($p['category']) ? $p['category'] : 'Uncategorized';
    
    if (!isset($cat_cache[$cat_name])) {
        $stmt_get_cat->execute([':name' => $cat_name]);
        $cat_row = $stmt_get_cat->fetch(PDO::FETCH_ASSOC);
        
        if ($cat_row) {
            $cat_cache[$cat_name] = $cat_row['id'];
        } else {
            $stmt_ins_cat->execute([':name' => $cat_name, ':desc' => "$cat_name category"]);
            $cat_cache[$cat_name] = $db->lastInsertId();
        }
    }
    
    $cat_id = $cat_cache[$cat_name];
    
    // Check if product exists by name to avoid duplicates
    $title = $p['title'];
    $stmt_check_prod->execute([':name' => $title]);
    if ($stmt_check_prod->fetch(PDO::FETCH_ASSOC)) {
        // Already exists! Skip it.
        $skippedCount++;
        continue;
    }
    
    // Format image URL
    $image_filename = $p['image'];
    $image_url = 'assets/images/products/' . $image_filename;
    
    // Insert Product
    $stmt_ins_prod->execute([
        ':cat_id' => $cat_id,
        ':name' => $title,
        ':desc' => $p['description'],
        ':price' => (float)$p['price'],
        ':old_price' => isset($p['oldPrice']) ? (float)$p['oldPrice'] : 0,
        ':rating' => isset($p['rating']) ? (float)$p['rating'] : 0,
        ':stock' => isset($p['stock']) ? (int)$p['stock'] : 0,
        ':img' => $image_url
    ]);
    
    $importedCount++;
}

echo "Import complete!\n";
echo "Successfully imported: $importedCount\n";
echo "Skipped (already exist): $skippedCount\n";
?>
