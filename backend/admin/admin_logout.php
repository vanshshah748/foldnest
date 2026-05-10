<?php
// backend/admin/admin_logout.php
session_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

session_unset();
session_destroy();

http_response_code(200);
echo json_encode(array("message" => "Logged out successfully."));
?>
