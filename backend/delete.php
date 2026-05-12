<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit('Unauthorized');
}

require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {

    $product_id = $_POST['product_id'];

    try {
        $pdo->beginTransaction();

        /* 1. Delete child records first */
        $stmt = $pdo->prepare("
            DELETE FROM sales_items
            WHERE product_id = ?
        ");
        $stmt->execute([$product_id]);

        /* 2. Delete product */
        $stmt = $pdo->prepare("
            DELETE FROM products
            WHERE product_id = ?
        ");
        $stmt->execute([$product_id]);

        $pdo->commit();

        header('Location: ../frontend/dashboard.php?msg=deleted');
        exit;

    } catch (Exception $e) {

        $pdo->rollBack();

        header('Location: ../frontend/dashboard.php?error=delete_failed');
        exit;
    }
}
?>