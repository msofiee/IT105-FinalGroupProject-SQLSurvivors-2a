<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit('Unauthorized');
}
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_id = $_POST['customer_id'];
    $product_id = $_POST['product_id'];
    $quantity = $_POST['quantity'];

    try {
        $pdo->beginTransaction();

        // Get product price and current stock
        $stmt = $pdo->prepare("SELECT unit_price, current_stock FROM products WHERE product_id = ? FOR UPDATE");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();

        if (!$product) throw new Exception("Product not found");
        if ($product['current_stock'] < $quantity) throw new Exception("Insufficient stock");

        $total_amount = $product['unit_price'] * $quantity;

        // Insert sale
        $stmt = $pdo->prepare("INSERT INTO sales (customer_id, sale_date, total_amount) VALUES (?, NOW(), ?)");
        $stmt->execute([$customer_id, $total_amount]);

        // Update product stock
        $stmt = $pdo->prepare("UPDATE products SET current_stock = current_stock - ? WHERE product_id = ?");
        $stmt->execute([$quantity, $product_id]);

        $pdo->commit();
        header('Location: ../frontend/dashboard.php?msg=sale_created');
    } catch (Exception $e) {
        $pdo->rollBack();
        header('Location: ../frontend/dashboard.php?error=' . urlencode($e->getMessage()));
    }
    exit;
}
?>