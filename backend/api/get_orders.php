<?php
// backend/api/get_orders.php
// Get all orders for a user with order items
// Method: GET — ?user_id=X

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

$user_id = isset($_GET['user_id']) ? htmlspecialchars(strip_tags($_GET['user_id'])) : null;

if (!$user_id) {
    http_response_code(400);
    echo json_encode(array("message" => "user_id is required."));
    exit();
}

$query = "SELECT o.*, a.full_name as address_name, a.city as address_city, a.state as address_state, a.pincode as address_pincode
          FROM orders o
          LEFT JOIN user_addresses a ON o.address_id = a.id
          WHERE o.user_id = :user_id
          ORDER BY o.created_at DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();

$orders = array();

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    // Fetch items for this order
    $itemQuery = "SELECT oi.*, p.name, p.image_url FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = :order_id";
    $itemStmt = $db->prepare($itemQuery);
    $itemStmt->bindParam(':order_id', $row['id']);
    $itemStmt->execute();
    $items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

    $orders[] = array(
        "id" => (int) $row['id'],
        "tracking_id" => $row['tracking_id'],
        "total_amount" => (float) $row['total_amount'],
        "status" => $row['status'],
        "payment_method" => $row['payment_method'],
        "payment_status" => $row['payment_status'],
        "estimated_delivery" => $row['estimated_delivery'],
        "created_at" => $row['created_at'],
        "address" => $row['address_name'] ? $row['address_name'] . ', ' . $row['address_city'] . ', ' . $row['address_state'] . ' - ' . $row['address_pincode'] : 'N/A',
        "items" => $items
    );
}

http_response_code(200);
echo json_encode(array("orders" => $orders, "total" => count($orders)));
?>
