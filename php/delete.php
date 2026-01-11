<?php

$dbPath = __DIR__ . '/db.php';
if (file_exists($dbPath)) require_once $dbPath;
require_once 'functions.php';
if (!isset($_GET['product_id'])) die('Missing product ID');

$product_id = (int) $_GET['product_id'];
$message = '';

// Fetch product info from MySQL
$stmt = $pdo->prepare("SELECT name, description, price FROM products WHERE product_id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();
if (!$product) die('Product not found');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Delete product from MySQL
    $delStmt = $pdo->prepare("DELETE FROM products WHERE product_id = ?");
    $delStmt->execute([$product_id]);
    $message = '<div class="message success">Product deleted successfully!</div>';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>Delete Product</title>
        <link rel="stylesheet" href="../css/style.css">
</head>
<body>

    <div class="modal-backdrop" role="dialog" aria-modal="true">
        <div class="dialog">

            <?php if ($message): ?>
                <div class="success-note">
                    <div class="tick">✓</div>
                    <div>
                        <h3>Product Deleted</h3>
                        <p class="subtitle">The product was removed successfully.</p>
                    </div>
                </div>
                <div class="actions" style="margin-top:18px; justify-content:flex-end;">
                    <a class="btn" href="/management/php/read.php">Back to list</a>
                </div>

            <?php else: ?>
                <div class="title">
                    <div>
                        <h3>Confirm Deletion</h3>
                        <p class="subtitle">This action cannot be undone. Please confirm.</p>
                    </div>
                </div>

                <div class="meta" aria-live="polite">
                    <ul style="margin:0;padding-left:18px;">
                        <li><strong>Name:</strong> <?= htmlspecialchars($product['name']) ?></li>
                        <li><strong>Description:</strong> <?= htmlspecialchars($product['description']) ?></li>
                        <li><strong>Price:</strong> R <?= number_format($product['price'], 2) ?></li>
                    </ul>
                </div>

                <div class="actions">
                    <form method="POST" style="margin:0;">
                        <button type="submit" class="btn danger">Yes, Delete</button>
                        <a href="/management/php/read.php" class="btn cancel">Cancel</a>
                    </form>
                </div>

            <?php endif; ?>

        </div>
    </div>

    <script>
        // focus the primary action for keyboard users
        (function(){
            var primary = document.querySelector('.dialog .danger');
            if(primary) primary.focus();
        })();
    </script>

</body>
</html>
