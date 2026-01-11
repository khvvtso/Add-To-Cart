<?php
require_once __DIR__ . '/db.php';

/*
EXPECTED images.file_path value:
assets/images/filename.jpg
*/

// Pagination and Sorting
$itemsPerPage = 6;
$currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'name';

// Validate sort option
$validSorts = ['name' => 'p.name ASC', 'name_desc' => 'p.name DESC', 'price_low' => 'p.price ASC', 'price_high' => 'p.price DESC', 'newest' => 'p.product_id DESC'];
$orderClause = isset($validSorts[$sortBy]) ? $validSorts[$sortBy] : $validSorts['name'];

// handle optional search query (GET param: q)
$q = '';

$sql = "
    SELECT
        p.product_id,
        p.name,
        p.description,
        p.price,
        p.stock,
        i.file_path
    FROM products p
    LEFT JOIN product_management.images i
        ON p.product_id = i.product_id
";

// Count query for pagination
$countSql = "SELECT COUNT(*) as total FROM products p WHERE 1=1";

if (!empty($_GET['q'])) {
    $q = trim($_GET['q']);
    $sql .= " WHERE p.name LIKE :q1 OR p.description LIKE :q2 ";
    $countSql .= " AND (p.name LIKE :q1 OR p.description LIKE :q2) ";
}

$countStmt = $pdo->prepare($countSql);
if ($q !== '') {
    $like = '%' . $q . '%';
    $countStmt->bindValue(':q1', $like, PDO::PARAM_STR);
    $countStmt->bindValue(':q2', $like, PDO::PARAM_STR);
}
$countStmt->execute();
$countResult = $countStmt->fetch(PDO::FETCH_ASSOC);
$totalProducts = (int)$countResult['total'];
$totalPages = ceil($totalProducts / $itemsPerPage);
$currentPage = min($currentPage, $totalPages) ?: 1;
$offset = ($currentPage - 1) * $itemsPerPage;

$sql .= " ORDER BY {$orderClause} LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);
if ($q !== '') {
  $like = '%' . $q . '%';
  $stmt->bindValue(':q1', $like, PDO::PARAM_STR);
  $stmt->bindValue(':q2', $like, PDO::PARAM_STR);
}
$stmt->bindValue(':limit', (int)$itemsPerPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// compute dashboard stats (from all products, not just current page)
$allProductsSql = "SELECT p.price, p.stock FROM products p";
if ($q !== '') {
    $allProductsSql .= " WHERE p.name LIKE :q1 OR p.description LIKE :q2";
}
$allStmt = $pdo->prepare($allProductsSql);
if ($q !== '') {
    $like = '%' . $q . '%';
    $allStmt->bindValue(':q1', $like, PDO::PARAM_STR);
    $allStmt->bindValue(':q2', $like, PDO::PARAM_STR);
}
$allStmt->execute();
$allProducts = $allStmt->fetchAll(PDO::FETCH_ASSOC);

$productCount = count($allProducts);
$totalValue = 0.0;
foreach ($allProducts as $pp) {
  $price = (float) ($pp['price'] ?? 0);
  $stock = isset($pp['stock']) ? (int) $pp['stock'] : 0;
  $totalValue += $price * $stock;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard — Products</title>
    <link rel="stylesheet" href="../css/style.css">
    <script src="../js/read_admin.js" defer></script>
</head>
<body>

<div class="dashboard-wrap">

  <div class="top-row">
    <div class="brand">Admin Dashboard</div>

  </div>

<div class="stats">
  <div class="stat-card" data-count="<?= number_format($totalValue, 2, '.', '') ?>" data-prefix="R ">
    <div class="icon">💰</div>
    <div class="label">Total Inventory Value</div>
    <div class="value">R 0</div>
  </div>

  <div class="stat-card" data-count="<?= $productCount ?>">
    <div class="icon">📦</div>
    <div class="label">Available Products</div>
    <div class="value">0</div>
  </div>

  <div class="stat-card">
    <div class="icon">🔍</div>
    <div class="label">Search</div>
    <div class="value">
      <form method="get" action="read.php" style="display:flex;gap:8px;align-items:center;">
        <input id="search" name="q" type="text" placeholder="Search by name or description"
          value="<?= htmlspecialchars($q) ?>">
        <button type="submit" class="btn">Search</button>
        <?php if ($q !== ''): ?>
          <a href="read.php" class="btn" style="background:linear-gradient(90deg,#9ca3ff,#60a5fa);">Clear</a>
        <?php endif; ?>
      </form>
    </div>
  </div>

  <!-- Sort Filter -->
  <div class="stat-card">
    <div class="icon">📊</div>
    <div class="label">Sort & Filter</div>
    <div class="value">
      <form method="get" action="read.php" style="display:flex;gap:8px;align-items:center;">
        <input type="hidden" name="q" value="<?= htmlspecialchars($q) ?>">
        <select name="sort" onchange="this.form.submit()" style="padding:6px 8px;">
          <option value="name" <?= $sortBy === 'name' ? 'selected' : '' ?>>Name (A-Z)</option>
          <option value="name_desc" <?= $sortBy === 'name_desc' ? 'selected' : '' ?>>Name (Z-A)</option>
          <option value="price_low" <?= $sortBy === 'price_low' ? 'selected' : '' ?>>Price (Low to High)</option>
          <option value="price_high" <?= $sortBy === 'price_high' ? 'selected' : '' ?>>Price (High to Low)</option>
          <option value="newest" <?= $sortBy === 'newest' ? 'selected' : '' ?>>Newest First</option>
        </select>
      </form>
    </div>
  </div>

  <div class="stat-card">
    <div class="icon">📄</div>
    <div class="label">Pagination</div>
    <div class="value" style="font-size:0.9em;">
      <strong>Page <?= $currentPage ?> of <?= $totalPages ?></strong><br>
      Showing <?= $offset + 1 ?>-<?= min($offset + $itemsPerPage, $totalProducts) ?> of <?= $totalProducts ?> products
    </div>
  </div>
      <div class="top-actions">
      <a class="btn home" href="../index.php">Home</a>
      
    </div>
    <a class="btn add" href="create.php">Add New Product</a>
</div>

</div>


  <div class="product-list" id="productList">

    <?php foreach ($products as $p): ?>
      <div class="product-card" data-name="<?= htmlspecialchars(strtolower($p['name'])) ?>" data-desc="<?= htmlspecialchars(strtolower($p['description'])) ?>" data-price="<?= number_format($p['price'], 2, '.', '') ?>" data-stock="<?= (int)($p['stock'] ?? 0) ?>">

        <?php
        if (!empty($p['file_path'])) {

            $relativePath = ltrim($p['file_path'], '/');

            // filesystem path (PHP)
            $diskPath = $_SERVER['DOCUMENT_ROOT']
                . '/product-management/'
                . $relativePath;

            // URL path (browser)
            $urlPath = '/product-management/' . $relativePath;

            if (file_exists($diskPath)) {
                echo '<div class="product-image"><img src="' . htmlspecialchars($urlPath) . '" alt="' . htmlspecialchars($p['name']) . '"></div>';
            } else {
                echo '<div class="product-image">Image missing</div>';
            }

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
          <a class="edit-btn" href="update.php?product_id=<?= (int)$p['product_id'] ?>">Edit</a>
          <a class="delete-btn" href="delete.php?product_id=<?= (int)$p['product_id'] ?>" onclick="return confirm('Are you sure you want to delete this product?');">Delete</a>
        </div>

      </div>
    <?php endforeach; ?>

  </div>

  <!-- Pagination Controls -->
  <?php if ($totalPages > 1): ?>
    <div style="margin-top:24px; margin-left:24px; margin-right:24px; text-align:center; padding:16px; background:#f5f5f5; border-radius:4px;">
      <div style="display:flex; gap:12px; justify-content:center; flex-wrap:wrap;\">
        <?php if ($currentPage > 1): ?>
          <a href="?page=1&sort=<?= urlencode($sortBy) ?>&q=<?= urlencode($q) ?>" class="btn" style="padding:8px 14px;">« First</a>
          <a href="?page=<?= $currentPage - 1 ?>&sort=<?= urlencode($sortBy) ?>&q=<?= urlencode($q) ?>" class="btn" style="padding:8px 14px;">‹ Previous</a>
        <?php endif; ?>

        <?php if ($currentPage < $totalPages): ?>
          <a href="?page=<?= $currentPage + 1 ?>&sort=<?= urlencode($sortBy) ?>&q=<?= urlencode($q) ?>" class="btn" style="padding:8px 14px;">Next ›</a>
          <a href="?page=<?= $totalPages ?>&sort=<?= urlencode($sortBy) ?>&q=<?= urlencode($q) ?>" class="btn" style="padding:8px 14px;">Last »</a>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>

</div>

...
<!-- your page content -->

<script>
  /* Animate numbers */
  function animateCounter(el, start, end, duration, prefix = "") {
      let startTime = null;

      function update(timestamp) {
          if (!startTime) startTime = timestamp;
          const progress = Math.min((timestamp - startTime) / duration, 1);
          const value = progress * (end - start) + start;

          if (end % 1 !== 0) {
              el.textContent = prefix + value.toFixed(2);
          } else {
              el.textContent = prefix + Math.floor(value).toLocaleString("en-ZA");
          }

          if (progress < 1) requestAnimationFrame(update);
      }

      requestAnimationFrame(update);
  }

  /* Intersection Observer */
  const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
          if (entry.isIntersecting) {
              const card = entry.target;
              card.classList.add("in-view");

              const valueEl = card.querySelector(".value");
              const target = card.dataset.count;

              if (target && !card.dataset.animated) {
                  const prefix = card.dataset.prefix || "";
                  animateCounter(valueEl, 0, parseFloat(target), 900, prefix);
                  card.dataset.animated = "true";
              }

              observer.unobserve(card);
          }
      });
  }, { threshold: 0.35 });

  document.querySelectorAll(".stat-card").forEach(card => {
      observer.observe(card);
  });
</script>
</body>


</body>
</html>
