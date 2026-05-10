<?php
// backend/api/auth_register.php

// Required headers
header("Access-Control-Allow-Origin: *"); // Will restrict to Netlify origin later
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
// Include database and object files
include_once '../config/database.php';
include_once '../services/email_service.php';

$database = new Database();
$db = $database->getConnection();

if ($db === null) {
    http_response_code(503);
    echo json_encode(array("message" => "Database connection failed. Please check setup."));
    exit();
}

// Get posted data
$data = json_decode(file_get_contents("php://input"));

// Make sure data is not empty
if (
    !empty($data->full_name) &&
    !empty($data->email) &&
    !empty($data->password)
) {
    // Sanitize inputs
    $full_name = htmlspecialchars(strip_tags($data->full_name));
    $email = htmlspecialchars(strip_tags($data->email));
    $password = htmlspecialchars(strip_tags($data->password));

    // Hash the password
    $password_hash = password_hash($password, PASSWORD_BCRYPT);

    // Query to insert record
    $query = "INSERT INTO users SET full_name=:full_name, email=:email, password_hash=:password_hash";

    // Prepare query
    $stmt = $db->prepare($query);

    // Bind values
    $stmt->bindParam(":full_name", $full_name);
    $stmt->bindParam(":email", $email);
    $stmt->bindParam(":password_hash", $password_hash);

    try {
        // Execute query
        if ($stmt->execute()) {
            // Send Welcome Email asynchronously-ish (won't block UI much since it's fast)
            EmailService::sendWelcomeEmail($email, $full_name);

            http_response_code(201); // 201 Created
            echo json_encode(array("message" => "User was successfully registered."));
        } else {
            http_response_code(503); // 503 Service Unavailable
            echo json_encode(array("message" => "Unable to register the user."));
        }
    } catch(PDOException $e) {
        // Likely a duplicate email exception because of the UNIQUE constraint
        http_response_code(400); 
        echo json_encode(array("message" => "Error: Email might already be taken.", "error" => $e->getMessage()));
    }
} else {
    // Data is incomplete
    http_response_code(400); // 400 Bad Request
    echo json_encode(array("message" => "Unable to register user. Data is incomplete."));
}
?>
