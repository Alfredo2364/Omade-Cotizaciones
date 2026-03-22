<?php
// api/pos_scan.php — Optimizado: columnas específicas, charset correcto
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['found' => false, 'error' => 'No autorizado']);
    exit;
}

require_once '../includes/db.php';

$code = trim($_GET['code'] ?? '');
if ($code === '') {
    echo json_encode(['found' => false]);
    exit;
}

// Solo columnas necesarias — antes usaba SELECT *
$stmt = $pdo->prepare(
    "SELECT id, name, product_code, price, image, stock
     FROM products
     WHERE product_code = ? AND stock > 0
     LIMIT 1"
);
$stmt->execute([$code]);
$product = $stmt->fetch();

echo json_encode($product
    ? ['found' => true,  'product' => $product]
    : ['found' => false]
);
?>
