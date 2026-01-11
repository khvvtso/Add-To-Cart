<?php
// php/functions.php

// Sanitize user input
function sanitize($input) {
    return htmlspecialchars(trim($input));
}

// Return products as an array (optionally filtered by search query)
function fetchProducts($q = '') {
    global $pdo;

    $sql = "SELECT p.product_id, p.name, p.description, p.price, p.stock, i.file_path FROM products p LEFT JOIN images i ON p.product_id = i.product_id";

    if ($q !== '') {
        $sql .= " WHERE p.name LIKE :q OR p.description LIKE :q";
        $stmt = $pdo->prepare($sql);
        $like = '%' . $q . '%';
        $stmt->bindValue(':q', $like, PDO::PARAM_STR);
        $stmt->execute();
    } else {
        $stmt = $pdo->query($sql);
    }

    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Normalize file_path to browser-usable URL if present
    foreach ($products as &$p) {
        if (!empty($p['file_path'])) {
            $p['file_url'] = '/product-management/' . ltrim($p['file_path'], '/');
        } else {
            $p['file_url'] = null;
        }
    }

    return $products;
}

// Generate product table HTML from SQL database
function renderProductsTable() {
    global $pdo;
    $stmt = $pdo->query("SELECT p.product_id, p.name, p.description, p.price, p.stock, i.file_path FROM products p LEFT JOIN images i ON p.product_id = i.product_id");
    $products = $stmt->fetchAll();
    if (empty($products)) return "<p>No products found.</p>";

    $rows = '';
    foreach ($products as $p) {
        $imgPath = !empty($p['file_path']) ? '/product-management/' . $p['file_path'] : '';
        $stockCell = isset($p['stock']) ? (int)$p['stock'] : 0;
        $rows .= "\n         <tr>\n                <td>" . htmlspecialchars($p['name']) . "</td>\n                <td>" . htmlspecialchars($p['description']) . "</td>\n                <td>R " . number_format($p['price'], 2) . "</td>\n                <td>" . $stockCell . "</td>\n                <td>\n                    <a href='/product-management/php/update.php?product_id=" . urlencode($p['product_id']) . "'>Edit</a> |\n                    <a href='/product-management/php/delete.php?product_id=" . urlencode($p['product_id']) . "' onclick=\"return confirm('Are you sure you want to delete this product?');\">Delete</a>\n                </td>\n                <td>" . ($imgPath ? "<img src='" . htmlspecialchars($imgPath) . "' alt='" . htmlspecialchars($p['name']) . "' width='80'>" : '<span style="color:#888;">No image</span>') . "</td>\n            </tr>\n        ";
    }

    return "\n    <table border='1' cellpadding='10' cellspacing='0'>\n        <thead>\n            <tr>\n                <th>Name</th>\n                <th>Description</th>\n                <th>Price</th>\n                <th>Stock</th>\n                <th>Actions</th>\n                <th>Image</th>\n            </tr>\n        </thead>\n        <tbody>\n            $rows\n        </tbody>\n    </table>\n    ";
}
