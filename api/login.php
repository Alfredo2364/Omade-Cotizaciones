<?php
session_start();                         // Must be before ANY output and before db.php
header('Content-Type: application/json; charset=utf-8');
require_once '../includes/db.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos.']);
    exit;
}

$email = $data['email'];
$password = $data['password'];

// Find user logic
// Allow login by email or name for admin simplicity if needed, but email is unique
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR name = ?");
$stmt->execute([$email, $email]);
$user = $stmt->fetch();

if ($user) {
    // Check ban
    if ($user['is_banned']) {
        echo json_encode(['success' => false, 'message' => 'Tu cuenta ha sido bloqueada. Contacta al soporte.']);
        exit;
    }

    // Check email verification
    if (isset($user['is_verified']) && $user['is_verified'] == 0) {
        echo json_encode(['success' => false, 'message' => 'Debes verificar tu correo electrónico antes de iniciar sesión. Por favor, revisa tu bandeja de entrada o SPAM.']);
        exit;
    }

    if (password_verify($password, $user['password'])) {
        // Success
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];

        // Log login if admin
        if ($user['role'] !== 'client') {
            logActivity($pdo, $user['id'], 'LOGIN', 'Inicio de sesión exitoso');
            $redirect = 'admin/dashboard.php';
        } else {
            $redirect = 'user/dashboard.php';
        }

        echo json_encode(['success' => true, 'redirect' => $redirect]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Contraseña incorrecta.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Usuario no encontrado.']);
}
?>
