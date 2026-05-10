<?php
// backend/api/get_products.php

// Required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// Include database
include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

if ($db === null) {
    http_response_code(503);
    echo json_encode(array("success" => false, "message" => "Database connection failed."));
    exit();
}

$category_id = isset($_GET['category_id']) ? $_GET['category_id'] : null;

try {
    if ($category_id) {
        $query = "SELECT p.id, p.name as title, p.description, p.price, p.old_price as oldPrice, p.rating, p.stock_quantity as stock, p.image_url as image, c.name as category 
                  FROM products p
                  LEFT JOIN categories c ON p.category_id = c.id
                  WHERE p.category_id = :category_id AND p.is_active = 1
                  ORDER BY p.id ASC";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":category_id", $category_id);
    } else {
        $query = "SELECT p.id, p.name as title, p.description, p.price, p.old_price as oldPrice, p.rating, p.stock_quantity as stock, p.image_url as image, c.name as category 
                  FROM products p
                  LEFT JOIN categories c ON p.category_id = c.id
                  WHERE p.is_active = 1
                  ORDER BY p.id ASC";
        $stmt = $db->prepare($query);
    }

    $stmt->execute();
    $products_arr = array();

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Ensure numbers are cast correctly for JS
        $row['price'] = (float) $row['price'];
        $row['oldPrice'] = (float) $row['oldPrice'];
        $row['rating'] = (float) $row['rating'];
        $row['stock'] = (int) $row['stock'];
        $row['id'] = (int) $row['id'];
        
        // Handle missing images safely
        if (empty($row['image'])) {
            $row['image'] = 'assets/images/placeholder.jpg';
        }

        array_push($products_arr, $row);
    }

    // Always return 200 OK with success flag as requested
    http_response_code(200);
    echo json_encode(array(
        "success" => true,
        "products" => $products_arr
    ));

} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(array("success" => false, "message" => "Failed to fetch products. " . $e->getMessage()));
}
?>
