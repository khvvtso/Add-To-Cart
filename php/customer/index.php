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
$searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';

// Validate sort option
$validSorts = ['name' => 'p.name ASC', 'name_desc' => 'p.name DESC', 'price_low' => 'p.price ASC', 'price_high' => 'p.price DESC', 'newest' => 'p.product_id DESC'];
$orderClause = isset($validSorts[$sortBy]) ? $validSorts[$sortBy] : $validSorts['name'];

// Build base query with search filter
$baseQuery = "FROM products p LEFT JOIN images i ON p.product_id = i.product_id WHERE 1=1";
if ($searchQuery !== '') {
    $baseQuery .= " AND (p.name LIKE :q OR p.description LIKE :q)";
}

// Get total count with search filter
$countSql = "SELECT COUNT(*) as total " . $baseQuery;
$countStmt = $pdo->prepare($countSql);
if ($searchQuery !== '') {
    $countStmt->bindValue(':q', '%' . $searchQuery . '%', PDO::PARAM_STR);
}
$countStmt->execute();
$countResult = $countStmt->fetch(PDO::FETCH_ASSOC);
$totalProducts = (int)$countResult['total'];
$totalPages = ceil($totalProducts / $itemsPerPage);
$currentPage = min($currentPage, $totalPages) ?: 1;
$offset = ($currentPage - 1) * $itemsPerPage;

// Fetch paginated products with search
$sql = "SELECT p.product_id, p.name, p.description, p.price, p.stock, i.file_path " . $baseQuery . " ORDER BY " . $orderClause . " LIMIT ? OFFSET ?";
$stmt = $pdo->prepare($sql);
if ($searchQuery !== '') {
    $stmt->bindValue(':q', '%' . $searchQuery . '%', PDO::PARAM_STR);
}
$stmt->execute([$itemsPerPage, $offset]);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get cart item count for this user
$stmt = $pdo_project->prepare('SELECT SUM(quantity) as total_items FROM user_products WHERE user_id = ?');
$stmt->execute([$userId]);
$cartResult = $stmt->fetch(PDO::FETCH_ASSOC);
$cartCount = (int)($cartResult['total_items'] ?? 0);

$flash = '';
if (isset($_SESSION['flash'])) {
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Shop — Products</title>
    <link rel="stylesheet" href="../../css/style.css">
</head>
<body>
    <div style="max-width:1000px;margin:24px auto;padding:18px;">
        <h2>Browse Products</h2>
        <p>Welcome, <?= htmlspecialchars($_SESSION['user_name'] ?? 'Customer') ?>!</p>
        <?php if ($flash): ?>
            <div class="message success"><?= htmlspecialchars($flash) ?></div>
        <?php endif; ?>

        <div style="margin-bottom:16px;">
            <a href="../../index.php" class="btn">Home</a>
            <a href="view_cart.php" class="btn">View Cart (<?= $cartCount ?>)</a>
            <a href="../login.php" class="btn" style="background-color:#dc3545;">Logout</a>
        </div>

        <!-- Search Bar -->
        <div style="margin-bottom:16px; padding:12px; background:#f5f5f5; border-radius:4px;">
            <form method="get" style="display:flex; gap:8px; align-items:center;">
                <input type="text" name="q" placeholder="Search by name or description" 
                       value="<?= htmlspecialchars($searchQuery) ?>" 
                       style="flex:1; padding:8px; border:1px solid #ddd; border-radius:4px;">
                <button type="submit" class="btn" style="padding:8px 16px;">Search</button>
                <?php if ($searchQuery !== ''): ?>
                    <a href="index.php" class="btn" style="padding:8px 16px; background-color:#dc3545;">Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Sort Filter -->
        <div style="margin-bottom:16px; padding:12px; background:#f5f5f5; border-radius:4px;">
            <form method="get" style="display:flex; gap:12px; align-items:center;">
                <input type="hidden" name="q" value="<?= htmlspecialchars($searchQuery) ?>">
                <label for="sort" style="margin:0; font-weight:bold;">Sort by:</label>
                <select id="sort" name="sort" onchange="this.form.submit()" style="padding:6px 8px;">
                    <option value="name" <?= $sortBy === 'name' ? 'selected' : '' ?>>Name (A-Z)</option>
                    <option value="name_desc" <?= $sortBy === 'name_desc' ? 'selected' : '' ?>>Name (Z-A)</option>
                    <option value="price_low" <?= $sortBy === 'price_low' ? 'selected' : '' ?>>Price (Low to High)</option>
                    <option value="price_high" <?= $sortBy === 'price_high' ? 'selected' : '' ?>>Price (High to Low)</option>
                    <option value="newest" <?= $sortBy === 'newest' ? 'selected' : '' ?>>Newest First</option>
                </select>
                <span style="color:#666; font-size:0.9em;">Page <?= $currentPage ?> of <?= $totalPages ?></span>
            </form>
        </div>

        <div class="product-list" id="productList">
            <?php foreach ($products as $p): ?>
              <div class="product-card" data-name="<?= htmlspecialchars(strtolower($p['name'])) ?>" data-desc="<?= htmlspecialchars(strtolower($p['description'])) ?>" data-price="<?= number_format($p['price'], 2, '.', '') ?>" data-stock="<?= (int)($p['stock'] ?? 0) ?>">

                <?php if (!empty($p['file_path'])) {

                    $relativePath = ltrim($p['file_path'], '/');

                    // URL path (browser)
                    $urlPath = '/product-management/' . $relativePath;

                    echo '<div class="product-image"><img src="' . htmlspecialchars($urlPath) . '" alt="' . htmlspecialchars($p['name']) . '"></div>';

                } else {
                    echo '<div class="product-image">No image</div>';
                }
                ?>

                <div class="product-info">
                  <div class="product-title"><?= htmlspecialchars($p['name']) ?></div>
                  <div class="product-desc"><?= htmlspecialchars($p['description']) ?></div>
                  <div class="product-price">R <?= number_format($p['price'], 2) ?></div>
                  <div class="product-stock"><?php if (isset($p['stock']) && (int)$p['stock'] > 0) { echo 'In stock: ' . (int)$p['stock']; } else { echo 'Out of stock'; } ?></div>
                </div>

                <div class="product-actions">
                  <?php if ((int)$p['stock'] > 0): ?>
                    <form method="post" action="add_to_cart.php">
                        <input type="hidden" name="product_id" value="<?= (int)$p['product_id'] ?>">
                        <label>Qty <input type="number" name="qty" min="1" max="<?= (int)$p['stock'] ?>" value="1" required></label>
                        <button type="submit" class="btn">Add to Cart</button>
                    </form>
                    
                  <?php else: ?>
                    <button class="btn" disabled>Out of Stock</button>
                  <?php endif; ?>
                </div>

              </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination Controls -->
        <?php if ($totalPages > 1): ?>
            <div style="margin-top:24px; text-align:center; padding:16px; background:#f5f5f5; border-radius:4px;">
                <div style="display:flex; gap:12px; justify-content:center; flex-wrap:wrap;">
                    <?php if ($currentPage > 1): ?>
                        <a href="?page=1&sort=<?= urlencode($sortBy) ?>&q=<?= urlencode($searchQuery) ?>" class="btn" style="padding:8px 14px;">« First</a>
                        <a href="?page=<?= $currentPage - 1 ?>&sort=<?= urlencode($sortBy) ?>&q=<?= urlencode($searchQuery) ?>" class="btn" style="padding:8px 14px;">‹ Previous</a>
                    <?php endif; ?>

                    <?php if ($currentPage < $totalPages): ?>
                        <a href="?page=<?= $currentPage + 1 ?>&sort=<?= urlencode($sortBy) ?>&q=<?= urlencode($searchQuery) ?>" class="btn" style="padding:8px 14px;">Next ›</a>
                        <a href="?page=<?= $totalPages ?>&sort=<?= urlencode($sortBy) ?>&q=<?= urlencode($searchQuery) ?>" class="btn" style="padding:8px 14px;">Last »</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
