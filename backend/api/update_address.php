<?php
// backend/api/update_address.php
// API to update an existing shipping address
// Method: POST
// Required fields: address_id, user_id, + any fields to update

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

// Validate required fields
if (empty($data->address_id) || empty($data->user_id)) {
    http_response_code(400);
    echo json_encode(array("message" => "address_id and user_id are required."));
    exit();
}

$address_id = htmlspecialchars(strip_tags($data->address_id));
$user_id    = htmlspecialchars(strip_tags($data->user_id));

// Verify ownership — user can only update their own addresses
$checkQuery = "SELECT id FROM user_addresses WHERE id = :address_id AND user_id = :user_id";
$checkStmt = $db->prepare($checkQuery);
$checkStmt->bindParam(':address_id', $address_id);
$checkStmt->bindParam(':user_id', $user_id);
$checkStmt->execute();

if ($checkStmt->rowCount() == 0) {
    http_response_code(403);
    echo json_encode(array("message" => "Address not found or access denied."));
    exit();
}

// Build dynamic update query based on provided fields
$updateFields = [];
$params = [':address_id' => $address_id, ':user_id' => $user_id];

// List of updatable fields
$fieldMap = [
    'full_name' => 'full_name',
    'mobile_number' => 'mobile_number',
    'alternate_mobile' => 'alternate_mobile',
    'address_line_1' => 'address_line_1',
    'address_line_2' => 'address_line_2',
    'landmark' => 'landmark',
    'city' => 'city',
    'state' => 'state',
    'pincode' => 'pincode',
    'country' => 'country',
    'address_type' => 'address_type'
];

foreach ($fieldMap as $jsonKey => $dbColumn) {
    if (isset($data->$jsonKey)) {
        $value = htmlspecialchars(strip_tags($data->$jsonKey));
        $updateFields[] = "$dbColumn = :$dbColumn";
        $params[":$dbColumn"] = $value;
    }
}

// Validate mobile number if being updated
if (isset($data->mobile_number)) {
    $mobile = htmlspecialchars(strip_tags($data->mobile_number));
    if (!preg_match('/^[6-9]\d{9}$/', $mobile)) {
        http_response_code(400);
        echo json_encode(array("message" => "Invalid mobile number. Must be 10 digits starting with 6-9."));
        exit();
    }
}

// Validate pincode if being updated
if (isset($data->pincode)) {
    $pincode = htmlspecialchars(strip_tags($data->pincode));
    if (!preg_match('/^\d{6}$/', $pincode)) {
        http_response_code(400);
        echo json_encode(array("message" => "Invalid pincode. Must be exactly 6 digits."));
        exit();
    }
}

// Handle is_default flag (even if no other fields are being updated)
$hasDefaultUpdate = isset($data->is_default) && $data->is_default == 1;

if (empty($updateFields) && !$hasDefaultUpdate) {
    http_response_code(400);
    echo json_encode(array("message" => "No fields to update."));
    exit();
}

$transactionStarted = false;

try {
    $db->beginTransaction();
    $transactionStarted = true;

    // Handle is_default flag
    if ($hasDefaultUpdate) {
        // Reset all other defaults for this user
        $resetStmt = $db->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = :user_id");
        $resetStmt->bindParam(':user_id', $user_id);
        $resetStmt->execute();

        $updateFields[] = "is_default = 1";
    }

    if (!empty($updateFields)) {
        // Execute update
        $query = "UPDATE user_addresses SET " . implode(', ', $updateFields) .
                 " WHERE id = :address_id AND user_id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->execute($params);
    }

    $db->commit();

    http_response_code(200);
    echo json_encode(array("message" => "Address updated successfully."));

} catch (\Throwable $e) {
    if ($transactionStarted && $db !== null) {
        try { $db->rollBack(); } catch (\Throwable $r) {
            error_log("Rollback failed: " . $r->getMessage());
        }
    }
    error_log("update_address.php ERROR: " . $e->getMessage());
    http_response_code(503);
    echo json_encode(array("message" => "Unable to update address. " . $e->getMessage()));
}
?>
