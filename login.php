<?php
session_start();
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'super_admin') {
        header("Location: admin/dashboard.php");
    } else if ($_SESSION['role'] === 'client') {
        header("Location: user/dashboard.php");
    } else {
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
    <link rel="stylesheet" href="assets/vendor/font-awesome/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- Favicon -->
    <link rel="apple-touch-icon" sizes="180x180" href="assets/favicon_io/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/favicon_io/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/favicon_io/favicon-16x16.png">

    <style>
        /* Kill all scroll permanently on login page */
        html, body.login-body {
            overflow: hidden !important;
            height: 100%;
            max-height: 100vh;
        }

        body.login-body {
            background: radial-gradient(circle at 50% 50%, #1a2a44 0%, #0b1e3b 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            height: 100vh;
            margin: 0;
            position: relative;
            z-index: 1; /* relative to inner pseudo-elements */
        }
        body.login-body::before {
            content: ""; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            z-index: -2; pointer-events: none;
        }
        body.login-body::after {
            content: ''; position: fixed; width: 150vw; height: 150vh;
            background: radial-gradient(circle, rgba(26, 188, 156, 0.15) 0%, transparent 60%);
            top: -25vh; left: -25vw; animation: rotateBg 30s linear infinite;
            z-index: -1; pointer-events: none;
        }
        @keyframes rotateBg {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .btn-back-absolute {
            position: absolute; top: 20px; left: 20px;
            text-decoration: none; color: #fff; font-weight: 500;
            display: flex; align-items: center; gap: 8px;
            background: rgba(255,255,255,0.1); padding: 8px 15px; border: 1px solid rgba(255,255,255,0.2);
            border-radius: 20px; backdrop-filter: blur(5px); transition: all 0.3s;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1); z-index: 1000;
        }
        .btn-back-absolute:hover { background: rgba(255,255,255,0.2); transform: translateX(-5px); }

        .auth-container {
            background-color: #fff;
            border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5), 0 0 20px rgba(0,0,0,0.3);
            position: relative;
            overflow: hidden;
            width: 900px; /* Big wide container like the drawing */
            max-width: 95%;
            min-height: 650px;
        }

        .form-container {
            background-color: #ffffff;
            position: absolute;
            top: 0;
            height: 100%;
            transition: all 0.6s ease-in-out;
            padding: 0 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        /* Forms content styling inside the containers */
        .form-container h2 {
            font-weight: 700;
            margin-bottom: 20px;
            color: #0b1e3b;
            text-align: center;
            font-size: 2rem;
        }
        
        .form-container p.subtitle {
            font-size: 0.9rem; color: #64748b; text-align: center; margin-bottom: 25px;
        }

        .form-container .form-group {
            margin-bottom: 15px;
            width: 100%;
        }

        .form-container label {
            font-weight: 600; font-size: 0.85rem; color: #475569; display: block; margin-bottom: 5px;
        }

        .form-container input {
            background-color: #f1f5f9;
            color: #1e293b;
            border: none;
            padding: 12px 15px;
            margin-bottom: 8px;
            width: 100%;
            border-radius: 8px;
            font-family: 'Inter', sans-serif;
            outline: none;
            transition: 0.2s;
            box-sizing: border-box;
        }
        .form-container input::placeholder {
            color: #94a3b8;
        }
        .form-container input:focus {
            background-color: #e2e8f0;
            box-shadow: inset 0 0 0 2px #3b82f6;
        }

        .form-container .btn-submit {
            border-radius: 20px;
            border: 1px solid #1abc9c;
            background-color: #1abc9c;
            color: #FFFFFF;
            font-size: 1rem;
            font-weight: bold;
            padding: 12px 45px;
            letter-spacing: 1px;
            text-transform: uppercase;
            transition: transform 80ms ease-in, background 0.3s;
            cursor: pointer;
            width: 100%;
            margin-top: 15px;
        }
        .form-container .btn-submit:active { transform: scale(0.95); }
        .form-container .btn-submit:hover { background-color: #16a085; }

        .forgot-pass {
            font-size: 0.85rem; color: #3b82f6; text-decoration: none; font-weight: normal; float: right;
        }

        /* Ghost panel Layout Logistics */
        .sign-in-container { left: 0; width: 50%; z-index: 2; opacity: 1; }
        .sign-up-container { left: 0; width: 50%; opacity: 0; z-index: 1; }

        .auth-container.right-panel-active .sign-in-container { transform: translateX(100%); opacity: 0; z-index: 1; }
        .auth-container.right-panel-active .sign-up-container {
            transform: translateX(100%);
            opacity: 1;
            z-index: 5;
            animation: show 0.6s;
        }

        @keyframes show {
            0%, 49.99% { opacity: 0; z-index: 1; }
            50%, 100% { opacity: 1; z-index: 5; }
        }

        /* The Overlay styling */
        .overlay-container {
            position: absolute;
            top: 0;
            left: 50%;
            width: 50%;
            height: 100%;
            overflow: hidden;
            transition: transform 0.6s ease-in-out;
            z-index: 100;
        }

        .auth-container.right-panel-active .overlay-container {
            transform: translateX(-100%);
        }

        .overlay {
            /* The gradient background of the floating tab */
            background: #1abc9c;
            background: -webkit-linear-gradient(to right, #3b82f6, #1abc9c);
            background: linear-gradient(to right, #3b82f6, #1abc9c);
            background-repeat: no-repeat;
            background-size: cover;
            background-position: 0 0;
            color: #FFFFFF;
            position: relative;
            left: -100%;
            height: 100%;
            width: 200%;
            transform: translateX(0);
            transition: transform 0.6s ease-in-out;
        }

        .auth-container.right-panel-active .overlay { transform: translateX(50%); }

        .overlay-panel {
            position: absolute;
            display: flex;  align-items: center; justify-content: center; flex-direction: column;
            padding: 0 50px; text-align: center;
            top: 0; height: 100%; width: 50%;
            transform: translateX(0);
            transition: transform 0.6s ease-in-out;
            box-sizing: border-box;
        }

        .overlay-panel h1 { font-size: 2.2rem; margin-bottom: 10px; font-weight: 700; }
        .overlay-panel p { font-size: 1rem; font-weight: 300; line-height: 1.5; margin-bottom: 30px; }

        .overlay-left { transform: translateX(-20%); }
        .auth-container.right-panel-active .overlay-left { transform: translateX(0); }
        .overlay-right { right: 0; transform: translateX(0); }
        .auth-container.right-panel-active .overlay-right { transform: translateX(20%); }

        .ghost-btn {
            background-color: transparent;
            border-color: #FFFFFF;
            color: #fff;
            border-radius: 20px;
            border: 2px solid #fff;
            font-size: 1rem;
            font-weight: bold;
            padding: 12px 45px;
            letter-spacing: 1px;
            text-transform: uppercase;
            transition: transform 80ms ease-in, background 0.3s, color 0.3s;
            cursor: pointer;
        }
        .ghost-btn:hover { background: #fff; color: #1abc9c; }
        .ghost-btn:active { transform: scale(0.95); }

        #resendBtn {
            display: none; background: #f1f5f9; color: #3b82f6; border: 1px solid #cbd5e1;
            padding: 8px 15px; border-radius: 20px; font-weight: 600; cursor: pointer;
            width: 100%; transition: background 0.2s; margin-top: 15px; font-size: 0.9rem;
        }
        #resendBtn:hover { background: #e2e8f0; }

        /* Tablet & Mobile Wrap - 3D Card Flip */
        @media (max-width: 900px) {
            body.login-body { 
                perspective: 1500px; 
                overflow: hidden !important; /* Strict No-Scroll */
                justify-content: center; /* Center perfectly */
                padding: 0; 
            }
            
            .auth-container { 
                display: block; 
                width: 90%; 
                max-width: 450px; 
                min-height: 400px; /* Reduced to integrate buttons */
                height: 65vh;
                max-height: 480px;
                background-color: transparent !important; 
                box-shadow: none !important;
                border: none !important;
                transition: transform 0.8s cubic-bezier(0.175, 0.885, 0.32, 1.275); 
                transform-style: preserve-3d;
                overflow: visible !important;
                position: relative;
                margin: 0 auto; /* Center horizontally */
                border-radius: 20px 20px 0 0 !important; /* Flat bottom to connect to buttons */
            }
            
            /* The 3D flip rotation trigger */
            .auth-container.right-panel-active { transform: rotateY(180deg); }
            
            /* Form containers act as the front/back faces */
            .form-container { 
                position: absolute !important; width: 100% !important; height: 100% !important; 
                padding: 30px 20px !important; 
                opacity: 1 !important; 
                animation: none !important; 
                background-color: #ffffff !important; 
                border-radius: 20px 20px 0 0 !important; /* Flat bottom for toggle buttons */
                box-shadow: 0 -10px 40px rgba(0,0,0,0.3) !important; /* Upward shadow */
                backface-visibility: hidden;
                display: flex !important; 
                top: 0; left: 0;
            }
            
            /* Fix front and back rotations */
            .sign-in-container { transform: rotateY(0deg) !important; z-index: 2; }
            .sign-up-container { transform: rotateY(180deg) !important; z-index: 1; }
            
            /* Extra mobile tweaks to fit inside smaller height */
            .overlay-container { display: none !important; } 
            .form-container h2 { font-size: 1.6rem; margin-bottom: 5px; }
            .form-container p.subtitle { margin-bottom: 15px; font-size: 0.8rem; }
            .form-container .form-group { margin-bottom: 10px; }
            .form-container input { padding: 10px 15px; margin-bottom: 5px; font-size: 0.9rem; }
            .form-container .btn-submit { padding: 10px 45px; margin-top: 10px; }
            
            .mobile-toggle { 
                display: flex; position: relative; 
                bottom: auto; left: auto; transform: none; top: auto;
                width: 90%; max-width: 450px; 
                background: #ffffff; /* Match the form containers */
                border-radius: 0 0 20px 20px; /* Round bottom only */
                z-index: 1000;
                overflow: hidden; 
                box-shadow: 0 15px 40px rgba(0,0,0,0.3); /* Downward shadow */
                margin: 0; /* Zero margin to stick to auth-container */
                border-top: 1px solid #cbd5e1; /* Visual separator from the form */
            }
            .mobile-tab { 
                flex: 1; padding: 15px; text-align: center; font-weight: 600; 
                color: #64748b; cursor: pointer; transition: 0.3s; 
            }
            .mobile-tab:first-child {
                border-right: 1px solid #cbd5e1; /* Vertical separator between buttons */
            }
            .mobile-tab.active { 
                color: #3b82f6; 
                background: #f8fafc; /* Very light subtle active state */
            }
        }
        @media (min-width: 901px) { .mobile-toggle { display: none; } }
    </style>
</head>
<body class="login-body">

    <a href="index.html" class="btn-back-absolute">
        <i class="fas fa-arrow-left"></i> Volver al Inicio
    </a>

    <!-- Wide Ghost Auth Container -->
    <div class="auth-container" id="authContainer">
        
        <!-- Registration Form (Left behind ghost right) -->
        <div class="form-container sign-up-container">
            <form id="registerForm">
                <h2>Crear Cuenta</h2>
                <p class="subtitle">Únete a Distribuciones Omade</p>
                <div class="form-group">
                    <label>Nombre(s)</label>
                    <input type="text" id="name" required placeholder="Juan">
                </div>
                <div style="display: flex; gap: 10px; width: 100%;">
                    <div class="form-group" style="flex: 1;">
                        <label>Paterno</label>
                        <input type="text" id="paternal_surname" required placeholder="Pérez">
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label>Materno</label>
                        <input type="text" id="maternal_surname" required placeholder="García">
                    </div>
                </div>
                <div class="form-group">
                    <label>Correo Electrónico</label>
                    <input type="email" id="reg_email" required placeholder="tu@correo.com">
                </div>
                <div class="form-group">
                    <label>Contraseña</label>
                    <input type="password" id="reg_password" required placeholder="Mínimo 6 caracteres">
                </div>
                <button type="submit" class="btn-submit" style="background:#3b82f6; border-color:#3b82f6;">Regístrate</button>
            </form>
        </div>

        <!-- Login Form (Left visible initially) -->
        <div class="form-container sign-in-container">
            <form id="loginForm">
                <h2>Iniciar Sesión</h2>
                <p class="subtitle">Gestiona tus cotizaciones y pedidos</p>
                <div class="form-group">
                    <label>Correo Electrónico</label>
                    <input type="email" id="email" required placeholder="tu@correo.com">
                </div>
                <div class="form-group">
                    <label>
                        Contraseña 
                        <a href="forgot_password.php" class="forgot-pass">¿Olvidaste tu contraseña?</a>
                    </label>
                    <input type="password" id="password" required placeholder="••••••••">
                </div>
                <button type="submit" class="btn-submit">Ingresar</button>
                <button type="button" id="resendBtn" onclick="resendVerificationEmail()">Reenviar correo de validación</button>
            </form>
        </div>

        <!-- The beautiful floating overlay -->
        <div class="overlay-container">
            <div class="overlay">
                <!-- Revealed when overlay acts like a curtain shifting right (shows Register on right) -->
                <div class="overlay-panel overlay-left">
                    <h1>¡Hola de Nuevo!</h1>
                    <p>Si ya tienes una cuenta con nosotros, ingresa aquí para ver tus pedidos.</p>
                    <button class="ghost-btn" id="signInBtn" onclick="togglePanel(false)">Iniciar Sesión</button>
                </div>
                
                <!-- Shown initially on the right (Covers Register) -->
                <div class="overlay-panel overlay-right">
                    <h1>¿Nuevo aquí?</h1>
                    <p>Regístrate gratis para comenzar a cotizar nuestros productos de forma inmediata.</p>
                    <button class="ghost-btn" id="signUpBtn" onclick="togglePanel(true)">Registrarse</button>
                </div>
            </div>
        </div>

    </div>

    <!-- Mobile Tab Controls (visible on mobile/tablet only, glued to base of card) -->
    <div class="mobile-toggle">
        <div class="mobile-tab active" id="mob-login" onclick="togglePanel(false)">Iniciar Sesión</div>
        <div class="mobile-tab" id="mob-register" onclick="togglePanel(true)">Registrarse</div>
    </div>

    <!-- Toast Notification -->
    <div id="toast-overlay"><div id="toast-box"><div id="toast-icon"></div><div id="toast-message"></div></div></div>

    <script src="assets/js/script.js"></script>
    <script>
        const container = document.getElementById('authContainer');
        const mobLogin = document.getElementById('mob-login');
        const mobRegister = document.getElementById('mob-register');

        function togglePanel(isRegister) {
            if (isRegister) {
                container.classList.add("right-panel-active");
                if(mobRegister) { mobRegister.classList.add('active'); mobLogin.classList.remove('active'); }
            } else {
                container.classList.remove("right-panel-active");
                if(mobLogin) { mobLogin.classList.add('active'); mobRegister.classList.remove('active'); }
            }
        }

        // Handle native API response global `window.slideAuth` for script.js
        window.slideAuth = (mode) => {
            togglePanel(mode === 'register');
        };

        // URL parsing logic
        const urlParams = new URLSearchParams(window.location.search);
        if(urlParams.get('action') === 'register') {
            togglePanel(true);
        }

        // Resend Verification Email Function
        async function resendVerificationEmail() {
            const btn = document.getElementById('resendBtn');
            const email = document.getElementById('email').value;
            
            if(!email) { showToast('Ingresa tu correo primero.', 'error'); return; }

            btn.disabled = true; btn.innerText = 'Enviando correo...';

            try {
                const response = await fetch('api/resend_verification.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({email: email})
                });
                const result = await response.json();
                
                if (result.success) {
                    showToast(result.message, 'success');
                    btn.style.display = 'none';
                } else {
                    showToast(result.message, 'error');
                }
            } catch(e) {
                showToast('Error de red', 'error');
            } finally {
                btn.disabled = false; btn.innerText = 'Reenviar correo de validación';
            }
        }
    </script>
</body>
</html>
