<?php
// backend/api/admin/settings/update_password.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include_once '../../../config/database.php';

$database = new Database();
$db = $database->getConnection();
$data = json_decode(file_get_contents("php://input"));

if (empty($data->admin_id) || empty($data->current_password) || empty($data->new_password)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "All fields are required."]);
    exit();
}

try {
    $id = intval($data->admin_id);
    
    // Verify current password
    $stmt = $db->prepare("SELECT password_hash FROM admins WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$admin || !password_verify($data->current_password, $admin['password_hash'])) {
        echo json_encode(["success" => false, "message" => "Incorrect current password."]);
        exit();
    }
    
    // Update password
    $new_hash = password_hash($data->new_password, PASSWORD_BCRYPT);
    $updateStmt = $db->prepare("UPDATE admins SET password_hash = :hash WHERE id = :id");
    
    if ($updateStmt->execute([':hash' => $new_hash, ':id' => $id])) {
        echo json_encode(["success" => true, "message" => "Password updated successfully."]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to update password."]);
    }
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
