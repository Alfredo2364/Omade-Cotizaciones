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

if (!is_array($input['cart']) || count($input['cart']) > 200) {
    echo json_encode(['success' => false, 'message' => 'Límite de productos por ticket excedido (Max 200).']);
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

    // Process Items — price fetched from DB (never trust client-sent price)
    $getProduct = $pdo->prepare(
        "SELECT id, price, stock FROM products WHERE id = ? LIMIT 1"
    );
    $stmtItem   = $pdo->prepare(
        "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)"
    );
    $updateStock = $pdo->prepare(
        "UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?"
    );

    $total = 0; // Recalculate total from real DB prices

    foreach ($input['cart'] as $item) {
        $itemId  = (int)($item['id']  ?? 0);
        $itemQty = (int)($item['qty'] ?? 1);

        if ($itemId <= 0 || $itemQty <= 0) continue;

        // Fetch real price from DB — reject if product doesn't exist
        $getProduct->execute([$itemId]);
        $product = $getProduct->fetch();
        if (!$product) {
            throw new Exception("Producto no encontrado: ID $itemId");
        }

        $realPrice = (float)$product['price'];
        $total    += $realPrice * $itemQty;

        // Deduct stock atomically
        $updateStock->execute([$itemQty, $itemId, $itemQty]);
        if ($updateStock->rowCount() == 0) {
            throw new Exception("Stock insuficiente para: " . ($item['name'] ?? "ID $itemId"));
        }

        $stmtItem->execute([$order_id, $itemId, $itemQty, $realPrice]);
    }

    // Update order total with real DB-calculated amount
    $pdo->prepare("UPDATE orders SET total = ? WHERE id = ?")->execute([$total, $order_id]);

    $pdo->commit();
    logActivity($pdo, $_SESSION['user_id'], 'POS_SALE', "Venta realizada ID: $order_id Total: $$total");
    
    echo json_encode(['success' => true, 'message' => '¡Venta realizada con éxito!', 'order_id' => $order_id]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
