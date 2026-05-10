<?php
// backend/api/cart_add.php

// Required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
// Include database
include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get posted data
$data = json_decode(file_get_contents("php://input"));

if (
    !empty($data->user_id) &&
    !empty($data->product_id) &&
    !empty($data->quantity)
) {
    $user_id = htmlspecialchars(strip_tags($data->user_id));
    $product_id = htmlspecialchars(strip_tags($data->product_id));
    $quantity = htmlspecialchars(strip_tags($data->quantity));

    // Try to insert, if duplicate (user_id + product_id), then update quantity
    $query = "INSERT INTO cart_items (user_id, product_id, quantity) 
              VALUES (:user_id, :product_id, :quantity) 
              ON DUPLICATE KEY UPDATE quantity = quantity + :quantity";

    $stmt = $db->prepare($query);

    $stmt->bindParam(":user_id", $user_id);
    $stmt->bindParam(":product_id", $product_id);
    $stmt->bindParam(":quantity", $quantity);

    if ($stmt->execute()) {
        http_response_code(201);
        echo json_encode(array("message" => "Item added to cart."));
    } else {
        http_response_code(503);
        echo json_encode(array("message" => "Unable to add item to cart."));
    }
} else {
    http_response_code(400);
    echo json_encode(array("message" => "Unable to add to cart. Data is incomplete."));
}
?>
