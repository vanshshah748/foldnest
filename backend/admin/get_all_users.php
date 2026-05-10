<?php
// backend/admin/get_all_users.php
// Get all users for admin panel

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

$query = "SELECT u.id, u.full_name, u.email, u.phone, u.role, u.is_active, u.created_at,
                 (SELECT COUNT(*) FROM orders WHERE user_id = u.id) as order_count,
                 (SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE user_id = u.id AND status != 'cancelled') as total_spent
          FROM users u ORDER BY u.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();

$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

http_response_code(200);
echo json_encode(array("users" => $users, "total" => count($users)));
?>
