<?php
// backend/admin/toggle_user_status.php
// Block/Unblock a user — POST: { user_id, is_active }

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

include_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if (empty($data->user_id) || !isset($data->is_active)) {
    http_response_code(400);
    echo json_encode(array("message" => "user_id and is_active are required."));
    exit();
}

$stmt = $db->prepare("UPDATE users SET is_active = :is_active WHERE id = :user_id");
$stmt->execute([':is_active' => $data->is_active ? 1 : 0, ':user_id' => $data->user_id]);

http_response_code(200);
echo json_encode(array("message" => $data->is_active ? "User activated." : "User blocked."));
?>
