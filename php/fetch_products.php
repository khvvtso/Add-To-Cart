<?php
require_once 'functions.php';

$format = isset($_GET['format']) ? strtolower($_GET['format']) : '';

if ($format === 'json') {
	$q = '';
	if (!empty($_GET['q'])) {
		$q = trim($_GET['q']);
	}
	$products = fetchProducts($q);
	header('Content-Type: application/json');
	echo json_encode(['products' => $products], JSON_UNESCAPED_SLASHES);
	exit;
}

// fallback: render existing HTML table for direct includes
echo renderProductsTable();
