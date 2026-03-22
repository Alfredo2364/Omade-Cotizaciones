<?php
session_start();
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: user/dashboard.php");
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Distribuciones Omade</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- Favicon -->
    <link rel="apple-touch-icon" sizes="180x180" href="assets/favicon_io/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/favicon_io/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/favicon_io/favicon-16x16.png">

</head>
<body class="login-body">

    <div class="login-card">
        <!-- Volver Button -->
        <a href="index.html" class="btn-back">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
        <style>
            .btn-back {
                display: inline-flex;
                align-items: center;
                gap: 10px;
                text-decoration: none;
                color: #64748b;
                font-weight: 500;
                font-size: 0.95rem;
                padding: 10px 20px;
                border-radius: 50px;
                background: #f8fafc;
                border: 1px solid #e2e8f0;
                margin-bottom: 25px;
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            }
            .btn-back:hover {
                background: #e2e8f0;
                color: #0f172a;
                transform: translateX(-5px);
                box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            }
        </style>

        <div class="login-header">
            <h2 style="margin-bottom: 10px;">Bienvenido</h2>
            <p style="color: #666;">Ingresa a tu cuenta para gestionar pedidos y cotizaciones.</p>
        </div>

        <form id="loginForm">
            <div class="form-group">
                <label for="email">Correo Electrónico</label>
                <input type="email" id="email" name="email" required placeholder="tu@correo.com">
            </div>
            
            <div class="form-group">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" required placeholder="••••••••">
            </div>

            <button type="submit" class="btn-submit">Ingresar</button>
        </form>
        
        <div style="margin-top: 20px; text-align: center; font-size: 0.9rem;">
            <p style="color: #666;">¿No tienes cuenta? <a href="register.php" style="color: #0b1e3b; font-weight: 600; text-decoration: none;">Crear una cuenta</a></p>
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
    // Login and Toast logic is handled by assets/js/script.js
    </script>
</body>
</html>
