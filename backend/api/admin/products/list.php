<?php
// backend/api/admin/products/list.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");

include_once '../../../config/database.php';

$database = new Database();
$db = $database->getConnection();

try {
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $status = isset($_GET['status']) ? $_GET['status'] : 'active'; // active, deleted, all
    
    $query = "SELECT p.id, p.name, p.price, p.stock_quantity as stock, p.image_url as image, p.is_active, p.deleted_at, c.name as category_name 
              FROM products p 
              LEFT JOIN categories c ON p.category_id = c.id ";
              
    $conditions = [];
    $params = [];
    
    if ($status === 'active') {
        $conditions[] = "p.deleted_at IS NULL";
    } elseif ($status === 'deleted') {
        $conditions[] = "p.deleted_at IS NOT NULL";
    }
    
    if (!empty($search)) {
        $conditions[] = "(p.name LIKE :search OR p.id LIKE :search)";
        $params[':search'] = "%$search%";
    }
    
    if (count($conditions) > 0) {
        $query .= " WHERE " . implode(' AND ', $conditions);
    }
    
    $query .= " ORDER BY p.id DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(["success" => true, "data" => $products]);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
