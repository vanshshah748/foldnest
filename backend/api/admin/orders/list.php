<?php
// backend/api/admin/orders/list.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");

include_once '../../../config/database.php';

$database = new Database();
$db = $database->getConnection();

try {
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $status = isset($_GET['status']) && $_GET['status'] !== 'all' ? $_GET['status'] : '';
    
    $query = "SELECT o.id, o.tracking_id, o.total_amount, o.payment_method, o.payment_status, o.status, 
                     DATE_FORMAT(o.created_at, '%Y-%m-%d %H:%i') as order_date, o.estimated_delivery,
                     u.full_name as first_name, '' as last_name, u.email,
                     a.address_line_1 as address_line1, a.city, a.state, a.pincode
              FROM orders o
              LEFT JOIN users u ON o.user_id = u.id
              LEFT JOIN user_addresses a ON o.address_id = a.id";
              
    $conditions = [];
    $params = [];
    
    if (!empty($status)) {
        $conditions[] = "o.status = :status";
        $params[':status'] = $status;
    }
    
    if (!empty($search)) {
        $conditions[] = "(o.id LIKE :search OR o.tracking_id LIKE :search OR u.email LIKE :search OR u.full_name LIKE :search)";
        $params[':search'] = "%$search%";
    }
    
    if (count($conditions) > 0) {
        $query .= " WHERE " . implode(' AND ', $conditions);
    }
    
    $query .= " ORDER BY o.id DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(["success" => true, "data" => $orders]);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>
