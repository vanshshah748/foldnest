<?php
// backend/api/admin/get_dashboard_stats.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");

include_once '../../config/database.php';
$database = new Database();
$db = $database->getConnection();

try {
    // 1. CARDS DATA
    $stats = [];
    
    $stats['total_users'] = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $stats['total_orders'] = $db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    $stats['pending_orders'] = $db->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();
    $stats['delivered_orders'] = $db->query("SELECT COUNT(*) FROM orders WHERE status = 'delivered'")->fetchColumn();
    $stats['cancelled_orders'] = $db->query("SELECT COUNT(*) FROM orders WHERE status = 'cancelled'")->fetchColumn();
    
    // Revenue calculations
    $stats['total_revenue'] = $db->query("SELECT IFNULL(SUM(total_amount), 0) FROM orders WHERE status != 'cancelled'")->fetchColumn();
    $stats['monthly_revenue'] = $db->query("SELECT IFNULL(SUM(total_amount), 0) FROM orders WHERE status != 'cancelled' AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())")->fetchColumn();
    
    $productsResult = $db->query("SELECT COUNT(id) as total_products, SUM(CASE WHEN stock_quantity <= 5 THEN 1 ELSE 0 END) as out_of_stock FROM products WHERE deleted_at IS NULL")->fetch(PDO::FETCH_ASSOC);
    $stats['total_products'] = $productsResult['total_products'];
    $stats['out_of_stock'] = $productsResult['out_of_stock'];
    $stats['contact_queries'] = $db->query("SELECT COUNT(*) FROM contact_queries")->fetchColumn();

    // 2. CHART DATA: Monthly Sales (Last 6 months)
    $salesQuery = "
        SELECT DATE_FORMAT(created_at, '%b') as month, SUM(total_amount) as revenue
        FROM orders
        WHERE created_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 MONTH) AND status != 'cancelled'
        GROUP BY YEAR(created_at), MONTH(created_at)
        ORDER BY YEAR(created_at), MONTH(created_at)
    ";
    $stats['sales_chart'] = $db->query($salesQuery)->fetchAll(PDO::FETCH_ASSOC);

    // 3. CHART DATA: Order Status Distribution
    $statusQuery = "SELECT status, COUNT(*) as count FROM orders GROUP BY status";
    $stats['status_chart'] = $db->query($statusQuery)->fetchAll(PDO::FETCH_ASSOC);

    // 4. RECENT ACTIVITY: Latest Orders
    $recentOrders = "SELECT id, total_amount, status, DATE_FORMAT(created_at, '%d %b %Y') as date FROM orders ORDER BY created_at DESC LIMIT 5";
    $stats['recent_orders'] = $db->query($recentOrders)->fetchAll(PDO::FETCH_ASSOC);

    // 5. RECENT ACTIVITY: Latest Users
    $recentUsers = "SELECT id, full_name as first_name, '' as last_name, email, DATE_FORMAT(created_at, '%d %b %Y') as joined FROM users ORDER BY created_at DESC LIMIT 5";
    $stats['recent_users'] = $db->query($recentUsers)->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["success" => true, "data" => $stats]);

} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>
