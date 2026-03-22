<?php
header('Content-Type: application/json');
require_once '../includes/db.php';

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
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'El correo no es válido.']);
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

// Insert new client
$sql = "INSERT INTO users (name, paternal_surname, maternal_surname, email, password, role) VALUES (?, ?, ?, ?, ?, 'client')";
$stmt = $pdo->prepare($sql);

if ($stmt->execute([$name, $paternal, $maternal, $email, $hashed_password])) {
    echo json_encode(['success' => true, 'message' => 'Registro exitoso. ¡Ahora puedes iniciar sesión!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al registrar el usuario.']);
}
?>
