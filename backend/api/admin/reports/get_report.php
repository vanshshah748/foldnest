<?php
// backend/api/admin/reports/get_report.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");

include_once '../../../config/database.php';

$database = new Database();
$db = $database->getConnection();

$type = isset($_GET['type']) ? $_GET['type'] : 'sales';

try {
    $data = [];
    $title = "";

    switch ($type) {
        case 'sales':
            $title = "Monthly Sales Revenue";
            $query = "SELECT DATE_FORMAT(created_at, '%b %Y') as label, SUM(total_amount) as value, COUNT(id) as orders_count 
                      FROM orders 
                      WHERE status != 'cancelled' 
                      GROUP BY YEAR(created_at), MONTH(created_at) 
                      ORDER BY YEAR(created_at), MONTH(created_at) LIMIT 12";
            $data = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'top_products':
            $title = "Top Selling Products";
            $query = "SELECT p.name as label, SUM(oi.quantity) as value, SUM(oi.quantity * oi.price_at_purchase) as revenue 
                      FROM order_items oi 
                      JOIN products p ON oi.product_id = p.id 
                      JOIN orders o ON oi.order_id = o.id 
                      WHERE o.status != 'cancelled' 
                      GROUP BY p.id 
                      ORDER BY value DESC LIMIT 10";
            $data = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'low_stock':
            $title = "Low Stock Products";
            $query = "SELECT name as label, stock_quantity as value, price 
                      FROM products 
                      WHERE stock_quantity < 10 AND is_active = 1 
                      ORDER BY stock_quantity ASC LIMIT 20";
            $data = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'top_customers':
            $title = "Most Active Customers";
            $query = "SELECT u.full_name as label, SUM(o.total_amount) as value, COUNT(o.id) as orders_count 
                      FROM users u 
                      JOIN orders o ON u.id = o.user_id 
                      WHERE o.status != 'cancelled' 
                      GROUP BY u.id 
                      ORDER BY value DESC LIMIT 10";
            $data = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);
            break;
            
        case 'cancellations':
            $title = "Order Cancellation Trends";
            $query = "SELECT DATE_FORMAT(created_at, '%b %Y') as label, COUNT(id) as value, SUM(total_amount) as lost_revenue 
                      FROM orders 
                      WHERE status = 'cancelled' 
                      GROUP BY YEAR(created_at), MONTH(created_at) 
                      ORDER BY YEAR(created_at), MONTH(created_at) LIMIT 12";
            $data = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);
            break;
            
        default:
            throw new Exception("Invalid report type specified.");
    }
    
    echo json_encode(["success" => true, "title" => $title, "data" => $data]);
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
