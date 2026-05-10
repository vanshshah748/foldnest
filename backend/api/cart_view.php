<?php
// backend/api/cart_view.php

// Required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// Include database
include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$user_id = isset($_GET['user_id']) ? $_GET['user_id'] : die();

$query = "SELECT c.id as cart_item_id, c.quantity, p.id as product_id, p.name, p.price, p.image_url 
          FROM cart_items c
          JOIN products p ON c.product_id = p.id
          WHERE c.user_id = :user_id";

$stmt = $db->prepare($query);
$stmt->bindParam(":user_id", $user_id);
$stmt->execute();

$num = $stmt->rowCount();

if ($num > 0) {
    $cart_arr = array();
    $cart_arr["items"] = array();
    $cart_arr["total_price"] = 0;

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        extract($row);
        $cart_item = array(
            "cart_item_id" => $cart_item_id,
            "product_id" => $product_id,
            "name" => $name,
            "price" => $price,
            "quantity" => $quantity,
            "image_url" => $image_url,
            "subtotal" => $price * $quantity
        );
        $cart_arr["total_price"] += $cart_item["subtotal"];
        array_push($cart_arr["items"], $cart_item);
    }

    http_response_code(200);
    echo json_encode($cart_arr);
} else {
    http_response_code(200);
    echo json_encode(array("items" => array(), "total_price" => 0, "message" => "Cart is empty."));
}
?>
