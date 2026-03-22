<?php
// ==========================================
// CONFIGURACIÓN DE BASE DE DATOS
// ==========================================
// LOCAL (XAMPP):
$host = 'localhost'; $dbname = 'omade_db'; $username = 'root'; $password = '';

// PRODUCCIÓN (Infinity Free) — Descomentar al subir:
// $host = 'sql211.infinityfree.com';
// $dbname = 'if0_40233756_omade_db';
// $username = 'if0_40233756';
// $password = 'Ravager2365';

// ------------------------------------------

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,   // True prepared statements (faster + safer)
            PDO::ATTR_PERSISTENT         => false,   // Avoid stale connection state
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        ]
    );
} catch (PDOException $e) {
    $isDev = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1']);
    die($isDev ? "DB Error: " . $e->getMessage() : "Error de conexión. Intenta más tarde.");
}

// ------------------------------------------
// Log de actividad (reutiliza statement preparado)
// ------------------------------------------
function logActivity($pdo, $user_id, $action, $details = null) {
    static $stmt = null;
    if (!$stmt) {
        $stmt = $pdo->prepare(
            "INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)"
        );
    }
    $stmt->execute([$user_id, $action, $details, $_SERVER['REMOTE_ADDR'] ?? 'Unknown']);
}

// ------------------------------------------
// Verificación de permisos — cacheada en sesión
// ANTES: ~8 queries DB por página. AHORA: 1 query por sesión de usuario.
// ------------------------------------------
function hasPermission($pdo, $user_id, $required_permission) {
    $cacheKey = 'perm_' . $user_id;

    if (!isset($_SESSION[$cacheKey])) {
        $stmt = $pdo->prepare("SELECT role, permissions FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        $_SESSION[$cacheKey] = $user
            ? ['role' => $user['role'], 'perms' => json_decode($user['permissions'], true) ?? []]
            : ['role' => '', 'perms' => []];
    }

    $c = $_SESSION[$cacheKey];
    if ($c['role'] === 'super_admin') return true;
    return in_array($required_permission, $c['perms']) || in_array('all', $c['perms']);
}

// Llama esto después de cambiar permisos/rol de un usuario
function clearPermissionCache($user_id) {
    unset($_SESSION['perm_' . $user_id]);
}
?>
