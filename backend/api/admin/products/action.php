<?php
// backend/api/admin/products/action.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include_once '../../../config/database.php';

$database = new Database();
$db = $database->getConnection();
$data = json_decode(file_get_contents("php://input"));

if (empty($data->action) || empty($data->id)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Missing action or ID."]);
    exit();
}

try {
    $id = intval($data->id);
    
    if ($data->action === 'soft_delete') {
        $stmt = $db->prepare("UPDATE products SET deleted_at = CURRENT_TIMESTAMP WHERE id = :id");
        $stmt->execute([':id' => $id]);
        echo json_encode(["success" => true, "message" => "Product deleted successfully."]);
    } 
    elseif ($data->action === 'restore') {
        $stmt = $db->prepare("UPDATE products SET deleted_at = NULL WHERE id = :id");
        $stmt->execute([':id' => $id]);
        echo json_encode(["success" => true, "message" => "Product restored successfully."]);
    }
    elseif ($data->action === 'toggle_status') {
        $stmt = $db->prepare("UPDATE products SET is_active = NOT is_active WHERE id = :id");
        $stmt->execute([':id' => $id]);
        echo json_encode(["success" => true, "message" => "Status updated."]);
    }
    else {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Invalid action."]);
    }
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
