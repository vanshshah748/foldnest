<?php
// backend/seed_products.php
// Seeds the database with FoldNest sample products
header("Content-Type: text/plain");

include_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    die("Database connection failed.\n");
}

echo "Seeding FoldNest Products...\n";

// 1. Categories
$categories = [
    [1, 'Living Room', 'Foldable sofas, tables, and chairs for living spaces'],
    [2, 'Bedroom', 'Space-saving beds and wardrobes'],
    [3, 'Study & Office', 'Foldable desks and ergonomic chairs']
];

$stmt = $db->prepare("INSERT INTO categories (id, name, description) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE name=VALUES(name)");
foreach ($categories as $cat) {
    $stmt->execute($cat);
}
echo "Categories seeded.\n";

// 2. Products
$products = [
    [
        1, 1, 'Foldable Sofa Bed', 'Premium fabric foldable sofa that converts into a comfortable bed for guests. Perfect for compact apartments.',
        12999.00, 15999.00, 4.8, 25, 'assets/images/sofa.jpg'
    ],
    [
        2, 3, 'Smart Wall-Mount Desk', 'Minimalist wooden desk that folds completely flat against the wall. Features hidden storage compartments.',
        4500.00, 5999.00, 4.5, 40, 'assets/images/desk.jpg'
    ],
    [
        3, 1, 'Compact Dining Set', 'A dining table with 4 hidden chairs that tuck neatly underneath. Solid oak finish.',
        18500.00, 22000.00, 4.9, 15, 'assets/images/table.jpg'
    ],
    [
        4, 2, 'Murphy Wall Bed', 'Queen size fold-down bed with hydraulic lift mechanism. Blends seamlessly into your wall cabinet.',
        35000.00, 42000.00, 4.7, 10, 'assets/images/desk.2.jpg'
    ],
    [
        5, 3, 'Portable Folding Chair', 'Lightweight yet sturdy folding chair perfect for extra guests or outdoor use. Premium padding.',
        1200.00, 1800.00, 4.2, 100, 'assets/images/chair.jpg'
    ],
    [
        6, 1, 'Nesting Coffee Tables', 'Set of 3 stackable coffee tables. Space saving design with industrial metal frames and wood tops.',
        3800.00, 4500.00, 4.6, 30, 'assets/images/chair.2.jpg'
    ]
];

$stmt = $db->prepare("INSERT INTO products (id, category_id, name, description, price, old_price, rating, stock_quantity, image_url) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?) 
                      ON DUPLICATE KEY UPDATE name=VALUES(name), price=VALUES(price), image_url=VALUES(image_url)");
                      
foreach ($products as $prod) {
    $stmt->execute($prod);
}

echo "Products seeded successfully!\n";
?>
