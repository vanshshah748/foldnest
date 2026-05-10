<?php
// backend/api/chatbot.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$data = json_decode(file_get_contents("php://input"));

if (empty($data->message)) {
    http_response_code(400);
    echo json_encode(["reply" => "Please say something!"]);
    exit();
}

$user_message = trim(htmlspecialchars(strip_tags($data->message)));

// Fetch Gemini API Key from .env
$env_path = realpath(__DIR__ . '/../../.env');
$gemini_key = '';
if (file_exists($env_path)) {
    $env = parse_ini_file($env_path);
    if (isset($env['GEMINI_API_KEY'])) {
        $gemini_key = trim($env['GEMINI_API_KEY'], '"\'');
    }
}

// Fallback logic
function fallbackReply($msg) {
    $msg = strtolower($msg);
    if (strpos($msg, 'order') !== false || strpos($msg, 'track') !== false)
        return "You can track your order using the Orders tab in the navigation menu!";
    if (strpos($msg, 'return') !== false)
        return "We offer a 7-day hassle-free return policy if the product is damaged or defective.";
    if (strpos($msg, 'hello') !== false || strpos($msg, 'hi') !== false)
        return "Hi there! Welcome to FoldNest Furniture. How can I help you today?";
    if (strpos($msg, 'price') !== false || strpos($msg, 'cost') !== false || strpos($msg, 'expensive') !== false)
        return "Our foldable furniture ranges from Rs.1,200 for chairs to premium beds up to Rs.35,000. Check our Products page!";
    return "I am your FoldNest AI assistant. For specific queries, please contact our support team via the Contact page!";
}

if (empty($gemini_key)) {
    echo json_encode(["reply" => fallbackReply($user_message)]);
    exit();
}

// Fetch active products from database for context
include_once '../config/database.php';
$product_context = "";
try {
    $database = new Database();
    $db = $database->getConnection();
    $stmt = $db->prepare("SELECT name, price, stock_quantity, category FROM products WHERE status = 'active' ORDER BY price DESC LIMIT 50");
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($products) {
        $product_context = "\n\nAvailable Products:\n";
        foreach ($products as $p) {
            $stock = $p['stock_quantity'] > 0 ? "In Stock" : "Out of Stock";
            $product_context .= "- {$p['name']} ({$p['category']}) : Rs.{$p['price']} [{$stock}]\n";
        }
    }
} catch (Exception $e) {
    // Ignore DB error
}

// Build the full prompt (embed system context into the user message for compatibility)
$full_prompt = "You are a friendly, expert customer support assistant for 'FoldNest Furniture', "
    . "a premium e-commerce site selling foldable, space-saving furniture in India. "
    . "Keep answers concise (1-3 sentences), conversational, and helpful. "
    . "If asked about orders, ask for Order ID and direct them to the Track Order page. "
    . "Return policy: 7 days hassle-free. Do NOT use markdown or asterisks."
    . $product_context
    . "\n\nCustomer question: " . $user_message;

$url = 'https://generativelanguage.googleapis.com/v1/models/gemini-2.5-flash:generateContent?key=' . $gemini_key;

$payload = [
    "contents" => [
        [
            "role" => "user",
            "parts" => [["text" => $full_prompt]]
        ]
    ],
    "generationConfig" => [
        "temperature" => 0.6,
        "maxOutputTokens" => 200
    ]
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 20);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code === 200 && $response) {
    $result = json_decode($response, true);
    if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
        $reply = $result['candidates'][0]['content']['parts'][0]['text'];
        // Strip any leftover markdown
        $reply = str_replace(['**', '*', '##', '#'], '', $reply);
        echo json_encode(["reply" => trim($reply)]);
    } else {
        echo json_encode(["reply" => fallbackReply($user_message)]);
    }
} else {
    error_log("[Chatbot] Gemini API error: HTTP $http_code | Response: " . substr($response, 0, 200));
    echo json_encode(["reply" => fallbackReply($user_message)]);
}
?>
