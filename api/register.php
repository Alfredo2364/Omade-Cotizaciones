<?php
header('Content-Type: application/json');
require_once '../includes/db.php';
require_once '../includes/email_validator.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos.']);
    exit;
}

$name     = trim($data['name']              ?? '');
$paternal = trim($data['paternal_surname']  ?? '');
$maternal = trim($data['maternal_surname']  ?? '');
$email    = trim($data['email']             ?? '');
$password = $data['password']               ?? '';

// Validate required fields
if (!$name || !$paternal || !$email || !$password) {
    echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios.']);
    exit;
}

// Custom Strict Email Validation
$email_check = is_valid_email_strict($email);
if (!$email_check['valid']) {
    echo json_encode(['success' => false, 'message' => $email_check['message']]);
    exit;
}

if (strlen($password) < 6) {
    echo json_encode(['success' => false, 'message' => 'La contraseña debe tener al menos 6 caracteres.']);
    exit;
}

// Check if user exists
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Este correo ya está registrado.']);
    exit;
}

// Hash password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$verification_token = bin2hex(random_bytes(32));

// Insert new client
$sql = "INSERT INTO users (name, paternal_surname, maternal_surname, email, password, role, is_verified, verification_token) VALUES (?, ?, ?, ?, ?, 'client', 0, ?)";
$stmt = $pdo->prepare($sql);

if ($stmt->execute([$name, $paternal, $maternal, $email, $hashed_password, $verification_token])) {
    
    // Automatically determine host URL for the verification link
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    $dirname = dirname(dirname($_SERVER['REQUEST_URI']));
    // Normalize path just in case
    if ($dirname === '/' || $dirname === '\\') $dirname = '';
    
    $verify_link = $protocol . $host . $dirname . '/verify.php?token=' . $verification_token;
    
    $subject = "Verifica tu cuenta - Omade Cotizaciones";
    
    $message = "
    <html>
    <head>
        <title>Verifica tu cuenta</title>
    </head>
    <body style='font-family: Arial, sans-serif; background: #f4f7f6; padding: 20px;'>
        <div style='background: white; padding: 30px; border-radius: 10px; max-width: 600px; margin: auto;'>
            <h2 style='color: #0b1e3b;'>¡Bienvenido a Distribuciones Omade, " . htmlspecialchars($name) . "!</h2>
            <p style='color: #333;'>Gracias por registrarte. Para poder iniciar sesión y buscar o solicitar cotizaciones, por favor valida tu correo electrónico haciendo clic en el siguiente botón:</p>
            <br>
            <div style='text-align: center;'>
                <a href='" . htmlspecialchars($verify_link) . "' style='background: #1abc9c; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 16px; display: inline-block;'>
                    Verificar mi cuenta
                </a>
            </div>
            <br><br>
            <p style='font-size: 12px; color: #999;'>Si el botón no funciona, copia y pega este enlace en tu navegador:<br>$verify_link</p>
            <p style='font-size: 12px; color: #999; margin-top: 20px;'>Si no solicitaste esta cuenta, puedes ignorar este correo.</p>
        </div>
    </body>
    </html>
    ";

    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: no-reply@omade.com.mx" . "\r\n";

    // Since localhost mail() might fail without SMTP, we silence warnings to ensure JSON is returned to the frontend.
    @mail($email, $subject, $message, $headers);

    echo json_encode(['success' => true, 'message' => 'Registro exitoso. Revisa tu bandeja de entrada o spam para verificar tu cuenta antes de iniciar sesión.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al registrar el usuario.']);
}
?>
