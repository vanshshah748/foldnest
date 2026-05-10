<?php
// backend/api/get_product.php

// Required headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=UTF-8");

// Include database
include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get the product ID from URL parameter
$id = isset($_GET['id']) ? $_GET['id'] : die();

// Query product
$query = "SELECT p.id, p.category_id, p.name, p.description, p.price, p.stock_quantity, p.image_url, c.name as category_name 
          FROM products p
          LEFT JOIN categories c ON p.category_id = c.id
          WHERE p.id = :id 
          LIMIT 0,1";

$stmt = $db->prepare($query);
$stmt->bindParam(":id", $id);
$stmt->execute();

$num = $stmt->rowCount();

if ($num > 0) {
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    extract($row);
    
    $product_item = array(
        "id" => $id,
        "category_id" => $category_id,
        "name" => $name,
        "description" => html_entity_decode($description),
        "price" => $price,
        "stock_quantity" => $stock_quantity,
        "image_url" => $image_url,
        "category_name" => $category_name
    );

    http_response_code(200);
    echo json_encode($product_item);
} else {
    http_response_code(404);
    echo json_encode(array("message" => "Product does not exist."));
}
?>
