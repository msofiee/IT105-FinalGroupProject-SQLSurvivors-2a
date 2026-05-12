<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit('Unauthorized');
}
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sku = $_POST['sku'];
    $product_name = $_POST['product_name'];
    $category_id = $_POST['category_id'];
    $supplier_id = $_POST['supplier_id'];
    $unit_price = $_POST['unit_price'];
    $current_stock = $_POST['current_stock'];

    $stmt = $pdo->prepare("INSERT INTO products (sku, product_name, category_id, supplier_id, unit_price, current_stock) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$sku, $product_name, $category_id, $supplier_id, $unit_price, $current_stock]);

    header('Location: ../frontend/dashboard.php?msg=inserted');
    exit;
}
?>