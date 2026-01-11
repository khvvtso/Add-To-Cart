<?php
session_start();
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../db_project.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: view_cart.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];
$action = isset($_POST['action']) ? $_POST['action'] : '';
$productId = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;

if ($productId <= 0) {
    $_SESSION['flash'] = 'Invalid product.';
    header('Location: view_cart.php');
    exit;
}

// fetch product stock
$stmt = $pdo->prepare('SELECT product_id, stock FROM products WHERE product_id = ? LIMIT 1');
$stmt->execute([$productId]);
$prod = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$prod) {
    $_SESSION['flash'] = 'Product not found.';
    header('Location: view_cart.php');
    exit;
}

$available = isset($prod['stock']) ? (int)$prod['stock'] : 0;

try {
    if ($action === 'remove') {
        $stmt = $pdo_project->prepare('DELETE FROM user_products WHERE user_id = ? AND product_id = ?');
        $stmt->execute([$userId, $productId]);
        $_SESSION['flash'] = 'Item removed from cart.';
        header('Location: view_cart.php');
        exit;
    }

    if ($action === 'update') {
        $qty = isset($_POST['qty']) ? (int) $_POST['qty'] : 0;
        if ($qty <= 0) {
            $_SESSION['flash'] = 'Quantity must be at least 1.';
            header('Location: view_cart.php');
            exit;
        }
        if ($qty > $available) {
            $_SESSION['flash'] = 'Cannot update to requested quantity. Only ' . $available . ' available.';
            header('Location: view_cart.php');
            exit;
        }
        
        $stmt = $pdo_project->prepare('UPDATE user_products SET quantity = ? WHERE user_id = ? AND product_id = ?');
        $stmt->execute([$qty, $userId, $productId]);
        $_SESSION['flash'] = 'Cart updated successfully.';
        header('Location: view_cart.php');
        exit;
    }
} catch (Exception $e) {
    $_SESSION['flash'] = 'Error updating cart: ' . $e->getMessage();
}

// default
header('Location: view_cart.php');
exit;
