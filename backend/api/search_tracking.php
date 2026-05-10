<?php
// backend/api/search_tracking.php
// Search order by tracking ID (public — no auth required)
// Method: GET — ?tracking_id=TRKXXXXXXXXXX

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

$tracking_id = isset($_GET['tracking_id']) ? htmlspecialchars(strip_tags($_GET['tracking_id'])) : null;

if (!$tracking_id) {
    http_response_code(400);
    echo json_encode(array("message" => "tracking_id parameter is required."));
    exit();
}

$query = "SELECT o.id, o.tracking_id, o.status, o.total_amount, o.payment_method, o.payment_status, o.estimated_delivery, o.created_at, o.user_id,
                 u.full_name as customer_name
          FROM orders o
          JOIN users u ON o.user_id = u.id
          WHERE o.tracking_id = :tracking_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':tracking_id', $tracking_id);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    http_response_code(404);
    echo json_encode(array("message" => "No order found with this tracking ID."));
    exit();
}

$order = $stmt->fetch(PDO::FETCH_ASSOC);

// Build timeline
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
        "tracking_id" => $order['tracking_id'],
        "status" => $order['status'],
        "status_label" => ucwords(str_replace('_', ' ', $order['status'])),
        "estimated_delivery" => $order['estimated_delivery'],
        "created_at" => $order['created_at'],
        "customer_name" => $order['customer_name']
    ),
    "timeline" => $timeline,
    "is_cancelled" => ($order['status'] === 'cancelled')
));
?>
