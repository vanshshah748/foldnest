<?php
include_once __DIR__ . '/backend/config/database.php';
$db = (new Database())->getConnection();
$stmt = $db->query("SHOW TABLES");
print_r($stmt->fetchAll(PDO::FETCH_COLUMN));
?>
