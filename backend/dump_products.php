<?php
$pdo = new PDO('mysql:host=localhost;dbname=ecommerce_db', 'root', '');
$stmt = $pdo->query('SELECT id, name, image_url FROM products');
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($rows, JSON_PRETTY_PRINT);
