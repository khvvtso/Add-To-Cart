<?php
session_start();
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../db_project.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash'] = 'You must be logged in to add items to your cart.';
    header('Location: ../../index.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];
$productId = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;
$qty = isset($_POST['qty']) ? (int) $_POST['qty'] : 0;

if ($productId <= 0 || $qty <= 0) {
    $_SESSION['flash'] = 'Invalid product or quantity.';
    header('Location: index.php');
    exit;
}

// fetch product stock
$stmt = $pdo->prepare('SELECT product_id, name, price, stock FROM products WHERE product_id = ? LIMIT 1');
$stmt->execute([$productId]);
$prod = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$prod) {
    $_SESSION['flash'] = 'Product not found.';
    header('Location: index.php');
    exit;
}

$available = isset($prod['stock']) ? (int) $prod['stock'] : 0;
if ($available <= 0) {
    $_SESSION['flash'] = 'This product is out of stock and cannot be added to cart.';
    header('Location: index.php');
    exit;
}

try {
    // Check if product already in user's cart
    $stmt = $pdo_project->prepare('SELECT quantity FROM user_products WHERE user_id = ? AND product_id = ? LIMIT 1');
    $stmt->execute([$userId, $productId]);
    $cartItem = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($cartItem) {
        // Item exists, update quantity
        $currentQty = (int)$cartItem['quantity'];
        $newQty = $currentQty + $qty;
        
        if ($newQty > $available) {
            $_SESSION['flash'] = 'Cannot add requested quantity. Only ' . $available . ' available.';
            header('Location: index.php');
            exit;
        }
        
        $stmt = $pdo_project->prepare('UPDATE user_products SET quantity = ? WHERE user_id = ? AND product_id = ?');
        $stmt->execute([$newQty, $userId, $productId]);
    } else {
        // New item, insert into cart
        if ($qty > $available) {
            $_SESSION['flash'] = 'Cannot add requested quantity. Only ' . $available . ' available.';
            header('Location: index.php');
            exit;
        }
        
        $stmt = $pdo_project->prepare('INSERT INTO user_products (user_id, product_id, quantity) VALUES (?, ?, ?)');
        $stmt->execute([$userId, $productId, $qty]);
    }

    $_SESSION['flash'] = 'Cart updated successfully.';
} catch (Exception $e) {
    $_SESSION['flash'] = 'Error updating cart: ' . $e->getMessage();
}

header('Location: view_cart.php');
exit;
