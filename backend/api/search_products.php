<?php
// backend/api/search_products.php

// Required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// Include database
include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get search keywords
$keywords = isset($_GET['q']) ? $_GET['q'] : "";

if (empty($keywords)) {
    http_response_code(400);
    echo json_encode(array("message" => "Please provide a search term '?q=term'."));
    exit();
}

// Query products
$query = "SELECT p.id, p.name, p.description, p.price, p.stock_quantity, p.image_url, c.name as category_name 
          FROM products p
          LEFT JOIN categories c ON p.category_id = c.id
          WHERE p.name LIKE ? OR p.description LIKE ? OR c.name LIKE ?
          ORDER BY p.created_at DESC";

$stmt = $db->prepare($query);

// Sanitize and prepare search term
$search_term = "%{$keywords}%";

$stmt->bindParam(1, $search_term);
$stmt->bindParam(2, $search_term);
$stmt->bindParam(3, $search_term);

$stmt->execute();
$num = $stmt->rowCount();

if ($num > 0) {
    $products_arr = array();
    $products_arr["records"] = array();

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        extract($row);
        $product_item = array(
            "id" => $id,
            "name" => $name,
            "description" => html_entity_decode($description),
            "price" => $price,
            "stock_quantity" => $stock_quantity,
            "image_url" => $image_url,
            "category_name" => $category_name
        );
        array_push($products_arr["records"], $product_item);
    }

    http_response_code(200);
    echo json_encode($products_arr);
} else {
    http_response_code(404);
    echo json_encode(array("message" => "No products found matching your search."));
}
?>
