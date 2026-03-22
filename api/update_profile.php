<?php
session_start();
require_once '../includes/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$headers = getallheaders();
$csrf_header = $headers['X-CSRF-Token'] ?? $headers['x-csrf-token'] ?? '';

if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf_header)) {
    echo json_encode(['success' => false, 'message' => 'Error de validación CSRF']);
    exit;
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit;
}

$role = $_SESSION['role'];
$name = trim($data['name']);
$email = trim($data['email']);
$password = $data['password'] ?? '';

// Validation
if (empty($name) || empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Nombre y correo son obligatorios']);
    exit;
}

require_once '../includes/email_validator.php';
$email_check = is_valid_email_strict($email);
if (!$email_check['valid']) {
    echo json_encode(['success' => false, 'message' => $email_check['message']]);
    exit;
}

// RESTRICTION: Employees (admins) cannot change email
// Only 'client' can change their email. 
// However, the current email is likely what's passed if the field is disabled, 
// but we should strictly check against DB or ignore the input if role is admin.
if ($role !== 'client') {
    // Force email to remain the same as in DB or Session
    // Let's check what the user currently has
    $stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $current = $stmt->fetch();
    if ($email !== $current['email']) {
        echo json_encode(['success' => false, 'message' => 'Como trabajador, no puedes editar tu correo electrónico.']);
        exit;
    }
}

// Check if email taken (if changed)
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
$stmt->execute([$email, $user_id]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Este correo ya está en uso por otro usuario.']);
    exit;
}

// Update Logic
try {
    // Basic update
    $sql = "UPDATE users SET name = ?, email = ?, paternal_surname = ?, maternal_surname = ?, phone = ?, address = ?";
    $params = [
        $name, 
        $email, 
        trim($data['paternal_surname'] ?? ''), 
        trim($data['maternal_surname'] ?? ''), 
        trim($data['phone'] ?? ''), 
        trim($data['address'] ?? '')
    ];

    if (!empty($password)) {
        $sql .= ", password = ?";
        $params[] = password_hash($password, PASSWORD_DEFAULT);
    }

    $sql .= " WHERE id = ?";
    $params[] = $user_id;

    $stmt = $pdo->prepare($sql);
    if ($stmt->execute($params)) {
        // Update session
        $_SESSION['name'] = $name;
        $_SESSION['email'] = $email;
        $_SESSION['paternal_surname'] = trim($data['paternal_surname'] ?? '');
        $_SESSION['maternal_surname'] = trim($data['maternal_surname'] ?? '');
        $_SESSION['phone'] = trim($data['phone'] ?? '');
        $_SESSION['address'] = trim($data['address'] ?? '');
        
        echo json_encode(['success' => true, 'message' => 'Perfil actualizado correctamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar base de datos']);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error de sistema: ' . $e->getMessage()]);
}
?>
