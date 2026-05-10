<?php
include_once __DIR__ . '/backend/config/database.php';
$db = (new Database())->getConnection();

$query = "CREATE TABLE IF NOT EXISTS contact_queries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NULL,
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('unread', 'read', 'resolved') DEFAULT 'unread',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$db->exec($query);
echo "contact_queries table created.\n";

$stmt = $db->query("SELECT COUNT(*) FROM contact_queries");
if ($stmt->fetchColumn() == 0) {
    $db->exec("INSERT INTO contact_queries (name, email, phone, subject, message) VALUES ('John Doe', 'john@example.com', '9876543210', 'Bulk Order Inquiry', 'I would like to order 50 foldable desks for my office.')");
    echo "Dummy query inserted.";
}
?>
