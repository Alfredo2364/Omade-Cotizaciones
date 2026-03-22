<?php
header('Content-Type: application/json');
require_once '../includes/db.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || empty($data['email'])) {
    echo json_encode(['success' => false, 'message' => 'El correo es obligatorio.']);
    exit;
}

$email = trim($data['email']);

// We always return success even if mail doesn't exist, to prevent enumerating registered users by attackers.
$stmt = $pdo->prepare("SELECT id, name FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user) {
    // Generate secure token
    $token = bin2hex(random_bytes(32));
    // 1 Hr expiration
    $expires = date('Y-m-d H:i:s', time() + 3600); 

    $update = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires_at = ? WHERE id = ?");
    $update->execute([$token, $expires, $user['id']]);

    // Send logic
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    $dirname = dirname(dirname($_SERVER['REQUEST_URI']));
    if ($dirname === '/' || $dirname === '\\') $dirname = '';
    
    $reset_link = $protocol . $host . $dirname . '/reset_password.php?token=' . $token;
    
    $subject = "Restablece tu contraseña - Omade";
    
    $message = "
    <html>
    <head><title>Recuperación de Contraseña</title></head>
    <body style='font-family: Arial; padding: 20px; background: #f4f7f6;'>
        <div style='background: white; padding: 30px; border-radius: 8px; max-width: 500px; margin: auto;'>
            <h2 style='color: #0b1e3b;'>Hola, " . htmlspecialchars($user['name']) . "</h2>
            <p>Recibimos una solicitud para restablecer tu contraseña. Haz clic en el siguiente enlace para crear una nueva (tienes 1 hora antes de que expire):</p>
            <br>
            <div style='text-align: center;'>
                <a href='" . htmlspecialchars($reset_link) . "' style='background: #3b82f6; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold;'>
                    Restablecer Contraseña
                </a>
            </div>
            <br>
            <p style='font-size: 12px; color: #999; margin-top: 30px;'>Si no solicitaste este cambio, simplemente ignora este correo y tu cuenta se mantendrá segura.</p>
        </div>
    </body>
    </html>
    ";

    $headers = "MIME-Version: 1.0\r\nContent-type:text/html;charset=UTF-8\r\nFrom: no-reply@omade.com.mx\r\n";
    @mail($email, $subject, $message, $headers);
}

// Always echo success to avoid User Enumeration
echo json_encode(['success' => true, 'message' => 'Instrucciones enviadas.']);
?>
