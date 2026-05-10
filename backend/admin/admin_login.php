<?php
// backend/admin/admin_login.php
// Admin Login API
// Method: POST — { username_or_email, password }

session_start();

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if (empty($data->username_or_email) || empty($data->password)) {
    http_response_code(400);
    echo json_encode(array("message" => "Username/email and password are required."));
    exit();
}

$login = htmlspecialchars(strip_tags($data->username_or_email));
$password = $data->password; // Don't strip tags from password (may contain special chars)

// Find admin by username OR email
$query = "SELECT id, username, email, password_hash, full_name, role, is_active FROM admins WHERE (username = :login OR email = :login) LIMIT 1";
$stmt = $db->prepare($query);
$stmt->bindParam(':login', $login);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    http_response_code(401);
    echo json_encode(array("message" => "Invalid credentials."));
    exit();
}

$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin['is_active']) {
    http_response_code(403);
    echo json_encode(array("message" => "Account is deactivated."));
    exit();
}

if (!password_verify($password, $admin['password_hash'])) {
    http_response_code(401);
    echo json_encode(array("message" => "Invalid credentials."));
    exit();
}

// Set session
$_SESSION['admin_id'] = $admin['id'];
$_SESSION['admin_username'] = $admin['username'];
$_SESSION['admin_email'] = $admin['email'];
$_SESSION['admin_role'] = $admin['role'];

// Update last_login
$updateStmt = $db->prepare("UPDATE admins SET last_login = NOW() WHERE id = :id");
$updateStmt->bindParam(':id', $admin['id']);
$updateStmt->execute();

// Generate a simple token for frontend
$token = base64_encode($admin['id'] . ':' . $admin['email'] . ':' . time());

http_response_code(200);
echo json_encode(array(
    "message" => "Login successful.",
    "token" => $token,
    "admin" => array(
        "id" => $admin['id'],
        "username" => $admin['username'],
        "email" => $admin['email'],
        "full_name" => $admin['full_name'],
        "role" => $admin['role']
    )
));
?>
