<?php
require_once '../includes/db.php';
session_start();

// Security: Only Master Admin (ID 1)
if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != 1) {
    die("ACCESO DENEGADO: Solo el Admin Maestro (ID 1) puede reiniciar el sistema.");
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_reset'])) {
    if ($_POST['confirm_reset'] === 'CONFIRMAR') {
        try {
            // Disable foreign keys to allow truncation
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

            // Truncate Transactional Tables
            $tables = [
                'messages',
                'order_items',
                'orders',
                'quotes',
                'activity_logs'
            ];

            foreach ($tables as $table) {
                $pdo->exec("TRUNCATE TABLE $table");
            }

            // Optional: Delete clients (Keep Admins)
            // $pdo->exec("DELETE FROM users WHERE role = 'client'");

            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

            $message = "✅ SISTEMA REINICIADO: Pedidos, Cotizaciones, Mensajes y Logs han sido eliminados.";
        } catch (PDOException $e) {
            $message = "❌ Error: " . $e->getMessage();
        }
    } else {
        $message = "⚠️ Debes escribir 'CONFIRMAR' para proceder.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reset Sistema</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { background: #fee2e2; display: flex; justify-content: center; align-items: center; height: 100vh; font-family: sans-serif; }
        .card { background: white; padding: 40px; border-radius: 10px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); max-width: 500px; text-align: center; border: 2px solid #ef4444; }
        h1 { color: #dc2626; margin-top: 0; }
        .warning { background: #fef2f2; color: #991b1b; padding: 15px; border-radius: 5px; margin: 20px 0; font-size: 0.9rem; text-align: left; border-left: 4px solid #ef4444; }
        input { padding: 10px; width: 100%; border: 1px solid #ccc; border-radius: 5px; margin-top: 10px; }
        button { background: #dc2626; color: white; padding: 12px 20px; border: none; border-radius: 5px; width: 100%; margin-top: 20px; cursor: pointer; font-weight: bold; font-size: 1.1rem; }
        button:hover { background: #b91c1c; }
        .back { display: block; margin-top: 20px; color: #666; text-decoration: none; }
    </style>
</head>
<body>

<div class="card">
    <h1>⚠️ ZONA DE PELIGRO</h1>
    <p>Estás a punto de eliminar <b>TODOS</b> los datos transaccionales del sistema.</p>
    
    <div class="warning">
        <strong>Se eliminarán permanentemente:</strong>
        <ul style="margin: 5px 0 0 20px;">
            <li>Todos los Pedidos y Ventas</li>
            <li>Todas las Cotizaciones</li>
            <li>Todo el historial de Chat</li>
            <li>Todos los Logs de actividad</li>
        </ul>
        <br>
        * Los Productos y Usuarios (Clientes/Admins) <b>NO</b> se borrarán.
    </div>

    <?php if ($message): ?>
        <div style="padding: 10px; background: #dcfce7; color: #166534; border-radius: 5px; margin-bottom: 20px;">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <label>Escribe "CONFIRMAR" para borrar todo:</label>
        <input type="text" name="confirm_reset" placeholder="CONFIRMAR" autocomplete="off" required>
        <button type="submit">🗑️ BORRAR TODO LSO DATOS</button>
    </form>

    <a href="dashboard.php" class="back" style="display: flex; align-items: center; justify-content: center; gap: 8px;">
        <span style="font-size: 1.2rem;">&larr;</span> Volver al Dashboard
    </a>
</div>

</body>
</html>
