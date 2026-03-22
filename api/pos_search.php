<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

// Auth check (endpoint was previously unprotected)
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

require_once '../includes/db.php';

$q = trim($_GET['q'] ?? '');

try {
    $cols = "id, name, product_code, price, image, stock";

    if ($q === '') {
        // Favoritos — sin SELECT *, solo columnas necesarias
        $stmt = $pdo->query(
            "SELECT $cols FROM products
             WHERE pos_favorite = 1 AND stock > 0
             LIMIT 15"
        );
        echo json_encode($stmt->fetchAll());
        exit;
    }

    $stmt = $pdo->prepare(
        "SELECT $cols FROM products
         WHERE (name LIKE ? OR product_code LIKE ?) AND stock > 0
         LIMIT 15"
    );
    $like = "%$q%";
    $stmt->execute([$like, $like]);
    echo json_encode($stmt->fetchAll());

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error interno']);
}
?>
