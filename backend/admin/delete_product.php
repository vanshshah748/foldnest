<?php
// backend/admin/delete_product.php
// Soft delete a product (set is_active = 0)
// POST: { product_id }

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

$stmt = $db->prepare("UPDATE products SET is_active = 0 WHERE id = :id");
$stmt->execute([':id' => (int) $data->product_id]);

http_response_code(200);
echo json_encode(array("message" => "Product deleted (deactivated) successfully."));
?>
