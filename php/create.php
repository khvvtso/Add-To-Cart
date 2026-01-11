<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$dbPath = __DIR__ . '/db.php';
if (file_exists($dbPath)) require_once $dbPath;
require_once 'functions.php';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $imagePath = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../assets/images/';
        $fileName = basename($_FILES['image']['name']);
        $fileTmp = $_FILES['image']['tmp_name'];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($fileExt, $allowed)) {
            $existingPath = 'assets/images/' . $fileName;
            if (file_exists($uploadDir . $fileName)) {
                $imagePath = $existingPath;
            } else {
                $destPath = $uploadDir . $fileName;
                if (move_uploaded_file($fileTmp, $destPath)) {
                    $imagePath = $existingPath;
                } else {
                    $message = '<div class="message error">Failed to upload image.</div>';
                }
            }
        } else {
            $message = '<div class="message error">Invalid image file type.</div>';
        }
    } else {
        $message = '<div class="message error">Image is required.</div>';
    }
    if ($imagePath) {
        $stockVal = isset($_POST['stock']) ? (int) $_POST['stock'] : 0;
        $stmt = $pdo->prepare("INSERT INTO products (name, description, price, stock) VALUES (?, ?, ?, ?)");
        $success = $stmt->execute([
            sanitize($_POST['name']),
            sanitize($_POST['description']),
            (float) $_POST['price'],
            $stockVal
        ]);
        if (!$success) {
            echo '<pre>Product Insert Error: ';
            var_dump($stmt->errorInfo());
            echo '</pre>';
        }
        $productId = $pdo->lastInsertId();

        $imgCheck = $pdo->prepare("SELECT id FROM images WHERE product_id = ? AND file_path = ?");
        $imgCheck->execute([$productId, $imagePath]);
        if (!$imgCheck->fetch()) {
            $stmtImg = $pdo->prepare("INSERT INTO images (product_id, file_path) VALUES (?, ?)");
            $imgSuccess = $stmtImg->execute([$productId, $imagePath]);
            if (!$imgSuccess) {
                echo '<pre>Image Insert Error: ';
                var_dump($stmtImg->errorInfo());
                echo '</pre>';
            }
        }

        $message = '<div class="message success">Product added successfully!</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Product</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body style="background: linear-gradient(135deg, #f0f4f8 0%, #e0e7ff 100%); min-height: 100vh;">
    <div style="max-width: 600px; margin: 0 auto; animation: bgFadeIn 1.2s ease;">
        <h2>Add New Product</h2>
        <?php if ($message) echo $message; ?>
        <form method="POST" enctype="multipart/form-data">
            <label>Name<br><input type="text" name="name" required></label><br><br>
            <label>Description<br><textarea name="description" required></textarea></label><br><br>
            <label>Image<br><input type="file" name="image" accept="image/*" required></label><br><br>
            <label>Price<br><input type="number" step="0.01" name="price" required></label><br><br>
            <label>Stock<br><input type="number" name="stock" min="0" value="0" required></label><br><br>
            <button type="submit">Add Product</button>
        </form>
        <br>
        <a href="read.php">Back to Products</a>
    </div>
</body>
</html>
