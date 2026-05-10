<?php
// backend/admin/dashboard_stats.php
// Returns dashboard statistics for admin panel
// Protected by admin auth middleware

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

try {
    // Total Users
    $stmt = $db->query("SELECT COUNT(*) as total FROM users");
    $totalUsers = $stmt->fetch()['total'];

    // Total Orders
    $stmt = $db->query("SELECT COUNT(*) as total FROM orders");
    $totalOrders = $stmt->fetch()['total'];

    // Total Revenue
    $stmt = $db->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE status != 'cancelled'");
    $totalRevenue = $stmt->fetch()['total'];

    // Total Products
    $stmt = $db->query("SELECT COUNT(*) as total FROM products");
    $totalProducts = $stmt->fetch()['total'];

    // Orders Today
    $stmt = $db->query("SELECT COUNT(*) as total FROM orders WHERE DATE(created_at) = CURDATE()");
    $ordersToday = $stmt->fetch()['total'];

    // Revenue This Month
    $stmt = $db->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE()) AND status != 'cancelled'");
    $revenueThisMonth = $stmt->fetch()['total'];

    // Recent 5 Orders
    $stmt = $db->query("SELECT o.id, o.tracking_id, o.total_amount, o.status, o.created_at, u.full_name as customer FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT 5");
    $recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Monthly Revenue (last 6 months)
    $stmt = $db->query("SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COALESCE(SUM(total_amount), 0) as revenue, COUNT(*) as orders FROM orders WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) AND status != 'cancelled' GROUP BY DATE_FORMAT(created_at, '%Y-%m') ORDER BY month ASC");
    $monthlyData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Order Status Distribution
    $stmt = $db->query("SELECT status, COUNT(*) as count FROM orders GROUP BY status");
    $statusDist = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Top Selling Products
    $stmt = $db->query("SELECT p.name, SUM(oi.quantity) as total_sold, SUM(oi.quantity * oi.price_at_purchase) as revenue FROM order_items oi JOIN products p ON oi.product_id = p.id GROUP BY oi.product_id ORDER BY total_sold DESC LIMIT 5");
    $topProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode(array(
        "stats" => array(
            "total_users" => (int) $totalUsers,
            "total_orders" => (int) $totalOrders,
            "total_revenue" => (float) $totalRevenue,
            "total_products" => (int) $totalProducts,
            "orders_today" => (int) $ordersToday,
            "revenue_this_month" => (float) $revenueThisMonth
        ),
        "recent_orders" => $recentOrders,
        "monthly_data" => $monthlyData,
        "status_distribution" => $statusDist,
        "top_products" => $topProducts
    ));
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array("message" => "Error fetching stats.", "error" => $e->getMessage()));
}
?>
