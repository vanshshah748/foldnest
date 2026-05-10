<?php
// Test Registration API
echo "Testing Registration API...\n";
$regData = json_encode([
    'full_name' => 'Email Integration Tester',
    'email' => 'integration_test_' . time() . '@example.com',
    'password' => 'password123'
]);

$ch = curl_init('http://localhost/foldnest/backend/api/auth_register.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $regData);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$regResponse = curl_exec($ch);
$regCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Status Code: $regCode\n";
echo "Response: $regResponse\n\n";

// To test place_order.php, we need a user ID and a cart item.
echo "Setting up Order Test...\n";
include_once __DIR__ . '/config/database.php';
$db = (new Database())->getConnection();

// Get the user ID we just created
$stmt = $db->query("SELECT id FROM users ORDER BY id DESC LIMIT 1");
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$userId = $user['id'];

// Add a dummy item to cart
$db->exec("INSERT INTO cart_items (user_id, product_id, quantity) VALUES ($userId, 1, 1)");
echo "Added item to cart for user $userId.\n";

// Test Place Order API
echo "Testing Place Order API...\n";
$orderData = json_encode([
    'user_id' => $userId,
    'payment_method' => 'COD'
]);

$ch2 = curl_init('http://localhost/foldnest/backend/api/place_order.php');
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_POST, true);
curl_setopt($ch2, CURLOPT_POSTFIELDS, $orderData);
curl_setopt($ch2, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$orderResponse = curl_exec($ch2);
$orderCode = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
curl_close($ch2);

echo "Status Code: $orderCode\n";
echo "Response: $orderResponse\n\n";

echo "Check PHP error logs to verify that the 'SMTP not configured' message fired successfully without crashing!\n";
?>
