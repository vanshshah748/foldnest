<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if(!empty($data->name) && !empty($data->email) && !empty($data->message)) {
    try {
        $query = "INSERT INTO contact_queries (name, email, phone, subject, message, status, created_at) 
                  VALUES (:name, :email, :phone, :subject, :message, 'unread', NOW())";
        
        $stmt = $db->prepare($query);
        
        $name = htmlspecialchars(strip_tags($data->name));
        $email = htmlspecialchars(strip_tags($data->email));
        $phone = isset($data->phone) ? htmlspecialchars(strip_tags($data->phone)) : '';
        $subject = isset($data->subject) ? htmlspecialchars(strip_tags($data->subject)) : 'General Inquiry';
        $message = htmlspecialchars(strip_tags($data->message));
        
        $stmt->bindParam(":name", $name);
        $stmt->bindParam(":email", $email);
        $stmt->bindParam(":phone", $phone);
        $stmt->bindParam(":subject", $subject);
        $stmt->bindParam(":message", $message);
        
        if($stmt->execute()) {
            http_response_code(201);
            echo json_encode(array("success" => true, "message" => "Query submitted successfully."));
        } else {
            http_response_code(503);
            echo json_encode(array("success" => false, "message" => "Unable to submit query."));
        }
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(array("success" => false, "message" => "Database Error: " . $e->getMessage()));
    }
} else {
    http_response_code(400);
    echo json_encode(array("success" => false, "message" => "Incomplete data."));
}
?>
