<?php
header('Content-Type: application/json');
require_once '../includes/db.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || empty($data['token']) || empty($data['password'])) {
    echo json_encode(['success' => false, 'message' => 'Faltan datos.']);
    exit;
}

$token = $data['token'];
$password = $data['password'];

if (strlen($password) < 6) {
    echo json_encode(['success' => false, 'message' => 'Contraseña muy corta.']);
    exit;
}

// Verify token hasn't expired
$stmt = $pdo->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_expires_at > NOW()");
$stmt->execute([$token]);
$user = $stmt->fetch();

if ($user) {
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    
    // Update password AND destroy token
    $upd = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires_at = NULL WHERE id = ?");
    if($upd->execute([$hashed, $user['id']])) {
        echo json_encode(['success' => true, 'message' => 'Contraseña cambiada exitosamente.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al guardar la nueva contraseña.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'El token es inválido o ya caducó.']);
}
?>
