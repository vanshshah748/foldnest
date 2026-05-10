<?php
// backend/api/admin/queries/update.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include_once '../../../config/database.php';

$database = new Database();
$db = $database->getConnection();
$data = json_decode(file_get_contents("php://input"));

if (empty($data->id) || empty($data->status)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "ID and Status required."]);
    exit();
}

try {
    $id = intval($data->id);
    $status = htmlspecialchars(strip_tags($data->status)); // read, resolved
    
    $stmt = $db->prepare("UPDATE contact_queries SET status = :status WHERE id = :id");
    if ($stmt->execute([':status' => $status, ':id' => $id])) {
        echo json_encode(["success" => true, "message" => "Query status updated."]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to update status."]);
    }
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
