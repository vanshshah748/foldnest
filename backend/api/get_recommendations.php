<?php
// backend/api/get_recommendations.php
// Returns "Frequently Bought Together" or "Top Rated" product recommendations

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once __DIR__ . '/../config/database.php';

$database = new Database();
$db = $database->getConnection();

if ($db === null) {
    http_response_code(503);
    echo json_encode(array("message" => "Database connection failed."));
    exit();
}

// Get user_id if passed
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$limit = 4; // Number of recommendations to return

$recommended_products = [];
$cart_product_ids = [];
$cart_category_ids = [];

if ($user_id > 0) {
    // 1. Get products currently in the user's cart
    $cartStmt = $db->prepare("SELECT c.product_id, p.category_id FROM cart_items c JOIN products p ON c.product_id = p.id WHERE c.user_id = :uid");
    $cartStmt->execute([':uid' => $user_id]);
    while ($row = $cartStmt->fetch(PDO::FETCH_ASSOC)) {
        $cart_product_ids[] = $row['product_id'];
        if ($row['category_id'] && !in_array($row['category_id'], $cart_category_ids)) {
            $cart_category_ids[] = $row['category_id'];
        }
    }
}

if (!empty($cart_product_ids)) {
    // 2. FREQUENTLY BOUGHT TOGETHER LOGIC
    // Find other orders that contained these cart items
    $placeholders = str_repeat('?,', count($cart_product_ids) - 1) . '?';
    
    // Find top products bought in the same orders, EXCLUDING the items already in the cart
    $fbtQuery = "
        SELECT p.id, p.name as title, p.price, p.image_url as image, p.rating, COUNT(oi2.product_id) as purchase_count
        FROM order_items oi1
        JOIN order_items oi2 ON oi1.order_id = oi2.order_id
        JOIN products p ON oi2.product_id = p.id
        WHERE oi1.product_id IN ($placeholders) 
          AND oi2.product_id NOT IN ($placeholders)
        GROUP BY p.id
        ORDER BY purchase_count DESC, p.rating DESC
        LIMIT " . $limit;
        
    $fbtStmt = $db->prepare($fbtQuery);
    $params = array_merge($cart_product_ids, $cart_product_ids);
    $fbtStmt->execute($params);
    $recommended_products = $fbtStmt->fetchAll(PDO::FETCH_ASSOC);

    // 2.5 SIMILAR CATEGORY FALLBACK
    // If order history is empty (like on a fresh database), recommend from the same category!
    if (count($recommended_products) < $limit && !empty($cart_category_ids)) {
        $needed = $limit - count($recommended_products);
        
        $exclude_ids = $cart_product_ids;
        foreach ($recommended_products as $rp) { $exclude_ids[] = $rp['id']; }
        
        $cat_placeholders = str_repeat('?,', count($cart_category_ids) - 1) . '?';
        $ex_placeholders = str_repeat('?,', count($exclude_ids) - 1) . '?';
        
        $catQuery = "
            SELECT id, name as title, price, image_url as image, rating
            FROM products 
            WHERE category_id IN ($cat_placeholders) AND id NOT IN ($ex_placeholders)
            ORDER BY rating DESC, id DESC LIMIT " . intval($needed);
            
        $catStmt = $db->prepare($catQuery);
        $catParams = array_merge($cart_category_ids, $exclude_ids);
        $catStmt->execute($catParams);
        
        while ($row = $catStmt->fetch(PDO::FETCH_ASSOC)) {
            $recommended_products[] = $row;
        }
    }
}

// 3. FALLBACK LOGIC
// If we didn't find enough "Frequently Bought Together" items, fill the rest with top-rated products
if (count($recommended_products) < $limit) {
    $needed = $limit - count($recommended_products);
    
    // Build list of IDs to exclude so we don't recommend duplicates
    $exclude_ids = $cart_product_ids;
    foreach ($recommended_products as $rp) {
        $exclude_ids[] = $rp['id'];
    }
    
    $fallbackQuery = "
        SELECT id, name as title, price, image_url as image, rating
        FROM products 
    ";
    
    $params = [];
    if (!empty($exclude_ids)) {
        $placeholders = str_repeat('?,', count($exclude_ids) - 1) . '?';
        $fallbackQuery .= " WHERE id NOT IN ($placeholders) ";
        $params = $exclude_ids;
    }
    
    // Order by rating to show "Top Rated" as fallback
    $fallbackQuery .= " ORDER BY rating DESC, id DESC LIMIT " . intval($needed);
    
    $fallbackStmt = $db->prepare($fallbackQuery);
    $fallbackStmt->execute($params);
    
    while ($row = $fallbackStmt->fetch(PDO::FETCH_ASSOC)) {
        $recommended_products[] = $row;
    }
}

// 4. Return results
http_response_code(200);
echo json_encode(array(
    "success" => true,
    "recommendations" => $recommended_products,
    "type" => !empty($cart_product_ids) ? "Frequently Bought Together" : "Trending Products"
));
?>
