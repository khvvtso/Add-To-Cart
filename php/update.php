<?php
$dbPath = __DIR__ . '/db.php';
if (file_exists($dbPath)) require_once $dbPath;
require_once 'functions.php';
if (!isset($_GET['product_id'])) die('Missing product ID');

$product_id = (int) $_GET['product_id'];
$message = '';

// Fetch product info from MySQL
$stmt = $pdo->prepare("SELECT name, description, price, stock FROM products WHERE product_id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();
if (!$product) die('Product not found');

// Fetch current image path
$imgStmt = $pdo->prepare("SELECT file_path FROM images WHERE product_id = ? LIMIT 1");
$imgStmt->execute([$product_id]);
$currentImage = $imgStmt->fetchColumn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $description = sanitize($_POST['description']);
    $price = (float) $_POST['price'];
    $stock = isset($_POST['stock']) ? (int) $_POST['stock'] : 0;
    $updateStmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, price = ?, stock = ? WHERE product_id = ?");
    $updateStmt->execute([$name, $description, $price, $stock, $product_id]);

    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../assets/images/';
        $fileName = basename($_FILES['image']['name']);
        $fileTmp = $_FILES['image']['tmp_name'];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($fileExt, $allowed)) {
            $newImagePath = 'assets/images/' . $fileName;
            if (!file_exists($uploadDir . $fileName)) {
                move_uploaded_file($fileTmp, $uploadDir . $fileName);
            }
            // Update or insert image path in images table
            $imgCheck = $pdo->prepare("SELECT id FROM images WHERE product_id = ?");
            $imgCheck->execute([$product_id]);
            if ($imgCheck->fetch()) {
                $imgUpdate = $pdo->prepare("UPDATE images SET file_path = ? WHERE product_id = ?");
                $imgUpdate->execute([$newImagePath, $product_id]);
            } else {
                $imgInsert = $pdo->prepare("INSERT INTO images (product_id, file_path) VALUES (?, ?)");
                $imgInsert->execute([$product_id, $newImagePath]);
            }
            $currentImage = $newImagePath;
        } else {
            $message = '<div class="message error">Invalid image file type.</div>';
        }
    }

    $message = '<div class="message success">Product updated successfully!</div>';
    // Refresh product info
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Update Product</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body style="background: linear-gradient(135deg, #f0f4f8 0%, #e0e7ff 100%); min-height: 100vh;">
    <div style="max-width: 600px; margin: 0 auto; animation: bgFadeIn 1.2s ease;">
        <h2>Edit Product</h2>
        <?php if ($message) echo $message; ?>
        <form method="post" enctype="multipart/form-data">
                <label>Name<br><input type="text" name="name" value="<?= htmlspecialchars($product['name']) ?>" required></label><br><br>
                <label>Description<br><textarea name="description" required><?= htmlspecialchars($product['description']) ?></textarea></label><br><br>
                <label>Price<br><input type="number" step="0.01" name="price" value="<?= $product['price'] ?>" required></label><br><br>
                <label>Stock<br><input type="number" name="stock" min="0" value="<?= isset($product['stock']) ? (int)$product['stock'] : 0 ?>" required></label><br><br>
                <label>Image<br><input type="file" name="image" accept="image/*"></label><br>
                <?php if ($currentImage): ?>
                    <div>Current Image:<br><img src="/product-management/<?= htmlspecialchars($currentImage) ?>" width="80"></div><br>
                <?php endif; ?>
                <button type="submit">Update Product</button>
        </form>
        <br>
        <a href="read.php">Back to Products</a>
    </div>
</body>
</html>
