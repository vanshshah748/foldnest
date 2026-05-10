<?php
include_once __DIR__ . '/backend/config/database.php';
$db = (new Database())->getConnection();
$stmt = $db->query("DESCRIBE order_items");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
