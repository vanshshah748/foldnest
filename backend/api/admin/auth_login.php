<?php
// backend/api/admin/auth_login.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->email) && !empty($data->password)) {
    $email = htmlspecialchars(strip_tags($data->email));
    
    $query = "SELECT id, username, email, password, role, status FROM admins WHERE email = :email LIMIT 0,1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row['status'] === 'inactive') {
            http_response_code(403);
            echo json_encode(["message" => "Account is inactive. Contact Super Admin."]);
            exit();
        }
        
        if (password_verify($data->password, $row['password'])) {
            // Update last login
            $update_query = "UPDATE admins SET last_login = CURRENT_TIMESTAMP WHERE id = :id";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->bindParam(':id', $row['id']);
            $update_stmt->execute();
            
            http_response_code(200);
            echo json_encode([
                "message" => "Login successful.",
                "admin" => [
                    "id" => $row['id'],
                    "username" => $row['username'],
                    "email" => $row['email'],
                    "role" => $row['role']
                ]
            ]);
        } else {
            http_response_code(401);
            echo json_encode(["message" => "Invalid password."]);
        }
    } else {
        http_response_code(404);
        echo json_encode(["message" => "Admin account not found."]);
    }
} else {
    http_response_code(400);
    echo json_encode(["message" => "Incomplete data. Provide email and password."]);
}
?>
