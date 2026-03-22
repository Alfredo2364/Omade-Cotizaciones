<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: " . ($_SESSION['role'] === 'admin' ? "admin/dashboard.php" : "user/dashboard.php"));
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - Distribuciones Omade</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-body" style="position: relative;">

    <a href="login.php" class="btn-back-absolute" style="position: absolute; top: 20px; left: 20px; text-decoration: none; color: white; display: flex; align-items: center; gap: 8px; background: rgba(255,255,255,0.1); padding: 8px 15px; border-radius: 20px; backdrop-filter: blur(5px);">
        <i class="fas fa-arrow-left"></i> Volver
    </a>

    <div class="login-card" style="max-width: 400px; padding: 40px;">
        <div style="text-align: center; margin-bottom: 25px;">
            <div style="font-size: 3rem; color: #3b82f6; margin-bottom: 15px;"><i class="fas fa-key"></i></div>
            <h2 style="color: #0b1e3b;">Recuperar Acceso</h2>
            <p style="color: #64748b; font-size: 0.95rem; margin-top: 10px;">Ingresa el correo electrónico asociado a tu cuenta y te enviaremos un enlace para restablecer tu contraseña.</p>
        </div>

        <form id="recoverForm">
            <div class="form-group">
                <input type="email" id="email" required placeholder="tu@correo.com" style="text-align: center; font-weight: 600;">
            </div>

            <button type="submit" class="btn-submit" style="background: #3b82f6;">Enviar Instrucciones</button>
        </form>
        
        <div id="recover-success" style="display: none; text-align: center; margin-top: 20px; color: #10b981; font-weight: 600;">
            <i class="fas fa-envelope-open-text" style="font-size: 2rem; margin-bottom: 10px; display: block;"></i>
            Si el correo existe, hemos enviado un enlace de recuperación. Por favor, revisa también la carpeta de SPAM.
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast-overlay">
        <div id="toast-box">
            <div id="toast-icon"></div>
            <div id="toast-message"></div>
        </div>
    </div>

    <script src="assets/js/script.js"></script>
    <script>
        document.getElementById('recoverForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const form = e.target;
            const btn = form.querySelector('.btn-submit');
            btn.disabled = true;
            btn.innerText = 'Enviando...';

            const email = document.getElementById('email').value;

            try {
                const response = await fetch('api/request_reset.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email: email })
                });
                const result = await response.json();

                if (result.success) {
                    form.style.display = 'none';
                    document.getElementById('recover-success').style.display = 'block';
                } else {
                    showToast(result.message, 'error');
                }
            } catch (error) {
                showToast('Error de conexión.', 'error');
            } finally {
                btn.disabled = false;
                btn.innerText = 'Enviar Instrucciones';
            }
        });
    </script>
</body>
</html>
