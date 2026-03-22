<?php
header('Content-Type: application/json');
require_once '../includes/db.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || empty($data['email'])) {
    echo json_encode(['success' => false, 'message' => 'Falta el correo.']);
    exit;
}

$email = trim($data['email']);

// Only act if user exists AND is not verified
$stmt = $pdo->prepare("SELECT id, name, is_verified FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user && $user['is_verified'] == 0) {
    // Generate new token and expiration
    $token = bin2hex(random_bytes(32));
    // E.g., 24 hr expiration for tokens
    $expires = date('Y-m-d H:i:s', time() + 86400); 

    $upd = $pdo->prepare("UPDATE users SET verification_token = ?, token_expires_at = ? WHERE id = ?");
    $upd->execute([$token, $expires, $user['id']]);

    // Send logic
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    $dirname = dirname(dirname($_SERVER['REQUEST_URI']));
    if ($dirname === '/' || $dirname === '\\') $dirname = '';
    
    $link = $protocol . $host . $dirname . '/verify.php?token=' . $token;
    
    $subject = "Verifica tu cuenta - Nuevo Enlace Omade";
    
    $message = "
    <html>
    <head><title>Verifica tu cuenta</title></head>
    <body style='font-family: Arial; padding: 20px; background: #f4f7f6;'>
        <div style='background: white; padding: 30px; border-radius: 8px; max-width: 500px; margin: auto;'>
            <h2 style='color: #0b1e3b;'>Hola de nuevo, " . htmlspecialchars($user['name']) . "</h2>
            <p>Has solicitado un nuevo enlace para verificar tu correo. Haz clic en el siguiente botón (válido por 24 horas):</p>
            <br>
            <div style='text-align: center;'>
                <a href='" . htmlspecialchars($link) . "' style='background: #1abc9c; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold;'>
                    Verificar mi cuenta
                </a>
            </div>
        </div>
    </body>
    </html>
    ";

    $headers = "MIME-Version: 1.0\r\nContent-type:text/html;charset=UTF-8\r\nFrom: no-reply@omade.com.mx\r\n";
    @mail($email, $subject, $message, $headers);
    
    echo json_encode(['success' => true, 'message' => 'Correo enviado exitosamente. ¡Revisa tu SPAM!']);
} else {
    // Could already be verified, or fake email
    echo json_encode(['success' => false, 'message' => 'El correo es inválido o la cuenta ya está verificada.']);
}
?>
