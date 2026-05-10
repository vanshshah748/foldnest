<?php
// backend/api/admin/orders/update.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include_once '../../../config/database.php';

$database = new Database();
$db = $database->getConnection();
$data = json_decode(file_get_contents("php://input"));

if (empty($data->id) || empty($data->status)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Order ID and Status are required."]);
    exit();
}

try {
    $id = intval($data->id);
    $status = htmlspecialchars(strip_tags($data->status));
    $tracking = htmlspecialchars(strip_tags($data->tracking_id ?? ''));
    $est_delivery = htmlspecialchars(strip_tags($data->estimated_delivery ?? ''));
    
    $query = "UPDATE orders SET status = :status";
    $params = [':status' => $status, ':id' => $id];
    
    if (!empty($tracking)) {
        $query .= ", tracking_id = :tracking";
        $params[':tracking'] = $tracking;
    }
    if (!empty($est_delivery)) {
        $query .= ", estimated_delivery = :delivery";
        $params[':delivery'] = $est_delivery;
    }
    
    $query .= " WHERE id = :id";
    
    $stmt = $db->prepare($query);
    if ($stmt->execute($params)) {
        echo json_encode(["success" => true, "message" => "Order updated successfully."]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to update order."]);
    }
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
