<?php
include_once __DIR__ . '/backend/config/database.php';
$db = (new Database())->getConnection();
$stmt = $db->query("DESCRIBE admins");
$cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($cols);
?>
