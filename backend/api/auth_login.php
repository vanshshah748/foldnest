<?php
// backend/api/auth_login.php

// Required headers
header("Access-Control-Allow-Origin: *");
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
    !empty($data->email) &&
    !empty($data->password)
) {
    // Sanitize inputs
    $email = htmlspecialchars(strip_tags($data->email));
    $password = htmlspecialchars(strip_tags($data->password));

    // Query to check if email exists
    $query = "SELECT id, full_name, password_hash, role FROM users WHERE email = :email LIMIT 0,1";

    // Prepare query
    $stmt = $db->prepare($query);

    // Bind value
    $stmt->bindParam(":email", $email);

    // Execute query
    $stmt->execute();

    $num = $stmt->rowCount();

    if ($num > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $id = $row['id'];
        $full_name = $row['full_name'];
        $password_hash = $row['password_hash'];
        $role = $row['role'];

        // Verify password
        if (password_verify($password, $password_hash)) {
            // For a production app, we should use JWT (JSON Web Tokens) here.
            // But for simplicity in this sprint, we'll return user details and a simple token representation.
            
            // In a later sprint we could implement standard JWT.
            $token = base64_encode($id . ":" . $email . ":" . time());

            http_response_code(200); // 200 OK
            echo json_encode(array(
                "message" => "Successful login.",
                "token" => $token,
                "user" => array(
                    "id" => $id,
                    "full_name" => $full_name,
                    "email" => $email,
                    "role" => $role
                )
            ));
        } else {
            // Password invalid
            http_response_code(401); // 401 Unauthorized
            echo json_encode(array("message" => "Login failed. Incorrect password."));
        }
    } else {
        // Email not found
        http_response_code(401); // 401 Unauthorized
        echo json_encode(array("message" => "Login failed. User does not exist."));
    }
} else {
    // Data is incomplete
    http_response_code(400); // 400 Bad Request
    echo json_encode(array("message" => "Unable to login. Data is incomplete."));
}
?>
