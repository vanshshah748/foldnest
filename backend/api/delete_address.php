<?php
// backend/api/delete_address.php
// API to delete a shipping address
// Method: POST
// Required fields: address_id, user_id

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// FIX: Check database connection
if ($db === null) {
    http_response_code(503);
    echo json_encode(array("message" => "Database connection failed."));
    exit();
}

$data = json_decode(file_get_contents("php://input"));

// FIX: Check JSON parsing
if ($data === null) {
    http_response_code(400);
    echo json_encode(array("message" => "Invalid JSON data received."));
    exit();
}

if (empty($data->address_id) || empty($data->user_id)) {
    http_response_code(400);
    echo json_encode(array("message" => "address_id and user_id are required."));
    exit();
}

$address_id = htmlspecialchars(strip_tags($data->address_id));
$user_id    = htmlspecialchars(strip_tags($data->user_id));

$transactionStarted = false;

try {
    $db->beginTransaction();
    $transactionStarted = true;

    // Check if this address was the default
    $checkStmt = $db->prepare("SELECT is_default FROM user_addresses WHERE id = :address_id AND user_id = :user_id");
    $checkStmt->bindParam(':address_id', $address_id);
    $checkStmt->bindParam(':user_id', $user_id);
    $checkStmt->execute();

    if ($checkStmt->rowCount() == 0) {
        $db->rollBack();
        http_response_code(404);
        echo json_encode(array("message" => "Address not found or access denied."));
        exit();
    }

    $wasDefault = $checkStmt->fetch(PDO::FETCH_ASSOC)['is_default'];

    // Delete the address
    $deleteStmt = $db->prepare("DELETE FROM user_addresses WHERE id = :address_id AND user_id = :user_id");
    $deleteStmt->bindParam(':address_id', $address_id);
    $deleteStmt->bindParam(':user_id', $user_id);
    $deleteStmt->execute();

    // If the deleted address was default, set the next one as default
    if ($wasDefault == 1) {
        $nextStmt = $db->prepare("UPDATE user_addresses SET is_default = 1 WHERE user_id = :user_id ORDER BY created_at ASC LIMIT 1");
        $nextStmt->bindParam(':user_id', $user_id);
        $nextStmt->execute();
    }

    $db->commit();

    http_response_code(200);
    echo json_encode(array("message" => "Address deleted successfully."));

} catch (\Throwable $e) {
    if ($transactionStarted && $db !== null) {
        try { $db->rollBack(); } catch (\Throwable $r) {
            error_log("Rollback failed: " . $r->getMessage());
        }
    }
    error_log("delete_address.php ERROR: " . $e->getMessage());
    http_response_code(503);
    echo json_encode(array("message" => "Unable to delete address. " . $e->getMessage()));
}
?>
