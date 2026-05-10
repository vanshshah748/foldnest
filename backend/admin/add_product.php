<?php
// backend/admin/add_product.php
// Add a new product — POST with JSON body

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

include_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if (empty($data->name) || empty($data->price) || empty($data->category_id)) {
    http_response_code(400);
    echo json_encode(array("message" => "name, price, and category_id are required."));
    exit();
}

$stmt = $db->prepare("INSERT INTO products (name, description, price, old_price, rating, stock_quantity, category_id, image_url, is_active) VALUES (:name, :description, :price, :old_price, :rating, :stock, :category_id, :image_url, 1)");

$stmt->execute([
    ':name' => htmlspecialchars(strip_tags($data->name)),
    ':description' => !empty($data->description) ? htmlspecialchars(strip_tags($data->description)) : '',
    ':price' => (float) $data->price,
    ':old_price' => !empty($data->old_price) ? (float) $data->old_price : 0,
    ':rating' => !empty($data->rating) ? (float) $data->rating : 0,
    ':stock' => !empty($data->stock_quantity) ? (int) $data->stock_quantity : 0,
    ':category_id' => (int) $data->category_id,
    ':image_url' => !empty($data->image_url) ? htmlspecialchars(strip_tags($data->image_url)) : ''
]);

http_response_code(201);
echo json_encode(array("message" => "Product added successfully.", "product_id" => $db->lastInsertId()));
?>
