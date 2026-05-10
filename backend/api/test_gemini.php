<?php
$env_path = realpath(__DIR__ . '/../../.env');
$env = parse_ini_file($env_path);
$key = $env['GEMINI_API_KEY'];
$url = 'https://generativelanguage.googleapis.com/v1beta/models?key=' . $key;
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "HTTP: $http_code\nResponse: $response\n";
?>
