<?php
// backend/api/admin/products/save.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include_once '../../../config/database.php';

$database = new Database();
$db = $database->getConnection();
$data = json_decode(file_get_contents("php://input"));

if (empty($data->name) || empty($data->price) || !isset($data->stock) || empty($data->image_url)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Missing required fields."]);
    exit();
}

try {
    $id = isset($data->id) ? intval($data->id) : 0;
    
    $name = htmlspecialchars(strip_tags($data->name));
    $slug = htmlspecialchars(strip_tags($data->slug ?? ''));
    $price = floatval($data->price);
    $old_price = floatval($data->old_price ?? 0);
    $stock = intval($data->stock);
    $category_id = !empty($data->category_id) ? intval($data->category_id) : null;
    $image_url = htmlspecialchars(strip_tags($data->image_url));
    $desc = htmlspecialchars(strip_tags($data->description ?? ''));
    $is_active = isset($data->is_active) && $data->is_active ? 1 : 0;
    $is_featured = isset($data->is_featured) && $data->is_featured ? 1 : 0;

    if ($id > 0) {
        // UPDATE
        $query = "UPDATE products SET 
            name = :name, slug = :slug, price = :price, old_price = :old_price, 
            stock_quantity = :stock, category_id = :cat, image_url = :img, 
            description = :desc, is_active = :active, is_featured = :feat 
            WHERE id = :id";
            
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id);
    } else {
        // INSERT
        $query = "INSERT INTO products 
            (name, slug, price, old_price, stock_quantity, category_id, image_url, description, is_active, is_featured) 
            VALUES (:name, :slug, :price, :old_price, :stock, :cat, :img, :desc, :active, :feat)";
            
        $stmt = $db->prepare($query);
    }
    
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':slug', $slug);
    $stmt->bindParam(':price', $price);
    $stmt->bindParam(':old_price', $old_price);
    $stmt->bindParam(':stock', $stock);
    $stmt->bindParam(':cat', $category_id);
    $stmt->bindParam(':img', $image_url);
    $stmt->bindParam(':desc', $desc);
    $stmt->bindParam(':active', $is_active);
    $stmt->bindParam(':feat', $is_featured);
    
    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Product saved successfully."]);
    } else {
        echo json_encode(["success" => false, "message" => "Database error."]);
    }
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
