<?php
// backend/api/add_address.php
// API to add a new shipping address for a user
// Method: POST
// Required fields: user_id, full_name, mobile_number, address_line_1, city, state, pincode

// CORS & Headers — MUST be at the very top before any output
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Include database
include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// =============================================
// FIX #1: Check if database connection is valid
// If $db is null, PDO connection failed — return clean JSON error
// =============================================
if ($db === null) {
    http_response_code(503);
    echo json_encode(array(
        "message" => "Database connection failed. Please check your MySQL server is running."
    ));
    exit();
}

// Get posted data
$data = json_decode(file_get_contents("php://input"));

// =============================================
// FIX #2: Check if JSON data was received properly
// =============================================
if ($data === null) {
    http_response_code(400);
    echo json_encode(array(
        "message" => "Invalid JSON data received. Please check request body."
    ));
    exit();
}

// =============================================
// VALIDATION
// =============================================
if (
    empty($data->user_id) ||
    empty($data->full_name) ||
    empty($data->mobile_number) ||
    empty($data->address_line_1) ||
    empty($data->city) ||
    empty($data->state) ||
    empty($data->pincode)
) {
    http_response_code(400);
    echo json_encode(array("message" => "Required fields: user_id, full_name, mobile_number, address_line_1, city, state, pincode."));
    exit();
}

// Sanitize inputs
$user_id        = htmlspecialchars(strip_tags($data->user_id));
$full_name      = htmlspecialchars(strip_tags($data->full_name));
$mobile_number  = htmlspecialchars(strip_tags($data->mobile_number));
$alternate_mobile = !empty($data->alternate_mobile) ? htmlspecialchars(strip_tags($data->alternate_mobile)) : null;
$address_line_1 = htmlspecialchars(strip_tags($data->address_line_1));
$address_line_2 = !empty($data->address_line_2) ? htmlspecialchars(strip_tags($data->address_line_2)) : null;
$landmark       = !empty($data->landmark) ? htmlspecialchars(strip_tags($data->landmark)) : null;
$city           = htmlspecialchars(strip_tags($data->city));
$state          = htmlspecialchars(strip_tags($data->state));
$pincode        = htmlspecialchars(strip_tags($data->pincode));
$country        = !empty($data->country) ? htmlspecialchars(strip_tags($data->country)) : 'India';
$address_type   = !empty($data->address_type) ? htmlspecialchars(strip_tags($data->address_type)) : 'Home';
$is_default     = !empty($data->is_default) ? 1 : 0;

// Validate mobile number (10 digits)
if (!preg_match('/^[6-9]\d{9}$/', $mobile_number)) {
    http_response_code(400);
    echo json_encode(array("message" => "Invalid mobile number. Must be 10 digits starting with 6-9."));
    exit();
}

// Validate alternate mobile if provided
if ($alternate_mobile && !preg_match('/^[6-9]\d{9}$/', $alternate_mobile)) {
    http_response_code(400);
    echo json_encode(array("message" => "Invalid alternate mobile number."));
    exit();
}

// Validate pincode (6 digits)
if (!preg_match('/^\d{6}$/', $pincode)) {
    http_response_code(400);
    echo json_encode(array("message" => "Invalid pincode. Must be exactly 6 digits."));
    exit();
}

// Validate address_type
if (!in_array($address_type, ['Home', 'Office'])) {
    $address_type = 'Home';
}

// =============================================
// FIX #3: Use Throwable instead of Exception to catch
// TypeError, Error, and Exception in PHP 7+.
// Also guard $db->rollBack() to prevent secondary crashes.
// =============================================
$transactionStarted = false;

try {
    $db->beginTransaction();
    $transactionStarted = true;

    // If this address is set as default, un-default all other addresses for this user
    if ($is_default == 1) {
        $resetStmt = $db->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = :user_id");
        $resetStmt->bindParam(':user_id', $user_id);
        $resetStmt->execute();
    }

    // Check if user has any addresses — if not, make this one default
    $countStmt = $db->prepare("SELECT COUNT(*) as total FROM user_addresses WHERE user_id = :user_id");
    $countStmt->bindParam(':user_id', $user_id);
    $countStmt->execute();
    $countResult = $countStmt->fetch(PDO::FETCH_ASSOC);
    if ($countResult['total'] == 0) {
        $is_default = 1; // First address is always default
    }

    // Insert the new address
    $query = "INSERT INTO user_addresses 
              (user_id, full_name, mobile_number, alternate_mobile, address_line_1, address_line_2, 
               landmark, city, state, pincode, country, address_type, is_default) 
              VALUES 
              (:user_id, :full_name, :mobile_number, :alternate_mobile, :address_line_1, :address_line_2,
               :landmark, :city, :state, :pincode, :country, :address_type, :is_default)";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':full_name', $full_name);
    $stmt->bindParam(':mobile_number', $mobile_number);
    $stmt->bindParam(':alternate_mobile', $alternate_mobile);
    $stmt->bindParam(':address_line_1', $address_line_1);
    $stmt->bindParam(':address_line_2', $address_line_2);
    $stmt->bindParam(':landmark', $landmark);
    $stmt->bindParam(':city', $city);
    $stmt->bindParam(':state', $state);
    $stmt->bindParam(':pincode', $pincode);
    $stmt->bindParam(':country', $country);
    $stmt->bindParam(':address_type', $address_type);
    $stmt->bindParam(':is_default', $is_default);

    $stmt->execute();
    $address_id = $db->lastInsertId();

    $db->commit();

    http_response_code(201);
    echo json_encode(array(
        "message" => "Address added successfully.",
        "address_id" => $address_id
    ));

} catch (\Throwable $e) {
    // FIX #3: Only rollBack if transaction was actually started
    if ($transactionStarted && $db !== null) {
        try {
            $db->rollBack();
        } catch (\Throwable $rollbackError) {
            // Silently ignore rollback failure
            error_log("Rollback failed: " . $rollbackError->getMessage());
        }
    }

    // Log the actual error for debugging
    error_log("add_address.php ERROR: " . $e->getMessage());

    http_response_code(503);
    echo json_encode(array(
        "message" => "Unable to add address. " . $e->getMessage()
    ));
}
?>
