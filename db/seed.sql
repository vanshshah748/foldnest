USE ecommerce_db;

-- Seed Categories
INSERT INTO categories (id, name, description) VALUES
(1, 'Electronics', 'Gadgets, devices, and accessories'),
(2, 'Clothing', 'Men and Women fashion clothing'),
(3, 'Home & Kitchen', 'Appliances and home decor'),
(4, 'Books', 'Fiction, non-fiction, and educational books')
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- Seed Products
INSERT INTO products (id, category_id, name, description, price, stock_quantity, image_url) VALUES
(1, 1, 'Smartphone X', 'Latest smartphone with high-end camera', 699.99, 50, 'https://placehold.co/400?text=Smartphone'),
(2, 1, 'Wireless Earbuds', 'Noise cancelling bluetooth earbuds', 129.99, 100, 'https://placehold.co/400?text=Earbuds'),
(3, 2, 'Cotton T-Shirt', 'Comfortable 100% cotton t-shirt', 19.99, 200, 'https://placehold.co/400?text=T-Shirt'),
(4, 2, 'Denim Jeans', 'Classic blue denim jeans', 49.99, 150, 'https://placehold.co/400?text=Jeans'),
(5, 3, 'Coffee Maker', 'Drip coffee maker with timer', 89.99, 30, 'https://placehold.co/400?text=Coffee+Maker'),
(6, 4, 'Programming in PHP', 'A comprehensive guide to PHP development', 29.99, 75, 'https://placehold.co/400?text=PHP+Book')
ON DUPLICATE KEY UPDATE name=VALUES(name);
