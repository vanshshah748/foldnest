<?php
// backend/api/place_order.php
// UPGRADED: Now generates tracking ID, accepts address_id and payment_method,
// sets initial status to 'pending', calculates estimated delivery

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once '../config/database.php';
include_once '../services/email_service.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->user_id)) {
    $user_id = htmlspecialchars(strip_tags($data->user_id));
    $address_id = !empty($data->address_id) ? htmlspecialchars(strip_tags($data->address_id)) : null;
    $payment_method = !empty($data->payment_method) ? htmlspecialchars(strip_tags($data->payment_method)) : 'COD';

    // 1. Fetch Cart Items and calculate total
    $cartQuery = "SELECT c.quantity, p.id as product_id, p.name, p.price, p.stock_quantity 
                  FROM cart_items c
                  JOIN products p ON c.product_id = p.id
                  WHERE c.user_id = :user_id";
    $cartStmt = $db->prepare($cartQuery);
    $cartStmt->bindParam(":user_id", $user_id);
    $cartStmt->execute();

    if ($cartStmt->rowCount() == 0) {
        http_response_code(400);
        echo json_encode(array("message" => "Cart is empty."));
        exit();
    }

    $cartItems = $cartStmt->fetchAll(PDO::FETCH_ASSOC);
    $total_amount = 0;

    // Check stock
    foreach ($cartItems as $item) {
        if ($item['quantity'] > $item['stock_quantity']) {
            http_response_code(400);
            echo json_encode(array("message" => "Insufficient stock for product ID: " . $item['product_id']));
            exit();
        }
        $total_amount += ($item['price'] * $item['quantity']);
    }

    try {
        $db->beginTransaction();

        // 2. Generate unique Tracking ID: TRK + YYYYMMDD + 4 random digits
        $tracking_id = 'TRK' . date('Ymd') . str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);

        // Ensure tracking ID is unique
        $checkTrk = $db->prepare("SELECT id FROM orders WHERE tracking_id = :tid");
        $checkTrk->bindParam(':tid', $tracking_id);
        $checkTrk->execute();
        while ($checkTrk->rowCount() > 0) {
            $tracking_id = 'TRK' . date('Ymd') . str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
            $checkTrk->execute();
        }

        // 3. Calculate estimated delivery (7 days from now)
        $estimated_delivery = date('Y-m-d', strtotime('+7 days'));

        // 4. Determine payment status
        $payment_status = ($payment_method === 'COD') ? 'pending' : 'paid';

        // 5. Create Order with tracking
        $orderQuery = "INSERT INTO orders (tracking_id, user_id, address_id, total_amount, payment_method, payment_status, estimated_delivery, status) 
                       VALUES (:tracking_id, :user_id, :address_id, :total_amount, :payment_method, :payment_status, :estimated_delivery, 'pending')";
        $orderStmt = $db->prepare($orderQuery);
        $orderStmt->bindParam(":tracking_id", $tracking_id);
        $orderStmt->bindParam(":user_id", $user_id);
        $orderStmt->bindParam(":address_id", $address_id);
        $orderStmt->bindParam(":total_amount", $total_amount);
        $orderStmt->bindParam(":payment_method", $payment_method);
        $orderStmt->bindParam(":payment_status", $payment_status);
        $orderStmt->bindParam(":estimated_delivery", $estimated_delivery);
        $orderStmt->execute();
        
        $order_id = $db->lastInsertId();

        // 6. Insert Order Items & Update Stock
        foreach ($cartItems as $item) {
            $itemQuery = "INSERT INTO order_items (order_id, product_id, quantity, price_at_purchase) 
                          VALUES (:order_id, :product_id, :quantity, :price_at_purchase)";
            $itemStmt = $db->prepare($itemQuery);
            $itemStmt->bindParam(":order_id", $order_id);
            $itemStmt->bindParam(":product_id", $item['product_id']);
            $itemStmt->bindParam(":quantity", $item['quantity']);
            $itemStmt->bindParam(":price_at_purchase", $item['price']);
            $itemStmt->execute();

            $stockQuery = "UPDATE products SET stock_quantity = stock_quantity - :quantity WHERE id = :product_id";
            $stockStmt = $db->prepare($stockQuery);
            $stockStmt->bindParam(":quantity", $item['quantity']);
            $stockStmt->bindParam(":product_id", $item['product_id']);
            $stockStmt->execute();
        }

        // 7. Clear Cart
        $clearCartQuery = "DELETE FROM cart_items WHERE user_id = :user_id";
        $clearCartStmt = $db->prepare($clearCartQuery);
        $clearCartStmt->bindParam(":user_id", $user_id);
        $clearCartStmt->execute();

        $db->commit();

        // 8. Send Order Confirmation Email
        try {
            // Fetch User Details
            $userStmt = $db->prepare("SELECT full_name, email FROM users WHERE id = :uid");
            $userStmt->execute([':uid' => $user_id]);
            $user = $userStmt->fetch(PDO::FETCH_ASSOC);

            // Fetch Address Details
            $addressText = "Pickup / Default Address";
            if ($address_id) {
                $addrStmt = $db->prepare("SELECT address_line1, address_line2, city, state, postal_code FROM user_addresses WHERE id = :aid");
                $addrStmt->execute([':aid' => $address_id]);
                $addr = $addrStmt->fetch(PDO::FETCH_ASSOC);
                if ($addr) {
                    $addressText = "{$addr['address_line1']}<br>";
                    if (!empty($addr['address_line2'])) $addressText .= "{$addr['address_line2']}<br>";
                    $addressText .= "{$addr['city']}, {$addr['state']} - {$addr['postal_code']}";
                }
            }

            // Format items for EmailService
            $emailItems = [];
            foreach ($cartItems as $item) {
                $emailItems[] = [
                    'name' => $item['name'],
                    'quantity' => $item['quantity'],
                    'price_at_purchase' => $item['price']
                ];
            }

            if ($user && $user['email']) {
                EmailService::sendOrderConfirmation(
                    $user['email'], 
                    $user['full_name'], 
                    $order_id, 
                    $tracking_id, 
                    $emailItems, 
                    $total_amount, 
                    $addressText, 
                    $estimated_delivery
                );
            }
        } catch (Exception $emailEx) {
            // Log but don't fail the order if email fails
            error_log("Failed to send order confirmation email: " . $emailEx->getMessage());
        }

        http_response_code(201);
        echo json_encode(array(
            "message" => "Order placed successfully.",
            "order_id" => $order_id,
            "tracking_id" => $tracking_id,
            "estimated_delivery" => $estimated_delivery,
            "total_amount" => $total_amount,
            "payment_status" => $payment_status
        ));

    } catch (Exception $e) {
        $db->rollBack();
        http_response_code(503);
        echo json_encode(array("message" => "Unable to place order.", "error" => $e->getMessage()));
    }
} else {
    http_response_code(400);
    echo json_encode(array("message" => "Unable to place order. User ID is required."));
}
?>
