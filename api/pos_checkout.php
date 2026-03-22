<?php
// api/pos_checkout.php
session_start();
require_once '../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !hasPermission($pdo, $_SESSION['user_id'], 'pos')) {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || empty($input['cart'])) {
    echo json_encode(['success' => false, 'message' => 'El carrito está vacío']);
    exit;
}

try {
    $pdo->beginTransaction();

    $total = 0;
    foreach ($input['cart'] as $item) {
        $total += $item['price'] * $item['qty'];
    }

    // Insert Order — client_name from frontend, seller from session user_id
    $clientName = trim($input['client_name'] ?? '') ?: 'Venta en Tienda';
    $stmt = $pdo->prepare("INSERT INTO orders (user_id, client_name, total) VALUES (?, ?, ?)");
    $stmt->execute([$_SESSION['user_id'], $clientName, $total]);
    $order_id = $pdo->lastInsertId();

    // Process Items
    $stmtItem = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
    $updateStock = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?");

    foreach ($input['cart'] as $item) {
        // Check/Deduct Stock
        $updateStock->execute([$item['qty'], $item['id'], $item['qty']]);
        if ($updateStock->rowCount() == 0) {
            throw new Exception("Stock insuficiente para: " . $item['name']);
        }

        // Insert Item
        $stmtItem->execute([$order_id, $item['id'], $item['qty'], $item['price']]);
    }

    $pdo->commit();
    logActivity($pdo, $_SESSION['user_id'], 'POS_SALE', "Venta realizada ID: $order_id Total: $$total");
    
    echo json_encode(['success' => true, 'message' => '¡Venta realizada con éxito!', 'order_id' => $order_id]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
