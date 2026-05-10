<?php
// backend/admin/update_product.php
// Update a product — POST with JSON body including product_id

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

include_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if (empty($data->product_id)) {
    http_response_code(400);
    echo json_encode(array("message" => "product_id is required."));
    exit();
}

$updates = [];
$params = [':id' => (int) $data->product_id];

$fields = ['name', 'description', 'price', 'old_price', 'rating', 'stock_quantity', 'category_id', 'image_url', 'is_active'];
foreach ($fields as $f) {
    if (isset($data->$f)) {
        $updates[] = "$f = :$f";
        $params[":$f"] = htmlspecialchars(strip_tags($data->$f));
    }
}

if (empty($updates)) {
    http_response_code(400);
    echo json_encode(array("message" => "No fields to update."));
    exit();
}

$stmt = $db->prepare("UPDATE products SET " . implode(', ', $updates) . " WHERE id = :id");
$stmt->execute($params);

http_response_code(200);
echo json_encode(array("message" => "Product updated successfully."));
?>
