<?php
// backend/api/admin/users/list.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");

include_once '../../../config/database.php';

$database = new Database();
$db = $database->getConnection();

try {
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $status = isset($_GET['status']) && $_GET['status'] !== 'all' ? $_GET['status'] : '';
    
    // We only want to list customers, not admins, though our role field handles that
    $query = "SELECT u.id, u.full_name, u.email, u.phone, u.is_active, 
                     DATE_FORMAT(u.created_at, '%d %b %Y') as joined,
                     COUNT(o.id) as total_orders, 
                     COALESCE(SUM(o.total_amount), 0) as total_spent
              FROM users u
              LEFT JOIN orders o ON u.id = o.user_id AND o.status != 'cancelled'
              WHERE u.role = 'customer'";
              
    $conditions = [];
    $params = [];
    
    if ($status === 'active') {
        $conditions[] = "u.is_active = 1";
    } elseif ($status === 'blocked') {
        $conditions[] = "u.is_active = 0";
    }
    
    if (!empty($search)) {
        $conditions[] = "(u.full_name LIKE :search OR u.email LIKE :search OR u.phone LIKE :search)";
        $params[':search'] = "%$search%";
    }
    
    if (count($conditions) > 0) {
        $query .= " AND " . implode(' AND ', $conditions);
    }
    
    $query .= " GROUP BY u.id ORDER BY u.created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(["success" => true, "data" => $users]);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
