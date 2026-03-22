<?php
session_start();
require_once 'includes/db.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit;
}

$token = $_GET['token'] ?? '';
$valid = false;
$message = '';

if ($token) {
    // Check if token exists and hasn't expired.
    $stmt = $pdo->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_expires_at > NOW()");
    $stmt->execute([$token]);
    if ($stmt->fetch()) {
        $valid = true;
    } else {
        $message = 'El enlace de recuperación es inválido o ya ha expirado. Por favor, solicita uno nuevo.';
    }
} else {
    $message = 'No se proporcionó un token de seguridad.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Contraseña - Distribuciones Omade</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="login-body">
    <div class="login-card" style="max-width: 450px; padding: 40px; text-align: center;">

        <?php if ($valid): ?>
            <div style="font-size: 3rem; color: #10b981; margin-bottom: 15px;"><i class="fas fa-lock"></i></div>
            <h2 style="color: #0b1e3b;">Crear Nueva Contraseña</h2>
            <p style="color: #64748b; font-size: 0.95rem; margin-top: 10px; margin-bottom: 25px;">Por favor, ingresa tu nueva contraseña segura.</p>

            <form id="newPasswordForm" style="text-align: left;">
                <input type="hidden" id="token" value="<?= htmlspecialchars($token) ?>">
                
                <div class="form-group">
                    <label>Nueva Contraseña</label>
                    <input type="password" id="new_pass" required placeholder="Mínimo 6 caracteres">
                </div>
                <div class="form-group">
                    <label>Confirmar Contraseña</label>
                    <input type="password" id="confirm_pass" required placeholder="Vuelve a escribirla">
                </div>

                <button type="submit" class="btn-submit" style="background: #10b981;">Guardar Contraseña</button>
            </form>
            
            <div id="reset-success" style="display: none; margin-top: 20px; font-weight: 600; color: #10b981;">
                ¡Contraseña actualizada con éxito!
                <a href="login.php" style="display: block; width: 100%; text-decoration: none; padding: 12px; background: #0b1e3b; color: white; margin-top: 15px; border-radius: 5px;">
                    Ir a Iniciar Sesión
                </a>
            </div>

        <?php else: ?>
            <div style="font-size: 3rem; color: #ef4444; margin-bottom: 15px;"><i class="fas fa-exclamation-triangle"></i></div>
            <h2 style="color: #0b1e3b;">Enlace Expirado</h2>
            <p style="color: #64748b; font-size: 1rem; margin-top: 15px; margin-bottom: 25px;">
                <?= htmlspecialchars($message) ?>
            </p>
            <a href="forgot_password.php" class="btn-submit" style="text-decoration: none; background: #3b82f6;">Solicitar Nuevo Enlace</a>
        <?php endif; ?>

    </div>

    <!-- Toast -->
    <div id="toast-overlay"><div id="toast-box"><div id="toast-icon"></div><div id="toast-message"></div></div></div>
    
    <script src="assets/js/script.js"></script>
    <script>
        const passForm = document.getElementById('newPasswordForm');
        if(passForm) {
            passForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const p1 = document.getElementById('new_pass').value;
                const p2 = document.getElementById('confirm_pass').value;
                
                if(p1.length < 6) {
                    showToast('La contraseña debe tener al menos 6 caracteres', 'error');
                    return;
                }
                
                if(p1 !== p2) {
                    showToast('Las contraseñas no coinciden', 'error');
                    return;
                }

                const token = document.getElementById('token').value;
                const btn = e.target.querySelector('button');
                btn.disabled = true; btn.innerText = 'Guardando...';

                try {
                    const response = await fetch('api/reset_password.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({ token: token, password: p1 })
                    });
                    const res = await response.json();

                    if(res.success) {
                        passForm.style.display = 'none';
                        document.getElementById('reset-success').style.display = 'block';
                    } else {
                        showToast(res.message, 'error');
                        btn.disabled = false; btn.innerText = 'Guardar Contraseña';
                    }
                } catch(err) {
                    showToast('Error de red', 'error');
                    btn.disabled = false; btn.innerText = 'Guardar Contraseña';
                }
            });
        }
    </script>
</body>
</html>
