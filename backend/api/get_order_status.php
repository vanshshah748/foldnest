<?php
// backend/api/get_order_status.php
// Get order status with full timeline data
// Method: GET — ?order_id=X&user_id=Y

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

$order_id = isset($_GET['order_id']) ? htmlspecialchars(strip_tags($_GET['order_id'])) : null;
$user_id = isset($_GET['user_id']) ? htmlspecialchars(strip_tags($_GET['user_id'])) : null;

if (!$order_id || !$user_id) {
    http_response_code(400);
    echo json_encode(array("message" => "order_id and user_id are required."));
    exit();
}

$query = "SELECT o.*, a.full_name as addr_name, a.address_line_1, a.city, a.state, a.pincode, a.mobile_number as addr_phone
          FROM orders o
          LEFT JOIN user_addresses a ON o.address_id = a.id
          WHERE o.id = :order_id AND o.user_id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':order_id', $order_id);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    http_response_code(404);
    echo json_encode(array("message" => "Order not found."));
    exit();
}

$order = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch order items
$itemQuery = "SELECT oi.*, p.name, p.image_url FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = :order_id";
$itemStmt = $db->prepare($itemQuery);
$itemStmt->bindParam(':order_id', $order_id);
$itemStmt->execute();
$items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

// Build timeline data
$allStatuses = ['pending', 'confirmed', 'packed', 'shipped', 'out_for_delivery', 'delivered'];
$currentIndex = array_search($order['status'], $allStatuses);
if ($order['status'] === 'cancelled') $currentIndex = -1;

$timeline = array();
foreach ($allStatuses as $idx => $st) {
    $timeline[] = array(
        "status" => $st,
        "label" => ucwords(str_replace('_', ' ', $st)),
        "completed" => ($currentIndex !== false && $idx <= $currentIndex),
        "current" => ($idx === $currentIndex)
    );
}

http_response_code(200);
echo json_encode(array(
    "order" => array(
        "id" => (int) $order['id'],
        "tracking_id" => $order['tracking_id'],
        "status" => $order['status'],
        "total_amount" => (float) $order['total_amount'],
        "payment_method" => $order['payment_method'],
        "payment_status" => $order['payment_status'],
        "estimated_delivery" => $order['estimated_delivery'],
        "created_at" => $order['created_at']
    ),
    "address" => array(
        "name" => $order['addr_name'],
        "line1" => $order['address_line_1'],
        "city" => $order['city'],
        "state" => $order['state'],
        "pincode" => $order['pincode'],
        "phone" => $order['addr_phone']
    ),
    "items" => $items,
    "timeline" => $timeline,
    "is_cancelled" => ($order['status'] === 'cancelled')
));
?>
