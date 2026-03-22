<?php
session_start();
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'super_admin') {
        header("Location: admin/dashboard.php");
    } else if ($_SESSION['role'] === 'client') {
        header("Location: user/dashboard.php");
    } else {
        // Fallback to avoid catastrophic loops if role is corrupted
        session_destroy();
        header("Location: index.html");
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso - Distribuciones Omade</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- Favicon -->
    <link rel="apple-touch-icon" sizes="180x180" href="assets/favicon_io/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/favicon_io/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/favicon_io/favicon-16x16.png">

    <style>
        .auth-card {
            background: white;
            border-radius: 15px;
            width: 100%;
            max-width: 450px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            overflow: hidden;
            position: relative;
            margin: auto;
        }

        .auth-header {
            display: flex;
            border-bottom: 1px solid #e2e8f0;
            background: #f8fafc;
        }

        .auth-tab {
            flex: 1;
            padding: 20px;
            text-align: center;
            font-weight: 600;
            color: #64748b;
            cursor: pointer;
            transition: all 0.3s ease;
            user-select: none;
        }

        .auth-tab.active {
            color: #0b1e3b;
            background: white;
            border-bottom: 2px solid #1abc9c;
        }

        .auth-viewport {
            overflow: hidden;
            width: 100%;
        }

        .auth-slider {
            display: flex;
            width: 200%;
            transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .auth-pane {
            width: 50%;
            padding: 30px 40px;
            box-sizing: border-box;
        }

        /* Mobile adjustments for pane padding */
        @media (max-width: 480px) {
            .auth-pane { padding: 25px 20px; }
        }

        .btn-back-absolute {
            position: absolute;
            top: 20px;
            left: 20px;
            text-decoration: none;
            color: white;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            background: rgba(255,255,255,0.1);
            padding: 8px 15px;
            border-radius: 20px;
            backdrop-filter: blur(5px);
            transition: all 0.3s;
        }

        .btn-back-absolute:hover {
            background: rgba(255,255,255,0.2);
            transform: translateX(-5px);
        }

        #resendBtn {
            display: none;
            background: #f1f5f9;
            color: #3b82f6;
            border: 1px solid #cbd5e1;
            padding: 8px 15px;
            border-radius: 5px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 15px;
            width: 100%;
            transition: background 0.2s;
        }
        #resendBtn:hover { background: #e2e8f0; }

    </style>
</head>
<body class="login-body" style="position: relative;">

    <a href="index.html" class="btn-back-absolute">
        <i class="fas fa-arrow-left"></i> Volver al Inicio
    </a>

    <div class="auth-card">
        
        <div class="auth-header">
            <div class="auth-tab active" id="tab-login" onclick="slideAuth('login')">INICIAR SESIÓN</div>
            <div class="auth-tab" id="tab-register" onclick="slideAuth('register')">CREAR CUENTA</div>
        </div>

        <div class="auth-viewport">
            <div class="auth-slider" id="auth-slider">
                
                <!-- LOGIN PANE -->
                <div class="auth-pane">
                    <div style="text-align: center; margin-bottom: 25px;">
                        <h2 style="color: #0b1e3b;">Bienvenido</h2>
                        <p style="color: #666; font-size: 0.9rem;">Ingresa para gestionar tus pedidos.</p>
                    </div>

                    <form id="loginForm">
                        <div class="form-group">
                            <label for="email">Correo Electrónico</label>
                            <input type="email" id="email" name="email" required placeholder="tu@correo.com">
                        </div>
                        
                        <div class="form-group">
                            <label for="password" style="display: flex; justify-content: space-between;">
                                Contraseña
                                <a href="forgot_password.php" style="font-size: 0.85rem; color: #3b82f6; text-decoration: none; font-weight: normal;">¿Olvidaste tu contraseña?</a>
                            </label>
                            <input type="password" id="password" name="password" required placeholder="••••••••">
                        </div>

                        <button type="submit" class="btn-submit">Ingresar</button>
                        <button type="button" id="resendBtn" onclick="resendVerificationEmail()">Reenviar correo de validación</button>
                    </form>
                </div>

                <!-- REGISTER PANE -->
                <div class="auth-pane">
                    <div style="text-align: center; margin-bottom: 25px;">
                        <h2 style="color: #0b1e3b;">Únete a Omade</h2>
                        <p style="color: #666; font-size: 0.9rem;">Crea tu cuenta gratis en segundos.</p>
                    </div>

                    <form id="registerForm">
                        <div class="form-group">
                            <label for="name">Nombre(s)</label>
                            <input type="text" id="name" required placeholder="Juan">
                        </div>
                        
                        <div style="display: flex; gap: 10px;">
                            <div class="form-group" style="flex: 1;">
                                <label for="paternal_surname">Paterno</label>
                                <input type="text" id="paternal_surname" required placeholder="Pérez">
                            </div>
                            <div class="form-group" style="flex: 1;">
                                <label for="maternal_surname">Materno</label>
                                <input type="text" id="maternal_surname" required placeholder="García">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="reg_email">Correo Electrónico</label>
                            <input type="email" id="reg_email" required placeholder="tu@correo.com">
                        </div>

                        <div class="form-group">
                            <label for="reg_password">Contraseña</label>
                            <input type="password" id="reg_password" required placeholder="Mínimo 6 caracteres">
                        </div>

                        <button type="submit" class="btn-submit" style="background: #1abc9c;">Registrarse</button>
                    </form>
                </div>

            </div>
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
        // CSS Slider Logic
        const slider = document.getElementById('auth-slider');
        const tabLogin = document.getElementById('tab-login');
        const tabRegister = document.getElementById('tab-register');

        function slideAuth(mode) {
            if (mode === 'login') {
                slider.style.transform = 'translateX(0)';
                tabLogin.classList.add('active');
                tabRegister.classList.remove('active');
            } else {
                slider.style.transform = 'translateX(-50%)';
                tabRegister.classList.add('active');
                tabLogin.classList.remove('active');
            }
        }

        // Auto slide if URL has ?action=register
        const urlParams = new URLSearchParams(window.location.search);
        if(urlParams.get('action') === 'register') {
            slideAuth('register');
        }
    </script>
</body>
</html>
