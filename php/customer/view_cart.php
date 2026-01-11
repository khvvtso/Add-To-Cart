<?php
session_start();
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../db_project.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../index.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];

// Pagination and Sorting
$itemsPerPage = 6;
$currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'name';

$validSorts = ['name' => 'name ASC', 'name_desc' => 'name DESC', 'price_low' => 'price ASC', 'price_high' => 'price DESC'];
$orderClause = isset($validSorts[$sortBy]) ? $validSorts[$sortBy] : $validSorts['name'];

$cart = [];
$flash = '';
if (isset($_SESSION['flash'])) { $flash = $_SESSION['flash']; unset($_SESSION['flash']); }

$items = [];
$total = 0.0;

// Fetch user's cart items from database
try {
    // Get total count of items in cart
    $stmt = $pdo_project->prepare('SELECT COUNT(*) as total FROM user_products WHERE user_id = ?');
    $stmt->execute([$userId]);
    $countResult = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalItems = (int)$countResult['total'];
    $totalPages = ceil($totalItems / $itemsPerPage);
    $currentPage = min($currentPage, $totalPages) ?: 1;
    $offset = ($currentPage - 1) * $itemsPerPage;

    // Fetch paginated cart items
    $stmt = $pdo_project->prepare('SELECT product_id, quantity FROM user_products WHERE user_id = ? ORDER BY product_id ASC LIMIT ? OFFSET ?');
    $stmt->execute([$userId, $itemsPerPage, $offset]);
    $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($cartItems)) {
        // Get product details for items in cart
        $productIds = array_map(function($item) { return (int)$item['product_id']; }, $cartItems);
        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        
        $stmt = $pdo->prepare("SELECT product_id, name, price, stock FROM products WHERE product_id IN ($placeholders) ORDER BY {$orderClause}");
        $stmt->execute($productIds);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Map products with quantities
        $productMap = [];
        foreach ($products as $p) {
            $productMap[(int)$p['product_id']] = $p;
        }

        foreach ($cartItems as $ci) {
            $pid = (int)$ci['product_id'];
            if (isset($productMap[$pid])) {
                $r = $productMap[$pid];
                $qty = (int)$ci['quantity'];
                $subtotal = ((float)$r['price']) * $qty;
                $items[] = [
                    'product_id' => $pid,
                    'name' => $r['name'],
                    'price' => $r['price'],
                    'stock' => (int)$r['stock'],
                    'qty' => $qty,
                    'subtotal' => $subtotal
                ];
                $total += $subtotal;
            }
        }
    }
} catch (Exception $e) {
    $flash = 'Error loading cart: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Your Cart</title>
    <link rel="stylesheet" href="../../css/style.css">
</head>
<body>
    <div style="max-width:900px;margin:20px auto;padding:18px;">
        <h2>Your Cart</h2>
        <?php if ($flash): ?>
            <div class="message success"><?= htmlspecialchars($flash) ?></div>
        <?php endif; ?>

        <div style="margin-bottom:12px;"><a href="index.php" class="btn">Continue Shopping</a></div>

        <?php if (empty($items)): ?>
            <p>Your cart is empty.</p>
        <?php else: ?>
            <!-- Sort Filter -->
            <div style="margin-bottom:16px; padding:12px; background:#f5f5f5; border-radius:4px;">
                <form method="get" style="display:flex; gap:12px; align-items:center;">
                    <label for="sort" style="margin:0; font-weight:bold;">Sort by:</label>
                    <select id="sort" name="sort" onchange="this.form.submit()" style="padding:6px 8px;">
                        <option value="name" <?= $sortBy === 'name' ? 'selected' : '' ?>>Name (A-Z)</option>
                        <option value="name_desc" <?= $sortBy === 'name_desc' ? 'selected' : '' ?>>Name (Z-A)</option>
                        <option value="price_low" <?= $sortBy === 'price_low' ? 'selected' : '' ?>>Price (Low to High)</option>
                        <option value="price_high" <?= $sortBy === 'price_high' ? 'selected' : '' ?>>Price (High to Low)</option>
                    </select>
                    <span style="color:#666; font-size:0.9em;">Page <?= $currentPage ?> of <?= $totalPages ?></span>
                </form>
            </div>

            <table border="0" cellpadding="8" cellspacing="0" style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr style="text-align:left;border-bottom:1px solid #ddd;">
                        <th>Product</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Subtotal</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $it): ?>
                        <tr>
                            <td><?= htmlspecialchars($it['name']) ?></td>
                            <td>R <?= number_format($it['price'],2) ?></td>
                            <td>
                                <form method="post" action="update_cart.php" style="display:flex;gap:8px;align-items:center;">
                                    <input type="hidden" name="product_id" value="<?= (int)$it['product_id'] ?>">
                                    <input type="number" name="qty" min="1" max="<?= (int)$it['stock'] ?>" value="<?= (int)$it['qty'] ?>" required>
                                    <button type="submit" name="action" value="update" class="btn">Update</button>
                                </form>
                            </td>
                            <td>R <?= number_format($it['subtotal'],2) ?></td>
                            <td>
                                <form method="post" action="update_cart.php" onsubmit="return confirm('Remove item from cart?');">
                                    <input type="hidden" name="product_id" value="<?= (int)$it['product_id'] ?>">
                                    <button type="submit" name="action" value="remove" class="btn">Remove</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" style="text-align:right;font-weight:bold;">Total:</td>
                        <td colspan="2">R <?= number_format($total,2) ?></td>
                    </tr>
                </tfoot>
            </table>

            <!-- Pagination Controls -->
            <?php if ($totalPages > 1): ?>
                <div style="margin-top:24px; text-align:center; padding:16px; background:#f5f5f5; border-radius:4px;">
                    <div style="display:flex; gap:12px; justify-content:center; flex-wrap:wrap;">
                        <?php if ($currentPage > 1): ?>
                            <a href="?page=1&sort=<?= urlencode($sortBy) ?>" class="btn" style="padding:8px 14px;">« First</a>
                            <a href="?page=<?= $currentPage - 1 ?>&sort=<?= urlencode($sortBy) ?>" class="btn" style="padding:8px 14px;">‹ Previous</a>
                        <?php endif; ?>

                        <?php if ($currentPage < $totalPages): ?>
                            <a href="?page=<?= $currentPage + 1 ?>&sort=<?= urlencode($sortBy) ?>" class="btn" style="padding:8px 14px;">Next ›</a>
                            <a href="?page=<?= $totalPages ?>&sort=<?= urlencode($sortBy) ?>" class="btn" style="padding:8px 14px;">Last »</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>
