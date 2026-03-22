<?php
require_once 'includes/db.php';

$message = '';
$success = false;

if (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = $_GET['token'];
    
    // Find user by token
    $stmt = $pdo->prepare("SELECT id, name, is_verified FROM users WHERE verification_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    
    if ($user) {
        if ($user['is_verified'] == 1) {
            $message = 'Esta cuenta ya ha sido verificada anteriormente.';
            $success = true;
        } else {
            // Verify
            $update = $pdo->prepare("UPDATE users SET is_verified = 1, verification_token = NULL WHERE id = ?");
            if ($update->execute([$user['id']])) {
                $message = '¡Cuenta verificada con éxito! Ya puedes iniciar sesión en el sistema.';
                $success = true;
            } else {
                $message = 'Ocurrió un error al verificar la cuenta. Intenta de nuevo más tarde.';
            }
        }
    } else {
        $message = 'El enlace de verificación es inválido o ha expirado.';
    }
} else {
    $message = 'No se proporcionó ningún token de verificación.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Soporte de Verificación - Distribuciones Omade</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .verify-card {
            background: white;
            padding: 40px;
            border-radius: 12px;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            text-align: center;
        }
        .icon-container {
            font-size: 4rem;
            margin-bottom: 20px;
        }
        .success-icon { color: #10b981; }
        .error-icon { color: #ef4444; }
    </style>
</head>
<body class="login-body">
    <div class="verify-card">
        <div class="icon-container <?= $success ? 'success-icon' : 'error-icon' ?>">
            <?= $success ? '&#10004;' : '&#10006;' ?>
        </div>
        <h2 style="color: #0b1e3b; margin-bottom: 15px;">Validación de Correo</h2>
        <p style="color: #64748b; font-size: 1.1rem; line-height: 1.6; margin-bottom: 25px;">
            <?= htmlspecialchars($message) ?>
        </p>
        <a href="login.php" class="btn-submit" style="display: block; background: #0b1e3b; text-decoration: none; padding: 12px;">
            <?= $success ? 'Ir a Iniciar Sesión' : 'Volver a Iniciar Sesión' ?>
        </a>
    </div>
</body>
</html>
