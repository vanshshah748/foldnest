<?php
// backend/api/chat.php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
// Include configs and database
include_once '../config/database.php';
include_once '../config/ai_config.php';

// Check if API key is configured
if (AIConfig::$GEMINI_API_KEY === "YOUR_GEMINI_API_KEY_HERE" || empty(AIConfig::$GEMINI_API_KEY)) {
    http_response_code(500);
    echo json_encode(array("message" => "Server Error: AI API Key is not configured."));
    exit();
}

// Get posted data
$data = json_decode(file_get_contents("php://input"));

if (!empty($data->message)) {
    $user_message = htmlspecialchars(strip_tags($data->message));

    // Construct the payload for Gemini API
    $api_url = "https://generativelanguage.googleapis.com/v1beta/models/" . AIConfig::$MODEL . ":generateContent?key=" . AIConfig::$GEMINI_API_KEY;

    // We inject a system context to guide the AI to act as an e-commerce assistant
    $system_context = "You are a helpful, friendly, and knowledgeable AI assistant for an e-commerce store called 'Final Year Project Store'. You help users find products, understand return policies, and navigate the store. Keep your answers concise and professional. The user says: ";

    $payload = array(
        "contents" => array(
            array(
                "parts" => array(
                    array("text" => $system_context . $user_message)
                )
            )
        )
    );

    $json_payload = json_encode($payload);

    // Initialize cURL
    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json_payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json'
    ));
    
    // Disable SSL verification for local WAMP testing, ideally remove in production
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code == 200) {
        $response_data = json_decode($response, true);
        
        // Extract the generated text from Gemini's response structure
        $ai_reply = "I'm sorry, I couldn't process that.";
        if (isset($response_data['candidates'][0]['content']['parts'][0]['text'])) {
            $ai_reply = $response_data['candidates'][0]['content']['parts'][0]['text'];
        }

        http_response_code(200);
        echo json_encode(array(
            "message" => "Success",
            "reply" => $ai_reply
        ));
    } else {
        http_response_code(503);
        echo json_encode(array("message" => "Error communicating with AI service.", "details" => $response));
    }
} else {
    http_response_code(400);
    echo json_encode(array("message" => "Unable to chat. Message is empty."));
}
?>
