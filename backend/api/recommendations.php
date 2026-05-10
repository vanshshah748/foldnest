<?php
// backend/api/recommendations.php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/database.php';
include_once '../config/ai_config.php';

$database = new Database();
$db = $database->getConnection();

$target_product_id = isset($_GET['product_id']) ? $_GET['product_id'] : die(json_encode(["message" => "Please provide a product_id."]));

if (empty(AIConfig::$GEMINI_API_KEY)) {
    http_response_code(500);
    echo json_encode(array("message" => "Server Error: AI API Key is not configured."));
    exit();
}

// 1. Fetch all products to give Gemini the catalog context
$query = "SELECT p.id, p.name, c.name as category, p.description, p.price, p.image_url 
          FROM products p
          LEFT JOIN categories c ON p.category_id = c.id";
$stmt = $db->prepare($query);
$stmt->execute();

$catalog = array();
$target_product = null;

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $catalog[] = array(
        "id" => $row['id'],
        "name" => $row['name'],
        "category" => $row['category'],
        "description" => html_entity_decode($row['description'])
    );
    if ($row['id'] == $target_product_id) {
        $target_product = $row;
    }
}

if (!$target_product) {
    http_response_code(404);
    echo json_encode(array("message" => "Product not found."));
    exit();
}

// 2. Build the AI Prompt
$prompt = "You are a precise recommendation engine for an e-commerce store. 
Here is the entire product catalog in JSON format:\n" . json_encode($catalog) . "\n\n
The user is currently viewing the following product:\n" . json_encode($target_product) . "\n\n
Task: Recommend exactly 3 other products from the catalog that are highly related, complementary, or similar to the one the user is viewing. Do not recommend the product the user is currently viewing. 
IMPORTANT: Reply ONLY with a raw JSON array of the 3 recommended integer product IDs. Do not include markdown formatting, backticks, or explanations. Example: [2, 5, 8]";

$payload = array(
    "contents" => array(
        array(
            "parts" => array(
                array("text" => $prompt)
            )
        )
    )
);

// 3. Call Gemini API
$api_url = "https://generativelanguage.googleapis.com/v1beta/models/" . AIConfig::$MODEL . ":generateContent?key=" . AIConfig::$GEMINI_API_KEY;

$ch = curl_init($api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code == 200) {
    $response_data = json_decode($response, true);
    $ai_reply = "";
    
    if (isset($response_data['candidates'][0]['content']['parts'][0]['text'])) {
        $ai_reply = trim($response_data['candidates'][0]['content']['parts'][0]['text']);
        // Strip out markdown code blocks if the AI accidentally included them
        $ai_reply = str_replace(['```json', '```'], '', $ai_reply);
        $ai_reply = trim($ai_reply);
    }

    $recommended_ids = json_decode($ai_reply, true);

    if (is_array($recommended_ids) && count($recommended_ids) > 0) {
        // 4. Fetch the full product details for the recommended IDs
        $in_clause = implode(',', array_map('intval', $recommended_ids));
        
        $recQuery = "SELECT p.id, p.name, p.description, p.price, p.stock_quantity, p.image_url, c.name as category_name 
                     FROM products p
                     LEFT JOIN categories c ON p.category_id = c.id
                     WHERE p.id IN ($in_clause)";
        $recStmt = $db->prepare($recQuery);
        $recStmt->execute();
        
        $recommendations = array();
        while ($recRow = $recStmt->fetch(PDO::FETCH_ASSOC)) {
            $recommendations[] = $recRow;
        }

        http_response_code(200);
        echo json_encode(array(
            "message" => "Recommendations generated successfully by AI.",
            "recommendations" => $recommendations
        ));
    } else {
        http_response_code(500);
        echo json_encode(array("message" => "AI returned invalid format.", "raw_ai_reply" => $ai_reply));
    }

} else {
    http_response_code(503);
    echo json_encode(array("message" => "Error communicating with AI service.", "details" => $response));
}
?>
