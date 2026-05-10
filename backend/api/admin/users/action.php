<?php
// backend/api/admin/users/action.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include_once '../../../config/database.php';

$database = new Database();
$db = $database->getConnection();
$data = json_decode(file_get_contents("php://input"));

if (empty($data->id) || empty($data->action)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Missing parameters."]);
    exit();
}

try {
    $id = intval($data->id);
    
    if ($data->action === 'toggle_block') {
        $stmt = $db->prepare("UPDATE users SET is_active = NOT is_active WHERE id = :id AND role = 'customer'");
        $stmt->execute([':id' => $id]);
        echo json_encode(["success" => true, "message" => "Customer status updated."]);
    }
    elseif ($data->action === 'delete') {
        // We'll soft delete or hard delete. Hard deleting could break foreign keys if not cascaded.
        // Let's just block them and scramble their email for GDPR style "soft delete" instead of breaking orders.
        $stmt = $db->prepare("UPDATE users SET is_active = 0, email = CONCAT('deleted_', id, '@foldnest.com') WHERE id = :id AND role = 'customer'");
        $stmt->execute([':id' => $id]);
        echo json_encode(["success" => true, "message" => "Customer deleted."]);
    } else {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Invalid action."]);
    }
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
