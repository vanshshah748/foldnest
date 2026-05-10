<?php
// backend/fix_image_paths.php
// Script to sanitize and fix all broken image paths in the database

header("Content-Type: text/plain");
include_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    die("Database connection failed.\n");
}

echo "Fixing database image paths...\n";

$stmt = $db->query("SELECT id, image_url FROM products");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$updatedCount = 0;

foreach ($products as $p) {
    $original = $p['image_url'];
    $new = $original;

    // Fix Windows local paths (e.g. C:\Users\...)
    if (preg_match('/^[A-Za-z]:\\\\/', $new)) {
        // Extract just the filename
        $new = 'assets/images/' . basename(str_replace('\\', '/', $new));
    }
    
    // Fix missing localhost folder or wrong absolute paths starting with /
    if (strpos($new, '/') === 0) {
        $new = ltrim($new, '/'); // remove leading slash
    }

    // Fix ../images/ -> assets/images/
    $new = str_replace('../images/', 'assets/images/', $new);
    $new = str_replace('./images/', 'assets/images/', $new);
    $new = str_replace('images/', 'assets/images/', $new); // catches cases where it's just 'images/'

    // Fix double assets
    $new = str_replace('assets/assets/', 'assets/', $new);

    // Fix uploads to backend/uploads if that was intended, but let's assume they should all be assets/images/ 
    // unless they are actual uploaded files.
    if (strpos($new, 'uploads/') !== false) {
        // Strip everything before uploads/
        $new = substr($new, strpos($new, 'uploads/'));
        // Assuming uploads are kept in backend/uploads/
        // Wait, the user said convert to: http://localhost/foldnest/frontend/assets/images/product.jpg
        // Let's just convert uploads/ to assets/images/ for consistency if they manually moved files
        $new = str_replace('uploads/', 'assets/images/', $new);
    }
    
    // Ensure all paths start with assets/images/ if they don't already and aren't full HTTP urls
    if (!empty($new) && strpos($new, 'http') !== 0 && strpos($new, 'assets/images/') !== 0) {
        // Just extract filename and prepend correct path
        $new = 'assets/images/' . basename($new);
    }

    if (empty($new)) {
        $new = 'assets/images/placeholder.jpg';
    }

    if ($original !== $new) {
        $update = $db->prepare("UPDATE products SET image_url = :new_url WHERE id = :id");
        $update->execute([':new_url' => $new, ':id' => $p['id']]);
        echo "Fixed ID {$p['id']}: '{$original}' -> '{$new}'\n";
        $updatedCount++;
    }
}

echo "Database paths fixed: {$updatedCount} updated.\n";
?>
