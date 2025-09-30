<?php
require '../config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST["name"];
    $description = $_POST["description"];
    $price = $_POST["price"];
    $category = $_POST["category"];
    $image_url = $_POST["image_url"];
    
    $sql = "INSERT INTO products (name, description, price, category, image_url) 
            VALUES ('$name', '$description', $price, '$category', '$image_url')";
    
        try {
            $stmt = $pdo->query($sql);
            header("Location: /admin.php");
            exit();
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
}

if ($_SERVER['REQUEST_METHOD'] == 'DELETE') {
    // URL에서 ID 추출
    $path = $_SERVER['REQUEST_URI']; 
    // 예: "/commerce/product.php/123"
    $parts = explode('/', $path);
    $delete_id = end($parts);

    if (is_numeric($delete_id)) {
        try {
            $sql = "DELETE FROM products WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id' => $delete_id]);
            echo "상품이 성공적으로 삭제되었습니다.";
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    } else {
        echo "잘못된 ID 입니다.";
    }
}