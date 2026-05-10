<?php
// backend/admin/update_order_status.php
// Admin updates order status
// POST: { order_id, status }

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();
$data = json_decode(file_get_contents("php://input"));

if (empty($data->order_id) || empty($data->status)) {
    http_response_code(400);
    echo json_encode(array("message" => "order_id and status are required."));
    exit();
}

$orderId = htmlspecialchars(strip_tags($data->order_id));
$status = htmlspecialchars(strip_tags($data->status));

$validStatuses = ['pending','confirmed','packed','shipped','out_for_delivery','delivered','cancelled'];
if (!in_array($status, $validStatuses)) {
    http_response_code(400);
    echo json_encode(array("message" => "Invalid status. Valid: " . implode(', ', $validStatuses)));
    exit();
}

try {
    $stmt = $db->prepare("UPDATE orders SET status = :status WHERE id = :order_id");
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':order_id', $orderId);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        http_response_code(200);
        echo json_encode(array("message" => "Order status updated to: " . ucwords(str_replace('_', ' ', $status))));
    } else {
        http_response_code(404);
        echo json_encode(array("message" => "Order not found."));
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array("message" => "Error updating status.", "error" => $e->getMessage()));
}
?>
