<?php
// backend/api/admin/queries/list.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");

include_once '../../../config/database.php';

$database = new Database();
$db = $database->getConnection();

try {
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $status = isset($_GET['status']) && $_GET['status'] !== 'all' ? $_GET['status'] : '';
    
    $query = "SELECT id, name, email, phone, subject, message, status, 
                     DATE_FORMAT(created_at, '%d %b %Y, %h:%i %p') as date 
              FROM contact_queries";
              
    $conditions = [];
    $params = [];
    
    if (!empty($status)) {
        $conditions[] = "status = :status";
        $params[':status'] = $status;
    }
    
    if (!empty($search)) {
        $conditions[] = "(name LIKE :search OR email LIKE :search OR subject LIKE :search)";
        $params[':search'] = "%$search%";
    }
    
    if (count($conditions) > 0) {
        $query .= " WHERE " . implode(' AND ', $conditions);
    }
    
    $query .= " ORDER BY created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $queries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(["success" => true, "data" => $queries]);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>
