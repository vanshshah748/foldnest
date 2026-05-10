<?php
// backend/api/get_addresses.php
// API to get all addresses for a user
// Method: GET
// Required: ?user_id=X

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// FIX: Check database connection
if ($db === null) {
    http_response_code(503);
    echo json_encode(array("message" => "Database connection failed.", "addresses" => [], "total" => 0));
    exit();
}

$user_id = isset($_GET['user_id']) ? htmlspecialchars(strip_tags($_GET['user_id'])) : null;

if (!$user_id) {
    http_response_code(400);
    echo json_encode(array("message" => "user_id parameter is required.", "addresses" => [], "total" => 0));
    exit();
}

try {
    // Fetch all addresses, default address first
    $query = "SELECT * FROM user_addresses WHERE user_id = :user_id ORDER BY is_default DESC, created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();

    $addresses = array();

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $addresses[] = array(
            "id" => (int) $row['id'],
            "user_id" => (int) $row['user_id'],
            "full_name" => $row['full_name'],
            "mobile_number" => $row['mobile_number'],
            "alternate_mobile" => $row['alternate_mobile'],
            "address_line_1" => $row['address_line_1'],
            "address_line_2" => $row['address_line_2'],
            "landmark" => $row['landmark'],
            "city" => $row['city'],
            "state" => $row['state'],
            "pincode" => $row['pincode'],
            "country" => $row['country'],
            "address_type" => $row['address_type'],
            "is_default" => (bool) $row['is_default'],
            "created_at" => $row['created_at']
        );
    }

    http_response_code(200);
    echo json_encode(array(
        "addresses" => $addresses,
        "total" => count($addresses)
    ));

} catch (\Throwable $e) {
    error_log("get_addresses.php ERROR: " . $e->getMessage());
    http_response_code(200);
    // Return empty array so the frontend doesn't break — table may not exist yet
    echo json_encode(array(
        "addresses" => [],
        "total" => 0,
        "message" => "Could not load addresses. Run database migration first."
    ));
}
?>
