<?php
// backend/admin/get_all_orders.php
// Get all orders for admin panel with filters
// GET: ?status=X&search=tracking_id

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

$status = isset($_GET['status']) ? htmlspecialchars(strip_tags($_GET['status'])) : null;
$search = isset($_GET['search']) ? htmlspecialchars(strip_tags($_GET['search'])) : null;

$query = "SELECT o.*, u.full_name as customer_name, u.email as customer_email 
          FROM orders o JOIN users u ON o.user_id = u.id WHERE 1=1";
$params = [];

if ($status && $status !== 'all') {
    $query .= " AND o.status = :status";
    $params[':status'] = $status;
}
if ($search) {
    $query .= " AND (o.tracking_id LIKE :search OR u.full_name LIKE :search2)";
    $params[':search'] = "%$search%";
    $params[':search2'] = "%$search%";
}

$query .= " ORDER BY o.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute($params);

$orders = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    // Get items for each order
    $itemStmt = $db->prepare("SELECT oi.*, p.name, p.image_url FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = :oid");
    $itemStmt->execute([':oid' => $row['id']]);
    $row['items'] = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
    $row['total_amount'] = (float) $row['total_amount'];
    $orders[] = $row;
}

http_response_code(200);
echo json_encode(array("orders" => $orders, "total" => count($orders)));
?>
